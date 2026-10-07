<?php

namespace App\Http\Requests\Api;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Project::class) ?? false;
    }

    /**
     * Mirrors CreateProject, which validates again and stays authoritative.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            /** Uppercase letters and digits, 2–10 characters, starting with a letter. Cannot be changed later. */
            'key' => ['required', 'string', 'max:10'],
            /** Defaults to a slug of the name. Cannot be changed later. */
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
