import { Head, Link, useForm, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    BadgeCheck,
    CalendarCheck,
    Check,
    ClipboardList,
    Cpu,
    Lock,
    Phone,
    Sparkles,
    WandSparkles,
} from 'lucide-react';
import type { CSSProperties, FormEvent } from 'react';
import { useState } from 'react';
import AppointmentCard from '@/components/site/appointment-card';
import BeforeAfter from '@/components/site/before-after';
import HeroVideo from '@/components/site/hero-video';
import { formatDuration, formatPrice, isOpenNow, useReveal } from '@/lib/site';
import { cn } from '@/lib/utils';
import { book, contact } from '@/routes';
import leads from '@/routes/leads';
import treatmentRoutes from '@/routes/treatments';
import type {
    Branch,
    Category,
    Testimonial,
    Treatment,
} from '@/types/site';

type Props = {
    categories: Category[];
    featured: Treatment[];
    bookable: Treatment[];
    branches: Branch[];
    testimonials: Testimonial[];
};

const stagger = (i: number) => ({ '--i': i }) as CSSProperties;

export default function Home({
    categories,
    featured,
    bookable,
    branches,
    testimonials,
}: Props) {
    const { clinic, mediaUrl } = usePage().props;
    const media = (file: string) => `${mediaUrl}/${file}`;
    useReveal();

    return (
        <>
            <Head title="Physician-led aesthetic care in Makati, BGC and Cebu">
                <meta
                    name="description"
                    content={`${clinic.name}: facial treatments, injectables, body, hair and wellness care led by licensed physicians. Book a consultation online in under a minute.`}
                />
            </Head>

            {/*
                Split hero: copy on the left, image on the right, per the
                reference. This replaces the full-bleed video with a floating
                booking card, because the reference puts the two side by side
                rather than stacking them -- the film and the card were
                competing for the same full-width attention.

                The video is kept rather than dropped, but demoted to the right
                column, where it becomes an ordinary editorial image instead of
                the page's loudest element.
            */}
            <section className="relative overflow-hidden bg-background">
                <div className="mx-auto grid max-w-7xl items-center gap-10 px-4 pt-12 pb-14 sm:px-8 lg:grid-cols-2 lg:gap-16 lg:px-12 lg:pt-20 lg:pb-24">
                    <div>
                        <p className="eyebrow">Enhance. Refine. Empower.</p>
                        <h1 className="mt-5 max-w-[14ch] text-[2.75rem] leading-[1.05] sm:text-6xl">
                            Natural aesthetics.{' '}
                            <em className="text-rose-ink">Confident you.</em>
                        </h1>
                        <p className="mt-6 max-w-md text-lg leading-relaxed text-foreground/70">
                            Medical-led treatments tailored to your features,
                            planned by a licensed physician and booked in under
                            a minute.
                        </p>
                        <div className="mt-8 flex flex-wrap gap-3">
                            <Link href={book().url} className="press btn btn-solid">
                                Book a consultation
                            </Link>
                            <Link
                                href={treatmentRoutes.index().url}
                                className="press btn btn-outline"
                            >
                                Our treatments
                            </Link>
                        </div>

                        {/* The three trust points the reference sets under the
                            hero buttons, taken from the wider strip below so
                            the promise is made before the scroll starts. */}
                        <ul className="mt-10 grid gap-6 sm:grid-cols-3">
                            {[
                                [BadgeCheck, 'Medical-led care'],
                                [ClipboardList, 'Personalised plans'],
                                [Sparkles, 'Natural results'],
                            ].map(([Icon, label]) => {
                                const I = Icon as typeof BadgeCheck;

                                return (
                                    <li key={label as string} className="flex items-center gap-2.5">
                                        <I
                                            className="size-5 shrink-0 text-rose-ink"
                                            aria-hidden="true"
                                        />
                                        <span className="text-xs font-medium tracking-[0.12em] uppercase">
                                            {label as string}
                                        </span>
                                    </li>
                                );
                            })}
                        </ul>
                    </div>

                    <div className="relative min-h-[22rem] overflow-hidden bg-blush sm:min-h-[30rem] lg:min-h-[34rem]">
                        <HeroVideo inset={false} />
                    </div>
                </div>
            </section>

            {/*
                The booking card, given the full width of the page directly
                below the hero. It used to sit in the bottom corner of the
                hero's image, which made the fold do two jobs at once and left
                the form reading as part of the photograph. On its own band it
                is the one thing on the screen a visitor can act on, and the
                wide layout stops its three-across controls stretching.
            */}
            <section
                aria-labelledby="booking-heading"
                className="border-b border-border bg-soft-rose"
            >
                <div className="mx-auto max-w-7xl px-4 py-14 sm:px-8 lg:px-12 lg:py-16">
                    {/* The heading lives here rather than inside the card, so
                        it is set by the same eyebrow + serif + line pattern
                        as every other section on the page, and the box is
                        left as a form and nothing else. */}
                    <div className="mb-10 max-w-2xl">
                        <p className="eyebrow">Book online</p>
                        <h2
                            id="booking-heading"
                            className="mt-4 text-3xl sm:text-4xl"
                        >
                            Choose a time that suits you.
                        </h2>
                        <p className="mt-4 text-foreground/70">
                            Pick a treatment and a clinic, and we will hold the
                            slot while you confirm. Nothing is charged until
                            you have seen the plan.
                        </p>
                    </div>
                    <AppointmentCard
                        wide
                        treatments={bookable}
                        branches={branches}
                    />
                </div>
            </section>

            {/* Why Irish */}
            <section
                aria-label={`Why patients choose ${clinic.short_name}`}
                className="border-b border-border bg-white"
            >
                <ul className="grid grid-cols-2 divide-border gutter md:grid-cols-5 md:divide-x">
                    {[
                        [
                            BadgeCheck,
                            'Licensed professionals',
                            'Every treatment is led or supervised by a physician.',
                        ],
                        [
                            ClipboardList,
                            'Personalized plans',
                            'A plan agreed with you before anything begins.',
                        ],
                        [
                            Cpu,
                            'Modern technology',
                            'Current-generation lasers and devices.',
                        ],
                        [
                            Lock,
                            'Secure patient records',
                            'Handled under the Data Privacy Act.',
                        ],
                        [
                            CalendarCheck,
                            'Online booking',
                            'Pick a time in under a minute, no account needed.',
                        ],
                    ].map(([Icon, title, text], i) => {
                        const I = Icon as typeof BadgeCheck;

                        return (
                            <li
                                key={title as string}
                                data-reveal
                                style={stagger(i)}
                                className="py-6 md:px-6 md:first:pl-0"
                            >
                                <I
                                    className="size-5 text-rose-ink"
                                    aria-hidden="true"
                                />
                                <p className="mt-3 text-sm font-semibold">
                                    {title as string}
                                </p>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    {text as string}
                                </p>
                            </li>
                        );
                    })}
                </ul>
            </section>

            {/*
                "Our Signature Treatments": the reference's centred heading over
                a row of circular line icons, each with a name and a line of
                copy. It is a different job from the mosaic below -- that one
                sells the specific treatments, this one says the clinic has a
                shape and a method -- so both stay.
            */}
            <section className="border-b border-border bg-soft-rose gutter py-20 lg:py-24">
                <div className="mx-auto max-w-7xl">
                    <h2 data-reveal className="text-center text-3xl sm:text-4xl">
                        Our <em className="text-rose-ink">Signature</em>{' '}
                        Treatments
                    </h2>

                    <ul className="mt-14 grid gap-10 text-center sm:grid-cols-2 lg:grid-cols-5">
                        {categories.slice(0, 5).map((c, i) => (
                            <li
                                key={c.id}
                                data-reveal
                                style={stagger(i)}
                                className="flex flex-col items-center"
                            >
                                <span
                                    aria-hidden="true"
                                    className="flex size-16 items-center justify-center rounded-full border border-rose-deep/30 text-rose-ink"
                                >
                                    <WandSparkles className="size-6" />
                                </span>
                                <h3 className="mt-5 text-sm font-semibold tracking-[0.14em] uppercase">
                                    {c.name}
                                </h3>
                                <p className="mt-2 max-w-[22ch] text-sm text-muted-foreground">
                                    {c.description}
                                </p>
                            </li>
                        ))}
                    </ul>

                    <div className="mt-14 text-center">
                        <Link
                            href={treatmentRoutes.index().url}
                            className="press btn btn-outline"
                        >
                            View all treatments
                        </Link>
                    </div>
                </div>
            </section>

            {/* Featured treatments */}
            <section className="gutter py-20 lg:py-28">
                <div className="flex flex-wrap items-end justify-between gap-6">
                    <h2 data-reveal className="max-w-2xl text-3xl sm:text-4xl">
                        Treatments our patients book most
                    </h2>
                    <Link
                        href={treatmentRoutes.index().url}
                        className="inline-flex items-center gap-2 font-medium text-plum hover:text-rose-ink"
                    >
                        All {bookable.length} treatments{' '}
                        <ArrowRight className="size-4" />
                    </Link>
                </div>

                {/* Bento mosaic: 4 columns, tile 0 is the 2x2 feature, the last two share the bottom row. */}
                <div className="mt-12 grid auto-rows-[17rem] gap-2 sm:grid-cols-2 lg:auto-rows-[19rem] lg:grid-cols-4">
                    {featured.slice(0, 7).map((t, i) => {
                        const big = i === 0;
                        const wide = i >= 5;

                        return (
                            <article
                                key={t.id}
                                data-reveal
                                style={stagger(i % 4)}
                                className={cn(
                                    'group relative overflow-hidden bg-plum',
                                    big && 'sm:col-span-2 sm:row-span-2',
                                    wide && 'lg:col-span-2',
                                )}
                            >
                                <Link
                                    href={treatmentRoutes.show(t.slug).url}
                                    className="on-dark absolute inset-0 flex flex-col justify-end text-white"
                                >
                                    {t.image && (
                                        <img
                                            src={t.image}
                                            alt=""
                                            loading="lazy"
                                            className="absolute inset-0 -z-0 h-full w-full object-cover transition-transform duration-500 ease-out group-hover:scale-[1.04]"
                                        />
                                    )}
                                    <span
                                        aria-hidden="true"
                                        className="absolute inset-0 bg-[linear-gradient(to_top,rgb(59_36_48/0.88)_0%,rgb(59_36_48/0.35)_45%,rgb(59_36_48/0)_75%)]"
                                    />
                                    <span
                                        aria-hidden="true"
                                        className="absolute inset-3 border border-gold/0 transition-colors duration-300 ease-out group-hover:border-gold/60"
                                    />
                                    <div
                                        className={cn(
                                            'relative flex items-end justify-between gap-4',
                                            big ? 'p-8' : 'p-6',
                                        )}
                                    >
                                        <div>
                                            <p className="eyebrow on-dark">
                                                {t.category?.name}
                                            </p>
                                            <h3
                                                className={cn(
                                                    'mt-2 leading-tight',
                                                    big
                                                        ? 'text-4xl sm:text-5xl'
                                                        : 'text-2xl',
                                                )}
                                            >
                                                {t.name}
                                            </h3>
                                            {big && (
                                                <p className="mt-3 max-w-sm text-sm text-lilac">
                                                    {t.summary}
                                                </p>
                                            )}
                                        </div>
                                        <p className="numerals shrink-0 text-right">
                                            <span
                                                className={cn(
                                                    'block font-display',
                                                    big
                                                        ? 'text-4xl'
                                                        : 'text-2xl',
                                                )}
                                            >
                                                {formatPrice(
                                                    t.promo_price ?? t.price,
                                                )}
                                            </span>
                                            <span className="text-xs text-lilac">
                                                {formatDuration(
                                                    t.duration_minutes,
                                                )}
                                            </span>
                                        </p>
                                    </div>
                                </Link>
                            </article>
                        );
                    })}
                </div>
            </section>

            {/*
                About, in the reference's three columns: the clinic photograph,
                the argument, then a booking panel standing on its own. The
                panel used to be the hero's appointment card; moving it here
                leaves the hero to make one job -- say what this is -- and gives
                the booking step a place where the reader has just been given
                reasons to want it.
            */}
            <section className="border-y border-border bg-soft-rose">
                <div className="mx-auto grid max-w-7xl gap-10 px-4 py-20 sm:px-8 lg:grid-cols-3 lg:gap-8 lg:px-12 lg:py-24">
                    <img
                        src={media('clinic-interior.jpg')}
                        alt="A treatment room at the clinic, prepared before a patient arrives"
                        loading="lazy"
                        className="h-64 w-full object-cover sm:h-80 lg:h-full"
                    />

                    <div>
                        <p className="eyebrow">About {clinic.short_name}</p>
                        <h2 data-reveal className="mt-4 text-3xl sm:text-4xl">
                            Experienced. Trusted.{' '}
                            <em className="text-rose-ink">Here for you.</em>
                        </h2>
                        <p className="mt-5 text-muted-foreground">
                            {clinic.short_name} is a physician-led aesthetics
                            practice. Every plan is agreed with you in writing
                            before anything begins, and every result discussed
                            in terms you can hold onto.
                        </p>
                        <ul className="mt-7 space-y-3">
                            {[
                                'Physician-led, every treatment',
                                'Written plans you keep',
                                'Medical-grade products only',
                            ].map((item) => (
                                <li key={item} className="flex items-start gap-3">
                                    <Check
                                        className="mt-0.5 size-5 shrink-0 text-rose-ink"
                                        aria-hidden="true"
                                    />
                                    <span className="text-sm">{item}</span>
                                </li>
                            ))}
                        </ul>
                    </div>

                    <aside className="flex flex-col justify-center border border-border bg-background p-8">
                        <CalendarCheck
                            className="size-8 text-rose-ink"
                            aria-hidden="true"
                        />
                        <h3 className="mt-5 text-xl font-semibold tracking-tight">
                            Book your consultation
                        </h3>
                        <p className="mt-3 text-sm text-muted-foreground">
                            Start with a short assessment. Nothing is charged
                            until you have seen the plan and decided.
                        </p>
                        <Link
                            href={book().url}
                            className="press btn btn-solid mt-7 w-full"
                        >
                            Book now
                        </Link>
                    </aside>
                </div>
            </section>

            {/*
                The reference's closing row of four editorial cards, each a
                photograph with a title, a line of copy and a link. These are
                the four questions a first-time visitor actually arrives with,
                which is why they are given equal weight rather than being
                folded into the sections above.
            */}
            <section className="gutter py-20 lg:py-24">
                <ul className="mx-auto grid max-w-7xl gap-2 sm:grid-cols-2 lg:grid-cols-4">
                    {[
                        {
                            image: 'consultation.jpg',
                            alt: 'A doctor taking notes while a patient talks',
                            title: 'Your consultation',
                            text: 'Every journey starts with a personalised assessment.',
                            href: book().url,
                            cta: 'Learn more',
                        },
                        {
                            image: 'facial.jpg',
                            alt: 'A facial treatment being carried out',
                            title: 'Premium products',
                            text: 'Trusted, medical-grade products for safe, beautiful results.',
                            href: treatmentRoutes.index().url,
                            cta: 'Learn more',
                        },
                        {
                            image: 'reception.jpg',
                            alt: 'The clinic reception desk',
                            title: 'Aftercare matters',
                            text: 'Follow the aftercare guide for the best possible results.',
                            href: treatmentRoutes.index().url,
                            cta: 'Learn more',
                        },
                        {
                            image: 'makati.jpg',
                            alt: 'The street entrance to the Makati clinic',
                            title: 'Visit the clinic',
                            text: `${clinic.short_name}, ${clinic.contact.address}.`,
                            href: contact().url,
                            cta: 'Get directions',
                        },
                    ].map((card, i) => (
                        <li
                            key={card.title}
                            data-reveal
                            style={stagger(i)}
                            className="group flex flex-col bg-blush"
                        >
                            <img
                                src={media(card.image)}
                                alt={card.alt}
                                loading="lazy"
                                className="h-44 w-full object-cover"
                            />
                            <div className="flex flex-1 flex-col p-6">
                                <h3 className="text-sm font-semibold tracking-[0.14em] uppercase">
                                    {card.title}
                                </h3>
                                <p className="mt-3 text-sm text-muted-foreground">
                                    {card.text}
                                </p>
                                <Link
                                    href={card.href}
                                    className="press btn btn-outline mt-6 self-start"
                                >
                                    {card.cta}
                                </Link>
                            </div>
                        </li>
                    ))}
                </ul>
            </section>

            {/* Client reviews */}
            <section className="bg-mist gutter py-20 lg:py-28">
                <h2 data-reveal className="max-w-2xl text-3xl sm:text-4xl">
                    In our patients’ words
                </h2>
                <div className="mt-12 grid gap-px bg-plum/10 md:grid-cols-2">
                    {testimonials.map((t, i) => (
                        <figure
                            key={t.id}
                            data-reveal
                            style={stagger(i)}
                            className="bg-mist p-8 lg:p-10"
                        >
                            <blockquote
                                className={cn(
                                    'leading-relaxed',
                                    i === 0
                                        ? 'font-display text-2xl sm:text-3xl'
                                        : 'text-lg',
                                )}
                            >
                                “{t.quote}”
                            </blockquote>
                            <figcaption className="mt-6 text-sm">
                                <span className="font-semibold">
                                    {t.author_name}
                                </span>
                                <span className="text-muted-foreground">
                                    {' '}
                                    · {t.author_meta}
                                </span>
                            </figcaption>
                        </figure>
                    ))}
                </div>
            </section>

            {/* Beauty gallery */}
            <section className="bg-white gutter py-20 lg:py-28">
                <div className="grid items-center gap-12 lg:grid-cols-12">
                    <div className="lg:col-span-5">
                        <h2 data-reveal className="text-3xl sm:text-4xl">
                            See the difference a plan makes
                        </h2>
                        <p className="mt-5 max-w-md text-muted-foreground">
                            Drag the handle to compare. Real before and after
                            photos are only shared with written consent from the
                            patient, and you will see relevant cases during your
                            consultation.
                        </p>
                        <p className="mt-4 max-w-md text-sm text-muted-foreground">
                            Results vary from person to person. Your doctor will
                            talk you through what to expect for your skin.
                        </p>
                    </div>
                    <div className="lg:col-span-7">
                        <BeforeAfter
                            src={media('booster.jpg')}
                            alt="Portrait used to illustrate skin texture before and after a hydration treatment"
                        />
                    </div>
                </div>
            </section>

            {/* Ready to glow? */}
            <EnquiryBand treatments={bookable} />

            {/* Categories index */}
            <section className="bg-mist gutter py-20 lg:py-24">
                <div className="grid gap-10 lg:grid-cols-12">
                    <div className="lg:col-span-4">
                        <h2 data-reveal className="text-3xl sm:text-4xl">
                            Care for face, body, hair and wellbeing
                        </h2>
                        <p className="mt-4 max-w-sm text-muted-foreground">
                            Not sure what you need? Start with a consultation
                            and your doctor will build the plan with you.
                        </p>
                    </div>
                    <ul className="divide-y divide-plum/15 border-y border-plum/15 lg:col-span-8">
                        {categories.map((c, i) => (
                            <li key={c.id} data-reveal style={stagger(i)}>
                                <Link
                                    href={`${treatmentRoutes.index().url}#${c.slug}`}
                                    className="group grid grid-cols-[1fr_auto] items-center gap-6 py-6 sm:grid-cols-[14rem_1fr_auto]"
                                >
                                    <span className="font-display text-2xl font-semibold group-hover:text-plum">
                                        {c.name}
                                    </span>
                                    <span className="hidden text-sm text-muted-foreground sm:block">
                                        {c.description}
                                    </span>
                                    <span className="numerals flex items-center gap-3 text-sm text-muted-foreground">
                                        {c.treatments_count} treatments
                                        <ArrowRight className="size-4 text-plum transition-transform duration-200 ease-out group-hover:translate-x-1" />
                                    </span>
                                </Link>
                            </li>
                        ))}
                    </ul>
                </div>
            </section>

            {/* Technology and follow-up */}
            <section className="on-dark gutter grid bg-plum-deep text-white lg:grid-cols-2">
                <div className="py-20 lg:py-28">
                    <h2 data-reveal className="max-w-lg text-3xl sm:text-4xl">
                        Technology that works for you, before and after the
                        visit
                    </h2>
                    <ul className="mt-10 grid gap-8 sm:grid-cols-2">
                        {[
                            [
                                'Book in a minute',
                                'Live availability for every branch and doctor, confirmed by SMS and email.',
                            ],
                            [
                                'Reminders that help',
                                'A reminder the day before, with preparation notes for your treatment.',
                            ],
                            [
                                'Aftercare in writing',
                                'Your instructions arrive after each session, so nothing is forgotten.',
                            ],
                            [
                                'Records under lock',
                                'Consent forms and clinical photos stored privately, with every access logged.',
                            ],
                        ].map(([title, text], i) => (
                            <li
                                key={title}
                                data-reveal
                                style={stagger(i)}
                                className="border-t border-white/15 pt-5"
                            >
                                <p className="font-semibold text-champagne">
                                    {title}
                                </p>
                                <p className="mt-2 text-sm text-lilac/85">
                                    {text}
                                </p>
                            </li>
                        ))}
                    </ul>
                </div>
                <img
                    src={media('laser.jpg')}
                    alt="A practitioner using a handheld laser device during a treatment"
                    loading="lazy"
                    className="h-80 w-full object-cover lg:h-full"
                />
            </section>

        </>
    );
}

function EnquiryBand({ treatments }: { treatments: Treatment[] }) {
    const form = useForm({
        form: 'enquiry',
        first_name: '',
        phone: '',
        email: '',
        treatment_id: '',
        message: '',
        privacy_consent: false,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(leads.store().url, {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    const field =
        'h-12 w-full border border-white/20 bg-white/5 px-4 text-white placeholder:text-white/40 focus:border-gold focus:outline-none';

    return (
        <section
            id="enquire"
            className="on-dark scroll-mt-20 bg-plum text-white"
        >
            <div className="grid gap-12 gutter py-20 lg:grid-cols-12 lg:py-28">
                <div className="lg:col-span-5">
                    <h2 className="text-3xl sm:text-4xl">Ready to glow?</h2>
                    <p className="mt-5 max-w-md text-lg text-lilac">
                        Tell us what you would like to change. A patient
                        coordinator will reply within one business day with a
                        suggested first step.
                    </p>
                    <Link
                        href={book().url}
                        className="press mt-8 inline-flex h-12 items-center gap-2 bg-gold px-6 font-medium text-ink hover:bg-white"
                    >
                        Or book a consultation now{' '}
                        <ArrowRight className="size-4" />
                    </Link>
                </div>

                <form
                    onSubmit={submit}
                    className="grid gap-4 sm:grid-cols-2 lg:col-span-7"
                    noValidate
                >
                    <div>
                        <label
                            htmlFor="enq-name"
                            className="text-sm text-lilac"
                        >
                            First name
                        </label>
                        <input
                            id="enq-name"
                            autoComplete="given-name"
                            value={form.data.first_name}
                            onChange={(e) =>
                                form.setData('first_name', e.target.value)
                            }
                            className={cn(field, 'mt-1.5')}
                        />
                        {form.errors.first_name && (
                            <p role="alert" className="mt-1 text-xs text-champagne">
                                {form.errors.first_name}
                            </p>
                        )}
                    </div>
                    <div>
                        <label
                            htmlFor="enq-phone"
                            className="text-sm text-lilac"
                        >
                            Mobile number
                        </label>
                        <input
                            id="enq-phone"
                            type="tel"
                            autoComplete="tel"
                            value={form.data.phone}
                            onChange={(e) =>
                                form.setData('phone', e.target.value)
                            }
                            className={cn(field, 'mt-1.5')}
                            placeholder="0917 123 4567"
                        />
                    </div>
                    <div>
                        <label
                            htmlFor="enq-email"
                            className="text-sm text-lilac"
                        >
                            Email{' '}
                            <span className="text-lilac/60">(optional)</span>
                        </label>
                        <input
                            id="enq-email"
                            type="email"
                            autoComplete="email"
                            value={form.data.email}
                            onChange={(e) =>
                                form.setData('email', e.target.value)
                            }
                            className={cn(field, 'mt-1.5')}
                        />
                        {form.errors.email && (
                            <p role="alert" className="mt-1 text-xs text-champagne">
                                {form.errors.email}
                            </p>
                        )}
                    </div>
                    <div>
                        <label
                            htmlFor="enq-treatment"
                            className="text-sm text-lilac"
                        >
                            Interested in
                        </label>
                        <select
                            id="enq-treatment"
                            value={form.data.treatment_id}
                            onChange={(e) =>
                                form.setData('treatment_id', e.target.value)
                            }
                            className={cn(
                                field,
                                'mt-1.5 cursor-pointer [&>option]:text-ink',
                            )}
                        >
                            <option value="">Not sure yet</option>
                            {treatments.map((t) => (
                                <option key={t.id} value={t.id}>
                                    {t.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="sm:col-span-2">
                        <label
                            htmlFor="enq-message"
                            className="text-sm text-lilac"
                        >
                            What would you like help with?
                        </label>
                        <textarea
                            id="enq-message"
                            rows={4}
                            value={form.data.message}
                            onChange={(e) =>
                                form.setData('message', e.target.value)
                            }
                            className={cn(field, 'mt-1.5 h-auto py-3')}
                        />
                    </div>
                    <label className="flex items-start gap-3 text-sm text-lilac sm:col-span-2">
                        <input
                            type="checkbox"
                            checked={form.data.privacy_consent}
                            onChange={(e) =>
                                form.setData(
                                    'privacy_consent',
                                    e.target.checked,
                                )
                            }
                            className="mt-1"
                        />
                        <span>
                            I agree that the clinic may contact me about my
                            enquiry and process my details under its privacy
                            notice.
                        </span>
                    </label>
                    {form.errors.privacy_consent && (
                        <p
                            role="alert"
                            className="-mt-2 text-xs text-champagne sm:col-span-2"
                        >
                            {form.errors.privacy_consent}
                        </p>
                    )}
                    <div className="sm:col-span-2">
                        <button
                            type="submit"
                            disabled={form.processing}
                            className="press h-12 bg-white px-8 font-medium text-plum hover:bg-gold hover:text-ink disabled:opacity-50"
                        >
                            {form.processing ? 'Sending...' : 'Send enquiry'}
                        </button>
                    </div>
                </form>
            </div>
        </section>
    );
}
