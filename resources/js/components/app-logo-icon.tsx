import { usePage } from '@inertiajs/react';

import { cn } from '@/lib/utils';
import type { Clinic } from '@/types';

/**
 * The brand mark on its own, for the places that want the badge without the
 * descriptor beside it: the sign-in screens and the app header.
 *
 * The name is the alt text rather than a heading, so the link still reads
 * correctly to a screen reader without the name being painted twice beside a
 * mark that already has it written into the artwork.
 */
export default function AppLogoIcon({ className }: { className?: string }) {
    const { clinic } = usePage<{ clinic: Clinic }>().props;

    return (
        // As in the Wordmark: the sizing goes on <picture>, because that is the
        // flex item, and the image is then fitted inside it rather than the
        // other way round. Sizing the <img> alone let the picture be squeezed
        // by its flex parent and the square mark was stretched to match.
        <picture className={cn('block shrink-0', className)}>
            <source srcSet={clinic.logo} type="image/webp" />
            <img
                src={clinic.logo_fallback}
                alt={clinic.name}
                width={256}
                height={256}
                decoding="async"
                className="h-full w-full object-contain"
            />
        </picture>
    );
}
