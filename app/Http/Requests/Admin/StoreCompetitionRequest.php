<?php

namespace App\Http\Requests\Admin;

use App\Enums\CompetitionFormat;
use App\Enums\CompetitionSport;
use App\Models\Competition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreCompetitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', Competition::class);
    }

    public function rules(): array
    {
        $formats = array_map(fn (CompetitionFormat $f) => $f->value, CompetitionFormat::cases());
        $isKnockout = $this->input('format') === 'knockout';

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sport' => ['required', Rule::enum(CompetitionSport::class)],
            'format' => ['required', Rule::in($formats)],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'win_points' => [Rule::when($isKnockout, ['prohibited'], ['required', 'integer'])],
            'draw_points' => [Rule::when($isKnockout, ['prohibited'], ['required', 'integer'])],
            'loss_points' => [Rule::when($isKnockout, ['prohibited'], ['required', 'integer'])],
        ];
    }

    public function messages(): array
    {
        return [
            'win_points.required' => 'Poin menang wajib diisi untuk format klasemen.',
            'draw_points.required' => 'Poin seri wajib diisi untuk format klasemen.',
            'loss_points.required' => 'Poin kalah wajib diisi untuk format klasemen.',
            'win_points.integer' => 'Poin menang harus berupa angka.',
            'draw_points.integer' => 'Poin seri harus berupa angka.',
            'loss_points.integer' => 'Poin kalah harus berupa angka.',
        ];
    }
}
