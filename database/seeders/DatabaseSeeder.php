<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Fictional demo clinic (plan.md §69). Every demo login uses "password".
 */
class DatabaseSeeder extends Seeder
{
    public const BRANCHES = [
        ['name' => 'Makati', 'slug' => 'makati', 'city' => 'Makati City', 'address' => '28 Amorsolo Street, Legaspi Village', 'phone' => '+63 2 8123 4567'],
        ['name' => 'BGC', 'slug' => 'bgc', 'city' => 'Taguig City', 'address' => '11th Avenue corner 30th Street, Bonifacio Global City', 'phone' => '+63 2 8234 5678'],
        ['name' => 'Cebu', 'slug' => 'cebu', 'city' => 'Cebu City', 'address' => 'Unit 3, Park Mall Row, Cebu Business Park', 'phone' => '+63 32 234 5678'],
    ];

    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $organization = Organization::updateOrCreate(
            ['slug' => config('clinic.organization')],
            ['name' => config('clinic.name')],
        );

        foreach (self::BRANCHES as $branch) {
            // Created but never renamed. A clinic that has renamed its own
            // branch should not have the demo name put back by a later seed.
            Branch::withoutGlobalScopes()->firstOrCreate(
                ['organization_id' => $organization->id, 'slug' => $branch['slug']],
                $branch + ['email' => $branch['slug'].'@irish.test'],
            );
        }

        $users = [
            ['admin@irish.test', 'Clara Villanueva', null, 'Super Admin'],
            ['owner@irish.test', 'Isabel Montenegro', $organization->id, 'Organization Owner'],
            ['reception@irish.test', 'Joanna Dizon', $organization->id, 'Receptionist'],
        ];

        foreach ($users as [$email, $name, $organizationId, $role]) {
            $user = User::firstOrNew(['email' => $email]);
            $user->forceFill([
                'name' => $name,
                'organization_id' => $organizationId,
                'email_verified_at' => now(),
                // Only a brand new demo account gets the demo password. An
                // existing one keeps whatever it has, because seeding is run
                // repeatedly against installations that have been signed into
                // and whose passwords have since been changed -- quietly
                // resetting those to "password" would reopen them to anyone
                // who has read this repository.
            ] + ($user->exists ? [] : ['password' => 'password']))->save();
            $user->syncRoles($role);
        }

        $this->call(SiteContentSeeder::class);
        $this->call(MarketingContentSeeder::class);
        $this->call(PosDemoSeeder::class);
        $this->call(HrDemoSeeder::class);
        $this->call(AccountingSeeder::class);

        // Tests build their own leads and appointments.
        if (! app()->runningUnitTests()) {
            $this->call(CrmDemoSeeder::class);
        }
    }
}
