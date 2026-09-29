import { Head, Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import type { CSSProperties } from 'react';
import BeforeAfter from '@/components/site/before-after';
import { useReveal } from '@/lib/site';
import { book } from '@/routes';
import treatmentRoutes from '@/routes/treatments';
import type { CompareCase } from '@/types/site';

const stagger = (i: number) => ({ '--i': i }) as CSSProperties;

/**
 * Comparison gallery. Every image is a licensed stock portrait with a
 * simulated "before" treatment, labelled as an illustration and never as a
 * result (plan.md §18, §83).
 */
export default function BeforeAfterPage({ cases }: { cases: CompareCase[] }) {
    useReveal();

    return (
        <>
            <Head title="Before and after">
                <meta
                    name="description"
                    content="Illustrative before and after comparisons for common treatments, and how real clinical photographs are handled with written consent."
                />
            </Head>

            <section className="on-dark bg-plum px-4 pt-16 pb-14 text-white sm:px-8 lg:px-12 lg:pt-24">
                <div className="mx-auto max-w-7xl">
                    <p className="eyebrow">
                        Before and after
                    </p>
                    <h1 className="mt-4 max-w-4xl text-5xl leading-[1.05] sm:text-7xl">
                        What changes, and{' '}
                        <em className="text-champagne">what to expect.</em>
                    </h1>
                    <p className="mt-6 max-w-2xl text-lg text-lilac">
                        Drag the handle to compare. The images here are licensed
                        stock photographs, not patient results, and your clinician
                        will show you relevant cases in person during your
                        consultation.
                    </p>
                </div>
            </section>

            <section className="px-4 py-14 sm:px-8 lg:px-12">
                <div className="grid gap-6 border-y border-border py-6 sm:grid-cols-3">
                    <p className="text-sm text-muted-foreground">
                        Every comparison is labelled illustrative.
                    </p>
                    <p className="text-sm text-muted-foreground">
                        Real photographs stay in your clinical record only.
                    </p>
                    <p className="text-sm text-muted-foreground">
                        Results vary with your skin, history and aftercare.
                    </p>
                </div>
            </section>

            <section className="px-4 pb-16 sm:px-8 lg:px-12">
                <div className="grid gap-12 lg:grid-cols-2">
                    {cases.map((item, i) => (
                        <article
                            key={item.id}
                            data-reveal
                            style={stagger(i % 2)}
                        >
                            {item.image && (
                                <BeforeAfter
                                    src={item.image}
                                    alt={`Portrait used to illustrate ${item.name.toLowerCase()} in this demo`}
                                />
                            )}
                            <h2 className="mt-4 text-2xl">
                                <Link
                                    href={treatmentRoutes.show(item.slug).url}
                                    className="hover:text-rose-ink"
                                >
                                    {item.name}
                                </Link>
                            </h2>
                            {item.category && (
                                <p className="mt-1 text-xs tracking-wide text-muted-foreground uppercase">
                                    {item.category}
                                </p>
                            )}
                            <p className="mt-3 text-sm leading-relaxed text-muted-foreground">
                                {item.summary}
                            </p>
                        </article>
                    ))}
                </div>
            </section>

            <section className="bg-mist px-4 py-20 sm:px-8 lg:px-12 lg:py-24">
                <div className="grid gap-12 lg:grid-cols-12">
                    <div className="lg:col-span-5">
                        <h2 data-reveal className="text-4xl sm:text-5xl">
                            How we handle photographs
                        </h2>
                    </div>
                    <ul className="divide-y divide-border border-y border-border lg:col-span-7">
                        {[
                            [
                                'Consent first',
                                'Clinical photographs are taken only with your written consent, and you can decline at any point without it affecting your care.',
                            ],
                            [
                                'Kept with your record',
                                'Images sit in your clinical record, not in a marketing folder, and access is logged.',
                            ],
                            [
                                'Never published without approval',
                                'Nothing is published on this website from a real patient without a separate, specific consent that you can withdraw later.',
                            ],
                            [
                                'Judged with your clinician',
                                'Progress is best reviewed side by side at your follow up, in the same light and at the same angle.',
                            ],
                        ].map(([title, body]) => (
                            <li key={title} className="py-6">
                                <h3 className="text-xl">{title}</h3>
                                <p className="mt-2 text-sm leading-relaxed text-muted-foreground">
                                    {body}
                                </p>
                            </li>
                        ))}
                    </ul>
                </div>
            </section>

            <section className="px-4 py-16 sm:px-8 lg:px-12">
                <div className="flex flex-wrap items-end justify-between gap-6 border-t border-border pt-10">
                    <h2 className="max-w-xl text-3xl sm:text-4xl">
                        Ask to see relevant cases at your consultation.
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
