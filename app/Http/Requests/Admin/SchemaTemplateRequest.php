<?php

namespace App\Http\Requests\Admin;

use App\Models\SchemaTemplate;
use App\Services\SchemaService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SchemaTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('schema.edit');
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::in(SchemaTemplate::TYPES)],
            'applies_to' => ['required', Rule::in(SchemaTemplate::APPLIES_TO)],
            'json_template' => ['required', 'string', 'max:20000', function ($a, $v, $fail) {
                if ($e = SchemaService::validateJson($v)) {
                    $fail($e);
                }
            }],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
