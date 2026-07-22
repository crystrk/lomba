<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BulkStoreParticipantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('admin-access');
    }

    public function rules(): array
    {
        return [
            'raw_names' => ['required', 'string', 'max:20000'],
        ];
    }
}
