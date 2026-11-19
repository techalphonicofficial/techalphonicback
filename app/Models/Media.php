<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use SoftDeletes;

    protected $table = 'media';

    protected $fillable = [
        'disk', 'path', 'filename', 'original_name', 'mime_type', 'size', 'width', 'height',
        'alt', 'title', 'caption', 'description', 'variants', 'folder', 'uploaded_by',
    ];

    protected $casts = ['variants' => 'array'];

    protected $appends = ['url'];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getUrlAttribute(): string
    {
        return $this->urlFor($this->path);
    }

    public function urlFor(?string $path): string
    {
        if (! $path) {
            return '';
        }
        if (str_starts_with($path, 'http') || str_starts_with($path, '/')) {
            return $path;
        }

        return Storage::disk($this->disk)->url($path);
    }

    /** Best format for <img src>: webp variant when available. */
    public function getWebpUrlAttribute(): ?string
    {
        return isset($this->variants['webp']) ? $this->urlFor($this->variants['webp']) : null;
    }

    /** srcset string built from generated responsive sizes. */
    public function srcset(): ?string
    {
        $widths = ['sm' => 480, 'md' => 960, 'lg' => 1600];
        $parts = [];
        foreach ($widths as $key => $w) {
            if (! empty($this->variants[$key])) {
                $parts[] = $this->urlFor($this->variants[$key]).' '.$w.'w';
            }
        }

        return $parts ? implode(', ', $parts) : null;
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function humanSize(): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->size;
        $i = 0;
        while ($size >= 1024 && $i < 3) {
            $size /= 1024;
            $i++;
        }

        return round($size, 1).' '.$units[$i];
    }
}
