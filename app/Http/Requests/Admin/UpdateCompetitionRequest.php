<?php

namespace App\Http\Requests\Admin;

use App\Enums\CompetitionFormat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateCompetitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $competition = $this->route('competition');

        return Gate::allows('update', $competition);
    }

    public function rules(): array
    {
        $formats = array_map(fn (CompetitionFormat $f) => $f->value, CompetitionFormat::cases());
        $isKnockout = $this->input('format') === 'knockout';

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'format' => ['sometimes', 'required', Rule::in($formats)],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'win_points' => ['nullable', Rule::when($isKnockout, ['prohibited'], ['required_with:format', 'integer'])],
            'draw_points' => ['nullable', Rule::when($isKnockout, ['prohibited'], ['required_with:format', 'integer'])],
            'loss_points' => ['nullable', Rule::when($isKnockout, ['prohibited'], ['required_with:format', 'integer'])],
        ];
    }

    public function messages(): array
    {
        return [
            'win_points.required_with' => 'Poin menang wajib diisi untuk format klasemen.',
            'draw_points.required_with' => 'Poin seri wajib diisi untuk format klasemen.',
            'loss_points.required_with' => 'Poin kalah wajib diisi untuk format klasemen.',
            'win_points.integer' => 'Poin menang harus berupa angka.',
            'draw_points.integer' => 'Poin seri harus berupa angka.',
            'loss_points.integer' => 'Poin kalah harus berupa angka.',
        ];
    }
}
