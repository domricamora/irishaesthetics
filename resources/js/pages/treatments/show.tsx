import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, ArrowRight } from 'lucide-react';
import { formatDuration, formatPrice } from '@/lib/site';
import { book } from '@/routes';
import treatmentRoutes from '@/routes/treatments';
import type { Specialist, Treatment } from '@/types/site';

type Props = {
    treatment: Treatment;
    related: Treatment[];
    specialists: Specialist[];
};

export default function TreatmentShow({
    treatment: t,
    related,
    specialists,
}: Props) {
    const price = t.promo_price ?? t.price;

    return (
        <>
            <Head title={`${t.name}, from ${formatPrice(price)}`}>
                <meta
                    name="description"
                    content={`${t.summary} ${formatDuration(t.duration_minutes)} session from ${formatPrice(price)}. Book online.`}
                />
                <script type="application/ld+json">
                    {JSON.stringify({
                        '@context': 'https://schema.org',
                        '@type': 'Service',
                        name: t.name,
                        description: t.summary,
                        category: t.category?.name,
                        offers: {
                            '@type': 'Offer',
                            price,
                            priceCurrency: 'PHP',
                        },
                    })}
                </script>
            </Head>

            <section className="grid lg:min-h-[calc(100dvh-5rem)] lg:grid-cols-2">
                <div className="relative bg-lilac">
                    {t.image && (
                        <img
                            src={t.image}
                            alt=""
                            className="h-80 w-full object-cover sm:h-[28rem] lg:absolute lg:inset-0 lg:h-full"
                            fetchPriority="high"
                        />
                    )}
                </div>
                <div className="px-4 py-12 sm:px-8 lg:px-16 lg:py-20">
                    <Link
                        href={treatmentRoutes.index().url}
                        className="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-rose-ink"
                    >
                        <ArrowLeft className="size-4" /> All treatments
                    </Link>
                    <p className="mt-8 text-sm text-rose-ink">
                        {t.category?.name}
                    </p>
                    <h1 className="mt-2 text-5xl leading-[1.05] sm:text-6xl">
                        {t.name}
                    </h1>
                    <p className="mt-5 max-w-lg text-lg text-muted-foreground">
                        {t.summary}
                    </p>

                    <dl className="numerals mt-10 grid grid-cols-3 border-y border-border">
                        <div className="py-5 pr-4">
                            <dt className="text-xs text-muted-foreground">
                                From
                            </dt>
                            <dd className="mt-1 font-display text-3xl">
                                {formatPrice(price)}
                            </dd>
                            {t.promo_price && (
                                <dd className="text-xs text-muted-foreground">
                                    <s>{formatPrice(t.price)}</s> this month
                                </dd>
                            )}
                        </div>
                        <div className="border-l border-border py-5 pl-4">
                            <dt className="text-xs text-muted-foreground">
                                Session
                            </dt>
                            <dd className="mt-1 font-display text-3xl">
                                {formatDuration(t.duration_minutes)}
                            </dd>
                        </div>
                        <div className="border-l border-border py-5 pl-4">
                            <dt className="text-xs text-muted-foreground">
                                Usually
                            </dt>
                            <dd className="mt-2 text-sm">
                                {t.recommended_sessions}
                            </dd>
                        </div>
                    </dl>

                    <Link
                        href={book({ query: { treatment: t.id } }).url}
                        className="press mt-8 inline-flex h-13 items-center gap-2 bg-plum px-7 font-medium text-white hover:bg-violet"
                    >
                        Book {t.name} <ArrowRight className="size-4" />
                    </Link>

                    <p className="mt-10 max-w-xl leading-relaxed">
                        {t.description}
                    </p>

                    <div className="mt-10 divide-y divide-border border-y border-border">
                        {[
                            ['Before your visit', t.preparation],
                            ['Aftercare', t.aftercare],
                            ['Is it right for me?', t.contraindications],
                        ]
                            .filter(([, text]) => text)
                            .map(([title, text]) => (
                                <div key={title} className="py-6">
                                    <h2 className="font-sans text-sm font-semibold">
                                        {title}
                                    </h2>
                                    <p className="mt-2 max-w-xl text-sm leading-relaxed text-muted-foreground">
                                        {text}
                                    </p>
                                </div>
                            ))}
                    </div>

                    <div className="mt-10">
                        <h2 className="font-sans text-sm font-semibold">
                            Your physicians
                        </h2>
                        <ul className="mt-4 flex flex-wrap gap-5">
                            {specialists.map((s) => (
                                <li
                                    key={s.id}
                                    className="flex items-center gap-3"
                                >
                                    {s.photo && (
                                        <img
                                            src={s.photo}
                                            alt=""
                                            className="size-12 rounded-full object-cover object-top"
                                        />
                                    )}
                                    <span className="text-sm">
                                        <span className="block font-medium">
                                            {s.name}
                                        </span>
                                        <span className="text-muted-foreground">
                                            {s.branches?.join(', ')}
                                        </span>
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </div>
                </div>
            </section>

            {related.length > 0 && (
                <section className="bg-mist px-4 py-16 sm:px-8 lg:px-12 lg:py-20">
                    <h2 className="text-4xl">Often paired with</h2>
                    <div className="mt-10 grid gap-8 sm:grid-cols-3">
                        {related.map((r) => (
                            <Link
                                key={r.id}
                                href={treatmentRoutes.show(r.slug).url}
                                className="group block"
                            >
                                <div className="aspect-[4/3] overflow-hidden bg-lilac">
                                    {r.image && (
                                        <img
                                            src={r.image}
                                            alt=""
                                            loading="lazy"
                                            className="h-full w-full object-cover transition-transform duration-300 ease-out group-hover:scale-[1.03]"
                                        />
                                    )}
                                </div>
                                <div className="numerals mt-4 flex items-baseline justify-between gap-4">
                                    <h3 className="text-2xl group-hover:text-rose-ink">
                                        {r.name}
                                    </h3>
                                    <span className="font-display text-xl">
                                        {formatPrice(r.promo_price ?? r.price)}
                                    </span>
                                </div>
                            </Link>
                        ))}
                    </div>
                </section>
            )}
        </>
    );
}
