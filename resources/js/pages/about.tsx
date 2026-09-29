import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import type { CSSProperties } from 'react';
import { useReveal } from '@/lib/site';
import { book } from '@/routes';
import treatmentRoutes from '@/routes/treatments';
import type { Branch, Faq, Specialist, Testimonial } from '@/types/site';

type Props = {
    specialists: Specialist[];
    branches: Branch[];
    testimonials: Testimonial[];
    faqs: Faq[];
};

const stagger = (i: number) => ({ '--i': i }) as CSSProperties;

const principles = [
    [
        'Consultation before treatment',
        'Every plan starts with an assessment and a conversation about what you actually want to change.',
    ],
    [
        'One practitioner per plan',
        'The doctor who assesses you explains the plan, performs it and reviews how your skin responded.',
    ],
    [
        'Plain pricing, written down',
        'You receive the number of sessions and the total before anything begins. No packages you did not ask for.',
    ],
    [
        'Records under lock',
        'Consent forms, clinical notes and photographs are stored privately and handled under the Data Privacy Act.',
    ],
];

export default function About({
    specialists,
    branches,
    testimonials,
    faqs,
}: Props) {
    const { clinic, mediaUrl } = usePage().props;
    const media = (file: string) => `${mediaUrl}/${file}`;
    useReveal();

    const figures: [string, string][] = [
        ['2014', 'First clinic opened in Makati'],
        [`${specialists.length}`, 'Doctors and artists on the team'],
        [`${branches.length}`, 'Clinics in Metro Manila and Cebu'],
        ['60+', 'Treatments on the menu'],
    ];

    return (
        <>
            <Head title="About the clinic">
                <meta
                    name="description"
                    content={`How ${clinic.name} works: physician-led assessments, written plans, honest pricing and clinics in Makati, BGC and Cebu.`}
                />
            </Head>

            <section className="on-dark bg-plum px-4 pt-16 pb-14 text-white sm:px-8 lg:px-12 lg:pt-24">
                <div className="mx-auto max-w-7xl">
                    <p className="eyebrow">
                        About
                    </p>
                    <h1 className="mt-4 max-w-4xl text-5xl leading-[1.05] sm:text-7xl">
                        A clinic built around the{' '}
                        <em className="text-champagne">assessment</em>, not the upsell.
                    </h1>
                    <p className="mt-6 max-w-2xl text-lg text-lilac">
                        {clinic.name} is a physician-led aesthetic and wellness
                        clinic. {clinic.tagline} We plan treatment around your skin,
                        your history and your calendar, and we say plainly when a
                        treatment is not for you.
                    </p>
                    <div className="mt-10 flex flex-wrap gap-3">
                        <Link
                            href={book().url}
                            className="press btn btn-solid"
                        >
                            Book a consultation <ArrowRight className="size-4" />
                        </Link>
                        <Link
                            href={treatmentRoutes.index().url}
                            className="press btn btn-outline"
                        >
                            Treatments and prices
                        </Link>
                </div>
                </div>
            </section>

            <section className="grid gap-px border-b border-border bg-border sm:grid-cols-2 lg:grid-cols-4">
                {figures.map(([value, label]) => (
                    <div key={label} className="bg-white px-4 py-10 sm:px-8">
                        <p className="font-display text-4xl">{value}</p>
                        <p className="mt-2 text-sm text-muted-foreground">
                            {label}
                        </p>
                    </div>
                ))}
            </section>
            <section className="grid items-center gap-12 px-4 py-20 sm:px-8 lg:grid-cols-12 lg:px-12 lg:py-28">
                <div className="lg:col-span-6">
                    <h2 data-reveal className="text-4xl sm:text-5xl">
                        How we work
                    </h2>
                    <p
                        data-reveal
                        className="mt-5 max-w-lg text-muted-foreground"
                    >
                        The clinic opened on Amorsolo Street with one treatment
                        room and a simple rule: assess first, then treat. That
                        rule still decides how a visit runs today, in every
                        branch.
                    </p>
                    <ul className="mt-10 grid gap-8 sm:grid-cols-2">
                        {principles.map(([title, body], i) => (
                            <li key={title} data-reveal style={stagger(i)}>
                                <h3 className="text-xl">{title}</h3>
                                <p className="mt-2 text-sm text-muted-foreground">
                                    {body}
                                </p>
                            </li>
                        ))}
                    </ul>
                </div>
                <div className="lg:col-span-6">
                    <img
                        src={media('clinic-interior.jpg')}
                        alt="Treatment room at the clinic"
                        loading="lazy"
                        className="aspect-[4/3] w-full object-cover"
                    />
                    <div className="mt-4 grid grid-cols-2 gap-4">
                        <img
                            src={media('reception.jpg')}
                            alt="Reception area"
                            loading="lazy"
                            className="aspect-square w-full object-cover"
                        />
                        <img
                            src={media('technology.jpg')}
                            alt="Treatment technology"
                            loading="lazy"
                            className="aspect-square w-full object-cover"
                        />
                    </div>
                </div>
            </section>

            <section className="bg-mist px-4 py-20 sm:px-8 lg:px-12 lg:py-28">
                <h2 data-reveal className="text-4xl sm:text-5xl">
                    Who looks after you
                </h2>
                <p className="mt-5 max-w-xl text-muted-foreground">
                    Fictional profiles for this demonstration clinic. Each
                    practitioner works from a written plan that you keep.
                </p>
                <div className="mt-12 grid gap-10 sm:grid-cols-2 lg:grid-cols-3">
                    {specialists.map((s, i) => (
                        <article
                            key={s.id}
                            data-reveal
                            style={stagger(i % 3)}
                            className="flex flex-col"
                        >
                            {s.photo && (
                                <img
                                    src={s.photo}
                                    alt={s.name}
                                    loading="lazy"
                                    className="aspect-[4/5] w-full object-cover"
                                />
                            )}
                            <h3 className="mt-5 text-2xl">{s.name}</h3>
                            <p className="mt-1 text-sm text-rose-ink">
                                {s.title}
                            </p>
                            {s.credentials && (
                                <p className="mt-1 text-xs text-muted-foreground">
                                    {s.credentials}
                                </p>
                            )}
                            <p className="mt-4 text-sm leading-relaxed text-muted-foreground">
                                {s.bio}
                            </p>
                            {s.branches && s.branches.length > 0 && (
                                <p className="mt-4 text-xs text-muted-foreground">
                                    {s.branches.join(' · ')}
                                </p>
                            )}
                        </article>
                    ))}
                </div>
            </section>

            <section className="px-4 py-20 sm:px-8 lg:px-12 lg:py-28">
                <h2 data-reveal className="text-4xl sm:text-5xl">
                    Three clinics, one standard
                </h2>
                <div className="mt-12 grid gap-10 lg:grid-cols-3">
                    {branches.map((b, i) => (
                        <article key={b.id} data-reveal style={stagger(i)}>
                            {b.image && (
                                <img
                                    src={b.image}
                                    alt={b.name}
                                    loading="lazy"
                                    className="aspect-[4/3] w-full object-cover"
                                />
                            )}
                            <h3 className="mt-5 text-2xl">{b.name}</h3>
                            <p className="mt-2 text-sm text-muted-foreground">
                                {[b.address, b.city].filter(Boolean).join(', ')}
                            </p>
                            {b.phone && (
                                <p className="mt-1 text-sm">
                                    <a
                                        href={`tel:${b.phone.replace(/\s/g, '')}`}
                                        className="hover:text-rose-ink"
                                    >
                                        {b.phone}
                                    </a>
                                </p>
                            )}
                            {b.hours && (
                                <dl className="mt-4 divide-y divide-border border-y border-border text-sm">
                                    {Object.entries(b.hours).map(
                                        ([day, hours]) => (
                                            <div
                                                key={day}
                                                className="flex justify-between gap-4 py-2"
                                            >
                                                <dt className="text-muted-foreground">
                                                    {day}
                                                </dt>
                                                <dd>{hours}</dd>
                                            </div>
                                        ),
                                    )}
                                </dl>
                            )}
                        </article>
                    ))}
                </div>
            </section>

            {testimonials.length > 0 && (
                <section className="on-dark bg-plum px-4 py-20 text-white sm:px-8 lg:px-12 lg:py-28">
                    <h2 data-reveal className="text-4xl sm:text-5xl">
                        In their words
                    </h2>
                    <p className="mt-4 max-w-lg text-sm text-lilac">
                        Demo testimonials written for this demonstration clinic.
                        Reviews are only published with permission.
                    </p>
                    <div className="mt-12 grid gap-10 lg:grid-cols-3">
                        {testimonials.map((t, i) => (
                            <blockquote
                                key={t.id}
                                data-reveal
                                style={stagger(i)}
                                className="border-t border-white/20 pt-6"
                            >
                                <p className="font-display text-2xl leading-snug">
                                    {t.quote}
                                </p>
                                <footer className="mt-4 text-sm text-lilac">
                                    {t.author_name}
                                    {t.author_meta ? `, ${t.author_meta}` : ''}
                                </footer>
                            </blockquote>
                        ))}
                    </div>
                </section>
            )}

            <section className="bg-mist px-4 py-20 sm:px-8 lg:px-12 lg:py-28">
                <div className="grid gap-12 lg:grid-cols-12">
                    <div className="lg:col-span-4">
                        <h2 data-reveal className="text-4xl sm:text-5xl">
                            Questions people ask first
                        </h2>
                        <p className="mt-5 text-sm text-muted-foreground">
                            The longer list lives in the FAQ on the home page.
                            Ask us anything else at your consultation.
                        </p>
                    </div>
                    <ul className="divide-y divide-border border-y border-border lg:col-span-8">
                        {faqs.map((f) => (
                            <li key={f.id} className="py-6">
                                <h3 className="text-xl">{f.question}</h3>
                                <p className="mt-2 text-sm leading-relaxed text-muted-foreground">
                                    {f.answer}
                                </p>
                            </li>
                        ))}
                    </ul>
                </div>
            </section>

            <section className="px-4 py-20 sm:px-8 lg:px-12">
                <div className="flex flex-wrap items-end justify-between gap-6 border-t border-border pt-10">
                    <h2 className="max-w-xl text-3xl sm:text-4xl">
                        Come in for an assessment and leave with a plan.
                    </h2>
                    <Link
                        href={book().url}
                        className="press inline-flex items-center gap-2 bg-plum px-6 py-3 text-sm font-medium text-white hover:bg-plum-deep"
                    >
                        Book a consultation <ArrowRight className="size-4" />
                    </Link>
                </div>
            </section>
        </>
    );
}
