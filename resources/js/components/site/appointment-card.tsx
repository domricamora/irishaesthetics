import { Link, router } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
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
import { book } from '@/routes';
import bookRoutes from '@/routes/book';
import type { Branch, Treatment } from '@/types/site';

type Props = {
    treatments: Treatment[];
    branches: Branch[];
    className?: string;
    /**
     * Lay the card out across a wide band instead of stacking it in a column.
     *
     * The controls are sized for a narrow card: three branches side by side,
     * seven days, three times. Stretched across a full-width section on their
     * own that turns the time buttons into three enormous slabs, so wide
     * mode moves the "what and when" half into a second column and lets the
     * grids keep their natural width.
     */
    wide?: boolean;
};

const dayFormat = new Intl.DateTimeFormat('en-PH', { weekday: 'short' });

/**
 * The hero booking control: an appointment card the visitor fills in.
 * Choices persist across pages; picking a time continues on /book.
 */
export default function AppointmentCard({
    treatments,
    branches,
    className,
    wide = false,
}: Props) {
    const [draft, setDraft] = useCardDraft();
    const days = useMemo(() => upcomingDays(7), []);

    const treatment =
        treatments.find((t) => t.id === draft.treatment) ?? treatments[0];
    const branch = branches.find((b) => b.id === draft.branch) ?? branches[0];
    const date =
        draft.date && days.some((d) => toIsoDate(d) === draft.date)
            ? draft.date
            : toIsoDate(days.find((d) => openWindow(branch, d)) ?? days[0]);

    const [slots, setSlots] = useState<string[] | null>(null);

    useEffect(() => {
        if (!treatment || !branch) {
            return;
        }

        const controller = new AbortController();
        setSlots(null);
        fetch(
            bookRoutes.slots({
                query: {
                    treatment_id: treatment.id,
                    branch_id: branch.id,
                    date,
                },
            }).url,
            {
                headers: { Accept: 'application/json' },
                signal: controller.signal,
            },
        )
            .then((r) => (r.ok ? r.json() : { slots: [] }))
            .then((data: { slots: string[] }) => setSlots(data.slots))
            .catch(() => undefined);

        return () => controller.abort();
    }, [treatment, branch, date]);

    if (!treatment || !branch) {
        return null;
    }

    const steps = [
        { label: 'Treatment', done: draft.treatment !== undefined },
        { label: 'Branch', done: draft.branch !== undefined },
        { label: 'Day', done: draft.date !== undefined },
        { label: 'Time', done: false },
    ];

    const pick = (time: string) =>
        router.visit(
            book({
                query: {
                    treatment: treatment.id,
                    branch: branch.id,
                    date,
                    time,
                },
            }).url,
        );

    return (
        <section
            aria-labelledby="card-title"
            className={cn('card-stock w-full p-6 text-ink sm:p-7', className)}
        >
            {/* In wide mode the heading, the progress and the treatment choice
                sit in their own column, and the day/time half sits beside
                them, so a full-width band does not stretch every control. */}
            <div
                className={cn(
                    wide && 'grid gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.15fr)] lg:gap-12',
                )}
            >
                <div className={cn(wide && 'min-w-0')}>
                    <div className="flex items-baseline justify-between gap-4">
                        <h2 id="card-title" className="text-2xl font-semibold">
                            Your consultation
                        </h2>
                        <span className="border border-dashed border-plum/40 px-2 py-0.5 text-xs text-plum">
                            Draft
                        </span>
                    </div>

            <ol
                className="mt-5 grid grid-cols-4 gap-2"
                aria-label="Booking progress"
            >
                {steps.map((step) => (
                    <li key={step.label} className="flex flex-col gap-1.5">
                        <span
                            aria-hidden="true"
                            className={cn(
                                'h-1.5 transition-colors duration-200',
                                step.done ? 'bg-plum' : 'bg-plum/15',
                            )}
                        />
                        <span
                            className={cn(
                                'text-[11px]',
                                step.done
                                    ? 'text-plum'
                                    : 'text-muted-foreground',
                            )}
                        >
                            {step.label}
                            <span className="sr-only">
                                {step.done ? ', chosen' : ', not chosen'}
                            </span>
                        </span>
                    </li>
                ))}
            </ol>

            <label
                htmlFor="card-treatment"
                className="mt-6 block text-xs font-medium text-muted-foreground"
            >
                Treatment
            </label>
            <select
                id="card-treatment"
                value={treatment.id}
                onChange={(e) =>
                    setDraft({ treatment: Number(e.target.value) })
                }
                className="mt-1.5 h-12 w-full cursor-pointer border border-input bg-white px-3 text-base font-medium focus:border-plum focus:outline-none"
            >
                {treatments.map((t) => (
                    <option key={t.id} value={t.id}>
                        {t.name}
                    </option>
                ))}
            </select>
            <p className="numerals mt-2 flex gap-3 text-sm text-muted-foreground">
                <span>
                    {treatment.promo_price ? (
                        <>
                            <span className="font-medium text-ink">
                                {formatPrice(treatment.promo_price)}
                            </span>{' '}
                            <s>{formatPrice(treatment.price)}</s>
                        </>
                    ) : (
                        <span className="font-medium text-ink">
                            From {formatPrice(treatment.price)}
                        </span>
                    )}
                </span>
                <span aria-hidden="true">·</span>
                <span>{formatDuration(treatment.duration_minutes)}</span>
            </p>
                </div>

                <div className={cn(wide && 'min-w-0')}>
            <fieldset className="mt-5">
                <legend className="text-xs font-medium text-muted-foreground">
                    Branch
                </legend>
                <div className="mt-1.5 grid grid-cols-3 border border-input">
                    {branches.map((b, i) => (
                        <button
                            key={b.id}
                            type="button"
                            aria-pressed={b.id === branch.id}
                            onClick={() => setDraft({ branch: b.id })}
                            className={cn(
                                'press h-11 text-sm',
                                i > 0 && 'border-l border-input',
                                b.id === branch.id
                                    ? 'bg-plum text-white'
                                    : 'hover:bg-mist',
                            )}
                        >
                            {b.name}
                        </button>
                    ))}
                </div>
            </fieldset>

            <fieldset className="mt-5">
                <legend className="text-xs font-medium text-muted-foreground">
                    Day
                </legend>
                <div className="mt-1.5 grid grid-cols-7 gap-1">
                    {days.map((d) => {
                        const iso = toIsoDate(d);
                        const closed = openWindow(branch, d) === null;

                        return (
                            <button
                                key={iso}
                                type="button"
                                disabled={closed}
                                aria-pressed={iso === date}
                                onClick={() => setDraft({ date: iso })}
                                className={cn(
                                    'press numerals flex h-14 flex-col items-center justify-center border text-xs',
                                    iso === date
                                        ? 'border-plum bg-plum text-white'
                                        : 'border-input hover:border-plum',
                                    closed &&
                                        'cursor-not-allowed opacity-40 hover:border-input',
                                )}
                            >
                                <span>{dayFormat.format(d)}</span>
                                <span className="text-base font-semibold">
                                    {d.getDate()}
                                </span>
                            </button>
                        );
                    })}
                </div>
            </fieldset>

            <div className="mt-5">
                <p
                    className="text-xs font-medium text-muted-foreground"
                    id="times-label"
                >
                    Next open times
                </p>
                <div
                    aria-labelledby="times-label"
                    aria-live="polite"
                    className="mt-1.5 grid min-h-[7.5rem] grid-cols-3 gap-1"
                >
                    {slots === null &&
                        Array.from({ length: 6 }, (_, i) => (
                            <span
                                key={i}
                                className="h-14 animate-pulse bg-mist"
                            />
                        ))}
                    {slots?.length === 0 && (
                        <p className="col-span-3 self-center text-sm text-muted-foreground">
                            No open times that day. Try the next day or another
                            branch.
                        </p>
                    )}
                    {slots?.slice(0, 6).map((time) => {
                        const [clock, meridiem] = splitTime(time);

                        return (
                            <button
                                key={time}
                                type="button"
                                onClick={() => pick(time)}
                                className="press numerals group flex h-14 items-baseline justify-center gap-1 border border-input hover:border-plum hover:bg-plum hover:text-white"
                            >
                                <span className="font-display text-2xl font-semibold tracking-tight">
                                    {clock}
                                </span>
                                <span className="text-[11px] text-muted-foreground group-hover:text-white/80">
                                    {meridiem}
                                </span>
                            </button>
                        );
                    })}
                </div>
            </div>

            <Link
                href={
                    book({
                        query: {
                            treatment: treatment.id,
                            branch: branch.id,
                            date,
                        },
                    }).url
                }
                className="mt-5 inline-flex items-center gap-1.5 text-sm font-medium text-plum hover:text-violet"
            >
                See every time and choose a doctor{' '}
                <ArrowRight className="size-4" />
            </Link>
                </div>
            </div>
        </section>
    );
}
