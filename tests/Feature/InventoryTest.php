<?php

use App\Actions\Inventory\ManageStock;
use App\Models\Branch;
use App\Models\InventoryMovement;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Sale;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    $this->manager = app(ManageStock::class);
    $this->till = User::where('email', 'reception@irish.test')->firstOrFail();
    $this->supervisor = User::where('email', 'owner@irish.test')->firstOrFail();
});

function makati(): Branch
{
    return Branch::where('slug', 'makati')->firstOrFail();
}

function bgc(): Branch
{
    return Branch::where('slug', 'bgc')->firstOrFail() ?? Branch::orderBy('id')->skip(1)->first();
}

function serum(): Product
{
    return Product::where('slug', 'vitamin-c-serum-30ml')->firstOrFail();
}

function sunscreen(): Product
{
    return Product::where('slug', 'spf-50-sunscreen-50ml')->firstOrFail();
}

/** Stock at a branch as the counter sees it. */
function onHand(Product $product, Branch $branch): int
{
    return (int) ProductStock::where('product_id', $product->id)
        ->where('branch_id', $branch->id)
        ->value('on_hand');
}

it('starts with an opening lot and a matching count at the stocked branch', function () {
    $product = sunscreen();

    expect(onHand($product, makati()))->toBe(30)
        ->and(onHand($product, bgc()))->toBe(0)
        ->and($product->batches()->count())->toBe(1)
        ->and(InventoryMovement::where('type', 'opening')->count())->toBeGreaterThan(0);

    // The count is exactly the lots added up.
    $lots = (int) $product->batches()->where('branch_id', makati()->id)->sum('quantity');
    expect($lots)->toBe(onHand($product, makati()));
});

it('receives stock as a new lot and puts it on the shelf', function () {
    $product = sunscreen();
    $before = onHand($product, makati());

    $batch = $this->manager->receive($product, makati(), [
        'quantity' => 24,
        'lot_number' => 'LOT-7781',
        'expires_on' => today()->addYear()->toDateString(),
        'cost' => 1100,
        'note' => 'Delivery 4471',
    ], $this->supervisor);

    expect(onHand($product, makati()))->toBe($before + 24)
        ->and($batch->quantity)->toBe(24)
        ->and($batch->cost)->toBe(1100.0)
        ->and($product->batches()->count())->toBe(2);

    $movement = InventoryMovement::where('batch_id', $batch->id)->first();
    expect($movement->type)->toBe('receive')
        ->and($movement->quantity)->toBe(24)
        ->and($movement->note)->toBe('Delivery 4471')
        ->and($movement->user->email)->toBe('owner@irish.test');
});
it('uses the soonest expiry first when stock is taken', function () {
    $product = sunscreen();

    // The seeded lot expires in 20 days; a new one lands with a date next year.
    $this->manager->receive($product, makati(), [
        'quantity' => 10,
        'lot_number' => 'LOT-LATER',
        'expires_on' => today()->addYear()->toDateString(),
    ], $this->supervisor);

    $this->manager->consume($product, makati(), 5, 'consume', $this->till, null, 'Used on a client');

    $soon = $product->batches()->whereNull('lot_number')->firstOrFail();
    $later = $product->batches()->where('lot_number', 'LOT-LATER')->firstOrFail();

    // The five came off the lot that expires first, not the new one.
    expect($soon->quantity)->toBe(25)
        ->and($later->quantity)->toBe(10)
        ->and(onHand($product, makati()))->toBe(35);

    $movement = InventoryMovement::where('batch_id', $soon->id)->where('type', 'consume')->first();
    expect($movement->quantity)->toBe(-5)
        ->and($movement->note)->toBe('Used on a client');
});

it('refuses to take more than the branch holds and leaves the shelves untouched', function () {
    $product = sunscreen();
    $before = onHand($product, makati());

    $this->manager->consume($product, makati(), $before + 1, 'sale', $this->till, 'POS-TEST');
})->throws(ValidationException::class);

it('cannot sell at a branch that holds nothing, whatever the other branch has', function () {
    $product = sunscreen();
    $before = onHand($product, makati());

    $this->actingAs($this->till)->post('/admin/pos', [
        'branch_id' => bgc()->id,
        'items' => [['kind' => 'product', 'id' => $product->id, 'quantity' => 1]],
        'method' => 'cash',
    ])->assertSessionHasErrors('items');

    expect(Sale::withoutGlobalScopes()->count())->toBe(0)
        ->and(onHand($product, makati()))->toBe($before)
        ->and(onHand($product, bgc()))->toBe(0);
});

it('sells from the branch the sale was rung up at and records it in the ledger', function () {
    $product = sunscreen();
    $before = onHand($product, makati());

    $this->actingAs($this->till)->post('/admin/pos', [
        'branch_id' => makati()->id,
        'items' => [['kind' => 'product', 'id' => $product->id, 'quantity' => 2]],
        'method' => 'cash',
    ])->assertRedirect();

    $sale = Sale::withoutGlobalScopes()->sole();

    expect(onHand($product, makati()))->toBe($before - 2)
        ->and(InventoryMovement::where('type', 'sale')->where('reference', $sale->reference)->count())->toBe(1)
        ->and(InventoryMovement::where('type', 'sale')->value('quantity'))->toBe(-2);
});
it('writes off damaged and expired stock, and corrects a miscount upwards', function () {
    $product = serum();
    $before = onHand($product, makati());

    $this->manager->adjust($product, makati(), 'damage', 3, 'Two vials cracked in transit.', $this->till);
    expect(onHand($product, makati()))->toBe($before - 3)
        ->and(InventoryMovement::where('type', 'damage')->value('note'))->toBe('Two vials cracked in transit.');

    $this->manager->adjust($product, makati(), 'adjustment', 5, 'Stocktake found five behind the shelf.', $this->till);
    expect(onHand($product, makati()))->toBe($before + 2)
        ->and($product->batches()->count())->toBe(2);

    // The seeded recovery balm is already past its date, so the shelf shows it.
    $expired = Product::where('slug', 'post-treatment-recovery-balm')->firstOrFail();
    $this->manager->adjust($expired, makati(), 'expired', 10, 'Past the date, written off.', $this->till);
    expect(onHand($expired, makati()))->toBe(30)
        ->and(InventoryMovement::where('type', 'expired')->exists())->toBeTrue();
});

it('moves stock between branches without changing the total held', function () {
    $product = sunscreen();
    $makatiBefore = onHand($product, makati());
    $bgcBefore = onHand($product, bgc());

    $this->manager->transfer($product, makati(), bgc(), 12, $this->supervisor);

    expect(onHand($product, makati()))->toBe($makatiBefore - 12)
        ->and(onHand($product, bgc()))->toBe($bgcBefore + 12)
        ->and(onHand($product, makati()) + onHand($product, bgc()))->toBe($makatiBefore + $bgcBefore)
        ->and(InventoryMovement::where('type', 'transfer_out')->value('quantity'))->toBe(-12)
        ->and(InventoryMovement::where('type', 'transfer_in')->value('quantity'))->toBe(12);
});

it('will not move stock to the branch it already sits in', function () {
    $this->manager->transfer(sunscreen(), makati(), makati(), 1, $this->supervisor);
})->throws(ValidationException::class);

it('puts a refunded sale back on the shelf as a new lot', function () {
    $product = sunscreen();
    $before = onHand($product, makati());

    $this->actingAs($this->till)->post('/admin/pos', [
        'branch_id' => makati()->id,
        'items' => [['kind' => 'product', 'id' => $product->id, 'quantity' => 2]],
        'method' => 'cash',
    ])->assertRedirect();

    $sale = Sale::withoutGlobalScopes()->sole();
    expect(onHand($product, makati()))->toBe($before - 2);

    $this->actingAs($this->till)->post("/admin/pos/sales/{$sale->id}/refund", [
        'amount' => 1000,
        'method' => 'cash',
        'reason' => 'Client returned both bottles.',
    ])->assertRedirect();

    expect(onHand($product, makati()))->toBe($before)
        ->and(InventoryMovement::where('type', 'return')->value('quantity'))->toBe(2)
        ->and($product->batches()->where('note', 'like', 'Returned from%')->count())->toBe(1);
});
it('shows the stock, the alerts and the ledger at a branch', function () {
    $this->actingAs($this->supervisor)->get('/admin/inventory')->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/inventory/index')
            ->where('branch.id', makati()->id)
            ->where('branch.name', 'Makati')
            ->has('rows', 8)
            ->has('movements', 8)
            ->where('alerts.expired', 1)   // the recovery balm is past its date
            ->where('alerts.expiring', 2)  // the serum in 45 days, the sunscreen in 20
            ->where('alerts.out', 0)       // Makati holds everything; the others do not
            ->where('total_value', 184550));

    // The other branches are empty, and the filter says so.
    $this->actingAs($this->supervisor)->get('/admin/inventory?branch='.bgc()->id)
        ->assertInertia(fn ($page) => $page->where('alerts.out', 8)
            ->where('rows.0.on_hand', 0)
            ->where('rows.0.status', 'out'));

    $this->actingAs($this->supervisor)->get('/admin/inventory?alert=expired')
        ->assertInertia(fn ($page) => $page->has('rows', 1)
            ->where('rows.0.name', 'Post-treatment recovery balm')
            ->where('rows.0.status', 'expired'));
});

it('opens the lot history of a product in expiry order', function () {
    $product = sunscreen();
    $this->manager->receive($product, makati(), [
        'quantity' => 5,
        'lot_number' => 'LOT-SOON',
        'expires_on' => today()->addDays(10)->toDateString(),
    ], $this->supervisor);

    $this->actingAs($this->supervisor)->get("/admin/inventory/products/{$product->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/inventory/product')
            ->where('on_hand', 35)
            ->has('batches', 2)
            ->where('batches.0.lot_number', 'LOT-SOON')   // soonest first
            ->where('batches.0.days_to_expiry', 10)
            ->where('batches.1.lot_number', null)
            ->where('batches.1.expires_on', today()->addDays(20)->toDateString()));
});

it('hands the stock forms what they need before anything is typed', function () {
    // The forms show what is on the shelf and open on the right item, so the
    // props they lean on are pinned here rather than discovered in the browser.
    $receive = $this->actingAs($this->supervisor)
        ->get('/admin/inventory/receive?branch='.makati()->id.'&product='.serum()->id)
        ->assertOk()
        ->viewData('page')['props'];

    expect($receive['product_id'])->toBe(serum()->id)
        ->and($receive['branch']['name'])->toBe(makati()->name)
        ->and(collect($receive['products'])->firstWhere('id', serum()->id)['on_hand'])
        ->toBe(onHand(serum(), makati()));

    $adjust = $this->actingAs($this->supervisor)
        ->get('/admin/inventory/adjust?product='.serum()->id)
        ->assertOk()
        ->viewData('page')['props'];

    expect($adjust['product_id'])->toBe(serum()->id)
        ->and($adjust['types'])->toHaveKeys(['adjustment', 'damage', 'expired', 'consume', 'transfer']);
});

it('records a whole delivery from the receive form', function () {
    $this->actingAs($this->supervisor)->post('/admin/inventory/receive', [
        'branch_id' => makati()->id,
        'lines' => [
            [
                'product_id' => sunscreen()->id,
                'quantity' => 10,
                'lot_number' => 'LOT-WEB',
                'expires_on' => today()->addMonths(6)->toDateString(),
                'cost' => 1200,
                'note' => 'Booked through the web form',
            ],
        ],
    ])->assertRedirect(route('admin.inventory.index', ['branch' => makati()->id]));

    expect(onHand(sunscreen(), makati()))->toBe(40)
        ->and(InventoryMovement::where('type', 'receive')->latest('id')->value('note'))->toBe('Booked through the web form');

    // An expiry in the past is refused rather than silently stored.
    $this->actingAs($this->supervisor)->post('/admin/inventory/receive', [
        'branch_id' => makati()->id,
        'lines' => [
            [
                'product_id' => sunscreen()->id,
                'quantity' => 5,
                'expires_on' => today()->subDay()->toDateString(),
            ],
        ],
    ])->assertSessionHasErrors('lines.0.expires_on');
});

it('receives several items in one delivery, each as its own lot', function () {
    $this->actingAs($this->supervisor)->post('/admin/inventory/receive', [
        'branch_id' => makati()->id,
        'lines' => [
            ['product_id' => sunscreen()->id, 'quantity' => 6, 'lot_number' => 'A-1', 'expires_on' => today()->addMonths(3)->toDateString()],
            ['product_id' => serum()->id, 'quantity' => 4, 'lot_number' => 'B-2'],
        ],
    ])->assertRedirect();

    expect(onHand(sunscreen(), makati()))->toBe(36)
        ->and(onHand(serum(), makati()))->toBe(22)
        ->and(sunscreen()->batches()->where('lot_number', 'A-1')->first()?->quantity)->toBe(6)
        ->and(serum()->batches()->where('lot_number', 'B-2')->first()?->quantity)->toBe(4);

    // A delivery with nothing on it is refused.
    $this->actingAs($this->supervisor)->post('/admin/inventory/receive', [
        'branch_id' => makati()->id,
        'lines' => [],
    ])->assertSessionHasErrors('lines');
});

it('takes a stock count and works out the difference either way', function () {
    // Counting fewer than the system holds has to be recordable, not just the
    // ones that go up: a shortfall is the case that matters.
    $this->actingAs($this->supervisor)->post('/admin/inventory/adjust', [
        'product_id' => serum()->id,
        'branch_id' => makati()->id,
        'kind' => 'adjustment',
        'mode' => 'count',
        'quantity' => 15,
        'note' => 'Counted the back shelf.',
    ])->assertRedirect();

    // The shelf holds 18, so counting 15 is three short.
    expect(onHand(serum(), makati()))->toBe(15)
        ->and(InventoryMovement::where('type', 'adjustment')->latest('id')->value('quantity'))->toBe(-3);

    // Counting more adds the difference.
    $this->actingAs($this->supervisor)->post('/admin/inventory/adjust', [
        'product_id' => serum()->id,
        'branch_id' => makati()->id,
        'kind' => 'adjustment',
        'mode' => 'count',
        'quantity' => 17,
        'note' => 'Found two behind the till.',
    ])->assertRedirect();

    expect(onHand(serum(), makati()))->toBe(17);

    // A count that matches is a real answer, not a mistake: it writes nothing
    // and says so rather than asking for units to remove.
    $this->actingAs($this->supervisor)->post('/admin/inventory/adjust', [
        'product_id' => serum()->id,
        'branch_id' => makati()->id,
        'kind' => 'adjustment',
        'mode' => 'count',
        'quantity' => 17,
    ])
        ->assertRedirect()
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success', fn ($message) => str_contains($message, 'matches'));

    expect(onHand(serum(), makati()))->toBe(17)
        ->and(InventoryMovement::where('type', 'adjustment')->count())->toBe(1);
});

it('writes off damage from the adjust form', function () {
    $this->actingAs($this->supervisor)->post('/admin/inventory/adjust', [
        'product_id' => serum()->id,
        'branch_id' => makati()->id,
        'kind' => 'damage',
        'quantity' => 2,
        'note' => 'Leaked in the fridge.',
    ])->assertRedirect();

    expect(onHand(serum(), makati()))->toBe(16)
        ->and(InventoryMovement::where('type', 'damage')->value('note'))->toBe('Leaked in the fridge.');

    $this->actingAs($this->supervisor)->post('/admin/inventory/adjust', [
        'product_id' => serum()->id,
        'branch_id' => makati()->id,
        'kind' => 'transfer',
        'quantity' => 5,
        'to_branch' => makati()->id,
        'note' => 'Nowhere to go.',
    ])->assertSessionHasErrors('to_branch');
});

it('keeps the shelves to staff with the permission', function () {
    $doctor = User::factory()->create(['organization_id' => Organization::sole()->id]);
    $doctor->assignRole('Doctor');

    $this->actingAs($doctor)->get('/admin/inventory')->assertForbidden();
    $this->actingAs($doctor)->get('/admin/inventory/receive')->assertForbidden();
    $this->actingAs($doctor)->post('/admin/inventory/receive', [
        'branch_id' => makati()->id,
        'lines' => [['product_id' => sunscreen()->id, 'quantity' => 1]],
    ])->assertForbidden();

    // Reception can see the stock but not change it.
    $this->actingAs($this->till)->get('/admin/inventory')->assertOk();
    $this->actingAs($this->till)->get('/admin/inventory/receive')->assertForbidden();
    $this->actingAs($this->till)->post('/admin/inventory/adjust', [
        'product_id' => sunscreen()->id,
        'branch_id' => makati()->id,
        'kind' => 'damage',
        'quantity' => 1,
        'note' => 'Not mine to write off.',
    ])->assertForbidden();
});

it('will not touch stock belonging to another organization', function () {
    $rival = Organization::create(['name' => 'Rival Clinic', 'slug' => 'rival']);
    $theirProduct = Product::withoutGlobalScopes()->create([
        'organization_id' => $rival->id,
        'name' => 'Rival serum',
        'slug' => 'rival-serum',
        'price' => 100,
        'cost' => 50,
    ]);

    $this->actingAs($this->supervisor)->get('/admin/inventory')->assertOk()
        ->assertInertia(fn ($page) => $page->has('rows', 8)
            ->where('rows.0.name', 'Ceramide moisturiser, 50ml'));

    $this->actingAs($this->supervisor)->get('/admin/inventory?q=rival')
        ->assertInertia(fn ($page) => $page->has('rows', 0));
});
