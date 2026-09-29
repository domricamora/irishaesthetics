<?php

use App\Actions\Setup\ClearClinicData;
use App\Actions\Setup\LoadClinicData;
use App\Models\Account;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\InventoryMovement;
use App\Models\Lead;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Treatment;
use App\Models\User;
use Database\Seeders\CrmDemoSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

// The content seeders write into the signed-in clinic, so give them one to
// write into. Everything else is left empty on purpose: these tests are about
// what the button fills in, so they have to start from nothing.
beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $organization = Organization::create([
        'name' => config('clinic.name'),
        'slug' => config('clinic.organization'),
    ]);

    // No branch: the seeder creates its own, and these tests are about what it
    // fills in, so they have to start from an installation with nothing in it.
    app()->instance('organization.default', $organization);
});

function administrator(): User
{
    $user = User::factory()->create([
        'organization_id' => Organization::current()?->id,
    ]);
    $user->assignRole('Clinic Administrator');

    return $user;
}

it('sends guests to the login page', function () {
    $this->get(route('admin.setup.index'))->assertRedirect(route('login'));
    $this->post(route('admin.setup.store'))->assertRedirect(route('login'));
});

it('lets an administrator open the setup screen', function () {
    $this->actingAs(administrator())
        ->get(route('admin.setup.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/setup'));
});

it('refuses a role without settings permission', function () {
    $user = User::factory()->create(['organization_id' => Organization::current()?->id]);
    $user->assignRole('Receptionist');

    $this->actingAs($user)->get(route('admin.setup.index'))->assertForbidden();
    $this->actingAs($user)->post(route('admin.setup.store'))->assertForbidden();
});

it('fills the lists the app needs', function () {
    $this->actingAs(administrator());

    expect(Treatment::count())->toBe(0);

    $this->post(route('admin.setup.store'))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(Treatment::count())->toBeGreaterThan(0)
        ->and(Account::count())->toBeGreaterThan(0)
        ->and(Role::count())->toBeGreaterThan(0);
});

it('loads the demo records a demonstration installation needs', function () {
    $this->actingAs(administrator());

    expect(Employee::count())->toBe(0)->and(Product::count())->toBe(0);

    $this->post(route('admin.setup.store'));

    // Empty dashboards and empty reports cannot be told apart from broken
    // ones, so the demo clinic comes with the data the screens are drawn from.
    expect(Employee::count())->toBeGreaterThan(0)
        ->and(Product::count())->toBeGreaterThan(0)
        ->and(InventoryMovement::count())->toBeGreaterThan(0)
        ->and(Branch::count())->toBe(3);

    // Leads and appointments are the exception here, and only here:
    // DatabaseSeeder leaves them to the test suite, which builds its own.
    // A real run is not a test run, so it does seed them.
    expect(Lead::count())->toBe(0);
});

it('runs the same seeder as a local install', function () {
    expect(LoadClinicData::SEEDER)->toBe(DatabaseSeeder::class);
});

it('never changes the password of an account that already exists', function () {
    $this->actingAs(administrator());

    // The account has to exist first: a brand new demo login is meant to get
    // the demo password, and it is the ones people have since changed that
    // must survive.
    $this->post(route('admin.setup.store'));

    $demo = User::where('email', 'owner@irish.test')->firstOrFail();
    $demo->forceFill(['password' => Hash::make('something-else-entirely')])->save();

    $this->post(route('admin.setup.store'));
    $this->post(route('admin.setup.store'));

    // Seeding runs repeatedly against installations people have signed into.
    // Quietly resetting a changed password back to the one in this repository
    // would reopen every login on the site.
    expect(Hash::check('something-else-entirely', $demo->fresh()->password))->toBeTrue()
        ->and(Hash::check('password', $demo->fresh()->password))->toBeFalse();
});

it('gives a new demo account the demo password', function () {
    $this->actingAs(administrator());

    $this->post(route('admin.setup.store'));

    expect(Hash::check('password', User::where('email', 'owner@irish.test')->firstOrFail()->password))
        ->toBeTrue();
});

it('never renames a branch the clinic has renamed', function () {
    $this->actingAs(administrator());
    $this->post(route('admin.setup.store'));

    $branch = Branch::withoutGlobalScopes()->where('slug', 'makati')->firstOrFail();
    $branch->update(['name' => 'Makati flagship']);

    $this->post(route('admin.setup.store'));

    expect($branch->fresh()->name)->toBe('Makati flagship');
});

it('can be pressed twice without duplicating anything', function () {
    $this->actingAs(administrator());

    $this->post(route('admin.setup.store'));
    $first = Treatment::count();

    $this->post(route('admin.setup.store'));

    expect(Treatment::count())->toBe($first);
});

it('tells every screen which build it is running', function () {
    // A stale tab is indistinguishable from a bug otherwise, and this is what
    // settles it: compare the number on the sidebar with the server's.
    $this->get('/')->assertOk()->assertInertia(
        fn ($page) => $page->where('build', fn ($build) => is_string($build) && strlen($build) > 0),
    );
});

it('reports what is loaded on the dashboard', function () {
    $this->actingAs(administrator());

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('content', fn ($content) => collect($content)
            ->contains(fn ($row) => $row['key'] === 'treatments' && $row['count'] === 0)));

    $this->post(route('admin.setup.store'));

    $this->get(route('dashboard'))->assertInertia(
        fn ($page) => $page->where('content', fn ($content) => collect($content)
            ->firstWhere('key', 'treatments')['count'] > 0),
    );
});

it('keeps the dashboard counts away from roles that cannot see content', function () {
    $user = User::factory()->create(['organization_id' => Organization::current()?->id]);
    $user->assignRole('Receptionist');

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('content', null));
});

it('clears the seeded data', function () {
    $this->actingAs(administrator());
    $this->post(route('admin.setup.store'));

    expect(Treatment::count())->toBeGreaterThan(0);

    $this->post(route('admin.setup.clear'))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(Treatment::count())->toBe(0)
        ->and(Employee::count())->toBe(0)
        ->and(Lead::count())->toBe(0)
        ->and(Appointment::count())->toBe(0)
        ->and(Product::count())->toBe(0)
        ->and(Account::count())->toBe(0);
});

it('keeps the accounts and the clinic when clearing', function () {
    $this->actingAs(administrator());
    $this->post(route('admin.setup.store'));

    $users = User::count();
    $branches = Branch::withoutGlobalScopes()->count();
    $roles = Role::count();

    $this->post(route('admin.setup.clear'));

    // Clearing is offered to a signed-in person. If it removed the accounts
    // there would be no way back in, which is the one outcome it must never
    // have.
    expect(User::count())->toBe($users)
        ->and(Branch::withoutGlobalScopes()->count())->toBe($branches)
        ->and(Role::count())->toBe($roles)
        ->and(Organization::count())->toBe(1);
});

it('loads again after a clear', function () {
    $this->actingAs(administrator());

    $this->post(route('admin.setup.store'));
    $this->post(route('admin.setup.clear'));
    $this->post(route('admin.setup.store'));

    expect(Treatment::count())->toBeGreaterThan(0)->and(Employee::count())->toBeGreaterThan(0);
});

it('leaves the reset behind the settings permission', function () {
    $user = User::factory()->create(['organization_id' => Organization::current()?->id]);
    $user->assignRole('Receptionist');

    $this->actingAs($user)->post(route('admin.setup.clear'))->assertForbidden();
});

it('never names a table that holds an account', function () {
    // The whole safety argument for writing the list out by hand is that an
    // account can never end up in it.
    foreach (['users', 'organizations', 'branches', 'roles', 'permissions', 'sessions', 'passkeys'] as $table) {
        expect(ClearClinicData::TABLES)->not->toContain($table);
    }
});

it('seeds a schedule that no specialist is double-booked for', function () {
    // Run twice, because that is where it actually breaks: the guard was an
    // in-memory map, so it knew about the appointments this run had just made
    // and nothing about the ones the last run left behind. On the live site
    // that surfaced as a duplicate key partway through the second seed, with
    // the demonstration left half loaded.
    $this->seed(DatabaseSeeder::class);
    (new CrmDemoSeeder)->run();
    (new CrmDemoSeeder)->run();

    $clashes = Appointment::withoutGlobalScopes()
        ->selectRaw('specialist_id, starts_at, count(*) as n')
        ->whereNotNull('specialist_id')
        ->groupBy('specialist_id', 'starts_at')
        ->havingRaw('count(*) > 1')
        ->get();

    expect($clashes)->toBeEmpty();
});

it('seeds appointments around today rather than drifting forward', function () {
    $this->seed(DatabaseSeeder::class);
    (new CrmDemoSeeder)->run();

    $near = Appointment::withoutGlobalScopes()
        ->whereDate('starts_at', '>=', today()->subDays(7))
        ->whereDate('starts_at', '<=', today()->addDays(7))
        ->count();

    $total = Appointment::withoutGlobalScopes()->count();

    // Carbon::addDays mutates, so counting off the base instance instead of a
    // copy pushed every appointment a day further along and the schedule
    // walked out of the window entirely.
    expect($near)->toBeGreaterThan(0)->and($total)->toBeGreaterThan(0);
});
