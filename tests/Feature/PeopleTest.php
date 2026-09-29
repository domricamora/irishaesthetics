<?php

use App\Actions\Payroll\CalculatePayslip;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\JournalEntry;
use App\Models\LeaveRequest;
use App\Models\PayrollRun;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

function owner(): User
{
    return User::where('email', 'owner@irish.test')->firstOrFail();
}

function nurse(): Employee
{
    return Employee::where('name', 'Camille Rivera')->firstOrFail();
}

function makeEmployee(array $overrides = []): Employee
{
    return Employee::create(array_merge([
        'employee_no' => 'EMP-T'.Employee::count(),
        'name' => 'Test Person',
        'position' => 'Aesthetician',
        'department' => 'Clinic',
        'branch_id' => Branch::where('slug', 'makati')->value('id'),
        'hire_date' => '2024-01-15',
        'employment_type' => 'regular',
        'pay_schedule' => 'monthly',
        'base_salary' => 30000,
        'monthly_allowance' => 0,
        'status' => 'active',
    ], $overrides));
}

it('lists the staff roll and keeps it behind the permission', function () {
    $this->actingAs(owner())->get('/admin/hr')->assertOk()->assertSee('Camille Rivera');

    $this->actingAs(User::where('email', 'reception@irish.test')->firstOrFail())
        ->get('/admin/hr')->assertOk();
});

it('adds an employee to the roll', function () {
    $this->actingAs(owner())
        ->post('/admin/hr', [
            'name' => 'Nina Velasquez',
            'employee_no' => 'EMP-9001',
            'position' => 'Receptionist',
            'department' => 'Front Desk',
            'hire_date' => '2025-06-02',
            'employment_type' => 'regular',
            'pay_schedule' => 'monthly',
            'base_salary' => 25000,
        ])
        ->assertRedirect();

    expect(Employee::where('name', 'Nina Velasquez')->exists())->toBeTrue();
});

it('refuses an unknown account at the desk', function () {
    $this->actingAs(User::where('email', 'reception@irish.test')->firstOrFail())
        ->post('/admin/hr', [
            'name' => 'Nobody',
            'employee_no' => 'EMP-9002',
            'position' => 'Nobody',
            'department' => 'Clinic',
            'hire_date' => '2025-06-02',
            'employment_type' => 'regular',
            'pay_schedule' => 'monthly',
            'base_salary' => 1,
        ])
        ->assertForbidden();
});

it('clocks a person in and out and turns it into hours', function () {
    $employee = Employee::where('user_id', owner()->id)->firstOrFail();

    // In at nine in the morning, out in the evening.
    $this->travelTo(now()->setTime(9, 0));
    $this->actingAs(owner())
        ->post('/admin/hr/attendance', ['date' => today()->toDateString()])
        ->assertRedirect();

    $this->travelTo(now()->setTime(18, 30));
    $this->actingAs(owner())
        ->post('/admin/hr/attendance', ['date' => today()->toDateString()])
        ->assertRedirect();

    $record = Attendance::where('employee_id', $employee->id)->whereDate('work_date', today())->firstOrFail();

    expect($record->time_in)->not->toBeNull()
        ->and($record->time_out)->not->toBeNull()
        ->and($record->minutes)->toBeGreaterThan(0)
        ->and($record->isComplete())->toBeTrue();
});

it('files a leave request and approves it', function () {
    $this->actingAs(owner())->post('/admin/hr/leave', [
        'employee_id' => nurse()->id,
        'type' => 'annual',
        'from_date' => now()->addWeek()->toDateString(),
        'to_date' => now()->addWeek()->addDay()->toDateString(),
        'days' => 2,
        'reason' => 'Wedding',
    ])->assertRedirect();

    $leave = LeaveRequest::latest('id')->firstOrFail();
    expect($leave->status)->toBe('pending');

    $this->actingAs(owner())
        ->patch("/admin/hr/leave/{$leave->id}", ['status' => 'approved'])
        ->assertRedirect();

    expect($leave->refresh()->status)->toBe('approved')
        ->and($leave->reviewed_by)->toBe(owner()->id);
});

it('pays an ordinary month with no tax and a real contribution', function () {
    $employee = makeEmployee(['base_salary' => 20000]);
    $month = (new CalculatePayslip)($employee, CarbonImmutable::today()->startOfMonth(), CarbonImmutable::today()->endOfMonth());

    // Below the first TRAIN band: no withholding.
    expect($month['gross'])->toBe(20000.0)
        ->and($month['withholding_tax'])->toBe(0.0)
        ->and($month['sss'])->toBe(1000.0)
        ->and($month['pagibig'])->toBe(50.0)
        ->and($month['net'])->toBeLessThan($month['gross']);
});

it('withholds tax above the first band and caps SSS at the ceiling', function () {
    $employee = makeEmployee(['base_salary' => 60000]);
    $month = (new CalculatePayslip)($employee, CarbonImmutable::today()->startOfMonth(), CarbonImmutable::today()->endOfMonth());

    // Marginal bands: 15% of the peso between 20,833 and 33,333, then 20% above 33,333.
    expect($month['withholding_tax'])->toBe(7208.40)
        ->and($month['sss'])->toBe(1750.0)
        ->and($month['philhealth'])->toBe(250.0);
});

it('pays overtime out of the clock and docks unpaid leave', function () {
    $employee = makeEmployee(['base_salary' => 22000, 'monthly_allowance' => 1000]);
    $from = CarbonImmutable::today()->startOfMonth();
    $to = CarbonImmutable::today()->endOfMonth();

    Attendance::create([
        'employee_id' => $employee->id,
        'work_date' => $from->addDays(2)->toDateString(),
        'time_in' => '09:00',
        'time_out' => '19:00',
        'minutes' => 600,
        'overtime_minutes' => 120,
    ]);

    LeaveRequest::create([
        'employee_id' => $employee->id,
        'type' => 'unpaid',
        'from_date' => $from->addDays(3)->toDateString(),
        'to_date' => $from->addDays(3)->toDateString(),
        'days' => 1,
        'status' => 'approved',
    ]);

    $month = (new CalculatePayslip)($employee, $from, $to);

    expect($month['overtime'])->toBeGreaterThan(0)
        ->and($month['unpaid_deduction'])->toBe(round(22000 / 22, 2))
        ->and($month['days_paid'])->toBeLessThan(22.0);
});

it('builds a pay run and posts it to the books only once approved', function () {
    $this->actingAs(owner())->post('/admin/payroll', [
        'label' => 'Payroll test',
        'period_start' => now()->startOfMonth()->toDateString(),
        'period_end' => now()->endOfMonth()->toDateString(),
        'paid_on' => now()->endOfMonth()->toDateString(),
    ])->assertRedirect();

    $run = PayrollRun::where('label', 'Payroll test')->firstOrFail();
    expect($run->status)->toBe('draft')
        ->and($run->payslips()->count())->toBe(Employee::active()->count())
        ->and($run->total_net)->toBeGreaterThan(0)
        ->and(JournalEntry::where('source_type', 'payroll')->exists())->toBeFalse();

    $this->actingAs(owner())->post("/admin/payroll/runs/{$run->id}/approve")->assertRedirect();

    $run->refresh();
    $entry = JournalEntry::where('source_type', 'payroll')->where('source_id', (string) $run->id)->firstOrFail();

    expect($run->status)->toBe('approved')
        ->and($run->posted_at)->not->toBeNull()
        ->and($entry->isBalanced())->toBeTrue()
        ->and(round((float) $entry->lines()->sum('debit'), 2))->toBe(round($run->total_gross, 2));
});

it('will not approve or pay a run out of order', function () {
    $this->actingAs(owner())->post('/admin/payroll', [
        'label' => 'Payroll order',
        'period_start' => now()->startOfMonth()->toDateString(),
        'period_end' => now()->endOfMonth()->toDateString(),
        'paid_on' => now()->endOfMonth()->toDateString(),
    ]);

    $run = PayrollRun::where('label', 'Payroll order')->firstOrFail();

    $this->actingAs(owner())->post("/admin/payroll/runs/{$run->id}/pay")->assertSessionHasErrors('status');

    $this->actingAs(owner())->post("/admin/payroll/runs/{$run->id}/approve")->assertRedirect();
    $this->actingAs(owner())->post("/admin/payroll/runs/{$run->id}/approve")->assertSessionHasErrors('status');
    $this->actingAs(owner())->post("/admin/payroll/runs/{$run->id}/pay")->assertRedirect();

    expect($run->refresh()->status)->toBe('paid')
        ->and(JournalEntry::where('source_id', $run->id.'-paid')->exists())->toBeTrue();
});

it('edits a staff record, pay and all', function () {
    $employee = nurse();

    $this->actingAs(boss())->patch("/admin/hr/employees/{$employee->id}", [
        'name' => 'Camille Rivera-Smith',
        'employee_no' => $employee->employee_no,
        'position' => 'Senior Nurse',
        'department' => 'Clinic',
        'hire_date' => $employee->hire_date->toDateString(),
        'employment_type' => 'regular',
        'pay_schedule' => 'monthly',
        'base_salary' => 38000,
        'monthly_allowance' => 1500,
    ])->assertRedirect();

    $employee->refresh();
    expect($employee->name)->toBe('Camille Rivera-Smith')
        ->and($employee->position)->toBe('Senior Nurse')
        ->and($employee->base_salary)->toBe(38000.0);
});

it('marks a clinician and keeps their credentials with them', function () {
    $employee = nurse();

    $this->actingAs(boss())->patch("/admin/hr/employees/{$employee->id}", [
        'name' => $employee->name,
        'employee_no' => $employee->employee_no,
        'position' => $employee->position,
        'department' => $employee->department,
        'hire_date' => $employee->hire_date->toDateString(),
        'employment_type' => $employee->employment_type,
        'pay_schedule' => $employee->pay_schedule,
        'base_salary' => $employee->base_salary,
        'practitioner' => 1,
        'credentials' => 'RN, Lic. 44120',
        'focus' => 'Injections',
    ])->assertRedirect();

    expect($employee->refresh()->practitioner)->toBeTrue()
        ->and($employee->credentials)->toBe('RN, Lic. 44120')
        ->and($employee->focus)->toBe('Injections');

    // Taking the tick off clears the clinical details rather than leaving them
    // attached to someone who no longer treats.
    $this->actingAs(boss())->patch("/admin/hr/employees/{$employee->id}", [
        'name' => $employee->name,
        'employee_no' => $employee->employee_no,
        'position' => 'Front Desk',
        'department' => 'Front Desk',
        'hire_date' => $employee->hire_date->toDateString(),
        'employment_type' => 'regular',
        'pay_schedule' => 'monthly',
        'base_salary' => $employee->base_salary,
        'practitioner' => 0,
    ])->assertRedirect();

    expect($employee->refresh()->practitioner)->toBeFalse()
        ->and($employee->credentials)->toBeNull()
        ->and($employee->focus)->toBeNull();
});

it('filters the roll down to the people who treat', function () {
    $doctors = $this->actingAs(boss())
        ->get('/admin/hr?kind=doctors')
        ->assertOk()
        ->viewData('page')['props']['employees'];

    expect($doctors)->not->toBeEmpty()
        ->and(collect($doctors)->every(fn ($e) => $e['practitioner'] === true))->toBeTrue();

    $office = $this->actingAs(boss())
        ->get('/admin/hr?kind=admin')
        ->assertOk()
        ->viewData('page')['props']['employees'];

    expect(collect($office)->every(fn ($e) => $e['practitioner'] === false))->toBeTrue();
});

it('stands someone down without losing their history, then brings them back', function () {
    $employee = nurse();
    $payslips = $employee->payslips()->count();

    $this->actingAs(boss())
        ->post("/admin/hr/employees/{$employee->id}/resign")
        ->assertRedirect();

    $employee->refresh();
    expect($employee->status)->toBe('resigned')
        ->and($employee->resigned_on)->not->toBeNull()
        ->and(Employee::whereKey($employee->id)->exists())->toBeTrue()
        ->and($employee->payslips()->count())->toBe($payslips);

    // A stood-down person is not on the next pay run.
    expect(Employee::employedOn(now())->whereKey($employee->id)->exists())->toBeFalse();

    $this->actingAs(boss())
        ->post("/admin/hr/employees/{$employee->id}/reinstate")
        ->assertRedirect();

    expect($employee->refresh()->status)->toBe('active')
        ->and($employee->resigned_on)->toBeNull();
});

it('will not take an employee number that is already on the roll', function () {
    $this->actingAs(boss())->patch('/admin/hr/employees/'.nurse()->id, [
        'name' => 'Camille Rivera',
        'employee_no' => 'EMP-0001',
        'position' => 'Nurse',
        'department' => 'Clinic',
        'hire_date' => '2022-02-14',
        'employment_type' => 'regular',
        'pay_schedule' => 'monthly',
        'base_salary' => 34000,
    ])->assertSessionHasErrors('employee_no');
});

it('opens a person with everything the edit form needs', function () {
    $employee = nurse();

    $props = $this->actingAs(boss())
        ->get("/admin/hr/employees/{$employee->id}")
        ->assertOk()
        ->viewData('page')['props'];

    expect($props['employee']['id'])->toBe($employee->id)
        ->and($props['employee'])->toHaveKeys([
            'practitioner',
            'credentials',
            'focus',
            'branch_id',
            'base_salary',
            'pay_schedule',
        ])
        ->and($props['types'])->toHaveKey('regular')
        ->and($props['schedules'])->toHaveKey('monthly');

    // A clinician reaches the page with their clinical details intact.
    $doctor = Employee::where('practitioner', true)->firstOrFail();
    $doctorProps = $this->actingAs(boss())
        ->get("/admin/hr/employees/{$doctor->id}")
        ->assertOk()
        ->viewData('page')['props']['employee'];

    expect($doctorProps['practitioner'])->toBeTrue()
        ->and($doctorProps['credentials'])->not->toBeNull()
        ->and($doctorProps['focus'])->not->toBeNull();
});
