<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Contracts\PlanResolver;
use App\Models\TitleRequest;
use App\Services\Ai\PlanCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreTitleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if ($user === null) {
            return false;
        }

        $plan = app(PlanResolver::class)->resolve($user);
        $limit = (int) (app(PlanCatalog::class)->get($plan)['requests_daily'] ?? 2);

        $dailyCount = TitleRequest::query()
            ->where('user_id', $user->id)
            ->where('created_at', '>=', now()->startOfDay())
            ->count();

        if ($dailyCount >= $limit) {
            abort(429, "You have reached your daily request limit of {$limit} titles.");
        }

        return true;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('title') && ! $this->has('title_text')) {
            $merge['title_text'] = $this->input('title');
        } elseif ($this->has('title_text') && ! $this->has('title')) {
            $merge['title'] = $this->input('title_text');
        }

        if ($this->has('year') && ! $this->has('release_year')) {
            $merge['release_year'] = $this->input('year');
        } elseif ($this->has('release_year') && ! $this->has('year')) {
            $merge['year'] = $this->input('release_year');
        }

        if ($this->has('type') && ! $this->has('media_type')) {
            $merge['media_type'] = $this->input('type');
        } elseif ($this->has('media_type') && ! $this->has('type')) {
            $merge['type'] = $this->input('media_type');
        }

        if (! empty($merge)) {
            $this->merge($merge);
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $maxYear = (int) date('Y') + 5;

        return [
            'title_text' => ['required', 'string', 'max:200'],
            'title' => ['sometimes', 'string', 'max:200'],
            'media_type' => ['nullable', 'string', Rule::in(['movie', 'tv'])],
            'type' => ['sometimes', 'nullable', 'string', Rule::in(['movie', 'tv'])],
            'release_year' => ['nullable', 'integer', 'between:1888,'.$maxYear],
            'year' => ['sometimes', 'nullable', 'integer', 'between:1888,'.$maxYear],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $titleText = trim((string) ($this->input('title_text') ?? $this->input('title')));
            $rawYear = $this->input('release_year') ?? $this->input('year');
            $year = filled($rawYear) ? (int) $rawYear : null;

            $query = TitleRequest::query()
                ->whereRaw('LOWER(title_text) = ?', [mb_strtolower($titleText)]);

            if ($year === null) {
                $query->whereNull('release_year');
            } else {
                $query->where('release_year', $year);
            }

            if ($query->exists()) {
                $message = 'This title and year combination has already been requested.';
                $validator->errors()->add('title', $message);
                $validator->errors()->add('title_text', $message);
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title_text.required' => 'Please provide a title.',
            'title.required' => 'Please provide a title.',
            'title_text.max' => 'The title must not exceed 200 characters.',
            'title.max' => 'The title must not exceed 200 characters.',
            'release_year.integer' => 'The release year must be a valid number.',
            'release_year.between' => 'The release year must be between 1888 and '.(date('Y') + 5).'.',
            'note.max' => 'The note must not exceed 500 characters.',
        ];
    }
}
