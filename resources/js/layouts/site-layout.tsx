import { Link, useForm, usePage } from '@inertiajs/react';
import { Menu, X } from 'lucide-react';
import type { FormEvent, ReactNode } from 'react';
import { useEffect, useState } from 'react';
import { SOCIAL_LABELS, SocialIcon } from '@/components/site/social-icon';
import Wordmark from '@/components/site/wordmark';
import { cn } from '@/lib/utils';
import { ChatWidget } from '@/components/site/chat-widget';
import {
    about,
    beforeAfter,
    book,
    contact,
    home,
    legal,
    login,
    membership,
    promotions,
} from '@/routes';
import blog from '@/routes/blog';
import leads from '@/routes/leads';
import treatments from '@/routes/treatments';

const nav = [
    { label: 'Treatments', href: treatments.index().url },
    { label: 'About', href: about().url },
    { label: 'Membership', href: membership().url },
    { label: 'Promotions', href: promotions().url },
    { label: 'Journal', href: blog.index().url },
    { label: 'Contact', href: contact().url },
];

export default function SiteLayout({ children }: { children: ReactNode }) {
    const { clinic, flash } = usePage<{ flash?: { success?: string | null } }>()
        .props;
    const [open, setOpen] = useState(false);

    useEffect(() => {
        document.body.style.overflow = open ? 'hidden' : '';
    }, [open]);

    return (
        <div className="site theme-light min-h-dvh bg-background text-foreground">
            <LocalBusinessSchema />
            <a
                href="#main"
                className="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:bg-white focus:px-4 focus:py-2"
            >
                Skip to content
            </a>

            {/*
                The announcement bar the reference layout opens with. It sits
                above the sticky header rather than inside it, so scrolling
                past the first screen takes it away and gives the header the
                full width to itself.
            */}
            <p
                className={cn(
                    'bg-plum px-4 py-2.5 text-center text-[0.6875rem] tracking-[0.18em] text-champagne uppercase',
                    // Taken away while the menu is open so the header sits
                    // directly under the top of the viewport. The mobile panel
                    // starts at top-20, which is the header's height; with the
                    // bar still in the flow at the top of the page the header
                    // is pushed down by the bar and the panel would overlap it.
                    open && 'hidden',
                )}
            >
                New clients welcome · Natural results · Medical-led care
            </p>

            <header className="sticky top-0 z-40 border-b border-border bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/80">
                <div className="mx-auto flex h-20 max-w-7xl items-center justify-between gap-6 px-4 sm:px-8 lg:px-12">
                    <Link
                        href={home().url}
                        className="press min-w-0"
                        aria-label={`${clinic.name} home`}
                    >
                        {/* The script name over the tracked caps line is the
                            header's only vertical cost, and it is sized so the
                            lockup clears the row rather than growing it. */}
                        <Wordmark />
                    </Link>

                    {/*
                        The nav starts at xl rather than lg. At lg the row is
                        1024px wide and the lockup now takes about 280 of them,
                        which leaves the six links, the login and the booking
                        button with nowhere to go.
                    */}
                    <nav
                        aria-label="Main"
                        className="hidden items-center gap-8 text-sm text-foreground/70 xl:flex"
                    >
                        {nav.map((item) => (
                            <Link
                                key={item.label}
                                href={item.href}
                                className="transition-colors duration-150 hover:text-rose-ink"
                            >
                                {item.label}
                            </Link>
                        ))}
                    </nav>

                    <div className="flex items-center gap-3">
                        <Link
                            href={login().url}
                            className="hidden text-sm text-foreground/70 hover:text-rose-ink sm:inline"
                        >
                            Patient login
                        </Link>
                        <Link
                            href={book().url}
                            className="press btn btn-solid hidden sm:inline-flex"
                        >
                            Book a consultation
                        </Link>
                        <button
                            type="button"
                            onClick={() => setOpen(!open)}
                            className="press -mr-2 p-2 xl:hidden"
                            aria-expanded={open}
                            aria-controls="mobile-nav"
                            aria-label={open ? 'Close menu' : 'Open menu'}
                        >
                            {open ? (
                                <X className="size-6" />
                            ) : (
                                <Menu className="size-6" />
                            )}
                        </button>
                    </div>
                </div>
            </header>

            {/*
                Deliberately a sibling of the header rather than a child of it.

                The header carries backdrop-blur, and backdrop-filter makes an
                element a containing block for its position:fixed descendants.
                With the panel inside it, `fixed top-20 bottom-0` was measured
                against the header's own 80px box instead of the viewport, which
                collapsed the panel's background to a ~120px strip: the links
                showed, but the page behind them did too. Outside the header
                there is no filtered ancestor, so fixed means fixed.

                z-30 keeps it below the header's z-40, so the mark and the
                close button stay visible and clickable above the panel.
            */}
            <nav
                id="mobile-nav"
                aria-label="Mobile"
                className={cn(
                    'fixed inset-x-0 top-20 bottom-0 z-30 overflow-y-auto bg-background px-4 pt-6 pb-24 sm:px-8 xl:hidden',
                    open ? 'block' : 'hidden',
                )}
            >
                <ul className="divide-y divide-border border-y border-border">
                    {[
                        ...nav,
                        { label: 'Patient login', href: login().url },
                    ].map((item) => (
                        <li key={item.label}>
                            <Link
                                href={item.href}
                                onClick={() => setOpen(false)}
                                className="flex py-4 font-display text-2xl"
                            >
                                {item.label}
                            </Link>
                        </li>
                    ))}
                </ul>
            </nav>

            <main id="main">{children}</main>

            <SiteFooter />
            <ChatWidget />

            <div className="fixed inset-x-0 bottom-0 z-30 border-t border-plum/10 bg-white/95 p-3 backdrop-blur sm:hidden">
                <Link
                    href={book().url}
                    className="press flex h-12 items-center justify-center bg-plum text-sm font-medium text-white"
                >
                    Book now
                </Link>
            </div>

            {flash?.success && (
                <div
                    role="status"
                    className="fixed right-4 bottom-20 z-50 max-w-sm bg-ink px-5 py-4 text-sm text-white shadow-lg sm:bottom-6"
                >
                    {flash.success}
                </div>
            )}
        </div>
    );
}

function LocalBusinessSchema() {
    const { clinic } = usePage().props;
    const schema = {
        '@context': 'https://schema.org',
        '@type': 'MedicalBusiness',
        name: clinic.name,
        description: `${clinic.tagline} Physician-led aesthetic and wellness clinic.`,
        url: home().url,
        telephone: clinic.contact.phone,
        email: clinic.contact.email,
        priceRange: '$$',
        currenciesAccepted: 'PHP',
        paymentAccepted: 'Cash, GCash, Maya, bank transfer, credit card',
        address: {
            '@type': 'PostalAddress',
            streetAddress: clinic.contact.address,
            addressCountry: 'PH',
        },
        openingHoursSpecification: [
            {
                '@type': 'OpeningHoursSpecification',
                dayOfWeek: [
                    'Monday',
                    'Tuesday',
                    'Wednesday',
                    'Thursday',
                    'Friday',
                ],
                opens: '10:00',
                closes: '20:00',
            },
            {
                '@type': 'OpeningHoursSpecification',
                dayOfWeek: ['Saturday'],
                opens: '10:00',
                closes: '18:00',
            },
        ],
        medicalSpecialty: ['Dermatology', 'Cosmetic surgery'],
        availableService: [
            { '@type': 'MedicalProcedure', name: 'Facial treatments' },
            { '@type': 'MedicalProcedure', name: 'Injectables' },
            { '@type': 'MedicalProcedure', name: 'Body contouring' },
            { '@type': 'MedicalProcedure', name: 'Hair and scalp care' },
        ],
    };

    return (
        <script
            type="application/ld+json"
            // Structured data for search engines; the payload is built from config.
            dangerouslySetInnerHTML={{ __html: JSON.stringify(schema) }}
        />
    );
}

function SiteFooter() {
    const { clinic, socials } = usePage().props;
    const form = useForm({
        form: 'newsletter',
        email: '',
        privacy_consent: false,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(leads.store().url, {
            preserveScroll: true,
            onSuccess: () => form.reset('email'),
        });
    };

    return (
        <footer className="border-t border-border bg-soft-rose pb-24 sm:pb-0">
            <div className="mx-auto max-w-7xl px-4 py-16 sm:px-8 lg:px-12 lg:py-20">
                <div className="grid gap-10 lg:grid-cols-12 lg:gap-8">
                    <div className="lg:col-span-4">
                        {/*
                            The footer is the one place the lockup is allowed to
                            be the largest thing on the page: the mark is 80px
                            here, so the script is scaled to match rather than
                            repeating the header's 27px.
                        */}
                        <Wordmark
                            markClassName="size-20"
                            nameClassName="text-[2.75rem]"
                        />
                        <p className="mt-5 max-w-sm text-sm leading-relaxed text-foreground/70">
                            {clinic.tagline} Physician-led aesthetic and
                            wellness care in Makati, BGC and Cebu.
                        </p>
                        <p className="mt-6 text-sm">
                            <a
                                href={`tel:${clinic.contact.phone.replace(/\s/g, '')}`}
                                className="text-foreground hover:text-rose-ink"
                            >
                                {clinic.contact.phone}
                            </a>
                            <br />
                            <a
                                href={`mailto:${clinic.contact.email}`}
                                className="text-foreground hover:text-rose-ink"
                            >
                                {clinic.contact.email}
                            </a>
                        </p>
                    </div>

                    <div className="grid grid-cols-2 gap-8 text-sm lg:col-span-4">
                        <div>
                            <h2 className="eyebrow">Explore</h2>
                            <ul className="mt-4 space-y-3">
                                {[
                                    ['Treatments', treatments.index().url],
                                    ['About', about().url],
                                    ['Membership', membership().url],
                                    ['Promotions', promotions().url],
                                    ['Before and after', beforeAfter().url],
                                    ['Journal', blog.index().url],
                                    ['Contact', contact().url],
                                ].map(([label, href]) => (
                                    <li key={label}>
                                        <Link
                                            href={href}
                                            className="hover:text-rose-ink"
                                        >
                                            {label}
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        </div>
                        <div>
                            <h2 className="eyebrow">Clinics</h2>
                            <ul className="mt-4 space-y-3">
                                <li>Makati</li>
                                <li>BGC, Taguig</li>
                                <li>Cebu Business Park</li>
                                {/*
                                    Points at the contact page rather than a
                                    home-page anchor: the locations section this
                                    used to jump to is gone, and the contact
                                    page is where the branch addresses, hours
                                    and directions actually live.
                                */}
                                <li>
                                    <Link
                                        href={contact().url}
                                        className="hover:text-rose-ink"
                                    >
                                        Hours and directions
                                    </Link>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <form onSubmit={submit} className="lg:col-span-4">
                        <h2 className="eyebrow">Skin notes, once a month</h2>
                        <p className="mt-3 text-sm text-foreground/70">
                            Seasonal care tips and member offers. No spam,
                            unsubscribe anytime.
                        </p>
                        <label htmlFor="newsletter-email" className="sr-only">
                            Email address
                        </label>
                        <div className="mt-5 flex">
                            <input
                                id="newsletter-email"
                                type="email"
                                required
                                autoComplete="email"
                                value={form.data.email}
                                onChange={(e) =>
                                    form.setData('email', e.target.value)
                                }
                                placeholder="you@email.com"
                                className="h-12 min-w-0 flex-1 border border-input bg-background px-4 text-sm placeholder:text-muted-foreground focus:border-rose-deep focus:outline-none"
                            />
                            <button
                                type="submit"
                                disabled={form.processing}
                                className="press btn btn-solid h-12 disabled:opacity-50"
                            >
                                Subscribe
                            </button>
                        </div>
                        <label className="mt-3 flex items-start gap-2 text-xs text-foreground/70">
                            <input
                                type="checkbox"
                                required
                                checked={form.data.privacy_consent}
                                onChange={(e) =>
                                    form.setData(
                                        'privacy_consent',
                                        e.target.checked,
                                    )
                                }
                                className="mt-0.5"
                            />
                            <span>
                                I agree to the privacy notice and to receive
                                email from {clinic.short_name}.
                            </span>
                        </label>
                        {(form.errors.email || form.errors.privacy_consent) && (
                            <p
                                role="alert"
                                className="mt-2 text-xs text-rose-ink"
                            >
                                {form.errors.email ??
                                    form.errors.privacy_consent}
                            </p>
                        )}
                    </form>
                </div>
            </div>

            <div className="mx-auto flex max-w-7xl flex-col gap-4 border-t border-border px-4 py-6 text-xs text-foreground/60 sm:flex-row sm:items-center sm:justify-between sm:px-8 lg:px-12">
                <p>
                    © {new Date().getFullYear()} {clinic.name}. A demonstration
                    clinic; names and people are fictional.
                </p>
                <ul className="flex flex-wrap gap-4">
                    {[
                        ['Privacy policy', legal('privacy-policy').url],
                        ['Terms', legal('terms').url],
                        [
                            'Data privacy notice',
                            legal('data-privacy-notice').url,
                        ],
                        ['Sitemap', '/sitemap.xml'],
                    ].map(([label, href]) => (
                        <li key={label}>
                            <Link href={href} className="hover:text-rose-ink">
                                {label}
                            </Link>
                        </li>
                    ))}
                </ul>
                {socials && Object.keys(socials).length > 0 && (
                    <ul className="flex flex-wrap gap-3">
                        {Object.entries(socials).map(([platform, href]) => (
                            <li key={platform}>
                                <a
                                    href={href}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="inline-flex items-center gap-1.5 text-sm hover:text-rose-ink"
                                >
                                    <SocialIcon
                                        platform={platform}
                                        className="size-4"
                                    />
                                    {SOCIAL_LABELS[platform] ?? platform}
                                </a>
                            </li>
                        ))}
                    </ul>
                )}
                <p>
                    Photos from Unsplash contributors, used under the Unsplash
                    License.
                </p>
            </div>
        </footer>
    );
}
