import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Printer } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { money } from '@/lib/pos';
import payroll from '@/routes/admin/payroll';

type Props = {
    payslip: {
        id: number;
        label: string;
        period: string;
        paid_on: string;
        status: string;
        employee: string;
        position: string;
        employee_no: string;
        days_paid: number;
        basic: number;
        allowance: number;
        commission: number;
        overtime: number;
        gross: number;
        sss: number;
        philhealth: number;
        pagibig: number;
        withholding_tax: number;
        unpaid_deduction: number;
        other_deductions: number;
        total_deductions: number;
        net: number;
    };
};

export default function Payslip({ payslip }: Props) {
    const earnings = [
        {
            label: `Basic salary (${payslip.days_paid} days)`,
            value: payslip.basic,
        },
        { label: 'Allowance', value: payslip.allowance },
        { label: 'Overtime', value: payslip.overtime },
        { label: 'Commission', value: payslip.commission },
    ].filter((line) => line.value > 0);

    const deductions = [
        { label: 'SSS', value: payslip.sss },
        { label: 'PhilHealth', value: payslip.philhealth },
        { label: 'Pag-IBIG', value: payslip.pagibig },
        { label: 'Withholding tax', value: payslip.withholding_tax },
        { label: 'Unpaid leave', value: payslip.unpaid_deduction },
        { label: 'Other deductions', value: payslip.other_deductions },
    ].filter((line) => line.value > 0);

    return (
        <>
            <Head title={`Payslip ${payslip.label}`} />
            <div className="flex flex-1 flex-col gap-6 px-4 py-6 md:px-8 md:py-8">
                <header className="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <Link
                            href={payroll.index().url}
                            className="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground"
                        >
                            <ArrowLeft className="h-4 w-4" />
                            Payroll
                        </Link>
                        <h1 className="mt-2 font-display text-3xl md:text-4xl">
                            Payslip
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {payslip.employee} · {payslip.position} ·{' '}
                            {payslip.employee_no}
                        </p>
                    </div>
                    <Button
                        type="button"
                        onClick={() => window.print()}
                        variant="outline"
                        className="h-10 print:hidden"
                    >
                        <Printer className="h-4 w-4" />
                        Print
                    </Button>
                </header>

                <div className="mx-auto w-full max-w-2xl border border-border bg-background p-8">
                    <div className="flex items-baseline justify-between border-b border-border pb-4">
                        <div>
                            <p className="font-display text-xl">
                                Irish Wellness
                            </p>
                            <p className="text-sm text-muted-foreground">
                                Payslip · {payslip.label}
                            </p>
                        </div>
                        <div className="text-right text-sm text-muted-foreground">
                            <p>{payslip.period}</p>
                            <p>Paid {payslip.paid_on}</p>
                        </div>
                    </div>

                    <div className="grid gap-8 py-6 sm:grid-cols-2">
                        <div>
                            <h2 className="text-sm tracking-wide text-muted-foreground uppercase">
                                Earnings
                            </h2>
                            <dl className="mt-3 flex flex-col gap-2 text-sm">
                                {earnings.map((line) => (
                                    <div
                                        key={line.label}
                                        className="flex justify-between gap-4"
                                    >
                                        <dt>{line.label}</dt>
                                        <dd className="tabular-nums">
                                            {money(line.value)}
                                        </dd>
                                    </div>
                                ))}
                                <div className="mt-2 flex justify-between border-t border-border pt-2 font-medium">
                                    <dt>Gross</dt>
                                    <dd className="tabular-nums">
                                        {money(payslip.gross)}
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <div>
                            <h2 className="text-sm tracking-wide text-muted-foreground uppercase">
                                Deductions
                            </h2>
                            <dl className="mt-3 flex flex-col gap-2 text-sm">
                                {deductions.map((line) => (
                                    <div
                                        key={line.label}
                                        className="flex justify-between gap-4"
                                    >
                                        <dt>{line.label}</dt>
                                        <dd className="tabular-nums">
                                            {money(line.value)}
                                        </dd>
                                    </div>
                                ))}
                                {deductions.length === 0 && (
                                    <p className="text-muted-foreground">
                                        None.
                                    </p>
                                )}
                                <div className="mt-2 flex justify-between border-t border-border pt-2 font-medium">
                                    <dt>Total</dt>
                                    <dd className="tabular-nums">
                                        {money(payslip.total_deductions)}
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <div className="flex items-baseline justify-between border-t-2 border-border pt-4">
                        <span className="font-display text-lg">
                            Take-home pay
                        </span>
                        <span className="font-display text-3xl tabular-nums">
                            {money(payslip.net)}
                        </span>
                    </div>

                    <p className="mt-6 text-xs text-muted-foreground">
                        This is a generated payslip from the system of record.
                        Questions about amounts should go to payroll, not to the
                        counter.
                    </p>
                </div>
            </div>
        </>
    );
}
