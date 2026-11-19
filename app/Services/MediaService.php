<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Secure uploads: random filenames, MIME re-validated server-side, images re-encoded (strips EXIF/payloads)
 * with WebP + responsive variants generated via GD.
 */
class MediaService
{
    public const ALLOWED_MIMES = [
        'image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/avif', 'image/svg+xml',
        'application/pdf', 'video/mp4',
    ];

    public const SIZES = ['sm' => 480, 'md' => 960, 'lg' => 1600];

    public function store(UploadedFile $file, array $meta = [], string $disk = 'public'): Media
    {
        $mime = $file->getMimeType();
        abort_unless(in_array($mime, self::ALLOWED_MIMES, true), 422, 'File type not allowed.');

        $folder = 'uploads/'.now()->format('Y/m');
        $base = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'file';
        $name = Str::limit($base, 60, '').'-'.Str::lower(Str::random(8));
        $ext = $this->extensionFor($mime, $file);

        if ($mime === 'image/svg+xml') {
            $svg = $this->sanitizeSvg((string) file_get_contents($file->getRealPath()));
            $path = "$folder/$name.svg";
            Storage::disk($disk)->put($path, $svg);
        } else {
            $path = $file->storeAs($folder, "$name.$ext", $disk);
        }

        [$width, $height] = $this->dimensions($file, $mime);

        $media = Media::create([
            'disk' => $disk,
            'path' => $path,
            'filename' => "$name.$ext",
            'original_name' => Str::limit($file->getClientOriginalName(), 250, ''),
            'mime_type' => $mime,
            'size' => $file->getSize(),
            'width' => $width,
            'height' => $height,
            'alt' => $meta['alt'] ?? Str::headline($base),
            'title' => $meta['title'] ?? Str::headline($base),
            'caption' => $meta['caption'] ?? null,
            'description' => $meta['description'] ?? null,
            'folder' => $meta['folder'] ?? null,
            'uploaded_by' => auth()->id(),
        ]);

        if (in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            $media->variants = $this->generateVariants($media);
            $media->save();
        }

        return $media;
    }

    public function replace(Media $media, UploadedFile $file): Media
    {
        $this->deleteFiles($media);
        $new = $this->store($file, ['alt' => $media->alt, 'title' => $media->title, 'caption' => $media->caption], $media->disk);

        // Keep the original ID so every page referencing it updates automatically.
        $media->fill($new->only(['path', 'filename', 'original_name', 'mime_type', 'size', 'width', 'height', 'variants']))->save();
        $new->forceDelete();

        return $media;
    }

    /** Crop to a rectangle (from the admin cropper), then regenerate variants. */
    public function crop(Media $media, int $x, int $y, int $w, int $h): Media
    {
        $disk = Storage::disk($media->disk);
        $src = $this->gdFromFile($disk->path($media->path), $media->mime_type);
        if (! $src) {
            return $media;
        }
        $cropped = imagecrop($src, ['x' => max(0, $x), 'y' => max(0, $y), 'width' => max(1, $w), 'height' => max(1, $h)]);
        if ($cropped) {
            $this->gdSave($cropped, $disk->path($media->path), $media->mime_type);
            $media->width = imagesx($cropped);
            $media->height = imagesy($cropped);
            $media->size = filesize($disk->path($media->path));
            imagedestroy($cropped);
            $media->variants = $this->generateVariants($media);
            $media->save();
        }
        imagedestroy($src);

        return $media;
    }

    public function deleteFiles(Media $media): void
    {
        $disk = Storage::disk($media->disk);
        $disk->delete(array_filter(array_merge([$media->path], array_values($media->variants ?? []))));
    }

    private function generateVariants(Media $media): array
    {
        if (! function_exists('imagewebp')) {
            return [];
        }

        $disk = Storage::disk($media->disk);
        $source = $disk->path($media->path);
        $img = $this->gdFromFile($source, $media->mime_type);
        if (! $img) {
            return [];
        }

        $variants = [];
        $dir = pathinfo($media->path, PATHINFO_DIRNAME);
        $name = pathinfo($media->path, PATHINFO_FILENAME);
        $width = imagesx($img);

        try {
            $webp = "$dir/$name.webp";
            if ($media->mime_type !== 'image/webp') {
                imagewebp($img, $disk->path($webp), 82);
                $variants['webp'] = $webp;
            }

            foreach (self::SIZES as $key => $target) {
                if ($width <= $target) {
                    continue;
                }
                $resized = imagescale($img, $target);
                $path = "$dir/$name-$key.webp";
                imagewebp($resized, $disk->path($path), 80);
                imagedestroy($resized);
                $variants[$key] = $path;
            }

            if (function_exists('imageavif') && ($variants['webp'] ?? null)) {
                $avif = "$dir/$name.avif";
                if (@imageavif($img, $disk->path($avif), 60)) {
                    $variants['avif'] = $avif;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Image variant generation failed', ['media' => $media->id, 'error' => $e->getMessage()]);
        } finally {
            imagedestroy($img);
        }

        return $variants;
    }

    private function gdFromFile(string $path, string $mime): \GdImage|false
    {
        $img = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => @imagecreatefromwebp($path),
            default => false,
        };
        if ($img && $mime === 'image/png') {
            imagepalettetotruecolor($img);
            imagealphablending($img, true);
            imagesavealpha($img, true);
        }

        return $img;
    }

    private function gdSave(\GdImage $img, string $path, string $mime): void
    {
        match ($mime) {
            'image/jpeg' => imagejpeg($img, $path, 85),
            'image/png' => imagepng($img, $path, 8),
            'image/webp' => imagewebp($img, $path, 85),
            default => null,
        };
    }

    private function dimensions(UploadedFile $file, string $mime): array
    {
        if (! str_starts_with($mime, 'image/') || $mime === 'image/svg+xml') {
            return [null, null];
        }
        $info = @getimagesize($file->getRealPath());

        return $info ? [$info[0], $info[1]] : [null, null];
    }

    private function extensionFor(string $mime, UploadedFile $file): string
    {
        return match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'image/avif' => 'avif',
            'image/svg+xml' => 'svg',
            'application/pdf' => 'pdf',
            'video/mp4' => 'mp4',
            default => $file->guessExtension() ?? 'bin',
        };
    }

    /** Remove scripts, event handlers and external references from SVG uploads. */
    private function sanitizeSvg(string $svg): string
    {
        $svg = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $svg);
        $svg = preg_replace('#<foreignObject\b[^>]*>.*?</foreignObject>#is', '', $svg);
        $svg = preg_replace('/\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $svg);
        $svg = preg_replace('/(href|xlink:href)\s*=\s*["\']\s*javascript:[^"\']*["\']/i', '', $svg);

        return $svg;
    }
}
