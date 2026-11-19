@props(['item', 'allFaqs' => collect()])
@php
    $currentFaqs = [];
    if (old('page_faqs')) {
        $currentFaqs = array_values((array) old('page_faqs'));
    } elseif ($item->exists && $item->faqs) {
        $currentFaqs = $item->faqs->map(fn ($f) => [
            'id' => (int) $f->id,
            'question' => (string) $f->question,
            'answer' => (string) $f->answer,
        ])->values()->all();
    }

    $existingList = $allFaqs->map(fn ($f) => [
        'id' => (int) $f->id,
        'question' => (string) $f->question,
        'answer' => (string) $f->answer,
    ])->values()->all();
@endphp

<input type="hidden" name="page_faqs_present" value="1">
<input type="hidden" name="faq_ids_present" value="1">

<div x-data="{
    faqs: @js($currentFaqs),
    existingLibrary: @js($existingList),

    add() {
        this.faqs.push({
            id: null,
            question: '',
            answer: ''
        });
        this.$nextTick(() => {
            const inputs = this.$refs.faqContainer?.querySelectorAll('input[type=text]');
            if (inputs && inputs.length) {
                inputs[inputs.length - 1].focus();
            }
        });
    },

    remove(i) {
        this.faqs.splice(i, 1);
    },

    move(i, dir) {
        const target = i + dir;
        if (target >= 0 && target < this.faqs.length) {
            const temp = this.faqs[i];
            this.faqs[i] = this.faqs[target];
            this.faqs[target] = temp;
        }
    },

    addExisting(id) {
        id = parseInt(id, 10);
        const found = this.existingLibrary.find(f => f.id === id);
        if (found) {
            // Check if already in list
            if (this.faqs.some(f => f.id === id)) {
                alert('This FAQ is already in this page\'s list.');
                return;
            }
            this.faqs.push({
                id: found.id,
                question: found.question,
                answer: found.answer
            });
        }
    }
}">
    <!-- Header -->
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-line bg-mist/50 p-3.5">
        <div>
            <p class="text-sm font-semibold text-ink">Page FAQs</p>
            <p class="text-xs text-slate-500">Add and edit questions & answers for this page directly below.</p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" @click="add()" class="a-btn a-btn-primary a-btn-sm flex items-center gap-1.5 shadow-sm">
                <x-icon name="plus" class="size-3.5" />
                <span>+ Add FAQ</span>
            </button>
        </div>
    </div>

    <!-- FAQ Items List -->
    <div class="space-y-3" x-ref="faqContainer">
        <template x-for="(faq, i) in faqs" :key="faq.id ? ('faq-' + faq.id) : ('idx-' + i)">
            <div class="rounded-xl border border-line bg-white p-4 shadow-2xs transition hover:border-slate-300">
                <!-- Row Top Bar -->
                <div class="mb-3 flex items-center justify-between border-b border-line/60 pb-2.5">
                    <div class="flex items-center gap-2">
                        <span class="flex flex-col">
                            <button type="button" @click="move(i, -1)" class="text-slate-400 hover:text-ink text-xs leading-none p-0.5" title="Move Up">▲</button>
                            <button type="button" @click="move(i, 1)" class="text-slate-400 hover:text-ink text-xs leading-none p-0.5" title="Move Down">▼</button>
                        </span>
                        <span class="rounded bg-brand/10 px-2 py-0.5 text-xs font-semibold text-brand" x-text="'FAQ #' + (i + 1)"></span>
                        <template x-if="faq.id">
                            <span class="text-[11px] text-slate-400 font-mono" x-text="'ID #' + faq.id"></span>
                        </template>
                    </div>
                    <button type="button" @click="remove(i)" class="inline-flex items-center gap-1 text-xs font-medium text-red-500 hover:text-red-700">
                        <x-icon name="x" class="size-3.5" />
                        <span>Remove</span>
                    </button>
                </div>

                <!-- Form Fields -->
                <input type="hidden" :name="'page_faqs[' + i + '][id]'" :value="faq.id || ''">
                
                <div class="space-y-3">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700">Question <span class="text-red-500">*</span></label>
                        <input type="text" 
                               class="a-input text-sm font-medium" 
                               :name="'page_faqs[' + i + '][question]'" 
                               x-model="faq.question" 
                               placeholder="e.g. How does your service work?">
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700">Answer <span class="text-red-500">*</span></label>
                        <textarea rows="3" 
                                  class="a-input text-sm" 
                                  :name="'page_faqs[' + i + '][answer]'" 
                                  x-model="faq.answer" 
                                  placeholder="Enter the detailed answer here..."></textarea>
                    </div>
                </div>
            </div>
        </template>

        <!-- Empty State -->
        <div x-show="! faqs.length" class="rounded-xl border border-dashed border-line p-8 text-center">
            <p class="text-sm font-medium text-slate-600">No FAQs added to this page yet.</p>
            <p class="mt-1 text-xs text-slate-400">Click below to add your first question and answer.</p>
            <button type="button" @click="add()" class="mt-4 a-btn a-btn-primary a-btn-sm inline-flex items-center gap-1.5">
                <x-icon name="plus" class="size-3.5" />
                <span>+ Add First FAQ</span>
            </button>
        </div>
    </div>

    <!-- Bottom Actions -->
    <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-line/70 pt-3.5">
        <button type="button" @click="add()" class="a-btn a-btn-primary a-btn-sm flex items-center gap-1.5">
            <x-icon name="plus" class="size-3.5" />
            <span>+ Add Another FAQ</span>
        </button>

        @if($allFaqs->isNotEmpty())
            <div class="flex items-center gap-2">
                <select @change="if($event.target.value) { addExisting($event.target.value); $event.target.value = ''; }" class="a-input text-xs w-auto max-w-xs">
                    <option value="">+ Or pick an existing FAQ from library...</option>
                    @foreach($allFaqs as $af)
                        <option value="{{ $af->id }}">{{ \Illuminate\Support\Str::limit($af->question, 60) }}</option>
                    @endforeach
                </select>
            </div>
        @endif
    </div>
</div>
