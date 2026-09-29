<?php

use App\Actions\Pos\RingUpSale;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Specialist;
use App\Models\Treatment;
use App\Models\TreatmentCategory;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

function boss(): User
{
    return User::where('email', 'owner@irish.test')->firstOrFail();
}

function desk(): User
{
    return User::where('email', 'reception@irish.test')->firstOrFail();
}

function shopItem(): Product
{
    return Product::where('slug', 'cleansing-balm-100ml')->firstOrFail();
}

function facials(): int
{
    return (int) TreatmentCategory::where('slug', 'facial')->value('id');
}

function shop(): Branch
{
    return Branch::where('slug', 'makati')->firstOrFail();
}

it('lists the catalogue and keeps it behind the permission', function () {
    $this->actingAs(boss())->get('/admin/catalog')->assertOk()->assertSee('Cleansing balm, 100ml');

    // A sign-in with no role is turned away rather than shown the page.
    $norole = User::create([
        'organization_id' => boss()->organization_id,
        'name' => 'No Role',
        'email' => 'norole@irish.test',
        'password' => 'password',
    ]);
    $norole->syncRoles([]);

    $this->actingAs($norole)->get('/admin/catalog')->assertRedirect();
    $this->actingAs($norole)
        ->post('/admin/catalog', ['name' => 'Sneaky', 'price' => 1, 'cost' => 1])
        ->assertRedirect();
});

it('adds an item that the register can see straight away', function () {
    $this->actingAs(boss())
        ->post('/admin/catalog', [
            'name' => 'Collagen Eye Gel 15ml',
            'sku' => 'SER-001',
            'category' => 'Serum',
            'price' => 2450,
            'cost' => 1200,
        ])
        ->assertRedirect();

    $product = Product::where('sku', 'SER-001')->firstOrFail();

    expect($product->slug)->toBe('collagen-eye-gel-15ml')
        ->and($product->is_active)->toBeTrue()
        ->and($product->onHandAt(shop()))->toBe(0);

    // On the register, with nothing on the shelf to sell.
    $this->actingAs(desk())
        ->get('/admin/pos')
        ->assertOk()
        ->assertSee('Collagen Eye Gel 15ml');
});

it('will not add an item without a price or a cost', function () {
    $this->actingAs(boss())
        ->post('/admin/catalog', ['name' => 'Mystery Box'])
        ->assertSessionHasErrors(['price', 'cost']);
});

it('refuses a duplicate SKU but allows a name to be reused', function () {
    $this->actingAs(boss())
        ->post('/admin/catalog', [
            'name' => 'Another Balm',
            'sku' => shopItem()->sku,
            'price' => 100,
            'cost' => 50,
        ])
        ->assertSessionHasErrors('sku');
});

it('edits an item and adjusts its price, and the register follows', function () {
    $product = shopItem();
    $original = $product->price;

    $this->actingAs(boss())
        ->patch("/admin/catalog/{$product->id}", [
            'name' => $product->name,
            'sku' => $product->sku,
            'category' => $product->category,
            'price' => 2100,
            'cost' => 950,
        ])
        ->assertRedirect();

    $product->refresh();
    expect($product->price)->toBe(2100.0)
        ->and($product->cost)->toBe(950.0)
        ->and($product->price)->not->toBe($original);

    // The register prices the new sale from the catalogue, not from the browser.
    $sale = app(RingUpSale::class)([
        'branch_id' => shop()->id,
        'items' => [['kind' => 'product', 'id' => $product->id, 'quantity' => 1]],
        'method' => 'cash',
        'amount' => 2100,
    ], desk())->sale;

    expect($sale->total)->toBe(2100.0)
        ->and($sale->items()->first()->unit_price)->toBe(2100.0);
});

it('does not rewrite a receipt when the price changes afterwards', function () {
    $product = shopItem();
    $original = (int) $product->price;
    $sale = app(RingUpSale::class)([
        'branch_id' => shop()->id,
        'items' => [['kind' => 'product', 'id' => $product->id, 'quantity' => 1]],
        'method' => 'cash',
        'amount' => $original,
    ], desk())->sale;

    $this->actingAs(boss())
        ->patch("/admin/catalog/{$product->id}", [
            'name' => $product->name,
            'price' => $product->price + 500,
            'cost' => $product->cost,
        ])
        ->assertRedirect();

    // The printed receipt is a record of what it sold for, not of what the
    // item costs today.
    $this->actingAs(desk())
        ->get("/admin/pos/sales/{$sale->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/pos/sales/show')
            ->where('sale.items.0.unit_price', $original)
            ->where('sale.items.0.line_total', $original));
});

it('takes a hidden item off the register but keeps its history', function () {
    $product = shopItem();

    $this->actingAs(boss())
        ->patch("/admin/catalog/{$product->id}", [
            'name' => $product->name,
            'sku' => $product->sku,
            'price' => $product->price,
            'cost' => $product->cost,
            'is_active' => 0,
        ])
        ->assertRedirect();

    expect($product->refresh()->is_active)->toBeFalse();

    $this->actingAs(desk())->get('/admin/pos')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/pos/index')
            ->where('products', fn ($items) => collect($items)->pluck('name')->doesntContain($product->name)));

    // Still on the shelf and still in the ledger, just not for sale.
    expect($product->onHandAt(shop()))->toBeGreaterThan(0)
        ->and($product->movements()->count())->toBeGreaterThan(0);
});

it('will not sell a new item that has never been received', function () {
    $this->actingAs(boss())->post('/admin/catalog', [
        'name' => 'Unsold Serum',
        'price' => 1000,
        'cost' => 400,
    ]);

    $product = Product::where('name', 'Unsold Serum')->firstOrFail();

    $this->actingAs(desk())
        ->post('/admin/pos', [
            'branch_id' => shop()->id,
            'items' => [['kind' => 'product', 'id' => $product->id, 'quantity' => 1]],
            'method' => 'cash',
            'amount' => 1000,
        ])
        ->assertSessionHasErrors('items');

    expect(Sale::whereHas('items', fn ($q) => $q->where('product_id', $product->id))->exists())->toBeFalse();
});

it('keeps the register and the shelves in step when stock is received', function () {
    $product = shopItem();
    $before = $product->onHandAt(shop());

    $this->actingAs(boss())
        ->post('/admin/inventory/receive', [
            'branch_id' => shop()->id,
            'lines' => [[
                'product_id' => $product->id,
                'quantity' => 5,
                'cost' => 900,
            ]],
        ])
        ->assertRedirect();

    expect($product->refresh()->onHandAt(shop()))->toBe($before + 5);
});

it('adds a service that the register can sell straight away', function () {
    $this->actingAs(boss())
        ->post('/admin/catalog/services', [
            'name' => 'Hydra Lift',
            'summary' => 'A deep cleanse with a cold roll.',
            'description' => 'Cleanses, exfoliates and finishes with a cold jade roll.',
            'duration_minutes' => 75,
            'treatment_category_id' => facials(),
            'price' => 4200,
        ])
        ->assertRedirect();

    $service = Treatment::where('name', 'Hydra Lift')->firstOrFail();

    expect($service->slug)->toBe('hydra-lift')
        ->and($service->is_active)->toBeTrue()
        ->and($service->currentPrice())->toBe(4200.0);

    $register = $this->actingAs(desk())->get('/admin/pos')->assertOk()->viewData('page')['props'];

    expect(collect($register['services'])->firstWhere('name', 'Hydra Lift')['price'])
        ->toBe(4200.0);
});

it('adjusts a service price and the register follows, receipts do not', function () {
    $service = Treatment::where('slug', 'hydra-facial')->firstOrFail();
    $was = (float) $service->currentPrice();

    $this->actingAs(boss())
        ->patch("/admin/catalog/services/{$service->id}", [
            'name' => $service->name,
            'summary' => $service->summary,
            'description' => $service->description,
            'duration_minutes' => $service->duration_minutes,
            'treatment_category_id' => $service->treatment_category_id,
            'price' => 3900,
            'promo_price' => 3500,
        ])
        ->assertRedirect();

    expect($service->refresh()->currentPrice())->toBe(3500.0)
        ->and($service->isOnPromo())->toBeTrue();

    $sale = app(RingUpSale::class)([
        'branch_id' => shop()->id,
        'items' => [['kind' => 'service', 'id' => $service->id, 'quantity' => 1]],
        'method' => 'cash',
        'amount' => 3500,
    ], desk())->sale;

    expect($sale->total)->toBe(3500.0)
        ->and((float) $sale->items()->first()->unit_price)->not->toBe($was)
        ->and($was)->toBe(2990.0);
});

it('retires a service instead of deleting one that has been used', function () {
    // Sell one first, so there is a receipt that has to keep naming it.
    $sold = Treatment::where('slug', 'hydra-facial')->firstOrFail();
    app(RingUpSale::class)([
        'branch_id' => shop()->id,
        'items' => [['kind' => 'service', 'id' => $sold->id, 'quantity' => 1]],
        'method' => 'cash',
        'amount' => $sold->currentPrice(),
    ], desk());

    expect($sold->saleItems()->count())->toBe(1);

    $this->actingAs(boss())
        ->delete("/admin/catalog/services/{$sold->id}")
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(Treatment::whereKey($sold->id)->exists())->toBeTrue();

    // A booking pins the service too: the foreign key would refuse a delete.
    $booked = Treatment::where('slug', 'chemical-peel')->firstOrFail();
    Appointment::create([
        'organization_id' => boss()->organization_id,
        'reference' => 'APT-TEST-1',
        'branch_id' => shop()->id,
        'treatment_id' => $booked->id,
        'specialist_id' => Specialist::value('id'),
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDay()->addHour(),
        'status' => 'confirmed',
    ]);

    expect($booked->appointments()->count())->toBe(1);
    $this->actingAs(boss())
        ->delete("/admin/catalog/services/{$booked->id}")
        ->assertSessionHas('error');

    expect(Treatment::whereKey($booked->id)->exists())->toBeTrue();
});

it('deletes a service that was never sold or booked', function () {
    $this->actingAs(boss())
        ->post('/admin/catalog/services', [
            'name' => 'Trial Peel',
            'summary' => 'A trial',
            'description' => 'A short trial peel.',
            'duration_minutes' => 30,
            'treatment_category_id' => facials(),
            'price' => 1500,
        ])
        ->assertRedirect();

    $service = Treatment::where('name', 'Trial Peel')->firstOrFail();

    $this->actingAs(boss())
        ->delete("/admin/catalog/services/{$service->id}")
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Treatment::whereKey($service->id)->exists())->toBeFalse();
});

it('refuses a service with no price or no duration', function () {
    $this->actingAs(boss())
        ->post('/admin/catalog/services', ['name' => 'Mystery Service', 'summary' => 'x', 'description' => 'x', 'treatment_category_id' => facials()])
        ->assertSessionHasErrors(['price', 'duration_minutes']);
});

it('lets the office put a photograph on a treatment', function () {
    $treatment = Treatment::where('name', 'Hydra Facial')->firstOrFail();

    $this->actingAs(boss())
        ->post("/admin/catalog/services/{$treatment->id}/photo", [
            'photo' => UploadedFile::fake()->image('hydra.jpg', 800, 600),
        ])
        ->assertOk()
        ->assertJsonStructure(['path', 'url']);

    // The seed gave this one a filename already; the point is that the office
    // can replace it, and that the record the site reads is the new one.
    expect($treatment->fresh()->image)->not->toBeNull();
});

it('takes the photograph off a treatment again', function () {
    $treatment = Treatment::where('name', 'Hydra Facial')->firstOrFail();

    $this->actingAs(boss())
        ->delete("/admin/catalog/services/{$treatment->id}/photo")
        ->assertOk();

    expect($treatment->fresh()->image)->toBeNull();
});

it('refuses a treatment photo that is not an image', function () {
    $treatment = Treatment::where('name', 'Hydra Facial')->firstOrFail();

    $this->actingAs(boss())
        ->post("/admin/catalog/services/{$treatment->id}/photo", [
            'photo' => UploadedFile::fake()->create('notes.pdf', 40, 'application/pdf'),
        ])
        ->assertSessionHasErrors('photo');

    expect($treatment->fresh()->image)->not->toBeNull();
});

it('keeps treatment photos behind the catalogue permission', function () {
    $treatment = Treatment::where('name', 'Hydra Facial')->firstOrFail();

    $this->actingAs(desk())
        ->post("/admin/catalog/services/{$treatment->id}/photo", [
            'photo' => UploadedFile::fake()->image('x.jpg', 400, 400),
        ])
        ->assertForbidden();
});
