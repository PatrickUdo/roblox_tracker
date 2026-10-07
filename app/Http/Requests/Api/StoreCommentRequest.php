<?php

namespace App\Http\Requests\Api;

use App\Models\Item;
use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $item = $this->route('item');

        return $item instanceof Item && ($this->user()?->can('comment', $item) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            /** Markdown. */
            'body' => ['required', 'string', 'max:65535'],
        ];
    }
}
