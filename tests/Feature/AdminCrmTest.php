<?php

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\CrmActivity;
use App\Models\Lead;
use App\Models\Organization;
use App\Models\Treatment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    CarbonImmutable::setTestNow(CarbonImmutable::parse('next monday 08:00'));
    $this->post('/book', [
        'treatment_id' => Treatment::where('slug', 'hydra-facial')->value('id'),
        'branch_id' => Branch::where('slug', 'makati')->value('id'),
        'date' => now()->toDateString(),
        'time' => '10:00',
        'first_name' => 'Ana',
        'phone' => '0917 123 4567',
        'privacy_consent' => '1',
    ])->assertRedirect();
});

function staff(string $email): User
{
    return User::where('email', $email)->firstOrFail();
}

it('shows a website booking on the dashboard and the day agenda right away', function () {
    $reception = staff('reception@irish.test');
    $reference = Appointment::withoutGlobalScopes()->sole()->reference;

    $this->actingAs($reception)->get('/dashboard')->assertOk()
        ->assertInertia(fn ($page) => $page->component('dashboard')->where('today.0.reference', $reference)->where('stats.to_confirm', 1));

    $this->get('/admin/appointments?date='.now()->toDateString())->assertOk()
        ->assertInertia(fn ($page) => $page->where('appointments.0.client', 'Ana'));
});

it('lets reception move a lead stage and log a note', function () {
    $lead = Lead::withoutGlobalScopes()->sole();

    $this->actingAs(staff('reception@irish.test'))
        ->patch("/admin/leads/{$lead->id}", ['stage' => 'contacted'])->assertRedirect();
    $this->post("/admin/leads/{$lead->id}/notes", ['note' => 'Called, prefers afternoons.'])->assertRedirect();

    expect($lead->fresh()->stage)->toBe('contacted')
        ->and(CrmActivity::withoutGlobalScopes()->where('lead_id', $lead->id)->where('type', 'note')->value('description'))->toBe('Called, prefers afternoons.');

    $this->get("/admin/leads/{$lead->id}")->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/leads/show')->has('activities', 3)->has('appointments', 1));
});

it('filters and searches leads', function () {
    $this->actingAs(staff('owner@irish.test'))->get('/admin/leads?stage=consultation_booked&q=Ana')->assertOk()
        ->assertInertia(fn ($page) => $page->has('leads.data', 1));
    $this->get('/admin/leads?q=nobody')->assertInertia(fn ($page) => $page->has('leads.data', 0));
});

it('updates an appointment status and records it on the lead', function () {
    $appointment = Appointment::withoutGlobalScopes()->sole();

    $this->actingAs(staff('reception@irish.test'))
        ->patch("/admin/appointments/{$appointment->id}", ['status' => 'confirmed'])->assertRedirect();

    expect($appointment->fresh()->status)->toBe('confirmed')
        ->and(CrmActivity::withoutGlobalScopes()->where('type', 'appointment')->exists())->toBeTrue();

    $this->patch("/admin/appointments/{$appointment->id}", ['status' => 'teleported'])->assertSessionHasErrors('status');
});

it('blocks staff without the permission', function () {
    $doctor = User::factory()->create(['organization_id' => Organization::sole()->id]);
    $doctor->assignRole('Doctor');
    $lead = Lead::withoutGlobalScopes()->sole();
    $appointment = Appointment::withoutGlobalScopes()->sole();

    $this->actingAs($doctor)->get('/admin/leads')->assertForbidden();
    $this->get("/admin/leads/{$lead->id}")->assertForbidden();
    $this->get('/admin/appointments')->assertOk();
    $this->patch("/admin/appointments/{$appointment->id}", ['status' => 'cancelled'])->assertForbidden();
    $this->get('/dashboard')->assertOk()->assertInertia(fn ($page) => $page->where('leads', null));
});

it('keeps other clinics out', function () {
    $other = Organization::create(['name' => 'Other Clinic', 'slug' => 'other']);
    $intruder = User::factory()->create(['organization_id' => $other->id]);
    $intruder->assignRole('Organization Owner');
    $lead = Lead::withoutGlobalScopes()->sole();

    $this->actingAs($intruder)->get("/admin/leads/{$lead->id}")->assertNotFound();
    $this->patch("/admin/leads/{$lead->id}", ['stage' => 'vip'])->assertNotFound();
    $this->get('/admin/leads')->assertInertia(fn ($page) => $page->has('leads.data', 0));
});

function staffBooking(array $overrides = []): array
{
    return $overrides + [
        'treatment_id' => Treatment::where('slug', 'hydra-facial')->value('id'),
        'branch_id' => Branch::where('slug', 'makati')->value('id'),
        'date' => now()->toDateString(),
        'time' => '14:00',
        'status' => 'confirmed',
    ];
}

it('books a phone client from the front desk', function () {
    $this->actingAs(staff('reception@irish.test'))
        ->post('/admin/appointments', staffBooking(['first_name' => 'Lia', 'phone' => '0918 555 1234', 'source' => 'phone']))
        ->assertRedirect();

    $lead = Lead::withoutGlobalScopes()->where('first_name', 'Lia')->sole();
    expect($lead->source)->toBe('phone')
        ->and($lead->form)->toBe('admin')
        ->and($lead->appointments()->sole()->status)->toBe('confirmed')
        ->and(CrmActivity::withoutGlobalScopes()->where('lead_id', $lead->id)->value('user_id'))->toBe(staff('reception@irish.test')->id);
});

it('books an existing lead without creating a new one', function () {
    $lead = Lead::withoutGlobalScopes()->sole();

    $this->actingAs(staff('reception@irish.test'))
        ->post('/admin/appointments', staffBooking(['lead_id' => $lead->id]))->assertRedirect();

    expect(Lead::withoutGlobalScopes()->count())->toBe(1)
        ->and($lead->appointments()->count())->toBe(2);
});

it('moves an appointment and frees the old slot', function () {
    $old = Appointment::withoutGlobalScopes()->sole();

    $this->actingAs(staff('reception@irish.test'))
        ->post('/admin/appointments', staffBooking(['reschedule_id' => $old->id, 'specialist_id' => $old->specialist_id]))->assertRedirect();

    expect($old->fresh()->status)->toBe('rescheduled')
        ->and(Appointment::withoutGlobalScopes()->where('status', '!=', 'rescheduled')->sole()->starts_at->format('H:i'))->toBe('14:00');

    // The same doctor at the old time can be booked again.
    $this->post('/admin/appointments', staffBooking(['time' => '10:00', 'specialist_id' => $old->specialist_id, 'first_name' => 'Rae', 'phone' => '0918 555 0000']))
        ->assertSessionHasNoErrors();
});

it('lets a cancelled slot be booked again', function () {
    $old = Appointment::withoutGlobalScopes()->sole();
    $old->update(['status' => 'cancelled']);

    $this->actingAs(staff('reception@irish.test'))
        ->post('/admin/appointments', staffBooking(['time' => '10:00', 'specialist_id' => $old->specialist_id, 'first_name' => 'Rae', 'phone' => '0918 555 0000']))
        ->assertSessionHasNoErrors();
});

it('adds a lead from the admin and finds it by search', function () {
    $this->actingAs(staff('reception@irish.test'))
        ->post('/admin/leads', ['first_name' => 'Marga', 'last_name' => 'Uy', 'phone' => '0917 000 1111', 'source' => 'walk_in', 'privacy_consent' => true])
        ->assertRedirect();

    $lead = Lead::withoutGlobalScopes()->where('first_name', 'Marga')->sole();
    expect($lead->privacy_consent_at)->not->toBeNull()->and($lead->stage)->toBe('new');

    $this->getJson('/admin/search?q=marga uy')->assertOk()->assertJsonPath('0.id', $lead->id);
    $this->post('/admin/leads', ['first_name' => 'Nobody', 'source' => 'phone'])->assertSessionHasErrors('phone');
});

it('keeps booking and lead creation to staff with the permission', function () {
    $doctor = User::factory()->create(['organization_id' => Organization::sole()->id]);
    $doctor->assignRole('Doctor');

    $this->actingAs($doctor)->get('/admin/appointments/create')->assertForbidden();
    $this->post('/admin/leads', ['first_name' => 'X', 'phone' => '0917 000 1111', 'source' => 'phone'])->assertForbidden();
    $this->getJson('/admin/search?q=ana')->assertForbidden();
});

it('opens the booking form for a new client, a lead visit and a move', function () {
    $reception = staff('reception@irish.test');
    $lead = Lead::withoutGlobalScopes()->sole();
    $appointment = Appointment::withoutGlobalScopes()->sole();

    $this->actingAs($reception)->get('/admin/appointments/create')->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/appointments/create')
            ->where('lead', null)
            ->where('moving', null)
            ->where('date', now()->toDateString())
            ->has('treatments'));

    $this->get("/admin/appointments/create?lead={$lead->id}")
        ->assertInertia(fn ($page) => $page->where('lead.id', $lead->id)->where('moving', null));

    $this->get("/admin/appointments/create?reschedule={$appointment->id}&date=".now()->addDay()->toDateString())
        ->assertInertia(fn ($page) => $page->where('moving.id', $appointment->id)
            ->where('moving.reference', $appointment->reference)
            ->where('lead.id', $lead->id)
            ->where('date', now()->addDay()->toDateString()));
});

it('opens the add lead form for staff who may create leads', function () {
    $this->actingAs(staff('reception@irish.test'))->get('/admin/leads/create')->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/leads/create')
            ->has('sources')
            ->has('treatments')
            ->has('branches'));
});
