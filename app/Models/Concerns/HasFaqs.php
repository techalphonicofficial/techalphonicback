<?php

namespace App\Models\Concerns;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasFaqs
{
    public function faqs(): MorphToMany
    {
        return $this->morphToMany(Faq::class, 'faqable', 'page_faqs')
            ->withPivot('sort_order')
            ->orderBy('page_faqs.sort_order');
    }

    public function activeFaqs(): MorphToMany
    {
        return $this->faqs()->where('faqs.is_active', true);
    }

    /** @param  array<int, int>  $faqIds  ordered list */
    public function syncFaqs(array $faqIds): void
    {
        $sync = [];
        foreach (array_values(array_unique(array_filter($faqIds))) as $i => $id) {
            $sync[(int) $id] = ['sort_order' => $i];
        }
        $this->faqs()->sync($sync);
    }
}
