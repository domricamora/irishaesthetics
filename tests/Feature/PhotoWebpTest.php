<?php

use App\Actions\Media\UploadPhoto;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    File::deleteDirectory(public_path('media/photos/staff'));
    File::deleteDirectory(public_path('media/photos/products'));
});

afterEach(function () {
    File::deleteDirectory(public_path('media/photos/staff'));
    File::deleteDirectory(public_path('media/photos/products'));
});

function manager(): User
{
    return User::where('email', 'owner@irish.test')->firstOrFail();
}

function shopBalm(): Product
{
    return Product::where('slug', 'cleansing-balm-100ml')->firstOrFail();
}

it('turns a jpeg into a webp, whatever the extension says', function () {
    $path = (new UploadPhoto)(
        UploadedFile::fake()->image('portrait.jpg', 900, 1200),
        'Camille Rivera',
    );

    expect($path)->toEndWith('.webp')
        ->and(File::exists(public_path(ltrim($path, '/'))))->toBeTrue();

    $info = getimagesize(public_path(ltrim($path, '/')));

    expect($info['mime'])->toBe('image/webp');
});

it('converts a png and keeps it a real image', function () {
    $path = (new UploadPhoto)(
        UploadedFile::fake()->image('logo.png', 400, 400),
        'Clinic Logo',
    );

    expect($path)->toEndWith('.webp');
    expect(getimagesize(public_path(ltrim($path, '/')))['mime'])->toBe('image/webp');
});

it('converts a webp that was already webp', function () {
    $path = (new UploadPhoto)(
        UploadedFile::fake()->image('already.webp', 300, 300),
        'Already Done',
    );

    expect($path)->toEndWith('.webp')
        ->and(getimagesize(public_path(ltrim($path, '/')))['mime'])->toBe('image/webp');
});

it('scales an oversized photo down rather than storing a monster', function () {
    $path = (new UploadPhoto)(
        UploadedFile::fake()->image('phone.jpg', 2400, 1600),
        'Big Photo',
    );

    [$width, $height] = getimagesize(public_path(ltrim($path, '/')));

    // 2400x1600 keeps its 3:2 shape, capped on the long edge.
    expect(max($width, $height))->toBe(2000)
        ->and($width)->toBe(2000)
        ->and($height)->toBe(1333);
});

it('leaves a small photo at its own size', function () {
    $path = (new UploadPhoto)(
        UploadedFile::fake()->image('small.jpg', 200, 150),
        'Small Photo',
    );

    [$width, $height] = getimagesize(public_path(ltrim($path, '/')));

    expect($width)->toBe(200)->and($height)->toBe(150);
});

it('refuses something that is not an image at all', function () {
    expect(fn () => (new UploadPhoto)(UploadedFile::fake()->create('notes.pdf', 4, 'application/pdf'), 'Notes'))
        ->toThrow(ValidationException::class);
});

it('stores a product photo as webp and puts it on the record', function () {
    $product = shopBalm();

    $response = $this->actingAs(manager())
        ->post("/admin/catalog/products/{$product->id}/photo", [
            'photo' => UploadedFile::fake()->image('balm.jpg', 800, 800),
        ]);

    $response->assertOk();
    $path = $response->json('path');

    expect($path)->toStartWith('/media/photos/products/')
        ->and($path)->toEndWith('.webp')
        ->and($product->refresh()->imagePath())->toBe($path)
        ->and(getimagesize(public_path(ltrim($path, '/')))['mime'])->toBe('image/webp');
});

it('keeps a staff photo in its own folder, away from product photos', function () {
    $staffPath = (new UploadPhoto)(UploadedFile::fake()->image('a.jpg'), 'Camille Rivera');
    $productPath = (new UploadPhoto('products'))(UploadedFile::fake()->image('b.jpg'), 'Balm');

    expect($staffPath)->toStartWith('/media/photos/staff/')
        ->and($productPath)->toStartWith('/media/photos/products/');
});

it('replaces a product photo and clears the file it replaced', function () {
    $product = shopBalm();

    $first = $this->actingAs(manager())
        ->post("/admin/catalog/products/{$product->id}/photo", [
            'photo' => UploadedFile::fake()->image('one.jpg'),
        ])->json('path');

    $second = $this->actingAs(manager())
        ->post("/admin/catalog/products/{$product->id}/photo", [
            'photo' => UploadedFile::fake()->image('two.jpg'),
        ])->json('path');

    expect($second)->not->toBe($first)
        ->and(File::exists(public_path(ltrim($first, '/'))))->toBeFalse()
        ->and(File::exists(public_path(ltrim($second, '/'))))->toBeTrue();
});

it('removes a product photo without touching a clinic image that predates it', function () {
    $product = shopBalm();
    $seeded = public_path('media/photos/acne.jpg');
    $product->update(['image' => '/media/photos/acne.jpg']);

    $this->actingAs(manager())
        ->delete("/admin/catalog/products/{$product->id}/photo")
        ->assertOk();

    expect($product->refresh()->imagePath())->toBeNull()
        ->and(File::exists($seeded))->toBeTrue();
});

it('will not let the desk change a photo without the permission', function () {
    $reception = User::where('email', 'reception@irish.test')->firstOrFail();

    $this->actingAs($reception)
        ->post('/admin/catalog/products/'.shopBalm()->id.'/photo', [
            'photo' => UploadedFile::fake()->image('nope.jpg'),
        ])
        ->assertForbidden();

    expect(shopBalm()->refresh()->imagePath())->toBeNull();
});
