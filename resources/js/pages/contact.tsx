import { Head, Link, useForm, usePage } from '@inertiajs/react';
import type { CSSProperties, FormEvent } from 'react';
import { useReveal } from '@/lib/site';
import { book, legal } from '@/routes';
import leads from '@/routes/leads';
import type { Branch } from '@/types/site';

type Props = {
    branches: Branch[];
    treatments: { id: number; name: string }[];
};

const stagger = (i: number) => ({ '--i': i }) as CSSProperties;

const input =
    'h-12 w-full border border-input bg-background px-4 text-sm outline-none transition-colors duration-150 focus:border-violet aria-invalid:border-destructive';

export default function Contact({ branches, treatments }: Props) {
    const { clinic } = usePage().props;
    useReveal();

    const form = useForm({
        form: 'contact',
        first_name: '',
        phone: '',
        email: '',
        treatment_id: '',
        message: '',
        privacy_consent: false,
    });
    const { data, setData, errors } = form;

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(leads.store().url, { preserveScroll: true });
    };

    return (
        <>
            <Head title="Contact">
                <meta
                    name="description"
                    content={`Call, message or visit ${clinic.name} in Makati, BGC or Cebu. Branch addresses, hours and an enquiry form that reaches the clinic team.`}
                />
            </Head>

            <section className="on-dark bg-plum px-4 pt-16 pb-14 text-white sm:px-8 lg:px-12 lg:pt-24">
                <div className="mx-auto max-w-7xl">
                    <p className="eyebrow">
                        Contact
                    </p>
                    <h1 className="mt-4 max-w-4xl text-5xl leading-[1.05] sm:text-7xl">
                        Talk to us before you{' '}
                        <em className="text-champagne">decide anything.</em>
                    </h1>
                    <p className="mt-6 max-w-2xl text-lg text-lilac">
                        Call the branch, send a message, or book a consultation
                        straight from the calendar. A clinician answers clinical
                        questions; the front desk handles everything else.
                    </p>
                    <div className="mt-10 flex flex-wrap items-center gap-6 text-sm">
                        <a
                            href={`tel:${clinic.contact.phone.replace(/\s/g, '')}`}
                            className="text-white hover:text-champagne"
                        >
                            {clinic.contact.phone}
                        </a>
                        <a
                            href={`mailto:${clinic.contact.email}`}
                            className="text-white hover:text-champagne"
                        >
                            {clinic.contact.email}
                        </a>
                </div>
                </div>
            </section>

            <section className="grid gap-12 px-4 py-16 sm:px-8 lg:grid-cols-12 lg:px-12 lg:py-20">
                <div className="lg:col-span-5">
                    <h2 data-reveal className="text-3xl sm:text-4xl">
                        Send us a message
                    </h2>
                    <p className="mt-4 text-sm text-muted-foreground">
                        We reply within one business day. Your message reaches
                        the same dashboard the clinic team works from, with the
                        page you came from and any campaign details attached.
                    </p>
                    <form
                        onSubmit={submit}
                        className="mt-8 flex flex-col gap-5"
                    >
                        <div className="grid gap-5 sm:grid-cols-2">
                            <div className="flex flex-col gap-1.5">
                                <label
                                    htmlFor="name"
                                    className="text-sm font-medium"
                                >
                                    Your name
                                </label>
                                <input
                                    id="name"
                                    required
                                    autoComplete="name"
                                    value={data.first_name}
                                    onChange={(e) =>
                                        setData('first_name', e.target.value)
                                    }
                                    className={input}
                                />
                                {errors.first_name && (
                                    <p
                                        role="alert"
                                        className="text-xs text-destructive"
                                    >
                                        {errors.first_name}
                                    </p>
                                )}
                            </div>
                            <div className="flex flex-col gap-1.5">
                                <label
                                    htmlFor="phone"
                                    className="text-sm font-medium"
                                >
                                    Mobile number
                                </label>
                                <input
                                    id="phone"
                                    type="tel"
                                    inputMode="tel"
                                    autoComplete="tel"
                                    value={data.phone}
                                    onChange={(e) =>
                                        setData('phone', e.target.value)
                                    }
                                    className={input}
                                />
                            </div>
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <label
                                htmlFor="email"
                                className="text-sm font-medium"
                            >
                                Email
                            </label>
                            <input
                                id="email"
                                type="email"
                                autoComplete="email"
                                value={data.email}
                                onChange={(e) =>
                                    setData('email', e.target.value)
                                }
                                className={input}
                            />
                            {errors.email && (
                                <p
                                    role="alert"
                                    className="text-xs text-destructive"
                                >
                                    {errors.email}
                                </p>
                            )}
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <label
                                htmlFor="interest"
                                className="text-sm font-medium"
                            >
                                What is this about?{' '}
                                <span className="font-normal text-muted-foreground">
                                    (optional)
                                </span>
                            </label>
                            <select
                                id="interest"
                                value={data.treatment_id}
                                onChange={(e) =>
                                    setData('treatment_id', e.target.value)
                                }
                                className={input}
                            >
                                <option value="">A question in general</option>
                                {treatments.map((t) => (
                                    <option key={t.id} value={t.id}>
                                        {t.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <label
                                htmlFor="message"
                                className="text-sm font-medium"
                            >
                                Message
                            </label>
                            <textarea
                                id="message"
                                rows={5}
                                value={data.message}
                                onChange={(e) =>
                                    setData('message', e.target.value)
                                }
                                className={`${input} h-auto py-3`}
                            />
                            {errors.message && (
                                <p
                                    role="alert"
                                    className="text-xs text-destructive"
                                >
                                    {errors.message}
                                </p>
                            )}
                        </div>
                        <label className="flex items-start gap-3 text-sm">
                            <input
                                type="checkbox"
                                required
                                checked={data.privacy_consent}
                                onChange={(e) =>
                                    setData('privacy_consent', e.target.checked)
                                }
                                className="mt-1 accent-plum"
                            />
                            <span>
                                I agree to the{' '}
                                <Link
                                    href={legal('privacy-policy').url}
                                    className="text-rose-ink underline-offset-4 hover:underline"
                                >
                                    privacy policy
                                </Link>{' '}
                                and the clinic may contact me about this
                                message.
                            </span>
                        </label>
                        <button
                            type="submit"
                            disabled={form.processing}
                            className="press w-full bg-plum px-6 py-3 text-sm font-medium text-white hover:bg-plum-deep disabled:opacity-50 sm:w-auto"
                        >
                            {form.processing ? 'Sending...' : 'Send message'}
                        </button>
                    </form>
                </div>

                <div className="lg:col-span-7">
                    <h2 data-reveal className="text-3xl sm:text-4xl">
                        Where to find us
                    </h2>
                    <div className="mt-8 flex flex-col gap-10">
                        {branches.map((branch, i) => (
                            <article
                                key={branch.id}
                                data-reveal
                                style={stagger(i)}
                                className="border-t border-border pt-8"
                            >
                                <div className="grid gap-6 sm:grid-cols-2">
                                    {branch.image && (
                                        <img
                                            src={branch.image}
                                            alt={branch.name}
                                            loading="lazy"
                                            className="aspect-[4/3] w-full object-cover"
                                        />
                                    )}
                                    <div>
                                        <h3 className="text-2xl">
                                            {branch.name}
                                        </h3>
                                        <p className="mt-2 text-sm text-muted-foreground">
                                            {[branch.address, branch.city]
                                                .filter(Boolean)
                                                .join(', ')}
                                        </p>
                                        {branch.phone && (
                                            <p className="mt-2 text-sm">
                                                <a
                                                    href={`tel:${branch.phone.replace(/\s/g, '')}`}
                                                    className="text-rose-ink underline-offset-4 hover:underline"
                                                >
                                                    {branch.phone}
                                                </a>
                                            </p>
                                        )}
                                        {branch.email && (
                                            <p className="mt-1 text-sm">
                                                <a
                                                    href={`mailto:${branch.email}`}
                                                    className="text-rose-ink underline-offset-4 hover:underline"
                                                >
                                                    {branch.email}
                                                </a>
                                            </p>
                                        )}
                                        {branch.hours && (
                                            <dl className="mt-4 divide-y divide-border border-y border-border text-sm">
                                                {Object.entries(
                                                    branch.hours,
                                                ).map(([day, hours]) => (
                                                    <div
                                                        key={day}
                                                        className="flex justify-between gap-4 py-2"
                                                    >
                                                        <dt className="text-muted-foreground">
                                                            {day}
                                                        </dt>
                                                        <dd>{hours}</dd>
                                                    </div>
                                                ))}
                                            </dl>
                                        )}
                                        <div className="mt-5 flex flex-wrap gap-4 text-sm">
                                            <Link
                                                href={`${book().url}?branch=${branch.slug}`}
                                                className="press inline-flex items-center gap-2 bg-plum px-5 py-2.5 text-sm font-medium text-white hover:bg-plum-deep"
                                            >
                                                Book at {branch.name}
                                            </Link>
                                            {branch.map_url && (
                                                <a
                                                    href={branch.map_url}
                                                    target="_blank"
                                                    rel="noreferrer"
                                                    className="self-center text-rose-ink underline-offset-4 hover:underline"
                                                >
                                                    Directions
                                                </a>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </article>
                        ))}
                    </div>
                </div>
            </section>
        </>
    );
}
