<?php

namespace App\Http\Requests\Api;

use App\Actions\Items\ItemRules;
use App\Models\Item;
use Illuminate\Foundation\Http\FormRequest;

class UpdateItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $item = $this->route('item');

        return $item instanceof Item && ($this->user()?->can('update', $item) ?? false);
    }

    /**
     * Status is not changed here; use the transition endpoint.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Item $item */
        $item = $this->route('item');

        return array_map(fn (array $rules) => ['sometimes', ...$rules], ItemRules::for($item->project));
    }
}
