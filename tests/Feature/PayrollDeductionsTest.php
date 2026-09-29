<?php

use App\Actions\Payroll\CalculatePayslip;
use App\Models\Employee;
use App\Models\PayrollAdjustment;
use App\Models\PayrollRun;
use App\Models\PayrollSetting;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

function clerk(): User
{
    return User::where('email', 'owner@irish.test')->firstOrFail();
}

function theNurse(): Employee
{
    return Employee::where('name', 'Camille Rivera')->firstOrFail();
}

function rates(array $overrides = []): array
{
    return array_merge([
        'sss_rate' => 0.05,
        'sss_ceiling' => 35000,
        'sss_max' => 1750,
        'philhealth_rate' => 0.025,
        'philhealth_ceiling' => 10000,
        'philhealth_max' => 250,
        'pagibig_rate' => 0.01,
        'pagibig_ceiling' => 5000,
        'pagibig_max' => 50,
    ], $overrides);
}

function month(): array
{
    return [
        CarbonImmutable::today()->startOfMonth(),
        CarbonImmutable::today()->endOfMonth(),
    ];
}

/** One payslip for this month, recomputed from the employee as they stand now. */
function payslip(Employee $employee): array
{
    return (new CalculatePayslip)($employee->fresh(), ...month());
}

it('uses the clinic rate for an employee nobody has touched', function () {
    $this->seed(DatabaseSeeder::class);
    $employee = theNurse();

    // Null is not the same as zero: it means "whatever the clinic set", so an
    // employee added before this feature existed keeps paying correctly.
    expect($employee->sss_rate)->toBeNull()
        ->and($employee->statutoryRates()['sss'])->toBe(PayrollSetting::current()->sss_rate);

    expect(payslip($employee)['sss'])->toBeGreaterThan(0);
});

it('uses a rate set on the employee in place of the clinic rate', function () {
    $this->seed(DatabaseSeeder::class);
    $employee = theNurse();

    $before = payslip($employee)['sss'];

    $employee->update(['sss_rate' => 0.1]);

    expect($employee->statutoryRates()['sss'])->toBe(0.1)
        ->and(payslip($employee)['sss'])->toBeGreaterThan($before);
});

it('keeps each employee on their own rate', function () {
    $this->seed(DatabaseSeeder::class);

    $nurse = theNurse();
    $doctor = Employee::where('name', 'Dr. Adrian Santos')->firstOrFail();
    $doctor->update(['sss_rate' => 0.01, 'philhealth_rate' => 0.01]);

    expect($nurse->fresh()->statutoryRates()['sss'])->not->toBe(0.01)
        ->and($doctor->fresh()->statutoryRates()['sss'])->toBe(0.01);
});

it('replaces the withholding table with a flat rate set on the employee', function () {
    $this->seed(DatabaseSeeder::class);
    $employee = theNurse();

    $table = payslip($employee)['withholding_tax'];
    expect($table)->toBeGreaterThan(0);

    $employee->update(['withholding_rate' => 0.15]);

    expect(payslip($employee)['withholding_tax'])
        ->not->toBe($table)
        ->toBe(round((payslip($employee)['gross'] - payslip($employee)['unpaid_deduction']) * 0.15, 2));
});

it('withholds nothing for an exempt employee', function () {
    $this->seed(DatabaseSeeder::class);
    $employee = theNurse();

    $employee->update(['tax_exempt' => true, 'withholding_rate' => 0.15]);

    // Exempt wins over a rate that is somehow still set, because the flag is
    // the decision and the rate would be a leftover.
    expect(payslip($employee)['withholding_tax'])->toBe(0.0);
});

it('takes a blank statutory box to mean the clinic rate, not zero', function () {
    $this->seed(DatabaseSeeder::class);
    $user = clerk();
    $nurse = theNurse();

    $this->actingAs($user)
        ->patch(route('admin.hr.update', $nurse), [
            'name' => $nurse->name,
            'employee_no' => $nurse->employee_no,
            'position' => $nurse->position,
            'department' => $nurse->department,
            'hire_date' => $nurse->hire_date->toDateString(),
            'employment_type' => $nurse->employment_type,
            'pay_schedule' => $nurse->pay_schedule,
            'base_salary' => $nurse->base_salary,
            'monthly_allowance' => $nurse->monthly_allowance,
            'sss_rate' => '',
            'philhealth_rate' => '',
            'pagibig_rate' => '',
            'withholding_rate' => '',
        ])
        ->assertRedirect();

    $fresh = $nurse->fresh();
    expect($fresh->sss_rate)->toBeNull()
        ->and($fresh->sss_rate)->not->toBe(0.0)
        ->and($fresh->statutoryRates()['sss'])->toBe(PayrollSetting::current()->sss_rate);
});

it('saves a rate typed into the employee form', function () {
    $this->seed(DatabaseSeeder::class);
    $user = clerk();
    $nurse = theNurse();

    $this->actingAs($user)
        ->patch(route('admin.hr.update', $nurse), [
            'name' => $nurse->name,
            'employee_no' => $nurse->employee_no,
            'position' => $nurse->position,
            'department' => $nurse->department,
            'hire_date' => $nurse->hire_date->toDateString(),
            'employment_type' => $nurse->employment_type,
            'pay_schedule' => $nurse->pay_schedule,
            'base_salary' => $nurse->base_salary,
            'monthly_allowance' => $nurse->monthly_allowance,
            'sss_rate' => '0.03',
            'philhealth_rate' => '',
            'pagibig_rate' => '',
            'withholding_rate' => '0.1',
            'tax_exempt' => '1',
        ])
        ->assertRedirect();

    $fresh = $nurse->fresh();
    expect($fresh->sss_rate)->toBe(0.03)
        ->and($fresh->withholding_rate)->toBe(0.1)
        ->and($fresh->tax_exempt)->toBeTrue();
});

it('rejects a rate that is not a fraction', function () {
    $this->seed(DatabaseSeeder::class);
    $user = clerk();
    $nurse = theNurse();

    $this->actingAs($user)
        ->patch(route('admin.hr.update', $nurse), [
            'name' => $nurse->name,
            'employee_no' => $nurse->employee_no,
            'position' => $nurse->position,
            'department' => $nurse->department,
            'hire_date' => $nurse->hire_date->toDateString(),
            'employment_type' => $nurse->employment_type,
            'pay_schedule' => $nurse->pay_schedule,
            'base_salary' => $nurse->base_salary,
            'sss_rate' => '5',
        ])
        ->assertSessionHasErrors('sss_rate');
});

it('leaves a payslip already paid alone when a rate changes', function () {
    $this->seed(DatabaseSeeder::class);
    $employee = theNurse();

    $before = payslip($employee)['sss'];
    $employee->update(['sss_rate' => 0.2]);
    $after = payslip($employee)['sss'];

    // Rates apply going forward. A payslip already calculated keeps the figure
    // it was paid, so a later change cannot rewrite a year of history.
    expect($before)->not->toBe($after)
        ->and($before)->toBeGreaterThan(0);
});

it('starts on the config defaults when the clinic has changed nothing', function () {
    $this->seed(DatabaseSeeder::class);

    $month = (new CalculatePayslip)(make_trainee(), ...month());

    expect($month['sss'])->toBe(1000.0)
        ->and(PayrollSetting::query()->exists())->toBeFalse();
});

function make_trainee(int $baseSalary = 20000): Employee
{
    return Employee::create([
        'employee_no' => 'EMP-DED'.Employee::count(),
        'name' => 'Test Deductions',
        'position' => 'Nurse',
        'department' => 'Clinic',
        'hire_date' => '2024-01-01',
        'employment_type' => 'regular',
        'pay_schedule' => 'monthly',
        'base_salary' => $baseSalary,
        'status' => 'active',
    ]);
}

it('uses the rates the office has saved instead of the defaults', function () {
    // The maximum has to move too, or it caps the higher ceiling straight back down.
    PayrollSetting::create(rates([
        'sss_rate' => 0.04,
        'pagibig_ceiling' => 10000,
        'pagibig_max' => 500,
    ]));

    $month = (new CalculatePayslip)(make_trainee(), ...month());

    expect($month['sss'])->toBe(800.0)
        ->and($month['pagibig'])->toBe(100.0); // 1% of a raised 10,000 ceiling
});

it('caps a contribution at the maximum the office set', function () {
    // A big salary and a low ceiling: the ceiling decides.
    PayrollSetting::create(rates(['pagibig_ceiling' => 100000]));

    $month = (new CalculatePayslip)(
        make_trainee(200000),
        ...month()
    );

    expect($month['pagibig'])->toBe(50.0); // 1% of 200,000 is 2,000, but 50 is the maximum
});

it('edits the rates from the office and the next payslip follows', function () {
    $this->actingAs(clerk())
        ->get('/admin/payroll/settings')
        ->assertOk();

    $this->actingAs(clerk())
        ->patch('/admin/payroll/settings', rates(['sss_rate' => 0.02]))
        ->assertRedirect();

    expect(PayrollSetting::current()->sss_rate)->toBe(0.02)
        ->and((new CalculatePayslip)(make_trainee(), ...month())['sss'])->toBe(400.0);
});

it('refuses a rate that is not a share of the salary', function () {
    $this->actingAs(clerk())
        ->patch('/admin/payroll/settings', rates(['sss_rate' => 5]))
        ->assertSessionHasErrors('sss_rate');
});

it('takes a standing loan off every run until it is cleared', function () {
    $employee = make_trainee();
    $this->actingAs(clerk())->post('/admin/payroll/adjustments', [
        'employee_id' => $employee->id,
        'label' => 'Emergency loan',
        'kind' => 'loan',
        'amount' => 1500,
    ])->assertRedirect();

    $month = (new CalculatePayslip)($employee, ...month());

    expect($month['other_deductions'])->toBe(1500.0)
        ->and($month['net'])->toBe(round($month['gross'] - $month['sss'] - $month['philhealth'] - $month['pagibig'] - 1500, 2));

    $adjustment = PayrollAdjustment::firstOrFail();

    $this->actingAs(clerk())
        ->post("/admin/payroll/adjustments/{$adjustment->id}/clear")
        ->assertRedirect();

    expect($adjustment->refresh()->is_active)->toBeFalse()
        ->and((new CalculatePayslip)($employee, ...month())['other_deductions'])->toBe(0.0);
});

it('takes a one-off deduction off that period only', function () {
    $employee = make_trainee();
    $thisPeriod = CarbonImmutable::today()->startOfMonth()->format('Y-m');
    $nextPeriod = CarbonImmutable::today()->startOfMonth()->addMonth()->format('Y-m');

    PayrollAdjustment::create([
        'employee_id' => $employee->id,
        'label' => 'Garnishment order',
        'kind' => 'other',
        'amount' => 3000,
        'period' => $nextPeriod,
    ]);

    expect((new CalculatePayslip)($employee, ...month())['other_deductions'])->toBe(0.0);

    $next = (new CalculatePayslip)(
        $employee,
        CarbonImmutable::today()->startOfMonth()->addMonth(),
        CarbonImmutable::today()->startOfMonth()->addMonth()->endOfMonth(),
    );

    expect($next['other_deductions'])->toBe(3000.0);
});

it('adds up several deductions against the same person', function () {
    $employee = make_trainee();

    foreach ([['Loan', 'loan', 1000], ['Advance', 'advance', 500]] as [$label, $kind, $amount]) {
        $this->actingAs(clerk())->post('/admin/payroll/adjustments', [
            'employee_id' => $employee->id,
            'label' => $label,
            'kind' => $kind,
            'amount' => $amount,
        ])->assertSessionHasNoErrors();
    }

    expect((new CalculatePayslip)($employee, ...month())['other_deductions'])->toBe(1500.0);
});

it('shows the deductions on the pay run and on the person', function () {
    $employee = make_trainee();
    PayrollAdjustment::create([
        'employee_id' => $employee->id,
        'label' => 'Emergency loan',
        'amount' => 1500,
    ]);

    $this->actingAs(clerk())->post('/admin/payroll', [
        'label' => 'Deductions run',
        'period_start' => now()->startOfMonth()->toDateString(),
        'period_end' => now()->endOfMonth()->toDateString(),
        'paid_on' => now()->endOfMonth()->toDateString(),
    ])->assertRedirect();

    $run = PayrollRun::where('label', 'Deductions run')->firstOrFail();
    $slip = $run->payslips()->where('employee_id', $employee->id)->firstOrFail();

    expect($slip->other_deductions)->toBe(1500.0);

    $props = $this->actingAs(clerk())
        ->get("/admin/hr/employees/{$employee->id}")
        ->viewData('page')['props'];

    expect($props['adjustments'])->toHaveCount(1)
        ->and($props['adjustments'][0]['label'])->toBe('Emergency loan');
});

it('keeps the books to staff with the permission', function () {
    $reception = User::where('email', 'reception@irish.test')->firstOrFail();

    $this->actingAs($reception)->get('/admin/payroll/settings')->assertForbidden();
    $this->actingAs($reception)
        ->patch('/admin/payroll/settings', rates())
        ->assertForbidden();
    $this->actingAs($reception)
        ->post('/admin/payroll/adjustments', [
            'employee_id' => theNurse()->id,
            'label' => 'Not mine',
            'amount' => 100,
        ])
        ->assertForbidden();
});
