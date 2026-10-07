<?php

namespace App\Http\Requests\Api;

use App\Actions\Items\ItemRules;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;

class StoreItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project instanceof Project && ($this->user()?->can('createItem', $project) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Project $project */
        $project = $this->route('project');
        $rules = ItemRules::for($project);
        // Defaults to medium.
        $rules['priority'][0] = 'sometimes';

        return $rules;
    }
}
