import { usePage } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import type { Clinic } from '@/types';

/**
 * The clinic's own mark, as the file they supplied.
 *
 * The name is written into the artwork itself, so nothing is set beside it
 * here: the old version drew a letter in a frame and paired it with the name
 * in type, and the two said the same thing twice. The descriptor stays an
 * optional prop because the desk and the site are the same brand in two
 * places, and only the office needs saying which one you are in.
 *
 * WebP is offered first and the PNG is the fallback rather than the other way
 * round: the mark is drawn on every page, and the WebP is a third of the bytes
 * for the browsers that can read it, which is all of them that matter.
 */
export default function Wordmark({
    descriptor,
    className,
    markClassName,
}: {
    descriptor?: string;
    className?: string;
    markClassName?: string;
}) {
    const { clinic } = usePage<{ clinic: Clinic }>().props;

    return (
        <span className={cn('inline-flex items-center gap-3', className)}>
            <picture>
                <source srcSet={clinic.logo} type="image/webp" />
                <img
                    src={clinic.logo_fallback}
                    alt={clinic.name}
                    width={256}
                    height={256}
                    decoding="async"
                    className={cn('size-10 shrink-0', markClassName)}
                />
            </picture>
            {descriptor && (
                <span className="text-[9px] tracking-[0.28em] uppercase opacity-75">
                    {descriptor}
                </span>
            )}
        </span>
    );
}
