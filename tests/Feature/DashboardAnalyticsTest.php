<?php

use App\Actions\Dashboard\ClinicAnalytics;
use Database\Seeders\DatabaseSeeder;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Lead;
use App\Models\Organization;
use App\Models\Specialist;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\User;

/**
 * The dashboard's numbers, checked against figures worked out by hand.
 *
 * These exist because a chart is persuasive. A wrong line on a graph is taken
 * as fact by whoever is looking at it, and the only defence is arithmetic that
 * somebody checked rather than arithmetic the code merely produced.
 *
 * Sales are rung up through the real register rather than inserted directly, so
 * the figures come off the same tables the till writes to. A change to pricing
 * would break these loudly rather than quietly invalidating them.
 *
 * till(), facial(), facialPrice() and balm() come from PosTest, which is where
 * they belong: they describe the catalogue, not the dashboard.
 */
beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

/** Rings up a cart and backdates it, so a day other than today can be tested. */
function sellOn(string $day, array $items): Sale
{
    $before = Sale::withoutGlobalScopes()->count();

    test()->actingAs(till())->post('/admin/pos', [
        'branch_id' => Branch::where('slug', 'makati')->value('id'),
        'items' => $items,
        'discount_type' => 'none',
        'method' => 'cash',
    ])->assertRedirect();

    $sale = Sale::withoutGlobalScopes()->orderByDesc('id')->firstOrFail();
    expect(Sale::withoutGlobalScopes()->count())->toBe($before + 1);

    $sale->forceFill(['created_at' => $day, 'updated_at' => $day])->saveQuietly();

    // The payment carries its own timestamp and the daily series reads that,
    // so it has to move with the sale or the two will not agree.
    Payment::withoutGlobalScopes()
        ->where('sale_id', $sale->id)
        ->update(['paid_at' => $day, 'created_at' => $day, 'updated_at' => $day]);

    return $sale;
}

it('adds up revenue and gross profit across the window', function () {
    $service = facialPrice();
    $balm = balm();
    $goods = round(2 * (float) $balm->cost, 2);

    sellOn(now()->subDays(2)->toDateTimeString(), [
        ['kind' => 'service', 'id' => facial()->id, 'quantity' => 1],
        ['kind' => 'product', 'id' => $balm->id, 'quantity' => 2],
    ]);

    $money = (new ClinicAnalytics(30))->toArray()['money'];
    $revenue = round($service + 2 * (float) $balm->price, 2);

    // Only the product line carries cost of goods. A treatment is labour and
    // has none recorded, which is exactly the caveat the UI states next to
    // the number rather than leaving a reader to guess.
    expect($money['revenue'])->toBe($revenue)
        ->and($money['cost_of_goods'])->toBe($goods)
        ->and($money['profit'])->toBe(round($revenue - $goods, 2))
        ->and($money['sales'])->toBe(1)
        ->and($money['average_sale'])->toBe($revenue);
});

it('takes refunds out of the revenue the graph shows', function () {
    $sale = sellOn(now()->subDay()->toDateTimeString(), [
        ['kind' => 'service', 'id' => facial()->id, 'quantity' => 1],
    ]);

    $refund = 500.0;

    Payment::create([
        'sale_id' => $sale->id,
        'method' => 'card',
        'type' => 'refund',
        'amount' => $refund,
        'user_id' => till()->id,
        'paid_at' => now(),
    ]);

    $figures = (new ClinicAnalytics(30))->toArray();
    $expected = round((float) $sale->total - $refund, 2);

    // Money taken, less money handed back. A graph still showing the gross
    // would disagree with the till, which is how people stop believing one.
    expect($figures['money']['refunds'])->toBe($refund)
        ->and($figures['money']['revenue'])->toBe($expected);

    // The refund lands on the day it was paid, not the day of the sale. That
    // is the cash view a till reports, and it is the one that has to match
    // what the bank account did that day -- not what was rung up a week ago.
    $byDate = collect($figures['daily'])->keyBy('date');

    expect($byDate[now()->subDay()->toDateString()]['revenue'])
        ->toBe(round((float) $sale->total, 2))
        ->and($byDate[now()->toDateString()]['revenue'])->toBe(-$refund);
});

it('returns a row for every day, so a quiet day is a zero and not a gap', function () {
    sellOn(now()->subDay()->toDateTimeString(), [
        ['kind' => 'service', 'id' => facial()->id, 'quantity' => 1],
    ]);

    $daily = (new ClinicAnalytics(7))->toArray()['daily'];

    expect($daily)->toHaveCount(7);

    // The row for today exists even though nothing was sold today, and a day
    // in the middle with nothing on it is present rather than skipped.
    expect($daily[0]['date'])->toBe(now()->subDays(6)->toDateString())
        ->and($daily[6]['date'])->toBe(now()->toDateString())
        ->and($daily[6]['revenue'])->toBe(0.0);

    $empty = collect($daily)->firstWhere('date', now()->subDays(3)->toDateString());
    expect($empty)->not->toBeNull()
        ->and($empty['revenue'])->toBe(0.0)
        ->and($empty['bookings'])->toBe(0);
});

it('leaves cancelled visits out of the booking breakdown', function () {
    // Built here rather than read from the seed: the base seeder leaves the
    // diary empty, and a test that silently depended on demo data would start
    // failing the day somebody changed the demo.
    $org = Organization::firstOrFail();
    $specialist = Specialist::create([
        'organization_id' => $org->id,
        'name' => 'Dr. Test',
        'slug' => 'dr-test-'.uniqid(),
        'title' => 'Dermatologist',
        'bio' => 'For the booking breakdown test.',
    ]);

    $lead = Lead::create([
        'organization_id' => $org->id,
        'branch_id' => Branch::where('slug', 'makati')->value('id'),
        'first_name' => 'Ana',
        'last_name' => 'Reyes',
    ]);

    $offset = 0;

    foreach (['completed', 'completed', 'no_show', 'cancelled'] as $status) {
        Appointment::create([
            'organization_id' => $org->id,
            'lead_id' => $lead->id,
            'specialist_id' => $specialist->id,
            'reference' => 'T'.substr(uniqid(), -6),
            'branch_id' => Branch::where('slug', 'makati')->value('id'),
            'treatment_id' => facial()->id,
            'starts_at' => now()->subDays(2)->addHours(9 + $offset),
            'ends_at' => now()->subDays(2)->addHours(10 + $offset),
            'status' => $status,
        ]);
        $offset++;
    }

    $byStatus = collect((new ClinicAnalytics(30))->toArray()['bookings'])->keyBy('status');

    // Cancelled visits did not happen. Counting them would flatter the no-show
    // rate and understate how full the diary actually was.
    expect($byStatus['completed']['count'])->toBeGreaterThanOrEqual(2)
        ->and($byStatus['no_show']['count'])->toBe(1)
        ->and($byStatus->has('cancelled'))->toBeFalse();
});

it('says nothing about conversion when there were no enquiries at all', function () {
    $figures = (new ClinicAnalytics(30))->toArray();

    // A clinic with no enquiries has not failed to convert anybody. Zero
    // percent is a claim about performance; null is a claim about volume.
    expect($figures['front_desk']['conversion'])->toBeNull();
});

it('keeps the money off a screen for a role that should not see it', function () {
    // A plain factory user holds no clinic role at all, which is the
    // strongest form of the same check: no role, no takings.
    $this->actingAs(User::factory()->create())
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page->where('analytics.money', null));
});

it('shows the money to somebody who runs the books', function () {
    $owner = User::where('email', 'owner@irish.test')->firstOrFail();

    $this->actingAs($owner)
        ->get('/dashboard')
        ->assertInertia(
            fn ($page) => $page->has('analytics.money.revenue')
                ->has('analytics.money.profit')
                ->where('analytics.window.days', 30),
        );
});

it('only draws the window the clinic asked for', function () {
    $owner = User::where('email', 'owner@irish.test')->firstOrFail();

    $this->actingAs($owner)
        ->get('/dashboard?days=7')
        ->assertInertia(
            fn ($page) => $page->where('analytics.window.days', 7)
                ->where('analytics.window.from', now()->subDays(6)->toDateString()),
        );

    // A number nobody offered is not a window, it is a query. Falling back to
    // the default keeps a hand-typed URL from asking for a decade of rows.
    $this->actingAs($owner)
        ->get('/dashboard?days=99999')
        ->assertInertia(fn ($page) => $page->where('analytics.window.days', 30));
});
