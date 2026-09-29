<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Organization;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * The staff roll (plan.md 28): the people who sign in, plus the ones who do
 * not, with a month of clock-ins and a couple of leave requests so payroll
 * has something to work from.
 */
class HrDemoSeeder extends Seeder
{
    /**
     * email, name, position, department, salary, allowance, hired
     *
     * @var array<int, array<int, mixed>>
     */
    private const STAFF = [
        ['admin@irish.test', 'Isabel Montenegro', 'Clinic Director', 'Management', 95000, 8000, '2019-03-04'],
        ['owner@irish.test', 'RafaelSantiago', 'Owner', 'Management', 120000, 10000, '2018-01-15'],
        ['reception@irish.test', 'Joanna Dizon', 'Front Desk Supervisor', 'Front Desk', 38000, 2500, '2021-06-01'],
        [null, 'Camille Rivera', 'Registered Nurse', 'Clinic', 34000, 1500, '2022-02-14'],
        [null, 'Trisha Navarro', 'Aesthetician', 'Clinic', 32000, 1200, '2023-09-01'],
        [null, 'Marco Villanueva', 'Therapist', 'Clinic', 45000, 2000, '2020-11-09'],
        [null, 'Grace Lim', 'Head Aesthetician', 'Clinic', 52000, 3000, '2019-08-19'],
        [null, 'Dr. Adrian Santos', 'Aesthetic Physician', 'Clinic', 145000, 12000, '2018-05-07'],
        [null, 'Dr. Maya Navarro', 'Wellness Physician', 'Clinic', 128000, 10000, '2020-10-12'],
        [null, 'Dr. Sofia Reyes', 'Medical Director', 'Clinic', 165000, 15000, '2017-01-23'],
    ];

    /** Everyone who treats clients, doctors included (plan.md §28). */
    private const CLINICIANS = [
        'Dr. Adrian Santos',
        'Dr. Maya Navarro',
        'Dr. Sofia Reyes',
        'Camille Rivera',
        'Trisha Navarro',
        'Marco Villanueva',
        'Grace Lim',
    ];

    /** @var array<string, string> */
    private const CREDENTIALS = [
        'Dr. Adrian Santos' => 'MD, Lic. 18000',
        'Dr. Maya Navarro' => 'MD, Lic. 18411',
        'Dr. Sofia Reyes' => 'MD, FAAD, Lic. 17976',
        'Camille Rivera' => 'RN, Lic. 44120',
        'Trisha Navarro' => 'Aesthetician',
        'Marco Villanueva' => 'PT, Lic. 30877',
        'Grace Lim' => 'Aesthetician, Senior',
    ];

    public function run(): void
    {
        $credentials = self::CREDENTIALS;
        $organization = Organization::where('slug', config('clinic.organization'))->firstOrFail();
        $branch = Branch::where('slug', 'makati')->firstOrFail();
        $campus = Branch::where('slug', 'bgc')->first() ?? $branch;
        $reviewer = User::where('organization_id', $organization->id)->orderBy('id')->first();

        foreach (self::STAFF as $index => [$email, $name, $position, $department, $salary, $allowance, $hired]) {
            $employee = Employee::updateOrCreate(
                ['organization_id' => $organization->id, 'employee_no' => 'EMP-'.str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT)],
                [
                    'user_id' => $email ? User::where('email', $email)->value('id') : null,
                    'branch_id' => $index % 4 === 3 ? $campus->id : $branch->id,
                    'name' => $name,
                    'position' => $position,
                    'department' => $department,
                    'hire_date' => $hired,
                    'employment_type' => $index === 3 ? 'probationary' : 'regular',
                    'pay_schedule' => 'monthly',
                    'base_salary' => $salary,
                    'monthly_allowance' => $allowance,
                    'status' => 'active',
                    'practitioner' => in_array($name, self::CLINICIANS, true),
                    'credentials' => in_array($name, self::CLINICIANS, true)
                        ? ($credentials[$name] ?? null)
                        : null,
                    'focus' => match ($name) {
                        'Dr. Adrian Santos' => 'Injectables and laser',
                        'Dr. Maya Navarro' => 'Wellness and hormones',
                        'Dr. Sofia Reyes' => 'Medical dermatology',
                        'Marco Villanueva' => 'Body and face contouring',
                        'Trisha Navarro' => 'Facials and peels',
                        'Camille Rivera' => 'Injections and wound care',
                        default => null,
                    },
                ]
            );

            $this->clockIn($employee, 18);
        }

        // A clinic puts its clinicians on the website and keeps the rest behind
        // the counter, so the public roster is a decision somebody made rather
        // than the whole staff roll. Ticked here, after the roll exists.
        //
        // The default is the safe one: an employee added later is not published
        // until an administrator says so, because a name and a photograph going
        // live by accident is not a mistake anybody wants to find out about
        // from a customer.
        Employee::whereIn('name', self::CLINICIANS)->update(['show_on_site' => true]);

        $nurse = Employee::where('name', 'Camille Rivera')->firstOrFail();
        $therapist = Employee::where('name', 'Marco Villanueva')->firstOrFail();

        LeaveRequest::updateOrCreate(
            ['employee_id' => $nurse->id, 'from_date' => now()->subDays(6)->toDateString()],
            [
                'organization_id' => $organization->id,
                'to_date' => now()->subDays(5)->toDateString(),
                'type' => 'sick',
                'days' => 2,
                'reason' => 'Fever, advised to rest.',
                'status' => 'approved',
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now()->subDays(7),
            ]
        );

        LeaveRequest::updateOrCreate(
            ['employee_id' => $therapist->id, 'from_date' => now()->addDays(9)->toDateString()],
            [
                'organization_id' => $organization->id,
                'to_date' => now()->addDays(12)->toDateString(),
                'type' => 'annual',
                'days' => 4,
                'reason' => 'Family trip.',
                'status' => 'pending',
            ]
        );
    }

    /** A run of ordinary days, with the odd hour past eight. */
    private function clockIn(Employee $employee, int $days): void
    {
        $cursor = CarbonImmutable::today()->subDays($days);
        $overtime = 0;

        while ($cursor->lessThan(CarbonImmutable::today())) {
            if (! $cursor->isWeekend()) {
                $in = '09:'.str_pad((string) mt_rand(0, 20), 2, '0', STR_PAD_LEFT);
                $late = $overtime % 5 === 0;
                $out = $late ? '19:00' : '18:00';
                $minutes = (strtotime($out) - strtotime($in)) / 60;

                Attendance::updateOrCreate(
                    ['employee_id' => $employee->id, 'work_date' => $cursor->toDateString()],
                    [
                        'organization_id' => $employee->organization_id,
                        'time_in' => $in,
                        'time_out' => $out,
                        'minutes' => (int) $minutes,
                        'overtime_minutes' => (int) max(0, $minutes - 480),
                    ]
                );

                $overtime++;
            }

            $cursor = $cursor->addDay();
        }
    }
}
