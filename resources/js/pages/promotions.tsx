import { Head, Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { formatPrice, useReveal } from '@/lib/site';
import { book, legal, membership as membershipRoute } from '@/routes';
import treatmentRoutes from '@/routes/treatments';
import type { MembershipTier, Promotion } from '@/types/site';

type Props = {
    promotions: Promotion[];
    tiers: MembershipTier[];
};

/** "2026-12-31" to "31 December 2026", read in Manila time. */
const endsOn = (date: string): string =>
    new Intl.DateTimeFormat('en-PH', {
        timeZone: 'Asia/Manila',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(new Date(`${date}T12:00:00+08:00`));

export default function Promotions({ promotions, tiers }: Props) {
    useReveal();

    return (
        <>
            <Head title="Promotions and packages">
                <meta
                    name="description"
                    content="Current Irish offers: series pricing, packages and first visit credits, each with what is included and when it ends."
                />
            </Head>

            <section className="on-dark bg-plum px-4 pt-16 pb-14 text-white sm:px-8 lg:px-12 lg:pt-24">
                <div className="mx-auto max-w-7xl">
                    <p className="eyebrow">
                        Promotions
                    </p>
                    <h1 className="mt-4 max-w-4xl text-5xl leading-[1.05] sm:text-7xl">
                        Offers, <em className="text-champagne">written plainly.</em>
                    </h1>
                    <p className="mt-6 max-w-2xl text-lg text-lilac">
                        Each offer below says what is included, what it costs and
                        when it ends. No countdown timers, no conditions buried in
                        small print, and every treatment still begins with an
                        assessment.
                    </p>
                </div>
            </section>

            <section className="px-4 py-16 sm:px-8 lg:px-12 lg:py-20">
                <ul className="flex flex-col gap-16">
                    {promotions.map((promo, i) => (
                        <li
                            key={promo.id}
                            data-reveal
                            className="grid gap-10 border-t border-border pt-10 lg:grid-cols-12"
                        >
                            <div
                                className={
                                    i % 2
                                        ? 'lg:order-2 lg:col-span-5'
                                        : 'lg:col-span-5'
                                }
                            >
                                {promo.image && (
                                    <img
                                        src={promo.image}
                                        alt=""
                                        loading="lazy"
                                        className="aspect-[4/3] w-full object-cover"
                                    />
                                )}
                            </div>
                            <div
                                className={
                                    i % 2
                                        ? 'lg:order-1 lg:col-span-7'
                                        : 'lg:col-span-7'
                                }
                            >
                                {promo.badge && (
                                    <span className="inline-block border border-gold px-2.5 py-1 text-xs text-rose-ink">
                                        {promo.badge}
                                    </span>
                                )}
                                <h2 className="mt-4 text-3xl sm:text-4xl">
                                    {promo.title}
                                </h2>
                                <p className="mt-3 max-w-xl text-lg">
                                    {promo.summary}
                                </p>
                                <p className="mt-4 max-w-xl text-sm leading-relaxed text-muted-foreground">
                                    {promo.description}
                                </p>
                                {promo.details && promo.details.length > 0 && (
                                    <ul className="mt-6 max-w-xl divide-y divide-border border-y border-border text-sm">
                                        {promo.details.map((detail) => (
                                            <li
                                                key={detail}
                                                className="py-3 text-muted-foreground"
                                            >
                                                {detail}
                                            </li>
                                        ))}
                                    </ul>
                                )}
                                <div className="mt-6 flex flex-wrap items-center gap-4">
                                    <Link
                                        href={book().url}
                                        className="press inline-flex items-center gap-2 bg-plum px-6 py-3 text-sm font-medium text-white hover:bg-plum-deep"
                                    >
                                        Book this
                                        <ArrowRight className="size-4" />
                                    </Link>
                                    {promo.treatment && (
                                        <span className="text-sm text-muted-foreground">
                                            Applies to{' '}
                                            <Link
                                                href={
                                                    treatmentRoutes.show(
                                                        promo.treatment.slug,
                                                    ).url
                                                }
                                                className="text-rose-ink underline-offset-4 hover:underline"
                                            >
                                                {promo.treatment.name}
                                            </Link>
                                            {promo.treatment.promo_price
                                                ? `, ${formatPrice(promo.treatment.promo_price)} per session`
                                                : ''}
                                        </span>
                                    )}
                                    {promo.ends_on && (
                                        <span className="numerals text-sm text-muted-foreground">
                                            Ends {endsOn(promo.ends_on)}
                                        </span>
                                    )}
                                </div>
                            </div>
                        </li>
                    ))}
                </ul>
            </section>

            {tiers.length > 0 && (
                <section className="on-dark bg-plum-deep px-4 py-20 text-white sm:px-8 lg:px-12 lg:py-24">
                    <div className="grid gap-12 lg:grid-cols-12">
                        <div className="lg:col-span-5">
                            <h2 data-reveal className="text-4xl sm:text-5xl">
                                Better than a promo: a plan
                            </h2>
                            <p className="mt-5 max-w-md text-sm text-lilac">
                                Offers suit a one off. If you plan to come back
                                a few times this year, membership usually costs
                                less across the year and keeps your records in
                                one place.
                            </p>
                            <Link
                                href={membershipRoute().url}
                                className="press btn btn-outline mt-8"
                            >
                                Compare memberships{' '}
                                <ArrowRight className="size-4" />
                            </Link>
                        </div>
                        <dl className="grid gap-px bg-white/15 sm:grid-cols-3 lg:col-span-7">
                            {tiers.map((tier) => (
                                <div key={tier.id} className="bg-plum-deep p-6">
                                    <dt className="text-lg">{tier.name}</dt>
                                    <dd className="numerals mt-3 font-display text-3xl">
                                        {formatPrice(tier.price_monthly)}
                                        <span className="ml-1 text-xs opacity-70">
                                            / month
                                        </span>
                                    </dd>
                                    <dd className="mt-3 text-xs text-lilac">
                                        {tier.tagline}
                                    </dd>
                                </div>
                            ))}
                        </dl>
                    </div>
                </section>
            )}

            <section className="px-4 py-16 sm:px-8 lg:px-12">
                <div className="flex flex-wrap items-end justify-between gap-6 border-t border-border pt-8">
                    <p className="max-w-2xl text-xs text-muted-foreground">
                        Offers cannot be combined with each other or with member
                        pricing unless the offer says so, and they apply to
                        treatment sessions booked and attended within the stated
                        dates. The general rules are in our{' '}
                        <Link
                            href={legal('terms').url}
                            className="text-rose-ink underline-offset-4 hover:underline"
                        >
                            terms of service
                        </Link>
                        .
                    </p>
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
