import { Head, Link } from '@inertiajs/react';
import { Check } from 'lucide-react';
import { useEffect } from 'react';
import { formatDuration } from '@/lib/site';
import { home } from '@/routes';

type Props = {
    appointment: {
        reference: string;
        starts_at: string;
        ends_at: string;
        status: string;
        first_name: string | null;
        treatment: { name: string; duration_minutes: number };
        branch: {
            name: string;
            address: string | null;
            city: string | null;
            phone: string | null;
        };
        specialist: { name: string; title: string; photo: string | null };
    };
};

const dateFormat = new Intl.DateTimeFormat('en-PH', {
    weekday: 'long',
    month: 'long',
    day: 'numeric',
    timeZone: 'Asia/Manila',
});
const timeFormat = new Intl.DateTimeFormat('en-PH', {
    hour: 'numeric',
    minute: '2-digit',
    timeZone: 'Asia/Manila',
});

export default function BookConfirmed({ appointment: a }: Props) {
    const start = new Date(a.starts_at);

    useEffect(() => {
        try {
            localStorage.removeItem('veloura-card');
        } catch {
            // Storage blocked: nothing to clear.
        }
    }, []);

    return (
        <>
            <Head title="Appointment requested" />
            <section className="on-dark bg-plum px-4 py-16 text-white sm:px-8 lg:px-12 lg:py-24">
                <div className="mx-auto max-w-7xl">
                <div className="grid items-center gap-12 lg:grid-cols-12">
                    <div className="lg:col-span-6">
                        <span className="inline-grid size-12 place-items-center border border-gold text-champagne">
                            <Check className="size-6" aria-hidden="true" />
                        </span>
                        <h1 className="mt-6 text-5xl leading-[1.05] sm:text-6xl">
                            Thank you{a.first_name ? `, ${a.first_name}` : ''}.{' '}
                            <em className="text-champagne">Your time is held.</em>
                        </h1>
                        <p className="mt-6 max-w-lg text-lg text-lilac">
                            We will text you to confirm within the hour during
                            clinic hours. Keep your reference handy if you need
                            to call the branch.
                        </p>
                        <Link
                            href={home().url}
                            className="press mt-8 inline-flex h-12 items-center border border-white/40 px-6 font-medium hover:border-white"
                        >
                            Back to home
                        </Link>
                        </div>

                        <div className="card-stock p-7 text-ink lg:col-span-5 lg:col-start-8">
                            <div className="flex items-baseline justify-between">
                                <h2 className="text-2xl">Your appointment</h2>
                                <span className="numerals border border-plum px-2 py-0.5 text-xs font-medium text-plum">
                                    {a.reference}
                                </span>
                            </div>
                            <p className="numerals mt-6 font-display text-5xl">
                                {timeFormat.format(start)}
                            </p>
                            <p className="mt-1 text-lg">
                                {dateFormat.format(start)}
                            </p>
                            <dl className="mt-6 divide-y divide-border border-y border-border text-sm">
                                <div className="flex justify-between gap-4 py-3">
                                    <dt className="text-muted-foreground">
                                        Treatment
                                    </dt>
                                    <dd className="text-right">
                                        {a.treatment.name},{' '}
                                        {formatDuration(
                                            a.treatment.duration_minutes,
                                        )}
                                    </dd>
                                </div>
                                <div className="flex justify-between gap-4 py-3">
                                    <dt className="text-muted-foreground">With</dt>
                                    <dd className="text-right">
                                        {a.specialist.name}
                                    </dd>
                                </div>
                                <div className="flex justify-between gap-4 py-3">
                                    <dt className="text-muted-foreground">Where</dt>
                                    <dd className="text-right">
                                        {a.branch.name}
                                        <br />
                                        <span className="text-muted-foreground">
                                            {a.branch.address}, {a.branch.city}
                                        </span>
                                    </dd>
                                </div>
                                <div className="flex justify-between gap-4 py-3">
                                    <dt className="text-muted-foreground">
                                        Status
                                    </dt>
                                    <dd className="text-right">
                                        Awaiting confirmation
                                    </dd>
                                </div>
                            </dl>
                            <p className="mt-5 text-xs text-muted-foreground">
                                Please arrive 10 minutes early. Free to reschedule
                                up to 24 hours before.
                            </p>
                        </div>
                    </div>
                </div>
            </section>
        </>
    );
}
