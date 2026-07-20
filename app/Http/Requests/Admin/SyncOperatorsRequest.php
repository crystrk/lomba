<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SyncOperatorsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'operator_ids' => ['present', 'array'],
            'operator_ids.*' => ['integer', 'exists:users,id'],
        ];
    }
}
