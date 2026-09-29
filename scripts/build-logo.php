<?php

/*
    Regenerates every brand asset from the master logo file.

    The mark is a raster file the clinic hands over, and every size the browser
    asks for is derived from it here: the header and footer, the login screen,
    the favicon and the iOS home screen icon. Doing it in a script rather than
    by hand is the point -- the next person given a new logo edits one file and
    reruns this, instead of hunting down six images and hoping they resized all
    of them the same way.

    Usage, from the project root:

        php scripts/build-logo.php "C:\Users\Nick\Downloads\irish\logo.png"

    Writes:

        public/media/brand/logo.webp     256px   the mark the site paints
        public/media/brand/logo.png      256px   the same, for a browser
                                                    that will not take WebP
        public/favicon.svg                64px   scales in every browser
        public/favicon.ico               16, 32, 48
        public/apple-touch-icon.png      180px   opaque; iOS renders
                                                    transparency as black, so
                                                    this one is flattened
                                                    onto the page colour

    The 256px edge is not arbitrary. The mark is painted at 40-48px in the
    header and 32px in the admin sidebar, so 256 covers a 3x screen with room
    to spare; anything larger is bytes that nobody ever sees.
*/

declare(strict_types=1);

$root = dirname(__DIR__);

$source = $argv[1] ?? '';

if ($source === '' || ! is_file($source)) {
    fwrite(STDERR, "Usage: php scripts/build-logo.php <path to the master logo>\n");
    exit(1);
}

$master = @imagecreatefrompng($source);

if ($master === false) {
    fwrite(STDERR, "Could not read {$source} as a PNG.\n");
    exit(1);
}

// Scaling happens with blending off and saving on, which is the only way GD
// copies the alpha channel rather than compositing the transparent corners
// onto black. The same recipe is in App\Actions\Media\UploadPhoto.
imagealphablending($master, false);
imagesavealpha($master, true);

$brand = $root.'/public/media/brand';

if (! is_dir($brand) && ! mkdir($brand, 0775, true) && ! is_dir($brand)) {
    fwrite(STDERR, "Could not create {$brand}\n");
    exit(1);
}

/**
 * Scales the whole mark into an $edge square, preserving transparency.
 *
 * A logo carries lettering, so it is fitted with transparent padding rather
 * than cropped to its middle square: cutting the edges off a wordmark to make
 * it fit would ship a different logo than the one that was supplied.
 */
function fit(GdImage $image, int $edge): GdImage
{
    $width = imagesx($image);
    $height = imagesy($image);
    $ratio = min($edge / $width, $edge / $height);
    $scaledW = max(1, (int) round($width * $ratio));
    $scaledH = max(1, (int) round($height * $ratio));

    $scaled = imagecreatetruecolor($scaledW, $scaledH);
    imagealphablending($scaled, false);
    imagesavealpha($scaled, true);
    imagecopyresampled($scaled, $image, 0, 0, 0, 0, $scaledW, $scaledH, $width, $height);

    // Nothing to pad: the source was already the right shape.
    if ($scaledW === $edge && $scaledH === $edge) {
        return $scaled;
    }

    $out = imagecreatetruecolor($edge, $edge);
    imagealphablending($out, false);
    imagesavealpha($out, true);

    // Fill before copying: whatever imagecopy does not cover would otherwise be
    // left as opaque black, which shows as dark corners around the mark.
    $clear = imagecolorallocatealpha($out, 0, 0, 0, 127);
    imagefilledrectangle($out, 0, 0, $edge, $edge, $clear);

    $x = (int) round(($edge - $scaledW) / 2);
    $y = (int) round(($edge - $scaledH) / 2);
    imagecopy($out, $scaled, $x, $y, 0, 0, $scaledW, $scaledH);

    return $out;
}

/** PNG bytes, for the two places that need the image inside another file. */
function pngBytes(GdImage $image): string
{
    ob_start();
    imagepng($image, null, 9);

    return (string) ob_get_clean();
}

/**
 * An ICO holding PNG-encoded images, which every browser that reads ICOs at all
 * can decode. GD writes no ICO, so the container is written by hand: a six byte
 * directory, a sixteen byte entry per image, then the images.
 *
 * @param  array<int, string>  $images  edge length => PNG bytes
 */
function ico(array $images): string
{
    $offset = 6 + (16 * count($images));
    $entries = '';
    $body = '';

    foreach ($images as $edge => $png) {
        $entries .= pack('CCCCvvVV', $edge, $edge, 0, 0, 1, 32, strlen($png), $offset);
        $body .= $png;
        $offset += strlen($png);
    }

    return pack('vvv', 0, 1, count($images)).$entries.$body;
}

/** Writes a file and hands back what was written, for the summary below. */
function put(string $path, string $bytes): array
{
    file_put_contents($path, $bytes);

    return [$path, strlen($bytes)];
}

$files = [];

$mark = fit($master, 256);

$files[] = put($brand.'/logo.png', pngBytes($mark));

ob_start();
imagewebp($mark, null, 82);
$files[] = put($brand.'/logo.webp', (string) ob_get_clean());

// The SVG favicon carries the mark as an embedded raster rather than a traced
// path: a hand-traced badge is a different logo, and this is the owner's file.
// The embedded copy is 64px because that is the largest a browser ever draws
// one, and this badge is grainy enough that a 128px embed triples the file for
// no pixel anyone sees.
$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">'
    .'<image width="64" height="64" href="data:image/png;base64,'
    .base64_encode(pngBytes(fit($master, 64)))
    .'"/></svg>';
$files[] = put($root.'/public/favicon.svg', $svg);

$files[] = put($root.'/public/favicon.ico', ico([
    16 => pngBytes(fit($master, 16)),
    32 => pngBytes(fit($master, 32)),
    48 => pngBytes(fit($master, 48)),
]));

$touch = imagecreatetruecolor(180, 180);
$ivory = imagecolorallocate($touch, 0xFF, 0xFD, 0xFC);
imagefilledrectangle($touch, 0, 0, 180, 180, $ivory);
imagealphablending($touch, true);
$touchIcon = fit($master, 180);
imagecopy($touch, $touchIcon, 0, 0, 0, 0, 180, 180);
$files[] = put($root.'/public/apple-touch-icon.png', pngBytes($touch));

$shown = static fn (string $path): string => str_replace('\\', '/', substr($path, strlen($root) + 1));

foreach ($files as [$path, $bytes]) {
    printf("  %-34s %7.1f kB\n", $shown($path), $bytes / 1024);
}

printf(
    "\n  %d files written from the %dx%d source.\n",
    count($files),
    imagesx($master),
    imagesy($master),
);
