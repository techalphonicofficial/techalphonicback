<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\FaqRequest;
use App\Models\Faq;
use App\Support\ContentCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class FaqController extends ContentController
{
    protected string $modelClass = Faq::class;

    protected string $resource = 'faqs';

    protected string $routeName = 'admin.faqs';

    protected string $viewPath = 'admin.faqs';

    protected string $label = 'FAQ';

    protected string $labelPlural = 'FAQs';

    protected array $searchColumns = ['question', 'answer'];

    protected array $sortable = ['sort_order'];

    protected array $listWith = [];

    protected array $columns = [
        ['Question', 'question', 'title'],
        ['Used on', 'pages_count', 'text'],
        ['Active', 'is_active', 'bool'],
        ['Order', 'sort_order', 'text'],
    ];

    protected function query()
    {
        return Faq::query()->selectRaw('faqs.*, (select count(*) from page_faqs where page_faqs.faq_id = faqs.id) as pages_count');
    }

    protected function requestClass(): string
    {
        return FaqRequest::class;
    }

    protected function defaults(): array
    {
        return ['is_active' => true];
    }

    /** Drag-and-drop reorder from the index (ids in new order). */
    public function reorder(Request $request)
    {
        abort_unless($request->user()->can('faqs.update'), 403);
        $ids = $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer']])['ids'];
        foreach ($ids as $i => $id) {
            Faq::whereKey($id)->update(['sort_order' => $i]);
        }
        ContentCache::flush();

        return response()->json(['ok' => true]);
    }

    /** Quick-create a new FAQ directly from a page/service/content form. */
    public function quickStore(Request $request)
    {
        abort_unless(
            $request->user()->can('faqs.create') ||
            $request->user()->can('pages.update') ||
            $request->user()->can('pages.create'),
            403
        );

        $validated = $request->validate([
            'question' => ['required', 'string', 'max:500'],
            'answer' => ['required', 'string'],
        ]);

        $faq = Faq::create([
            'question' => trim($validated['question']),
            'answer' => trim($validated['answer']),
            'is_active' => true,
            'sort_order' => 0,
        ]);

        ContentCache::flush();

        return response()->json([
            'success' => true,
            'faq' => [
                'id' => $faq->id,
                'question' => $faq->question,
                'answer' => $faq->answer,
            ],
        ]);
    }
}
