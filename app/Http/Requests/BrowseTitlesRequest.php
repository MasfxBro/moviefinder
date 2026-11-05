<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\MediaType;
use App\Services\Catalog\TitleFilter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BrowseTitlesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $earliestYear = 1900;
        $latestYear = (int) date('Y') + 1;

        return [
            'type' => ['nullable', Rule::enum(MediaType::class)],
            'genres' => ['nullable', 'array'],
            'genres.*' => ['integer', 'exists:genres,id'],
            'year_from' => ['nullable', 'integer', "between:{$earliestYear},{$latestYear}"],
            'year_to' => [
                'nullable',
                'integer',
                "between:{$earliestYear},{$latestYear}",
                Rule::when($this->filled('year_from'), 'gte:year_from'),
            ],
            'director' => ['nullable', 'string', 'min:3', 'max:150'],
            'min_rating' => ['nullable', 'numeric', 'between:0,10'],
            'sort' => ['nullable', Rule::in(array_keys(TitleFilter::SORTS))],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
