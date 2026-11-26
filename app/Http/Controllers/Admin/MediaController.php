<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

/**
 * Image upload for content pages (administrators only).
 *
 * Every image is decoded and re-encoded with GD: metadata (EXIF, GPS) and anything hidden
 * inside the file are dropped, and only a clean JPEG/PNG is stored. Random file names;
 * files live outside the web root and are served by MediaController::show.
 */
class MediaController extends AdminController
{
    private const MAX_PIXELS = 40_000_000;

    private const MAX_WIDTH = 2000;

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'file', 'max:5120', 'mimetypes:image/jpeg,image/png,image/webp', 'dimensions:max_width=8000,max_height=8000'],
        ]);

        $path = $request->file('image')->getRealPath();
        $info = @getimagesize($path);
        if ($info === false || $info[0] * $info[1] > self::MAX_PIXELS) {
            return response()->json(['message' => 'This image cannot be used.'], 422);
        }

        $src = @imagecreatefromstring((string) file_get_contents($path));
        if ($src === false) {
            return response()->json(['message' => 'This image cannot be used.'], 422);
        }

        if (imagesx($src) > self::MAX_WIDTH) {
            $scaled = imagescale($src, self::MAX_WIDTH);
            imagedestroy($src);
            $src = $scaled;
        }

        $png = $info[2] === IMAGETYPE_PNG || ($info[2] === IMAGETYPE_WEBP && $this->hasAlpha($src));
        $name = bin2hex(random_bytes(16)).($png ? '.png' : '.jpg');
        $dir = storage_path('app/media');
        File::ensureDirectoryExists($dir);

        if ($png) {
            imagealphablending($src, false);
            imagesavealpha($src, true);
            imagepng($src, $dir.'/'.$name, 6);
        } else {
            imagejpeg($src, $dir.'/'.$name, 85);
        }
        imagedestroy($src);

        $this->log('admin.media_uploaded', null, ['file' => $name]);

        return response()->json(['url' => route('media', $name)]);
    }

    private function hasAlpha(\GdImage $img): bool
    {
        $w = imagesx($img);
        $h = imagesy($img);
        $step = max(1, intdiv(max($w, $h), 50));
        for ($x = 0; $x < $w; $x += $step) {
            for ($y = 0; $y < $h; $y += $step) {
                if ((imagecolorat($img, $x, $y) >> 24) & 0x7F) {
                    return true;
                }
            }
        }

        return false;
    }
}
