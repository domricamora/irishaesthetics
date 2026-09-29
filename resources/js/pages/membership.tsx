import { Head, Link } from '@inertiajs/react';
import { ArrowRight, Check } from 'lucide-react';
import type { CSSProperties } from 'react';
import { formatPrice, useReveal } from '@/lib/site';
import { cn } from '@/lib/utils';
import { book, contact, legal } from '@/routes';
import type { Branch, Faq, MembershipTier } from '@/types/site';

type Props = {
    tiers: MembershipTier[];
    branches: Branch[];
    faqs: Faq[];
};

const stagger = (i: number) => ({ '--i': i }) as CSSProperties;

const steps = [
    [
        'Choose a tier',
        'Pick the monthly allowance that matches how often you visit. You can move up or down at the end of any month.',
    ],
    [
        'Book as usual',
        'Members book the same way everyone does, with priority windows for popular times.',
    ],
    [
        'Allowance applies at the desk',
        'Your allowance and member pricing are applied to the visit before you pay.',
    ],
    [
        'Review every quarter',
        'Premium and Signature include a doctor review so the plan keeps matching your skin.',
    ],
];

export default function Membership({ tiers, branches, faqs }: Props) {
    useReveal();

    return (
        <>
            <Head title="Membership">
                <meta
                    name="description"
                    content="Monthly membership tiers with treatment allowances, member pricing, priority booking and doctor reviews at Irish clinics in Makati, BGC and Cebu."
                />
            </Head>

            <section className="on-dark bg-plum gutter pt-16 pb-14 text-white lg:pt-24">
                <div className="mx-auto max-w-7xl">
                    <p className="eyebrow">
                        Membership
                    </p>
                    <h1 className="mt-4 max-w-4xl text-5xl leading-[1.05] sm:text-7xl">
                        Skin that stays on track,{' '}
                        <em className="text-champagne">month by month.</em>
                    </h1>
                    <p className="mt-6 max-w-2xl text-lg text-lilac">
                        A membership turns treatment into a rhythm: a monthly
                        allowance, member pricing and priority appointment times.
                        Cancel any month, at any branch, without a fee.
                    </p>
                    <div className="mt-10 flex flex-wrap gap-3">
                        <Link
                            href={book().url}
                            className="press btn btn-solid"
                        >
                            Book a visit first <ArrowRight className="size-4" />
                        </Link>
                        <Link
                            href={contact().url}
                            className="press btn btn-outline"
                        >
                            Ask about joining
                        </Link>
                </div>
                <p className="mt-6 text-sm text-lilac">
                    GCash, Maya, bank transfer and cards accepted. No joining
                    fee, no lock in.
                </p>
                </div>
            </section>

            <section className="gutter py-16 lg:py-20">
                <div className="grid gap-px bg-border lg:grid-cols-3">
                    {tiers.map((tier, i) => (
                        <article
                            key={tier.id}
                            data-reveal
                            style={stagger(i)}
                            className={cn(
                                'flex flex-col p-8',
                                tier.is_featured
                                    ? 'on-dark bg-plum text-white'
                                    : 'bg-white',
                            )}
                        >
                            <div className="flex items-center justify-between gap-4">
                                <h2 className="text-3xl">{tier.name}</h2>
                                {tier.is_featured && (
                                    <span className="border border-gold px-2 py-1 text-xs text-champagne">
                                        Most taken
                                    </span>
                                )}
                            </div>
                            <p
                                className={cn(
                                    'mt-3 text-sm',
                                    tier.is_featured
                                        ? 'text-lilac'
                                        : 'text-muted-foreground',
                                )}
                            >
                                {tier.tagline}
                            </p>
                            <p className="numerals mt-8">
                                <span className="font-display text-5xl font-semibold">
                                    {formatPrice(tier.price_monthly)}
                                </span>
                                <span className="ml-2 text-sm opacity-70">
                                    per month
                                </span>
                            </p>
                            <ul className="mt-8 flex-1 space-y-4 text-sm">
                                {tier.benefits.map((benefit) => (
                                    <li
                                        key={benefit}
                                        className="flex items-start gap-3"
                                    >
                                        <Check
                                            className={cn(
                                                'mt-0.5 size-4 shrink-0',
                                                tier.is_featured
                                                    ? 'text-champagne'
                                                    : 'text-rose-ink',
                                            )}
                                            aria-hidden
                                        />
                                        <span>{benefit}</span>
                                    </li>
                                ))}
                            </ul>
                            {tier.note && (
                                <p
                                    className={cn(
                                        'mt-6 text-xs',
                                        tier.is_featured
                                            ? 'text-lilac'
                                            : 'text-muted-foreground',
                                    )}
                                >
                                    {tier.note}
                                </p>
                            )}
                            <Link
                                href={book().url}
                                className={cn(
                                    'press mt-8 inline-flex items-center justify-center gap-2 px-6 py-3 text-sm font-medium',
                                    tier.is_featured
                                        ? 'bg-gold text-ink hover:bg-white'
                                        : 'bg-plum text-white hover:bg-plum-deep',
                                )}
                            >
                                Join {tier.name}
                            </Link>
                        </article>
                    ))}
                </div>
                <p className="mt-6 text-xs text-muted-foreground">
                    Membership benefits apply to treatments at all{' '}
                    {branches.length} branches. Injected and clinical treatments
                    still begin with a consultation, and membership prices are
                    applied at the desk.
                </p>
            </section>

            <section className="bg-mist gutter py-20 lg:py-28">
                <div className="grid gap-12 lg:grid-cols-12">
                    <div className="lg:col-span-4">
                        <h2 data-reveal className="text-3xl sm:text-4xl">
                            How membership works
                        </h2>
                        <p className="mt-5 text-sm text-muted-foreground">
                            Nothing about your care changes when you join. The
                            consultation, the plan and the aftercare stay the
                            same.
                        </p>
                    </div>
                    <ol className="grid gap-px bg-border sm:grid-cols-2 lg:col-span-8">
                        {steps.map(([title, body], i) => (
                            <li
                                key={title}
                                data-reveal
                                style={stagger(i)}
                                className="bg-mist p-6"
                            >
                                <p className="numerals text-xs text-muted-foreground">
                                    Step {i + 1}
                                </p>
                                <h3 className="mt-2 text-xl">{title}</h3>
                                <p className="mt-2 text-sm text-muted-foreground">
                                    {body}
                                </p>
                            </li>
                        ))}
                    </ol>
                </div>
            </section>

            <section className="gutter py-20 lg:py-28">
                <div className="grid gap-12 lg:grid-cols-12">
                    <div className="lg:col-span-4">
                        <h2 data-reveal className="text-3xl sm:text-4xl">
                            Membership questions
                        </h2>
                        <p className="mt-5 text-sm text-muted-foreground">
                            Anything else, ask the front desk or send us a
                            message. The membership terms sit in our{' '}
                            <Link
                                href={legal('terms').url}
                                className="text-rose-ink underline-offset-4 hover:underline"
                            >
                                terms of service
                            </Link>{' '}
                            and{' '}
                            <Link
                                href={legal('privacy-policy').url}
                                className="text-rose-ink underline-offset-4 hover:underline"
                            >
                                privacy policy
                            </Link>
                            .
                        </p>
                    </div>
                    <ul className="divide-y divide-border border-y border-border lg:col-span-8">
                        {faqs.slice(0, 6).map((f) => (
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

            <section className="gutter pb-20 ">
                <div className="flex flex-wrap items-end justify-between gap-6 border-t border-border pt-10">
                    <h2 className="max-w-xl text-3xl sm:text-4xl">
                        Start with a consultation, then decide.
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
