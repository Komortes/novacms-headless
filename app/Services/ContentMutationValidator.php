<?php

namespace App\Services;

use App\Models\Content;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ContentMutationValidator
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function validateForCreate(array $input): array
    {
        $normalized = $this->normalize($input);
        $locale = (string) ($normalized['locale'] ?? 'en');

        $validator = Validator::make($normalized, [
            'type' => ['required', Rule::in(['post', 'page'])],
            'slug' => [
                'required',
                'string',
                'max:160',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('contents', 'slug')->where(fn ($query) => $query->where('locale', $locale)),
            ],
            'title' => ['required', 'string', 'min:3', 'max:200'],
            'body' => ['required', 'string', 'min:1', 'max:100000'],
            'locale' => ['required', 'string', 'max:10', 'regex:/^[a-z]{2}(?:-[A-Z]{2})?$/'],
            'status' => ['nullable', Rule::in(['draft', 'published', 'archived'])],
        ]);

        return $validator->validate();
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function validateForUpdate(Content $content, array $input): array
    {
        $normalized = $this->normalize($input);
        $locale = (string) ($normalized['locale'] ?? $content->locale);

        $validator = Validator::make($normalized, [
            'type' => ['sometimes', Rule::in(['post', 'page'])],
            'slug' => [
                'sometimes',
                'string',
                'max:160',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('contents', 'slug')
                    ->ignore($content->id)
                    ->where(fn ($query) => $query->where('locale', $locale)),
            ],
            'title' => ['sometimes', 'string', 'min:3', 'max:200'],
            'body' => ['sometimes', 'string', 'min:1', 'max:100000'],
            'locale' => ['sometimes', 'string', 'max:10', 'regex:/^[a-z]{2}(?:-[A-Z]{2})?$/'],
            'status' => ['sometimes', Rule::in(['draft', 'published', 'archived'])],
        ]);

        return $validator->validate();
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function normalize(array $input): array
    {
        foreach (['slug', 'title', 'body', 'locale', 'status', 'type'] as $field) {
            if (isset($input[$field]) && is_string($input[$field])) {
                $input[$field] = trim($input[$field]);
            }
        }

        return $input;
    }
}
