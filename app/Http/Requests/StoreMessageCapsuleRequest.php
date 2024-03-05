<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMessageCapsuleRequest extends FormRequest
{
    /**
     * Authorisation is the policy's job; the controller calls it explicitly so
     * that the failure is a 403 with a message rather than a bare form-request
     * rejection.
     */
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
            // A capsule with nothing in it is not a capsule.
            'note' => ['required', 'string', 'min:1', 'max:'.config('capsules.max_note_length', 5000)],

            // Must be an instant in the future. Clients should send ISO-8601
            // with an offset ("2024-03-01T11:00:00Z"); a value with no offset
            // is read as APP_TIMEZONE, which is UTC by default.
            'scheduled_opening_time' => ['required', 'date', 'after:now'],

            // Sealing is the only way to create a capsule — it can never be
            // born open.
            'is_opened' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'scheduled_opening_time.after' => 'The opening time must be in the future.',
            'is_opened.prohibited' => 'A capsule cannot be created already open.',
        ];
    }
}
