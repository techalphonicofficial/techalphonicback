<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MediaUploadRequest;
use App\Models\ActivityLog;
use App\Models\Media;
use App\Services\MediaService;
use App\Support\ContentCache;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    public function __construct(private MediaService $media) {}

    public function index(Request $request)
    {
        abort_unless($request->user()->can('media.view'), 403);

        $query = Media::query()->latest();
        if ($q = trim((string) $request->query('q'))) {
            $like = '%'.addcslashes($q, '%_\\').'%';
            $query->where(fn ($w) => $w->where('filename', 'like', $like)->orWhere('alt', 'like', $like)->orWhere('title', 'like', $like));
        }
        if ($type = $request->query('type')) {
            $query->where('mime_type', 'like', match ($type) {
                'image' => 'image/%',
                'video' => 'video/%',
                'document' => 'application/%',
                default => '%',
            });
        }
        if ($request->query('missing_alt')) {
            $query->where('mime_type', 'like', 'image/%')->where(fn ($w) => $w->whereNull('alt')->orWhere('alt', ''));
        }

        $items = $query->paginate($request->wantsJson() ? 40 : 48)->withQueryString();

        // JSON for the media picker modal.
        if ($request->wantsJson()) {
            return response()->json([
                'data' => $items->map(fn (Media $m) => $this->toArray($m)),
                'next' => $items->nextPageUrl(),
            ]);
        }

        return view('admin.media.index', ['items' => $items]);
    }

    public function store(MediaUploadRequest $request)
    {
        $uploaded = [];
        foreach ($request->file('files') as $file) {
            $uploaded[] = $this->toArray($this->media->store($file, ['folder' => $request->input('folder')]));
        }
        ActivityLog::record('media_uploaded', null, count($uploaded).' file(s) uploaded');

        return $request->wantsJson()
            ? response()->json(['data' => $uploaded])
            : back()->with('success', count($uploaded).' file(s) uploaded.');
    }

    public function update(Request $request, int $id)
    {
        abort_unless($request->user()->can('media.update'), 403);
        $media = Media::findOrFail($id);
        $data = $request->validate([
            'alt' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);
        $media->update($data);
        ContentCache::flush();

        return $request->wantsJson() ? response()->json(['data' => $this->toArray($media)]) : back()->with('success', 'Media details saved.');
    }

    public function replace(Request $request, int $id)
    {
        abort_unless($request->user()->can('media.update'), 403);
        $request->validate(['file' => ['required', 'file', 'max:'.config('site.max_upload_kb'), 'mimetypes:'.implode(',', MediaService::ALLOWED_MIMES)]]);
        $media = $this->media->replace(Media::findOrFail($id), $request->file('file'));
        ActivityLog::record('media_replaced', $media);
        ContentCache::flush();

        return back()->with('success', 'File replaced. Every page using it now shows the new version.');
    }

    public function crop(Request $request, int $id)
    {
        abort_unless($request->user()->can('media.update'), 403);
        $data = $request->validate([
            'x' => ['required', 'integer', 'min:0'], 'y' => ['required', 'integer', 'min:0'],
            'width' => ['required', 'integer', 'min:10'], 'height' => ['required', 'integer', 'min:10'],
        ]);
        $media = $this->media->crop(Media::findOrFail($id), $data['x'], $data['y'], $data['width'], $data['height']);
        ContentCache::flush();

        return $request->wantsJson() ? response()->json(['data' => $this->toArray($media)]) : back()->with('success', 'Image cropped.');
    }

    public function destroy(Request $request, int $id)
    {
        abort_unless($request->user()->can('media.delete'), 403);
        $media = Media::findOrFail($id);
        $this->media->deleteFiles($media);
        $media->forceDelete();
        ActivityLog::record('media_deleted', null, 'Deleted '.$media->filename);
        ContentCache::flush();

        return $request->wantsJson() ? response()->json(['ok' => true]) : back()->with('success', 'File deleted.');
    }

    private function toArray(Media $m): array
    {
        return [
            'id' => $m->id,
            'url' => $m->url,
            'thumb' => isset($m->variants['sm']) ? $m->urlFor($m->variants['sm']) : ($m->webp_url ?: $m->url),
            'filename' => $m->filename,
            'alt' => $m->alt,
            'title' => $m->title,
            'caption' => $m->caption,
            'description' => $m->description,
            'mime' => $m->mime_type,
            'is_image' => $m->isImage(),
            'size' => $m->humanSize(),
            'width' => $m->width,
            'height' => $m->height,
            'created' => $m->created_at?->format('d M Y'),
        ];
    }
}
