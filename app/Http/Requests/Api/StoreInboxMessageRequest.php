<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreInboxMessageRequest extends FormRequest
{
    /**
     * Membership and the inbox:write ability are checked once the project is known.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            /** Project slug or key. */
            'project' => ['required', 'string'],
            /** The transcription. */
            'text' => ['required', 'string', 'max:20000'],
            /** Free-text name of the person reporting. */
            'reporter' => ['nullable', 'string', 'max:255'],
        ];
    }
}
