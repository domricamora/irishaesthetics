<?php

use App\Models\Employee;
use App\Models\Specialist;
use App\Models\Treatment;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

function supervisor(): User
{
    return User::where('email', 'owner@irish.test')->firstOrFail();
}

function clinicNurse(): Employee
{
    return Employee::where('name', 'Camille Rivera')->firstOrFail();
}

function staffPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Camille Rivera',
        'employee_no' => clinicNurse()->employee_no,
        'position' => 'Registered Nurse',
        'department' => 'Clinic',
        'hire_date' => '2022-02-14',
        'employment_type' => 'regular',
        'pay_schedule' => 'monthly',
        'base_salary' => 34000,
    ], $overrides);
}

it('uploads a photo against a staff record and keeps it as a path', function () {
    $employee = clinicNurse();

    $response = $this->actingAs(supervisor())
        ->post("/admin/hr/employees/{$employee->id}/photo", [
            'photo' => UploadedFile::fake()->image('camille.jpg', 600, 800),
        ]);

    $response->assertOk()->assertJsonStructure(['path', 'url']);

    $employee->refresh();
    $path = $response->json('path');

    // A path, not an absolute URL, so the same record works on every host.
    expect($path)->toStartWith('/media/photos/staff/')
        ->and($employee->photoPath())->toBe($path)
        ->and($employee->photo)->toBe($response->json('url'))
        ->and(File::exists(public_path(ltrim($path, '/'))))->toBeTrue();
});

it('replaces a photo and clears the file it replaced', function () {
    $employee = clinicNurse();

    $first = $this->actingAs(supervisor())
        ->post("/admin/hr/employees/{$employee->id}/photo", [
            'photo' => UploadedFile::fake()->image('one.jpg'),
        ])->json('path');

    $this->actingAs(supervisor())
        ->post("/admin/hr/employees/{$employee->id}/photo", [
            'photo' => UploadedFile::fake()->image('two.jpg'),
        ])->assertOk();

    $second = $employee->refresh()->photoPath();

    expect($second)->not->toBe($first)
        ->and(File::exists(public_path(ltrim($first, '/'))))->toBeFalse()
        ->and(File::exists(public_path(ltrim($second, '/'))))->toBeTrue();
});

it('refuses a file that is not an image, and one that is too big', function () {
    $employee = clinicNurse();

    $this->actingAs(supervisor())
        ->post("/admin/hr/employees/{$employee->id}/photo", ['photo' => clinicNurse()->id])
        ->assertSessionHasErrors('photo');

    $this->actingAs(supervisor())
        ->post("/admin/hr/employees/{$employee->id}/photo", [
            'photo' => UploadedFile::fake()->create('notes.pdf', 8, 'application/pdf'),
        ])
        ->assertSessionHasErrors('photo');
});

it('removes a photo when it is no longer wanted', function () {
    $employee = clinicNurse();

    $path = $this->actingAs(supervisor())
        ->post("/admin/hr/employees/{$employee->id}/photo", [
            'photo' => UploadedFile::fake()->image('gone.jpg'),
        ])->json('path');

    $this->actingAs(supervisor())
        ->delete("/admin/hr/employees/{$employee->id}/photo")
        ->assertOk();

    expect($employee->refresh()->photoPath())->toBeNull()
        ->and(File::exists(public_path(ltrim($path, '/'))))->toBeFalse();
});

it('keeps staff off the website until someone says otherwise', function () {
    $this->actingAs(User::factory()->create([
        'organization_id' => supervisor()->organization_id,
    ]));

    // Untick everybody first. The demo data deliberately publishes its
    // clinicians so the site has a roster, which is the point of the next
    // test; this one is about the flag gating publication at all.
    Employee::query()->update(['show_on_site' => false]);

    // Unauthenticated: the about page, before any staff is published.
    $team = collect($this->get('/about')->viewData('page')['props']['specialists'])
        ->pluck('name');

    expect($team->contains('Camille Rivera'))->toBeFalse();
})->skip(false);

it('publishes a member of staff to the about page when asked', function () {
    $this->actingAs(supervisor())->patch('/admin/hr/employees/'.clinicNurse()->id, staffPayload([
        'practitioner' => 1,
        'credentials' => 'RN, Lic. 44120',
        'focus' => 'Injections',
        'show_on_site' => 1,
    ]))->assertRedirect();

    expect(clinicNurse()->refresh()->show_on_site)->toBeTrue();

    $team = collect($this->get('/about')->viewData('page')['props']['specialists']);

    $her = $team->firstWhere('name', 'Camille Rivera');

    expect($her)->not->toBeNull()
        ->and($her['title'])->toBe('Registered Nurse')
        ->and($her['credentials'])->toBe('RN, Lic. 44120')
        ->and($her['focus'])->toBe(['Injections']);
});

it('shows a receptionist on the about page but not on a treatment page', function () {
    $reception = Employee::where('position', 'like', '%Front Desk%')->firstOrFail();

    $this->actingAs(supervisor())->patch("/admin/hr/employees/{$reception->id}", staffPayload([
        'name' => $reception->name,
        'employee_no' => $reception->employee_no,
        'position' => $reception->position,
        'department' => $reception->department,
        'hire_date' => $reception->hire_date->toDateString(),
        'base_salary' => $reception->base_salary,
        'show_on_site' => 1,
    ]))->assertRedirect();

    $about = collect($this->get('/about')->viewData('page')['props']['specialists'])
        ->pluck('name');

    $treatmentPage = collect(
        $this->get('/treatments/'.Treatment::value('slug'))
            ->viewData('page')['props']['specialists']
    )->pluck('name');

    expect($about->contains($reception->name))->toBeTrue()
        ->and($treatmentPage->doesntContain($reception->name))->toBeTrue();
});

it('never lists the same person twice when a doctor is both specialist and staff', function () {
    $doctor = Employee::where('name', 'Dr. Adrian Santos')->firstOrFail();
    expect(Specialist::where('name', $doctor->name)->exists())->toBeTrue();

    $this->actingAs(supervisor())->patch("/admin/hr/employees/{$doctor->id}", staffPayload([
        'name' => $doctor->name,
        'employee_no' => $doctor->employee_no,
        'position' => $doctor->position,
        'department' => $doctor->department,
        'hire_date' => $doctor->hire_date->toDateString(),
        'base_salary' => $doctor->base_salary,
        'practitioner' => 1,
        'show_on_site' => 1,
    ]))->assertRedirect();

    $names = collect($this->get('/about')->viewData('page')['props']['specialists'])
        ->pluck('name');

    expect($names->filter(fn ($n) => $n === 'Dr. Adrian Santos'))->toHaveCount(1);
});

it('takes a stood-down person off the website', function () {
    $employee = clinicNurse();

    $this->actingAs(supervisor())
        ->patch("/admin/hr/employees/{$employee->id}", staffPayload([
            'practitioner' => 1,
            'show_on_site' => 1,
        ]))->assertRedirect();

    $this->actingAs(supervisor())
        ->post("/admin/hr/employees/{$employee->id}/resign")
        ->assertRedirect();

    $names = collect($this->get('/about')->viewData('page')['props']['specialists'])
        ->pluck('name');

    expect($names->contains('Camille Rivera'))->toBeFalse();
});
