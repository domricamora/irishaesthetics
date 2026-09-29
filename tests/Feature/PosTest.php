<?php

use App\Models\Branch;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Sale;
use App\Models\Treatment;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

function till(): User
{
    return User::where('email', 'reception@irish.test')->firstOrFail();
}

function facial(): Treatment
{
    return Treatment::where('slug', 'hydra-facial')->firstOrFail();
}

function balm(): Product
{
    return Product::where('slug', 'cleansing-balm-100ml')->firstOrFail();
}

/** Units of a product on the shelf of the branch the till is selling at. */
function onHandAtMakati(Product $product): int
{
    return (int) ProductStock::where('product_id', $product->id)
        ->where('branch_id', Branch::where('slug', 'makati')->value('id'))
        ->value('on_hand');
}

/** A treatment on promotion sells at the promo price, not the list price. */
function facialPrice(): float
{
    $treatment = facial();

    return (float) ($treatment->promo_price ?: $treatment->price);
}
/** The payload the register posts: a cart, a payment and who it is for. */
function ringUp(array $overrides = []): array
{
    return array_merge([
        'branch_id' => Branch::where('slug', 'makati')->value('id'),
        'items' => [['kind' => 'service', 'id' => facial()->id, 'quantity' => 1]],
        'discount_type' => 'none',
        'method' => 'cash',
    ], $overrides);
}

it('rings up a sale priced from the catalogue', function () {
    $product = balm();
    $stockBefore = onHandAtMakati($product);

    $response = $this->actingAs(till())->post('/admin/pos', ringUp([
        'items' => [
            ['kind' => 'service', 'id' => facial()->id, 'quantity' => 1],
            ['kind' => 'product', 'id' => balm()->id, 'quantity' => 2],
        ],
        'client_name' => 'Ana Reyes',
    ]));

    $sale = Sale::withoutGlobalScopes()->sole();
    $expected = round(facialPrice() + 2 * (float) balm()->price, 2);

    expect($sale->total)->toBe($expected)
        ->and($sale->amount_paid)->toBe($expected)
        ->and($sale->balance)->toBe(0.0)
        ->and($sale->status)->toBe('paid')
        ->and($sale->client_name)->toBe('Ana Reyes')
        ->and($sale->user->email)->toBe('reception@irish.test')
        ->and($sale->items)->toHaveCount(2)
        ->and($sale->payments)->toHaveCount(1)
        ->and($sale->payments->first()->method)->toBe('cash')
        ->and($sale->reference)->toStartWith('POS-');

    $response->assertRedirect(route('admin.pos.sales.show', $sale));

    // Two balms left the shelf.
    expect(onHandAtMakati($product))->toBe($stockBefore - 2);
});

it('ignores prices sent by the browser', function () {
    $this->actingAs(till())->post('/admin/pos', ringUp([
        'items' => [['kind' => 'service', 'id' => facial()->id, 'quantity' => 1, 'unit_price' => 1, 'line_total' => 1]],
    ]))->assertRedirect();

    expect(Sale::withoutGlobalScopes()->sole()->total)->toBe(facialPrice());
});

it('applies a percentage discount and never goes below zero', function () {
    $this->actingAs(till())->post('/admin/pos', ringUp([
        'discount_type' => 'percent',
        'discount_value' => 10,
    ]))->assertRedirect();

    $sale = Sale::withoutGlobalScopes()->sole();
    $expected = round(facialPrice() * 0.9, 2);

    expect($sale->discount_amount)->toBe(round(facialPrice() * 0.1, 2))
        ->and($sale->total)->toBe($expected);

    $this->actingAs(till())->post('/admin/pos', ringUp([
        'discount_type' => 'fixed',
        'discount_value' => 999999,
    ]))->assertRedirect();

    expect(Sale::withoutGlobalScopes()->latest('id')->first()->total)->toBe(0.0);
});

it('records a deposit and leaves the rest due', function () {
    $this->actingAs(till())->post('/admin/pos', ringUp(['amount' => 1000]))->assertRedirect();

    $sale = Sale::withoutGlobalScopes()->sole();
    $due = round(facialPrice() - 1000, 2);

    expect($sale->amount_paid)->toBe(1000.0)
        ->and($sale->balance)->toBe($due)
        ->and($sale->status)->toBe('partial')
        ->and($sale->payments->sum('amount'))->toBe(1000.0);
});

it('keeps the change on cash and never overcharges a card', function () {
    $this->actingAs(till())->post('/admin/pos', ringUp(['method' => 'cash', 'amount' => 100000]))
        ->assertRedirect()
        ->assertSessionHas('success', fn (string $message) => str_contains($message, 'Change'));

    $cash = Sale::withoutGlobalScopes()->latest('id')->first();
    expect($cash->amount_paid)->toBe(facialPrice())->and($cash->balance)->toBe(0.0);

    $this->actingAs(till())->post('/admin/pos', ringUp(['method' => 'card', 'amount' => 100000]))->assertRedirect();

    $card = Sale::withoutGlobalScopes()->latest('id')->first();
    expect($card->amount_paid)->toBe(facialPrice())
        ->and($card->payments->first()->amount)->toBe(facialPrice());
});

it('will not sell more than the stock on hand', function () {
    $this->actingAs(till())->post('/admin/pos', ringUp([
        'items' => [['kind' => 'product', 'id' => balm()->id, 'quantity' => onHandAtMakati(balm()) + 1]],
    ]))->assertSessionHasErrors('items');

    $before = onHandAtMakati(balm());

    expect(Sale::withoutGlobalScopes()->count())->toBe(0);
    expect(onHandAtMakati(balm()))->toBe($before);
});

it('refuses an empty cart and an unknown item', function () {
    $this->actingAs(till())->post('/admin/pos', ringUp(['items' => []]))->assertSessionHasErrors('items');
    $this->actingAs(till())->post('/admin/pos', ringUp([
        'items' => [['kind' => 'product', 'id' => 999999, 'quantity' => 1]],
    ]))->assertSessionHasErrors('items');
});

it('refunds part of a sale, returns the stock and shows the balance', function () {
    $this->actingAs(till())->post('/admin/pos', ringUp([
        'items' => [['kind' => 'product', 'id' => balm()->id, 'quantity' => 2]],
        'amount' => 2000,
    ]))->assertRedirect();

    $sale = Sale::withoutGlobalScopes()->sole();
    $product = balm();
    $stockAfterSale = onHandAtMakati($product);
    $outstanding = round($sale->total - 2000, 2);

    $this->actingAs(till())
        ->post("/admin/pos/sales/{$sale->id}/refund", [
            'amount' => 1000,
            'method' => 'cash',
            'reason' => 'Client returned one balm.',
        ])->assertRedirect();

    $sale->refresh();
    expect($sale->amount_paid)->toBe(1000.0)
        ->and($sale->status)->toBe('partial')
        ->and($sale->payments)->toHaveCount(2)
        ->and($sale->payments->last()->type)->toBe('refund')
        ->and($sale->payments->last()->amount)->toBe(-1000.0)
        ->and(onHandAtMakati($product))->toBe($stockAfterSale + 2);

    // A refund can never exceed the money that was actually taken, so a deposit
    // left behind cannot be refunded into the negative.
    $this->actingAs(till())->post("/admin/pos/sales/{$sale->id}/refund", [
        'amount' => $outstanding + 100000,
        'method' => 'cash',
        'reason' => 'Over the top.',
    ])->assertRedirect();

    expect($sale->fresh()->amount_paid)->toBe(0.0)
        ->and($sale->fresh()->status)->toBe('refunded');
});

it('refunds a fully paid sale, which is the common case at the counter', function () {
    $this->actingAs(till())->post('/admin/pos', ringUp([
        'items' => [['kind' => 'product', 'id' => balm()->id, 'quantity' => 1]],
    ]))->assertRedirect();

    $sale = Sale::withoutGlobalScopes()->sole();
    $product = balm();
    $stockAfterSale = onHandAtMakati($product);

    $this->actingAs(till())
        ->post("/admin/pos/sales/{$sale->id}/refund", [
            'amount' => 1000,
            'method' => 'gcash',
            'reason' => 'Client changed her mind.',
        ])->assertRedirect()->assertSessionHasNoErrors();

    $sale->refresh();
    expect($sale->amount_paid)->toBe(round((float) balm()->price - 1000, 2))
        ->and($sale->status)->toBe('partial')
        ->and($sale->balance)->toBe(1000.0)
        ->and(onHandAtMakati($product))->toBe($stockAfterSale + 1);

    // Handing back the rest of a paid sale leaves it settled and marked refunded.
    $this->actingAs(till())
        ->post("/admin/pos/sales/{$sale->id}/refund", [
            'amount' => 1000,
            'method' => 'gcash',
            'reason' => 'Rest of it.',
        ])->assertRedirect();

    expect($sale->fresh()->amount_paid)->toBe(0.0)
        ->and($sale->fresh()->status)->toBe('refunded');

    // A second refund finds nothing left to give back.
    $this->actingAs(till())
        ->post("/admin/pos/sales/{$sale->id}/refund", [
            'amount' => 100,
            'method' => 'gcash',
            'reason' => 'Nothing left.',
        ])->assertSessionHasErrors('amount');
});
it('shows the register, the day list and the receipt to staff with the permission', function () {
    $this->actingAs(till())->get('/admin/pos')->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/pos/index')
            ->has('services')
            ->has('products')
            ->has('methods', 6)
            ->where('branch_id', Branch::where('slug', 'makati')->value('id')));

    $this->actingAs(till())->post('/admin/pos', ringUp())->assertRedirect();

    $sale = Sale::withoutGlobalScopes()->sole();

    $this->actingAs(till())->get('/admin/pos/sales')->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/pos/sales/index')
            ->where('summary.count', 1)
            ->where('sales.0.reference', $sale->reference)
            ->where('sales.0.status', 'paid'));

    $this->actingAs(till())->get("/admin/pos/sales/{$sale->id}")->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/pos/sales/show')
            ->where('sale.reference', $sale->reference)
            ->has('sale.items', 1)
            ->has('sale.payments', 1)
            ->where('sale.payments.0.method_label', 'Cash'));
});

it('keeps the counter away from staff without the permission', function () {
    $doctor = User::factory()->create(['organization_id' => Organization::sole()->id]);
    $doctor->assignRole('Doctor');

    $this->actingAs($doctor)->get('/admin/pos')->assertForbidden();
    $this->actingAs($doctor)->post('/admin/pos', ringUp())->assertForbidden();
    $this->actingAs($doctor)->get('/admin/pos/sales')->assertForbidden();

    $this->actingAs(till())->post('/admin/pos', ringUp())->assertRedirect();
    $sale = Sale::withoutGlobalScopes()->sole();
    $this->actingAs($doctor)->get("/admin/pos/sales/{$sale->id}")->assertForbidden();
    $this->actingAs($doctor)->post("/admin/pos/sales/{$sale->id}/refund", [
        'amount' => 10, 'method' => 'cash', 'reason' => 'Not mine to give.',
    ])->assertForbidden();
});

it('needs a reason for a refund and a real method for a sale', function () {
    $this->actingAs(till())->post('/admin/pos', ringUp(['method' => 'bitcoin']))->assertSessionHasErrors('method');

    $this->actingAs(till())->post('/admin/pos', ringUp())->assertRedirect();
    $sale = Sale::withoutGlobalScopes()->sole();

    $this->actingAs(till())->post("/admin/pos/sales/{$sale->id}/refund", [
        'amount' => 10, 'method' => 'cash', 'reason' => '',
    ])->assertSessionHasErrors('reason');
});
