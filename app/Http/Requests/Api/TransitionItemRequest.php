<?php

namespace App\Http\Requests\Api;

use App\Enums\ItemStatus;
use App\Models\Item;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransitionItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $item = $this->route('item');

        return $item instanceof Item && ($this->user()?->can('transition', $item) ?? false);
    }

    /**
     * Allowed: open → in_progress → done → closed, and back to open from any status.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(ItemStatus::class)],
        ];
    }
}
