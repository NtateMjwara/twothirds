<?php
namespace app\services;

use app\models\AssetImage;

/**
 * Asset photograph uploads.
 *
 * Deliberately different from CompanyDocumentService in one respect: these files
 * land inside the web root and are served directly by Apache, not streamed
 * through PHP. Company documents are records that may need an access check one
 * day; asset photographs are public marketing on a public page, and putting a
 * dozen thumbnails behind a PHP bootstrap each would make the gallery crawl.
 *
 * Everything is re-encoded rather than copied. That normalises the format,
 * caps the dimensions, and drops the EXIF block — which on a phone photo
 * usually carries the GPS coordinates of wherever the picture was taken.
 * Publishing an operator's home address by accident is a real risk and this is
 * the cheapest place to remove it.
 */
class AssetImageService
{
    private const ALLOWED_MIME = ['image/jpeg', 'image/png', 'image/webp'];
    private const MAX_BYTES = 12 * 1024 * 1024;

    private const DISPLAY_MAX_W = 1800;
    private const DISPLAY_MAX_H = 1400;
    private const THUMB_W = 600;
    private const THUMB_H = 400;
    private const QUALITY = 82;

    public static function publicDir(): string
    {
        return __DIR__ . '/../../public_html/uploads/assets';
    }

    /**
     * @return int the new image's id
     */
    public static function upload(int $companyId, array $file, ?int $assetId = null, string $caption = ''): int
    {
        if (!extension_loaded('gd')) {
            throw new \RuntimeException('Image uploads need the GD extension, which is not loaded.');
        }
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \InvalidArgumentException('The upload did not complete. Try again.');
        }
        if ($file['size'] > self::MAX_BYTES) {
            throw new \InvalidArgumentException('Images must be under 12MB.');
        }

        // Trust the file's own bytes, not the extension or the browser's
        // Content-Type — both are attacker-supplied.
        $info = @getimagesize($file['tmp_name']);
        if (!$info || !in_array($info['mime'], self::ALLOWED_MIME, true)) {
            throw new \InvalidArgumentException('Only JPG, PNG or WebP images are accepted.');
        }
        if ($info[0] < 600 || $info[1] < 400) {
            throw new \InvalidArgumentException(
                "That image is {$info[0]}x{$info[1]}. Use something at least 600x400 or it will look soft on the page."
            );
        }

        $dir = self::publicDir() . '/' . $companyId;
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException('Could not create the upload directory.');
        }
        self::protectDirectory(self::publicDir());

        $source = self::load($file['tmp_name'], $info['mime']);
        if (!$source) {
            throw new \InvalidArgumentException('That image could not be read.');
        }

        if ($info['mime'] === 'image/jpeg') {
            $source = self::applyExifRotation($source, $file['tmp_name']);
        }

        // Random names: never reuse what the browser sent, and never let two
        // uploads collide.
        $stem = bin2hex(random_bytes(8));
        $displayRel = "{$companyId}/{$stem}.jpg";
        $thumbRel = "{$companyId}/{$stem}_thumb.jpg";

        try {
            self::writeResized($source, $dir . "/{$stem}.jpg", self::DISPLAY_MAX_W, self::DISPLAY_MAX_H, false);
            self::writeResized($source, $dir . "/{$stem}_thumb.jpg", self::THUMB_W, self::THUMB_H, true);
        } finally {
            imagedestroy($source);
        }

        try {
            $id = (int) AssetImage::create([
                'company_id' => $companyId,
                'asset_id'   => $assetId,
                'file_path'  => $displayRel,
                'thumb_path' => $thumbRel,
                'caption'    => $caption !== '' ? mb_substr($caption, 0, 255) : null,
                'sort_order' => AssetImage::nextSortOrder($companyId),
                'is_primary' => 0,
            ]);
        } catch (\Throwable $e) {
            // Don't leave files on disk with no row pointing at them.
            @unlink($dir . "/{$stem}.jpg");
            @unlink($dir . "/{$stem}_thumb.jpg");
            throw $e;
        }

        return $id;
    }

    /** Removes the row and both files. Missing files are not an error. */
    public static function delete(array $image): void
    {
        foreach (['file_path', 'thumb_path'] as $key) {
            if (empty($image[$key])) {
                continue;
            }
            $path = realpath(self::publicDir() . '/' . $image[$key]);
            $root = realpath(self::publicDir());
            // Never unlink outside the upload root, whatever the column says.
            if ($path && $root && str_starts_with($path, $root) && is_file($path)) {
                @unlink($path);
            }
        }

        AssetImage::delete((int) $image['id']);
    }

    // ------------------------------------------------------------

    private static function load(string $path, string $mime)
    {
        return match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png'  => @imagecreatefrompng($path),
            'image/webp' => @imagecreatefromwebp($path),
            default      => false,
        };
    }

    /**
     * Phones record orientation in EXIF rather than rotating the pixels. Since
     * re-encoding drops the EXIF block, the rotation has to be baked in first or
     * every photo taken in portrait arrives on its side.
     */
    private static function applyExifRotation($image, string $path)
    {
        if (!function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($path);
        $orientation = (int) ($exif['Orientation'] ?? 0);

        $angle = match ($orientation) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        if ($angle === 0) {
            return $image;
        }

        $rotated = imagerotate($image, $angle, 0);
        if (!$rotated) {
            return $image;
        }

        imagedestroy($image);
        return $rotated;
    }

    /**
     * $crop false: fit inside the box, keep the whole frame, never upscale.
     * $crop true:  fill the box exactly and trim the overflow — thumbnails have
     *              to be a uniform size or the admin grid looks ragged.
     */
    private static function writeResized($source, string $destination, int $maxW, int $maxH, bool $crop): void
    {
        $sw = imagesx($source);
        $sh = imagesy($source);

        if ($crop) {
            $scale = max($maxW / $sw, $maxH / $sh);
            $tw = $maxW;
            $th = $maxH;
            $cw = (int) round($maxW / $scale);
            $ch = (int) round($maxH / $scale);
            $sx = (int) round(($sw - $cw) / 2);
            $sy = (int) round(($sh - $ch) / 2);
        } else {
            $scale = min($maxW / $sw, $maxH / $sh, 1.0);
            $tw = max(1, (int) round($sw * $scale));
            $th = max(1, (int) round($sh * $scale));
            $cw = $sw;
            $ch = $sh;
            $sx = 0;
            $sy = 0;
        }

        $canvas = imagecreatetruecolor($tw, $th);
        // Flatten transparency onto white: the output is JPEG, and an unfilled
        // alpha channel encodes as black blotches.
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopyresampled($canvas, $source, 0, 0, $sx, $sy, $tw, $th, $cw, $ch);

        imagejpeg($canvas, $destination, self::QUALITY);
        imagedestroy($canvas);
    }

    /**
     * An uploads directory inside the web root is only safe if nothing in it can
     * execute. The images are re-encoded, so a PHP payload can't survive the
     * round trip — but a misconfigured server is one bad vhost away, and this
     * costs nothing.
     */
    private static function protectDirectory(string $dir): void
    {
        $htaccess = $dir . '/.htaccess';
        if (is_file($htaccess)) {
            return;
        }

        @file_put_contents($htaccess, <<<'CONF'
# Uploaded images only. Nothing here is ever executed.
php_flag engine off
RemoveHandler .php .phtml .php3 .php4 .php5 .php7 .phps
RemoveType .php .phtml .php3 .php4 .php5 .php7 .phps
<FilesMatch "\.(?i:php[0-9]?|phtml|phps|cgi|pl|py|sh)$">
    Require all denied
</FilesMatch>
CONF);
    }
}
