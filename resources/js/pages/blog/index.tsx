import { Head, Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import type { CSSProperties } from 'react';
import { useReveal } from '@/lib/site';
import { cn } from '@/lib/utils';
import { book } from '@/routes';
import blogRoutes from '@/routes/blog';
import type { Post } from '@/types/site';

type Props = {
    posts: {
        data: Post[];
        links: { url: string | null; label: string; active: boolean }[];
        from: number | null;
        to: number | null;
        total: number;
    };
    filter: string | null;
    categories: Record<string, number>;
};

const stagger = (i: number) => ({ '--i': i }) as CSSProperties;

const day = new Intl.DateTimeFormat('en-PH', {
    timeZone: 'Asia/Manila',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
});

const pageLabel = (label: string): string =>
    label.replace('&laquo;', '‹').replace('&raquo;', '›');

export default function BlogIndex({ posts, filter, categories }: Props) {
    useReveal();
    const [first, ...rest] = posts.data;

    return (
        <>
            <Head title="Journal">
                <meta
                    name="description"
                    content="Plain-language clinic notes on skin care, aftercare, treatments and what to expect at a consultation."
                />
            </Head>

            <section className="on-dark bg-plum px-4 pt-16 pb-14 text-white sm:px-8 lg:px-12 lg:pt-24">
                <div className="mx-auto max-w-7xl">
                    <p className="eyebrow">
                        Journal
                    </p>
                    <h1 className="mt-4 max-w-4xl text-5xl leading-[1.05] sm:text-7xl">
                        Notes from the{' '}
                        <em className="text-champagne">treatment room.</em>
                    </h1>
                    <p className="mt-6 max-w-2xl text-lg text-lilac">
                        Guidance written by our practitioners: what a treatment
                        involves, how to care for your skin afterwards, and when a
                        result is worth judging. General information only, not a
                        diagnosis.
                    </p>
                </div>
            </section>

            <nav
                aria-label="Topics"
                className="flex gap-2 overflow-x-auto border-b border-border px-4 py-4 sm:px-8 lg:px-12"
            >
                <Link
                    href={blogRoutes.index().url}
                    aria-current={filter === null ? 'page' : undefined}
                    className={cn(
                        'press shrink-0 border px-4 py-2 text-sm',
                        filter === null
                            ? 'border-plum bg-plum text-white'
                            : 'border-border hover:border-violet',
                    )}
                >
                    All
                </Link>
                {Object.entries(categories).map(([category, count]) => (
                    <Link
                        key={category}
                        href={
                            blogRoutes.index({
                                query: { category },
                            }).url
                        }
                        aria-current={filter === category ? 'page' : undefined}
                        className={cn(
                            'press shrink-0 border px-4 py-2 text-sm',
                            filter === category
                                ? 'border-plum bg-plum text-white'
                                : 'border-border hover:border-violet',
                        )}
                    >
                        {category}{' '}
                        <span className="tabular-nums opacity-70">{count}</span>
                    </Link>
                ))}
            </nav>

            {first && (
                <section className="px-4 py-14 sm:px-8 lg:px-12">
                    <article
                        data-reveal
                        className="grid gap-8 border-b border-border pb-14 lg:grid-cols-2 lg:items-center"
                    >
                        {first.image && (
                            <Link
                                href={blogRoutes.show(first.slug).url}
                                tabIndex={-1}
                                className="block"
                            >
                                <img
                                    src={first.image}
                                    alt=""
                                    loading="lazy"
                                    className="aspect-[4/3] w-full object-cover"
                                />
                            </Link>
                        )}
                        <div>
                            <p className="eyebrow">
                                {first.category}
                            </p>
                            <h2 className="mt-3 text-3xl sm:text-4xl">
                                <Link
                                    href={blogRoutes.show(first.slug).url}
                                    className="hover:text-rose-ink"
                                >
                                    {first.title}
                                </Link>
                            </h2>
                            <p className="mt-4 max-w-lg text-muted-foreground">
                                {first.excerpt}
                            </p>
                            <p className="numerals mt-5 text-xs text-muted-foreground">
                                {day.format(new Date(first.published_at))} ·{' '}
                                {first.read_minutes} min read ·{' '}
                                {first.author_name}
                            </p>
                        </div>
                    </article>
                </section>
            )}

            <section className="px-4 pb-16 sm:px-8 lg:px-12">
                {rest.length ? (
                    <div className="grid gap-10 sm:grid-cols-2 lg:grid-cols-3">
                        {rest.map((post, i) => (
                            <article
                                key={post.id}
                                data-reveal
                                style={stagger(i % 3)}
                            >
                                {post.image && (
                                    <Link
                                        href={blogRoutes.show(post.slug).url}
                                        tabIndex={-1}
                                        className="block"
                                    >
                                        <img
                                            src={post.image}
                                            alt=""
                                            loading="lazy"
                                            className="aspect-[4/3] w-full object-cover"
                                        />
                                    </Link>
                                )}
                                <p className="mt-4 eyebrow">
                                    {post.category}
                                </p>
                                <h2 className="mt-2 text-2xl">
                                    <Link
                                        href={blogRoutes.show(post.slug).url}
                                        className="hover:text-rose-ink"
                                    >
                                        {post.title}
                                    </Link>
                                </h2>
                                <p className="mt-3 text-sm text-muted-foreground">
                                    {post.excerpt}
                                </p>
                                <p className="numerals mt-4 text-xs text-muted-foreground">
                                    {day.format(new Date(post.published_at))} ·{' '}
                                    {post.read_minutes} min
                                </p>
                            </article>
                        ))}
                    </div>
                ) : (
                    <p className="py-10 text-muted-foreground">
                        Nothing in this topic yet.
                    </p>
                )}
            </section>

            {posts.links.length > 3 && (
                <nav aria-label="Pages" className="px-4 pb-12 sm:px-8 lg:px-12">
                    <div className="flex flex-wrap items-center justify-between gap-4 border-t border-border pt-6 text-sm">
                        <p className="text-muted-foreground">
                            {posts.from} to {posts.to} of {posts.total}
                        </p>
                        <div className="flex">
                            {posts.links.map((link, i) =>
                                link.url ? (
                                    <Link
                                        key={i}
                                        href={link.url}
                                        preserveScroll
                                        aria-current={
                                            link.active ? 'page' : undefined
                                        }
                                        className={cn(
                                            '-ml-px border border-border px-3 py-1.5',
                                            link.active &&
                                                'relative border-plum bg-plum text-white',
                                        )}
                                    >
                                        {pageLabel(link.label)}
                                    </Link>
                                ) : (
                                    <span
                                        key={i}
                                        className="-ml-px border border-border px-3 py-1.5 text-muted-foreground"
                                    >
                                        {pageLabel(link.label)}
                                    </span>
                                ),
                            )}
                        </div>
                    </div>
                </nav>
            )}

            <section className="bg-mist px-4 py-16 sm:px-8 lg:px-12">
                <div className="flex flex-wrap items-end justify-between gap-6">
                    <h2 className="max-w-xl text-3xl sm:text-4xl">
                        Reading is free. The consultation is where it starts.
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
