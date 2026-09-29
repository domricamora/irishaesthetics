<?php

use App\Models\Branch;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => $this->seed(DatabaseSeeder::class));

it('scopes tenant data to the signed-in user organization', function () {
    $other = Organization::create(['name' => 'Other Clinic', 'slug' => 'other']);
    Branch::withoutGlobalScopes()->create(['organization_id' => $other->id, 'name' => 'Davao', 'slug' => 'davao']);

    $this->actingAs(User::where('email', 'owner@irish.test')->first());

    expect(Branch::pluck('slug')->sort()->values()->all())->toBe(['bgc', 'cebu', 'makati']);
});

it('fills organization_id on new tenant rows', function () {
    $this->actingAs(User::where('email', 'owner@irish.test')->first());

    $branch = Branch::create(['name' => 'Alabang', 'slug' => 'alabang']);

    expect($branch->organization_id)->toBe(Organization::where('slug', config('clinic.organization'))->value('id'));
});

it('shares clinic branding, module flags and permissions with every page', function () {
    $this->actingAs(User::where('email', 'reception@irish.test')->first())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('clinic.name', config('clinic.name'))
            ->where('clinic.currency', 'PHP')
            ->missing('clinic.organization')
            ->where('modules.booking', true)
            ->where('auth.permissions', fn ($permissions) => collect($permissions)->contains('leads.create')
                && ! collect($permissions)->contains('settings.edit')));
});

it('lets Super Admin pass every permission check', function () {
    $admin = User::where('email', 'admin@irish.test')->first();

    expect($admin->can('settings.edit'))->toBeTrue()
        ->and(User::where('email', 'reception@irish.test')->first()->can('settings.edit'))->toBeFalse();
});
