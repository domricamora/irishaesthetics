import { Head, useForm, usePage } from '@inertiajs/react';
import { Check } from 'lucide-react';
import type { FormEvent, ReactNode } from 'react';
import { useEffect, useMemo, useState } from 'react';
import {
    formatDuration,
    formatPrice,
    openWindow,
    splitTime,
    toIsoDate,
    upcomingDays,
    useCardDraft,
} from '@/lib/site';
import { cn } from '@/lib/utils';
import bookRoutes from '@/routes/book';
import type { Branch, Specialist, Treatment } from '@/types/site';

type Props = {
    treatments: Treatment[];
    branches: Branch[];
    specialists: Specialist[];
    initial: {
        treatment?: string;
        branch?: string;
        date?: string;
        time?: string;
    };
};

const dayFormat = new Intl.DateTimeFormat('en-PH', { weekday: 'short' });
const longDate = new Intl.DateTimeFormat('en-PH', {
    weekday: 'long',
    month: 'long',
    day: 'numeric',
});

export default function Book({
    treatments,
    branches,
    specialists,
    initial,
}: Props) {
    const { clinic } = usePage().props;
    const [draft, setDraft] = useCardDraft();
    const days = useMemo(() => upcomingDays(14), []);

    const form = useForm({
        treatment_id:
            Number(initial.treatment) || draft.treatment || treatments[0]?.id,
        branch_id: Number(initial.branch) || draft.branch || branches[0]?.id,
        specialist_id: '' as number | '',
        date: initial.date ?? draft.date ?? '',
        time: initial.time ?? '',
        first_name: '',
        last_name: '',
        phone: '',
        email: '',
        notes: '',
        privacy_consent: false,
        marketing_consent: false,
    });
    const { data, setData } = form;

    const treatment =
        treatments.find((t) => t.id === data.treatment_id) ?? treatments[0];
    const branch = branches.find((b) => b.id === data.branch_id) ?? branches[0];
    const doctors = specialists.filter((s) =>
        s.branch_ids?.includes(branch.id),
    );
    const doctor = specialists.find((s) => s.id === data.specialist_id);

    const [slots, setSlots] = useState<string[] | null>(null);

    useEffect(() => {
        if (!data.date) {
            setSlots(null);

            return;
        }

        const controller = new AbortController();
        setSlots(null);
        fetch(
            bookRoutes.slots({
                query: {
                    treatment_id: data.treatment_id,
                    branch_id: data.branch_id,
                    date: data.date,
                    ...(data.specialist_id
                        ? { specialist_id: data.specialist_id }
                        : {}),
                },
            }).url,
            {
                headers: { Accept: 'application/json' },
                signal: controller.signal,
            },
        )
            .then((r) => (r.ok ? r.json() : { slots: [] }))
            .then((res: { slots: string[] }) => {
                setSlots(res.slots);

                if (data.time && !res.slots.includes(data.time)) {
                    setData('time', '');
                }
            })
            .catch(() => undefined);

        return () => controller.abort();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [data.treatment_id, data.branch_id, data.specialist_id, data.date]);

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(bookRoutes.store().url, { preserveScroll: true });
    };

    const steps = [
        ['Treatment', !!data.treatment_id],
        ['Branch', !!data.branch_id],
        ['Day', !!data.date],
        ['Time', !!data.time],
        ['Details', !!data.first_name && !!data.phone],
    ] as const;

    const field =
        'mt-1.5 h-12 w-full border border-input bg-white px-4 focus:border-plum focus:outline-none';

    return (
        <>
            <Head title="Book a consultation" />

            <section className="on-dark bg-plum px-4 pt-14 pb-12 text-white sm:px-8 lg:px-12">
                <div className="mx-auto max-w-7xl">
                    <h1 className="text-5xl leading-[1.05] sm:text-6xl">
                        Book your <em className="text-champagne">visit.</em>
                    </h1>
                    <p className="mt-4 max-w-xl text-lilac">
                        No account needed. We confirm by SMS within the hour during
                        clinic hours.
                    </p>
                </div>
            </section>

            <form
                onSubmit={submit}
                noValidate
                className="grid gap-10 px-4 py-12 sm:px-8 lg:grid-cols-12 lg:gap-12 lg:px-12 lg:py-16"
            >
                <div className="space-y-12 lg:col-span-8">
                    <Step n={1} title="Choose a treatment">
                        <label htmlFor="treatment" className="sr-only">
                            Treatment
                        </label>
                        <select
                            id="treatment"
                            value={data.treatment_id}
                            onChange={(e) => {
                                setData('treatment_id', Number(e.target.value));
                                setDraft({ treatment: Number(e.target.value) });
                            }}
                            className={cn(field, 'cursor-pointer text-lg')}
                        >
                            {Object.entries(
                                treatments.reduce<Record<string, Treatment[]>>(
                                    (groups, t) => {
                                        (groups[t.category?.name ?? 'Other'] ??=
                                            []).push(t);

                                        return groups;
                                    },
                                    {},
                                ),
                            ).map(([group, items]) => (
                                <optgroup key={group} label={group}>
                                    {items.map((t) => (
                                        <option key={t.id} value={t.id}>
                                            {t.name},{' '}
                                            {formatPrice(
                                                t.promo_price ?? t.price,
                                            )}
                                        </option>
                                    ))}
                                </optgroup>
                            ))}
                        </select>
                    </Step>

                    <Step n={2} title="Choose a branch">
                        <div className="grid gap-2 sm:grid-cols-3">
                            {branches.map((b) => (
                                <Choice
                                    key={b.id}
                                    selected={b.id === data.branch_id}
                                    onClick={() => {
                                        setData({
                                            ...data,
                                            branch_id: b.id,
                                            specialist_id: '',
                                            time: '',
                                        });
                                        setDraft({ branch: b.id });
                                    }}
                                >
                                    <span className="block font-display text-xl">
                                        {b.name}
                                    </span>
                                    <span className="mt-1 block text-xs opacity-75">
                                        {b.address}
                                    </span>
                                </Choice>
                            ))}
                        </div>
                    </Step>

                    <Step n={3} title="Choose a doctor" optional>
                        <div className="grid gap-2 sm:grid-cols-3">
                            <Choice
                                selected={data.specialist_id === ''}
                                onClick={() =>
                                    setData({
                                        ...data,
                                        specialist_id: '',
                                        time: '',
                                    })
                                }
                            >
                                <span className="block font-display text-xl">
                                    First available
                                </span>
                                <span className="mt-1 block text-xs opacity-75">
                                    The most open times
                                </span>
                            </Choice>
                            {doctors.map((s) => (
                                <Choice
                                    key={s.id}
                                    selected={s.id === data.specialist_id}
                                    onClick={() =>
                                        setData({
                                            ...data,
                                            specialist_id: s.id,
                                            time: '',
                                        })
                                    }
                                >
                                    <span className="flex items-center gap-3">
                                        {s.photo && (
                                            <img
                                                src={s.photo}
                                                alt=""
                                                className="size-10 rounded-full object-cover object-top"
                                            />
                                        )}
                                        <span>
                                            <span className="block font-medium">
                                                {s.name}
                                            </span>
                                            <span className="block text-xs opacity-75">
                                                {s.title}
                                            </span>
                                        </span>
                                    </span>
                                </Choice>
                            ))}
                        </div>
                    </Step>

                    <Step n={4} title="Pick a day">
                        <div className="grid grid-cols-4 gap-2 sm:grid-cols-7">
                            {days.map((d) => {
                                const iso = toIsoDate(d);
                                const closed = openWindow(branch, d) === null;

                                return (
                                    <button
                                        key={iso}
                                        type="button"
                                        disabled={closed}
                                        aria-pressed={iso === data.date}
                                        onClick={() => {
                                            setData({
                                                ...data,
                                                date: iso,
                                                time: '',
                                            });
                                            setDraft({ date: iso });
                                        }}
                                        className={cn(
                                            'press numerals flex h-16 flex-col items-center justify-center border text-xs',
                                            iso === data.date
                                                ? 'border-plum bg-plum text-white'
                                                : 'border-input hover:border-plum',
                                            closed &&
                                                'cursor-not-allowed opacity-40 hover:border-input',
                                        )}
                                    >
                                        <span>
                                            {closed
                                                ? 'Closed'
                                                : dayFormat.format(d)}
                                        </span>
                                        <span className="font-display text-xl">
                                            {d.getDate()}
                                        </span>
                                    </button>
                                );
                            })}
                        </div>
                    </Step>

                    <Step n={5} title="Pick a time">
                        {!data.date && (
                            <p className="text-muted-foreground">
                                Choose a day to see open times.
                            </p>
                        )}
                        {data.date && (
                            <div
                                aria-live="polite"
                                className="grid grid-cols-3 gap-2 sm:grid-cols-5"
                            >
                                {slots === null &&
                                    Array.from({ length: 10 }, (_, i) => (
                                        <span
                                            key={i}
                                            className="h-16 animate-pulse bg-mist"
                                        />
                                    ))}
                                {slots?.length === 0 && (
                                    <p className="col-span-full text-muted-foreground">
                                        No open times that day. Please try
                                        another day, branch or doctor.
                                    </p>
                                )}
                                {slots?.map((time) => {
                                    const [clock, meridiem] = splitTime(time);

                                    return (
                                        <button
                                            key={time}
                                            type="button"
                                            aria-pressed={time === data.time}
                                            onClick={() =>
                                                setData('time', time)
                                            }
                                            className={cn(
                                                'press numerals flex h-16 items-baseline justify-center gap-1 border pt-4',
                                                time === data.time
                                                    ? 'border-plum bg-plum text-white'
                                                    : 'border-input hover:border-plum',
                                            )}
                                        >
                                            <span className="font-display text-2xl">
                                                {clock}
                                            </span>
                                            <span className="text-[11px] opacity-70">
                                                {meridiem}
                                            </span>
                                        </button>
                                    );
                                })}
                            </div>
                        )}
                        {form.errors.time && (
                            <p
                                role="alert"
                                className="mt-3 text-sm text-destructive"
                            >
                                {form.errors.time}
                            </p>
                        )}
                    </Step>

                    <Step n={6} title="Your details">
                        <div className="grid gap-5 sm:grid-cols-2">
                            <Field
                                label="First name"
                                error={form.errors.first_name}
                            >
                                <input
                                    autoComplete="given-name"
                                    value={data.first_name}
                                    onChange={(e) =>
                                        setData('first_name', e.target.value)
                                    }
                                    className={field}
                                />
                            </Field>
                            <Field label="Last name" optional>
                                <input
                                    autoComplete="family-name"
                                    value={data.last_name}
                                    onChange={(e) =>
                                        setData('last_name', e.target.value)
                                    }
                                    className={field}
                                />
                            </Field>
                            <Field
                                label="Mobile number"
                                error={form.errors.phone}
                                hint="We text your confirmation here."
                            >
                                <input
                                    type="tel"
                                    autoComplete="tel"
                                    inputMode="tel"
                                    placeholder="0917 123 4567"
                                    value={data.phone}
                                    onChange={(e) =>
                                        setData('phone', e.target.value)
                                    }
                                    className={field}
                                />
                            </Field>
                            <Field
                                label="Email"
                                optional
                                error={form.errors.email}
                            >
                                <input
                                    type="email"
                                    autoComplete="email"
                                    value={data.email}
                                    onChange={(e) =>
                                        setData('email', e.target.value)
                                    }
                                    className={field}
                                />
                            </Field>
                            <div className="sm:col-span-2">
                                <Field
                                    label="Anything we should know?"
                                    optional
                                >
                                    <textarea
                                        rows={3}
                                        value={data.notes}
                                        onChange={(e) =>
                                            setData('notes', e.target.value)
                                        }
                                        className={cn(field, 'h-auto py-3')}
                                    />
                                </Field>
                            </div>
                        </div>
                        <div className="mt-6 space-y-3 text-sm">
                            <label className="flex items-start gap-3">
                                <input
                                    type="checkbox"
                                    checked={data.privacy_consent}
                                    onChange={(e) =>
                                        setData(
                                            'privacy_consent',
                                            e.target.checked,
                                        )
                                    }
                                    className="mt-1"
                                />
                                <span>
                                    I agree that {clinic.short_name} may use my
                                    details to manage this appointment, as
                                    described in the privacy notice. Health
                                    information is only seen by my care team.
                                </span>
                            </label>
                            {form.errors.privacy_consent && (
                                <p role="alert" className="text-destructive">
                                    {form.errors.privacy_consent}
                                </p>
                            )}
                            <label className="flex items-start gap-3 text-muted-foreground">
                                <input
                                    type="checkbox"
                                    checked={data.marketing_consent}
                                    onChange={(e) =>
                                        setData(
                                            'marketing_consent',
                                            e.target.checked,
                                        )
                                    }
                                    className="mt-1"
                                />
                                <span>
                                    Send me occasional offers and skin care
                                    tips. Optional, and I can opt out anytime.
                                </span>
                            </label>
                        </div>
                    </Step>
                </div>

                <aside className="lg:col-span-4">
                    <div className="card-stock p-6 lg:sticky lg:top-28">
                        <div className="flex items-baseline justify-between">
                            <h2 className="text-2xl">Your appointment</h2>
                            <span className="border border-dashed border-plum/40 px-2 py-0.5 text-xs text-plum">
                                Draft
                            </span>
                        </div>
                        <ol
                            className="mt-5 grid grid-cols-5 gap-1.5"
                            aria-label="Booking progress"
                        >
                            {steps.map(([label, done]) => (
                                <li
                                    key={label}
                                    className="flex flex-col gap-1.5"
                                >
                                    <span
                                        aria-hidden="true"
                                        className={cn(
                                            'h-1.5 transition-colors duration-200',
                                            done ? 'bg-plum' : 'bg-plum/15',
                                        )}
                                    />
                                    <span
                                        className={cn(
                                            'text-[10px]',
                                            done
                                                ? 'text-plum'
                                                : 'text-muted-foreground',
                                        )}
                                    >
                                        {label}
                                        <span className="sr-only">
                                            {done ? ', done' : ', to do'}
                                        </span>
                                    </span>
                                </li>
                            ))}
                        </ol>
                        <dl className="numerals mt-6 divide-y divide-border border-y border-border text-sm">
                            <Row term="Treatment" value={treatment?.name} />
                            <Row term="Branch" value={branch?.name} />
                            <Row
                                term="Doctor"
                                value={doctor?.name ?? 'First available'}
                            />
                            <Row
                                term="Day"
                                value={
                                    data.date
                                        ? longDate.format(
                                              new Date(`${data.date}T00:00:00`),
                                          )
                                        : undefined
                                }
                            />
                            <Row
                                term="Time"
                                value={
                                    data.time
                                        ? splitTime(data.time).join(' ')
                                        : undefined
                                }
                            />
                            <Row
                                term="Duration"
                                value={
                                    treatment
                                        ? formatDuration(
                                              treatment.duration_minutes,
                                          )
                                        : undefined
                                }
                            />
                        </dl>
                        <p className="numerals mt-5 flex items-baseline justify-between">
                            <span className="text-sm text-muted-foreground">
                                From
                            </span>
                            <span className="font-display text-4xl">
                                {treatment
                                    ? formatPrice(
                                          treatment.promo_price ??
                                              treatment.price,
                                      )
                                    : ''}
                            </span>
                        </p>
                        <button
                            type="submit"
                            disabled={form.processing || !data.time}
                            className="press mt-6 h-13 w-full bg-plum font-medium text-white hover:bg-violet disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {form.processing
                                ? 'Booking...'
                                : data.time
                                  ? 'Confirm booking'
                                  : 'Pick a time to continue'}
                        </button>
                        <p className="mt-3 text-center text-xs text-muted-foreground">
                            No payment now. Free to reschedule up to 24 hours
                            before.
                        </p>
                    </div>
                </aside>
            </form>
        </>
    );
}

function Step({
    n,
    title,
    optional,
    children,
}: {
    n: number;
    title: string;
    optional?: boolean;
    children: ReactNode;
}) {
    return (
        <fieldset>
            <legend className="flex items-baseline gap-3">
                <span className="numerals font-display text-lg text-rose-ink">
                    {n}
                </span>
                <span className="font-display text-3xl">{title}</span>
                {optional && (
                    <span className="text-sm text-muted-foreground">
                        optional
                    </span>
                )}
            </legend>
            <div className="mt-5">{children}</div>
        </fieldset>
    );
}

function Choice({
    selected,
    onClick,
    children,
}: {
    selected: boolean;
    onClick: () => void;
    children: ReactNode;
}) {
    return (
        <button
            type="button"
            aria-pressed={selected}
            onClick={onClick}
            className={cn(
                'press relative border p-4 text-left',
                selected
                    ? 'border-plum bg-plum text-white'
                    : 'border-input hover:border-plum',
            )}
        >
            {selected && (
                <Check
                    className="absolute top-3 right-3 size-4 text-champagne"
                    aria-hidden="true"
                />
            )}
            {children}
        </button>
    );
}

function Field({
    label,
    optional,
    hint,
    error,
    children,
}: {
    label: string;
    optional?: boolean;
    hint?: string;
    error?: string;
    children: ReactNode;
}) {
    return (
        <label className="block text-sm">
            <span className="font-medium">
                {label}{' '}
                {optional && (
                    <span className="font-normal text-muted-foreground">
                        (optional)
                    </span>
                )}
            </span>
            {children}
            {hint && !error && (
                <span className="mt-1 block text-xs text-muted-foreground">
                    {hint}
                </span>
            )}
            {error && (
                <span
                    role="alert"
                    className="mt-1 block text-xs text-destructive"
                >
                    {error}
                </span>
            )}
        </label>
    );
}

function Row({ term, value }: { term: string; value?: string }) {
    return (
        <div className="flex justify-between gap-4 py-2.5">
            <dt className="text-muted-foreground">{term}</dt>
            <dd
                className={cn(
                    'text-right',
                    !value && 'text-muted-foreground/60',
                )}
            >
                {value ?? 'Not chosen'}
            </dd>
        </div>
    );
}
