<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Query-string contract for the capsule listing.
 *
 * Pagination is opt-in so that existing clients, which expect the whole
 * collection under `data`, keep working unchanged.
 */
class IndexMessageCapsuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:'.config('capsules.max_per_page', 100)],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * @return int|null null means "return the full collection"
     */
    public function perPage(): ?int
    {
        if ($this->filled('per_page')) {
            return (int) $this->input('per_page');
        }

        if ($this->filled('page')) {
            return (int) config('capsules.default_per_page', 15);
        }

        return null;
    }
}
