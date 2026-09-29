import { Head, Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import type { CSSProperties } from 'react';
import { useReveal } from '@/lib/site';
import { book, legal } from '@/routes';
import blogRoutes from '@/routes/blog';
import type { Post, PostDetail } from '@/types/site';

type Props = {
    post: PostDetail;
    related: Post[];
};

const stagger = (i: number) => ({ '--i': i }) as CSSProperties;

const day = new Intl.DateTimeFormat('en-PH', {
    timeZone: 'Asia/Manila',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
});

export default function BlogShow({ post, related }: Props) {
    useReveal();

    return (
        <>
            <Head title={post.title}>
                <meta name="description" content={post.excerpt} />
            </Head>

            <article>
                <header className="on-dark gutter bg-plum pt-16 pb-12 text-white lg:pt-24">
                    <p className="eyebrow">
                        {post.category}
                    </p>
                    <h1 className="mt-4 max-w-4xl text-4xl leading-[1.1] sm:text-6xl">
                        {post.title}
                    </h1>
                    <p className="numerals mt-6 text-sm text-lilac">
                        {day.format(new Date(post.published_at))} ·{' '}
                        {post.read_minutes} min read · {post.author_name}
                    </p>
                </header>

                {post.image && (
                    <img
                        src={post.image}
                        alt=""
                        className="aspect-[16/9] w-full object-cover"
                    />
                )}

                <div className="grid gap-12 gutter py-16 lg:grid-cols-12">
                    <div className="flex flex-col gap-6 lg:col-span-8">
                        <p className="text-lg">{post.excerpt}</p>
                        {post.paragraphs.map((paragraph) => (
                            <p
                                key={paragraph.slice(0, 24)}
                                className="max-w-2xl leading-relaxed text-muted-foreground"
                            >
                                {paragraph}
                            </p>
                        ))}

                        {post.takeaways.length > 0 && (
                            <aside className="mt-4 border-l-2 border-gold bg-mist p-6">
                                <h2 className="text-sm font-medium tracking-wide uppercase">
                                    In short
                                </h2>
                                <ul className="mt-4 flex flex-col gap-3 text-sm">
                                    {post.takeaways.map((point) => (
                                        <li
                                            key={point}
                                            className="flex items-start gap-3"
                                        >
                                            <span
                                                aria-hidden
                                                className="mt-2 size-1.5 shrink-0 bg-gold"
                                            />
                                            {point}
                                        </li>
                                    ))}
                                </ul>
                            </aside>
                        )}

                        <p className="border-t border-border pt-6 text-sm text-muted-foreground">
                            This note is general information for an adult
                            audience in the Philippines and is not medical
                            advice. Your own plan comes from your consultation
                            and the record we keep for you.{' '}
                            <Link
                                href={legal('terms').url}
                                className="text-rose-ink underline-offset-4 hover:underline"
                            >
                                Terms
                            </Link>
                            .
                        </p>
                    </div>

                    <aside className="lg:col-span-4">
                        <div className="border border-border p-6">
                            <h2 className="text-sm font-medium tracking-wide uppercase">
                                Written by
                            </h2>
                            <p className="mt-3 text-lg">{post.author_name}</p>
                            <p className="mt-2 text-sm text-muted-foreground">
                                Practitioner at Irish Aesthetics and
                                Beauty Lounge. A fictional profile in this
                                demonstration clinic.
                            </p>
                            <Link
                                href={book().url}
                                className="press mt-6 inline-flex w-full items-center justify-center gap-2 bg-plum px-5 py-3 text-sm font-medium text-white hover:bg-plum-deep"
                            >
                                Book a consultation
                                <ArrowRight className="size-4" />
                            </Link>
                        </div>
                    </aside>
                </div>
            </article>

            {related.length > 0 && (
                <section className="bg-mist gutter py-16 ">
                    <div className="flex items-baseline justify-between gap-4">
                        <h2 className="text-3xl">More from the journal</h2>
                        <Link
                            href={blogRoutes.index().url}
                            className="text-sm text-rose-ink underline-offset-4 hover:underline"
                        >
                            All notes
                        </Link>
                    </div>
                    <div className="mt-8 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                        {related.map((item, i) => (
                            <article
                                key={item.id}
                                data-reveal
                                style={stagger(i)}
                            >
                                {item.image && (
                                    <img
                                        src={item.image}
                                        alt=""
                                        loading="lazy"
                                        className="aspect-[4/3] w-full object-cover"
                                    />
                                )}
                                <p className="mt-4 eyebrow">
                                    {item.category}
                                </p>
                                <h3 className="mt-2 text-xl">
                                    <Link
                                        href={blogRoutes.show(item.slug).url}
                                        className="hover:text-rose-ink"
                                    >
                                        {item.title}
                                    </Link>
                                </h3>
                                <p className="mt-2 text-sm text-muted-foreground">
                                    {item.excerpt}
                                </p>
                            </article>
                        ))}
                    </div>
                </section>
            )}
        </>
    );
}
