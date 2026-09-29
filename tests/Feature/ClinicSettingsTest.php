<?php

use App\Models\ClinicSetting;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

function office(): User
{
    $user = User::where('email', 'owner@irish.test')->firstOrFail();

    return $user;
}

it('starts with no social links on the footer', function () {
    expect(ClinicSetting::socials())->toBe([]);
});

it('saves a social handle and puts it on every public page', function () {
    $this->actingAs(office())->patch(route('admin.settings.clinic.update'), [
        'socials' => ['instagram' => 'patrice.clinic'],
    ])->assertRedirect()->assertSessionHas('success');

    // Asserted on the prop rather than the markup: the page carries it as
    // JSON, where the slashes are escaped, so neither the escaped nor the
    // unescaped text is what actually appears in the response.
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where(
            'socials.instagram',
            'https://instagram.com/patrice.clinic',
        ));
});

it('accepts a full profile link as well as a handle', function () {
    // Somebody will paste the url from their browser bar. Without this the
    // footer would read facebook.com/https://facebook.com/patrice.
    $this->actingAs(office())->patch(route('admin.settings.clinic.update'), [
        'socials' => ['facebook' => 'https://www.facebook.com/patricebeauty'],
    ]);

    expect(ClinicSetting::socials())->toBe([
        'facebook' => 'https://facebook.com/patricebeauty',
    ]);
});

it('drops a platform the office cleared', function () {
    $this->actingAs(office())->patch(route('admin.settings.clinic.update'), [
        'socials' => ['instagram' => 'patrice.clinic', 'tiktok' => 'patrice'],
    ]);

    expect(ClinicSetting::socials())->toHaveCount(2);

    $this->actingAs(office())->patch(route('admin.settings.clinic.update'), [
        'socials' => ['instagram' => 'patrice.clinic', 'tiktok' => ''],
    ]);

    expect(ClinicSetting::socials())->toBe([
        'instagram' => 'https://instagram.com/patrice.clinic',
    ]);
});

it('refuses a handle that would build a broken link', function () {
    $this->actingAs(office())->patch(route('admin.settings.clinic.update'), [
        'socials' => ['instagram' => 'has spaces and <script>'],
    ]);

    expect(ClinicSetting::socials())->toBe([]);
});

it('keeps the settings screen behind the settings permission', function () {
    $desk = User::where('email', 'reception@irish.test')->firstOrFail();

    $this->actingAs($desk)->get(route('admin.settings.clinic.index'))->assertForbidden();
    $this->actingAs($desk)->patch(route('admin.settings.clinic.update'), [])->assertForbidden();
});

it('gives each clinic its own handles', function () {
    $this->actingAs(office())->patch(route('admin.settings.clinic.update'), [
        'socials' => ['instagram' => 'patrice.clinic'],
    ]);

    $other = Organization::create(['name' => 'Other Clinic', 'slug' => 'other-clinic']);
    $otherUser = User::factory()->create(['organization_id' => $other->id]);
    $otherUser->assignRole('Clinic Administrator');

    $this->actingAs($otherUser)->get('/')
        ->assertOk()
        ->assertDontSee('patrice.clinic');
});
