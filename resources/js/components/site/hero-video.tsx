import { usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { cn } from '@/lib/utils';

const CLIPS = ['hero-5308682', 'hero-5308683', 'hero-12322630'];

/**
 * Short muted clips that crossfade in turn, filling whatever box they are put
 * in. Reduced-motion visitors get the still poster of the first clip.
 *
 * `inset` decides how the component takes up space. Left true it is a
 * full-bleed background, absolutely positioned behind its section, which is
 * how the hero used to work. Set false it simply fills its parent, which is
 * what the split hero needs now that the film is an ordinary image in a
 * column rather than the page's backdrop. Callers that pass false must give
 * the parent a height.
 */
export default function HeroVideo({
    inset = true,
    className,
}: {
    inset?: boolean;
    className?: string;
}) {
    const { mediaUrl } = usePage().props;
    const base = mediaUrl.replace(/photos$/, 'video');
    const [active, setActive] = useState(0);
    const [still, setStill] = useState(false);
    const refs = useRef<(HTMLVideoElement | null)[]>([]);

    useEffect(() => {
        setStill(window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    }, []);

    useEffect(() => {
        if (still) {
            return;
        }

        const video = refs.current[active];

        if (video) {
            video.currentTime = 0;
            void video.play().catch(() => setStill(true));
        }
    }, [active, still]);

    return (
        <div
            aria-hidden="true"
            className={cn(inset ? 'absolute inset-0 -z-20' : 'h-full w-full', className)}
        >
            {still ? (
                <img
                    src={`${base}/${CLIPS[0]}.jpg`}
                    alt=""
                    className="h-full w-full object-cover"
                />
            ) : (
                CLIPS.map((clip, i) => (
                    <video
                        key={clip}
                        ref={(el) => {
                            refs.current[i] = el;
                        }}
                        src={`${base}/${clip}.mp4`}
                        poster={i === 0 ? `${base}/${clip}.jpg` : undefined}
                        muted
                        playsInline
                        preload={i === 0 ? 'auto' : 'metadata'}
                        onEnded={() => setActive((active + 1) % CLIPS.length)}
                        className={cn(
                            'absolute inset-0 h-full w-full object-cover transition-opacity duration-700 ease-out',
                            i === active ? 'opacity-100' : 'opacity-0',
                        )}
                    />
                ))
            )}
        </div>
    );
}
