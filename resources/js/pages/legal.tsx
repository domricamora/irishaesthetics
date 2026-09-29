import { Head, Link } from '@inertiajs/react';
import { useReveal } from '@/lib/site';
import { contact, legal } from '@/routes';
import type { LegalPage } from '@/types/site';

type Props = {
    page: LegalPage;
    others: { id: number; title: string; slug: string }[];
};

const reviewed = (date: string | null): string =>
    date
        ? new Intl.DateTimeFormat('en-PH', {
              timeZone: 'Asia/Manila',
              day: 'numeric',
              month: 'long',
              year: 'numeric',
          }).format(new Date(`${date}T12:00:00+08:00`))
        : '';

export default function LegalPage({ page, others }: Props) {
    useReveal();

    return (
        <>
            <Head title={page.title}>
                <meta name="description" content={page.summary ?? undefined} />
            </Head>

            <section className="on-dark bg-plum px-4 pt-16 pb-12 text-white sm:px-8 lg:px-12 lg:pt-24">
                <div className="mx-auto max-w-7xl">
                    <p className="eyebrow">
                        {page.summary ? 'The detail' : 'Clinic policy'}
                    </p>
                    <h1 className="mt-4 max-w-3xl text-5xl leading-[1.05] sm:text-6xl">
                        {page.title}
                    </h1>
                    {page.summary && (
                        <p className="mt-6 max-w-2xl text-lg text-lilac">
                            {page.summary}
                        </p>
                    )}
                    {page.reviewed_on && (
                        <p className="numerals mt-6 text-sm text-lilac">
                            Last reviewed {reviewed(page.reviewed_on)}
                        </p>
                    )}
                </div>
            </section>

            <section className="grid gap-12 px-4 py-16 sm:px-8 lg:grid-cols-12 lg:px-12 lg:py-20">
                <div className="lg:col-span-8">
                    {page.sections.map((section) => (
                        <section
                            key={section.heading}
                            data-reveal
                            className="mb-10 last:mb-0"
                        >
                            <h2 className="text-2xl sm:text-3xl">
                                {section.heading}
                            </h2>
                            <div className="mt-4 flex flex-col gap-4">
                                {section.paragraphs.map((paragraph) => (
                                    <p
                                        key={paragraph.slice(0, 24)}
                                        className="leading-relaxed text-muted-foreground"
                                    >
                                        {paragraph}
                                    </p>
                                ))}
                            </div>
                        </section>
                    ))}
                </div>

                <aside className="lg:col-span-4">
                    <div className="border border-border p-6">
                        <h2 className="text-sm font-medium tracking-wide uppercase">
                            Also on this site
                        </h2>
                        <ul className="mt-4 flex flex-col gap-3 text-sm">
                            {others.map((other) => (
                                <li key={other.id}>
                                    <Link
                                        href={legal(other.slug).url}
                                        className="text-rose-ink underline-offset-4 hover:underline"
                                    >
                                        {other.title}
                                    </Link>
                                </li>
                            ))}
                            <li>
                                <Link
                                    href={contact().url}
                                    className="text-rose-ink underline-offset-4 hover:underline"
                                >
                                    Contact the clinic
                                </Link>
                            </li>
                        </ul>
                    </div>
                    <p className="mt-6 text-xs text-muted-foreground">
                        This demonstration clinic is fictional. The copy is
                        written to show how a clinic can present its policies,
                        and it is not legal advice.
                    </p>
                </aside>
            </section>
        </>
    );
}
