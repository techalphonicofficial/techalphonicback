<?php

namespace App\Services;

use App\Models\LandingPage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Single source of truth for "which FAQs does this page show". Both the FAQ component and the FAQPage
 * schema call this, so schema can never contain FAQs that aren't visible on the page.
 */
class FaqService
{
    /** Per model *instance* memo: the view and the schema share one lookup, but nothing leaks across requests. */
    private \WeakMap $memo;

    public function __construct(private TemplateRenderer $renderer)
    {
        $this->memo = new \WeakMap;
    }

    /** @return Collection<int, object{question: string, answer: string}> */
    public function for(Model $model): Collection
    {
        $key = $model;
        if (isset($this->memo[$key])) {
            return $this->memo[$key];
        }

        if (! method_exists($model, 'activeFaqs') || ($model->getAttribute('faq_enabled') === false)) {
            return $this->memo[$key] = collect();
        }

        $faqs = $model->activeFaqs()->get();
        $vars = [];

        // Programmatic pages inherit the parent service's FAQs when none are assigned directly.
        if ($model instanceof LandingPage) {
            $vars = $model->templateVariables();
            if ($faqs->isEmpty() && $model->service) {
                $faqs = $model->service->activeFaqs()->get();
            }
        }

        return $this->memo[$key] = $faqs->map(fn ($f) => (object) [
            'id' => $f->id,
            'question' => $this->renderer->render($f->question, $vars),
            'answer' => $this->renderer->render($f->answer, $vars),
        ])->values();
    }
}
