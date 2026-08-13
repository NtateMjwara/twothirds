<?php
/**
 * Builds the full favicon and app-icon set from a single emblem PNG.
 *
 *   php tools/build-icons.php --src=path/to/emblem.png
 *
 * Options
 *   --src=FILE      source PNG, ideally 512px or larger on its long edge
 *   --bg=HEX        plate colour behind the mark      (default 1B2028)
 *   --bg=none       transparent background instead of a plate
 *   --invert        flip the mark's luminance, for when you only have the
 *                   light-on-black export and need it dark, or vice versa
 *   --pad=0.18      share of the canvas left as margin (default 0.18)
 *   --out=DIR       output directory (default public_html/assets/img/logo)
 *
 * Why a plate by default: a PNG or ICO cannot respond to prefers-color-scheme,
 * so a transparent light mark disappears against a light tab strip and a
 * transparent dark one disappears against a dark strip. Compositing onto the
 * brand's ink keeps it legible everywhere, and matches artwork that is metal on
 * black to begin with. Pass --bg=none if you would rather take that risk.
 *
 * Requires the GD extension, which is present on virtually all cPanel hosts.
 */

// ------------------------------------------------------------
// Options
// ------------------------------------------------------------
$opts = getopt('', ['src:', 'bg::', 'invert', 'pad::', 'out::']);

$src = $opts['src'] ?? null;
if (!$src || !is_file($src)) {
    fwrite(STDERR, "Usage: php tools/build-icons.php --src=path/to/emblem.png\n");
    exit(1);
}
if (!extension_loaded('gd')) {
    fwrite(STDERR, "The GD extension is required and isn't loaded.\n");
    exit(1);
}

$bgOpt  = strtolower($opts['bg'] ?? '1b2028');
$pad    = isset($opts['pad']) ? (float) $opts['pad'] : 0.18;
$invert = array_key_exists('invert', $opts);
$out    = rtrim($opts['out'] ?? 'public_html/assets/img/logo', '/');

if (!is_dir($out) && !mkdir($out, 0755, true)) {
    fwrite(STDERR, "Could not create {$out}\n");
    exit(1);
}

$transparentBg = ($bgOpt === 'none' || $bgOpt === 'transparent');
$bgRgb = $transparentBg ? null : hexToRgb($bgOpt);

// ------------------------------------------------------------
// Load and prepare the mark
// ------------------------------------------------------------
$source = @imagecreatefrompng($src);
if (!$source) {
    fwrite(STDERR, "Could not read {$src} — is it a PNG?\n");
    exit(1);
}
imagealphablending($source, false);
imagesavealpha($source, true);

$mark = trimTransparent($source);
if ($invert) {
    imagefilter($mark, IMG_FILTER_NEGATE);
}

$mw = imagesx($mark);
$mh = imagesy($mark);
printf("source %s  →  mark %dx%d after trim\n", basename($src), $mw, $mh);

if (max($mw, $mh) < 256) {
    fwrite(STDERR, "  warning: the mark is only {$mw}x{$mh}. The 512px icons will be\n");
    fwrite(STDERR, "  upscaled and will look soft. A larger export is worth getting.\n");
}

// ------------------------------------------------------------
// Compose
// ------------------------------------------------------------
/**
 * The mark centred on a square canvas.
 *
 * Scaling is driven by whichever dimension is tighter, so a tall mark keeps its
 * margins on the top and bottom rather than being cropped or stretched.
 */
function compose(int $size, float $padRatio, $mark, ?array $bg)
{
    $canvas = imagecreatetruecolor($size, $size);
    imagealphablending($canvas, false);
    imagesavealpha($canvas, true);

    if ($bg === null) {
        imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
    } else {
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, $bg[0], $bg[1], $bg[2]));
    }
    imagealphablending($canvas, true);

    $inner = $size * (1 - $padRatio * 2);
    $mw = imagesx($mark);
    $mh = imagesy($mark);
    $scale = min($inner / $mw, $inner / $mh);
    $w = max(1, (int) round($mw * $scale));
    $h = max(1, (int) round($mh * $scale));

    imagecopyresampled(
        $canvas, $mark,
        (int) round(($size - $w) / 2), (int) round(($size - $h) / 2),
        0, 0, $w, $h, $mw, $mh
    );

    return $canvas;
}

function writePng($img, string $path): void
{
    imagesavealpha($img, true);
    imagepng($img, $path, 9);
    printf("  %-28s %s\n", basename($path), humanSize(filesize($path)));
}

$written = [];

// Tab and search icons.
//
// Google Search ignores 16px icons and wants a square whose size is a multiple
// of 48, which is what the 96 and 192 exports are for. The 16/32/48 exports
// feed the ICO for browser tabs.
foreach ([16, 32, 48, 96] as $size) {
    // Small sizes get less padding: at 16px a generous margin leaves the mark
    // too few pixels to read as anything.
    $p = $size <= 32 ? max(0.06, $pad - 0.08) : $pad;
    $img = compose($size, $p, $mark, $bgRgb);
    writePng($img, "{$out}/favicon-{$size}.png");
    $written[$size] = "{$out}/favicon-{$size}.png";
    imagedestroy($img);
}

// Home screen and PWA icons. Always on a plate: iOS and Android composite these
// onto their own surfaces, where a transparent mark disappears.
$plateBg = $bgRgb ?? hexToRgb('1b2028');
foreach ([180 => 'apple-touch-icon.png', 192 => 'icon-192.png', 512 => 'icon-512.png'] as $size => $name) {
    $img = compose($size, $pad, $mark, $plateBg);
    writePng($img, "{$out}/{$name}");
    imagedestroy($img);
}

// Maskable icons are cropped to a circle, so everything has to sit inside the
// middle 80% of the canvas — hence the heavier padding.
$img = compose(512, max($pad, 0.28), $mark, $plateBg);
writePng($img, "{$out}/icon-maskable-512.png");
imagedestroy($img);

// Social preview: 1200x630, mark centred on the plate.
$og = imagecreatetruecolor(1200, 630);
imagefill($og, 0, 0, imagecolorallocate($og, $plateBg[0], $plateBg[1], $plateBg[2]));
imagealphablending($og, true);
$square = compose(630, 0.30, $mark, $plateBg);
imagecopy($og, $square, (1200 - 630) / 2, 0, 0, 0, 630, 630);
writePng($og, "{$out}/og-image.png");
imagedestroy($og);
imagedestroy($square);

// ------------------------------------------------------------
// ICO
// ------------------------------------------------------------
writeIco([$written[48], $written[32], $written[16]], "{$out}/favicon.ico");
printf("  %-28s %s\n", 'favicon.ico', humanSize(filesize("{$out}/favicon.ico")));

// Browsers and crawlers still probe /favicon.ico directly, whatever the <link>
// tags say, so it has to exist at the web root as well.
$docroot = dirname($out, 3);
if (is_dir($docroot)) {
    copy("{$out}/favicon.ico", "{$docroot}/favicon.ico");
    printf("  %-28s (copied to web root)\n", 'favicon.ico');
}

echo "\nDone. Hard-refresh with the cache cleared to see the tab icon change;\n";
echo "browsers cache favicons aggressively and Google only updates the search\n";
echo "result icon on its next crawl of the site root.\n";

imagedestroy($mark);
imagedestroy($source);

// ------------------------------------------------------------
// Helpers
// ------------------------------------------------------------
function hexToRgb(string $hex): array
{
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    if (!preg_match('/^[0-9a-f]{6}$/i', $hex)) {
        fwrite(STDERR, "Not a hex colour: {$hex}\n");
        exit(1);
    }
    return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
}

/**
 * Crops fully transparent margins.
 *
 * Logo exports usually carry uneven whitespace, and centring the file rather
 * than the artwork leaves the mark visibly off-centre in every icon. Anything
 * with alpha above a low threshold counts as content, so soft edges survive.
 */
function trimTransparent($img)
{
    $w = imagesx($img);
    $h = imagesy($img);
    $minX = $w; $minY = $h; $maxX = -1; $maxY = -1;

    for ($y = 0; $y < $h; $y++) {
        for ($x = 0; $x < $w; $x++) {
            $alpha = (imagecolorat($img, $x, $y) >> 24) & 0x7F;
            if ($alpha < 120) {                 // 127 is fully transparent
                if ($x < $minX) $minX = $x;
                if ($y < $minY) $minY = $y;
                if ($x > $maxX) $maxX = $x;
                if ($y > $maxY) $maxY = $y;
            }
        }
    }

    // A flattened export with no alpha at all: nothing to trim.
    if ($maxX < 0) {
        return $img;
    }

    $cw = $maxX - $minX + 1;
    $ch = $maxY - $minY + 1;
    $cropped = imagecreatetruecolor($cw, $ch);
    imagealphablending($cropped, false);
    imagesavealpha($cropped, true);
    imagefill($cropped, 0, 0, imagecolorallocatealpha($cropped, 0, 0, 0, 127));
    imagecopy($cropped, $img, 0, 0, $minX, $minY, $cw, $ch);

    return $cropped;
}

/**
 * Writes a multi-resolution .ico with PNG payloads.
 *
 * PNG-compressed entries are legal in ICO from Windows Vista onward and are
 * understood by every current browser. Only IE 10 and older need uncompressed
 * BMP entries, which isn't a constraint worth carrying.
 */
function writeIco(array $pngPaths, string $target): void
{
    $entries = [];
    foreach ($pngPaths as $path) {
        $data = file_get_contents($path);
        $size = getimagesize($path);
        $entries[] = ['data' => $data, 'w' => $size[0], 'h' => $size[1]];
    }

    $count = count($entries);
    $offset = 6 + 16 * $count;

    $dir = pack('vvv', 0, 1, $count);
    $body = '';

    foreach ($entries as $e) {
        $dir .= pack(
            'CCCCvvVV',
            $e['w'] >= 256 ? 0 : $e['w'],
            $e['h'] >= 256 ? 0 : $e['h'],
            0, 0, 1, 32,
            strlen($e['data']),
            $offset
        );
        $offset += strlen($e['data']);
        $body .= $e['data'];
    }

    file_put_contents($target, $dir . $body);
}

function humanSize(int $bytes): string
{
    return $bytes < 1024 ? "{$bytes} B" : round($bytes / 1024, 1) . ' KB';
}
