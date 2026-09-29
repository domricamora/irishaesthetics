import { Head, Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import type { CSSProperties } from 'react';
import { formatDuration, formatPrice, useReveal } from '@/lib/site';
import { book } from '@/routes';
import treatmentRoutes from '@/routes/treatments';
import type { Category } from '@/types/site';

export default function TreatmentsIndex({
    categories,
}: {
    categories: Category[];
}) {
    useReveal();

    return (
        <>
            <Head title="Treatments and prices">
                <meta
                    name="description"
                    content="Facial treatments, injectables, body, hair, wellness and makeup services with prices, session times and what to expect."
                />
            </Head>

            <section className="on-dark bg-plum gutter pt-16 pb-14 text-white lg:pt-24">
                <div className="mx-auto max-w-7xl">
                    <h1 className="max-w-3xl text-5xl leading-[1.05] sm:text-7xl">
                        Treatments, <em className="text-champagne">priced plainly.</em>
                    </h1>
                    <p className="mt-6 max-w-xl text-lg text-lilac">
                        Every price below is the starting price for one session.
                        Your doctor confirms the plan and total before anything
                        begins.
                    </p>
                    <nav
                        aria-label="Categories"
                        className="mt-10 flex flex-wrap gap-2"
                    >
                        {categories.map((c) => (
                            <a
                                key={c.id}
                                href={`#${c.slug}`}
                                className="press btn btn-outline px-4 py-2"
                            >
                                {c.name}
                            </a>
                        ))}
                    </nav>
                </div>
            </section>

            {categories.map((c, ci) => (
                <section
                    key={c.id}
                    id={c.slug}
                    className={
                        ci % 2
                            ? 'scroll-mt-20 bg-mist'
                            : 'scroll-mt-20 bg-white'
                    }
                >
                    <div className="grid gap-10 gutter py-16 lg:grid-cols-12 lg:py-20">
                        <div className="lg:col-span-4">
                            <h2 data-reveal className="text-3xl sm:text-4xl">
                                {c.name}
                            </h2>
                            <p className="mt-4 max-w-sm text-muted-foreground">
                                {c.description}
                            </p>
                            {c.image && (
                                <img
                                    src={c.image}
                                    alt=""
                                    loading="lazy"
                                    className="mt-8 hidden aspect-[4/5] w-full max-w-sm object-cover lg:block"
                                />
                            )}
                        </div>
                        <ul className="divide-y divide-border border-y border-border lg:col-span-8">
                            {c.treatments?.map((t, i) => (
                                <li
                                    key={t.id}
                                    data-reveal
                                    style={{ '--i': i } as CSSProperties}
                                    className="group grid grid-cols-[5rem_1fr] gap-5 py-6 sm:grid-cols-[7rem_1fr_auto] sm:items-center"
                                >
                                    <Link
                                        href={treatmentRoutes.show(t.slug).url}
                                        className="block aspect-square overflow-hidden bg-lilac"
                                        tabIndex={-1}
                                    >
                                        {t.image && (
                                            <img
                                                src={t.image}
                                                alt=""
                                                loading="lazy"
                                                className="h-full w-full object-cover transition-transform duration-300 ease-out group-hover:scale-105"
                                            />
                                        )}
                                    </Link>
                                    <div>
                                        <h3 className="text-2xl">
                                            <Link
                                                href={
                                                    treatmentRoutes.show(t.slug)
                                                        .url
                                                }
                                                className="hover:text-rose-ink"
                                            >
                                                {t.name}
                                            </Link>
                                        </h3>
                                        <p className="mt-1 text-sm text-muted-foreground">
                                            {t.summary}
                                        </p>
                                        <p className="numerals mt-2 text-xs text-muted-foreground">
                                            {formatDuration(t.duration_minutes)}{' '}
                                            · {t.recommended_sessions}
                                        </p>
                                    </div>
                                    <div className="numerals col-span-2 flex items-center justify-between gap-6 sm:col-span-1 sm:flex-col sm:items-end">
                                        <p className="text-right">
                                            <span className="font-display text-3xl">
                                                {formatPrice(
                                                    t.promo_price ?? t.price,
                                                )}
                                            </span>
                                            {t.promo_price && (
                                                <s className="ml-2 text-sm text-muted-foreground">
                                                    {formatPrice(t.price)}
                                                </s>
                                            )}
                                        </p>
                                        <Link
                                            href={
                                                book({
                                                    query: { treatment: t.id },
                                                }).url
                                            }
                                            className="press inline-flex items-center gap-1.5 text-sm font-medium text-rose-ink hover:text-plum"
                                        >
                                            Book{' '}
                                            <ArrowRight className="size-4" />
                                        </Link>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    </div>
                </section>
            ))}
        </>
    );
}
