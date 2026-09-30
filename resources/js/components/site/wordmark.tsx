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
/**
 * The clinic's own mark, as the file they supplied, with the name set beside
 * it as a lockup.
 *
 * The mark's own artwork carries the name, so this repeats it -- which is why
 * the image is marked decorative whenever the name is also in text. Leaving
 * the alt text in would have a screen reader announce the name twice; letting
 * callers turn the text off without also turning the alt off would leave a
 * silent logo on the icon-only sidebar, where there is no visible name to
 * fall back on.
 */
export default function Wordmark({
    name,
    descriptor,
    className,
    markClassName,
    nameClassName,
}: {
    /**
     * Set this to collapse the lockup to a single line, which is what the
     * admin rail wants: a 256px column cannot carry the script name and the
     * tracked descriptor without either wrapping or dropping to a size that
     * the script stops reading at. Left unset, the name is split out of
     * `clinic.name` and set as the full two-line lockup.
     */
    name?: string | null;
    descriptor?: string;
    className?: string;
    markClassName?: string;
    nameClassName?: string;
}) {
    const { clinic } = usePage<{ clinic: Clinic }>().props;

    // "Irish Aesthetics and Beauty Lounge" minus the short name leaves the
    // half that wants setting in small caps, so the split follows the config
    // rather than being written out here. A brand whose short name is not a
    // prefix of its full name simply gets the whole name on the top line.
    const split =
        name === undefined
            ? clinic.name.startsWith(clinic.short_name)
                ? {
                      script: clinic.short_name,
                      caps: clinic.name.slice(clinic.short_name.length).trim(),
                  }
                : { script: clinic.name, caps: '' }
            : { script: name ?? '', caps: '' };

    const mark = (
        // The sizing lives on <picture>, not on the <img>. <picture> is the
        // child of the flex row, so it is the flex item: it is what shrinks,
        // and a class on the <img> inside it cannot stop that. Left on the
        // image, the picture was squeezed to 28px on a phone while the image
        // kept its 40px height, and object-fit:fill stretched the square mark
        // into a narrow ellipse. The image is then sized to the picture and
        // object-contain keeps its proportions whatever box it is given.
        <picture className={cn('block shrink-0', markClassName ?? 'size-10')}>
            <source srcSet={clinic.logo} type="image/webp" />
            <img
                src={clinic.logo_fallback}
                // Decorative once the name is in text beside it; the name is
                // the accessible label, and this keeps it announced once.
                alt={split.script ? '' : clinic.name}
                width={256}
                height={256}
                decoding="async"
                className="h-full w-full object-contain"
            />
        </picture>
    );

    if (!split.script) {
        return (
            <span className={cn('inline-flex items-center', className)}>
                {mark}
            </span>
        );
    }

    return (
        <span className={cn('inline-flex items-center gap-3', className)}>
            {mark}
            <span className="flex min-w-0 flex-col">
                <span
                    className={cn(
                        'font-script text-[1.7rem] leading-[0.85] tracking-normal',
                        nameClassName,
                    )}
                >
                    {split.script}
                </span>
                {split.caps && (
                    // Tracked-out small caps under a script is the pairing the
                    // reference uses: the script carries the personality and
                    // the caps carry the information, so the words still read
                    // when the flourish stops working.
                    <span className="mt-1.5 font-sans text-[0.5rem] leading-none font-medium tracking-[0.18em] text-foreground/70 uppercase">
                        {split.caps}
                    </span>
                )}
                {descriptor && (
                    <span className="mt-1 text-[9px] tracking-[0.28em] uppercase opacity-75">
                        {descriptor}
                    </span>
                )}
            </span>
        </span>
    );
}
