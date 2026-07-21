<?php

namespace App\Http\Requests\Admin;

use App\Models\Participant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateParticipantRequest extends FormRequest
{
    public function authorize(): bool
    {
        $competition = $this->route('competition');

        return Gate::allows('update', $competition);
    }

    public function rules(): array
    {
        $competition = $this->route('competition');
        $participant = $this->route('participant');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail) use ($competition, $participant): void {
                    $normalized = str($value)->lower();
                    $exists = Participant::where('competition_id', $competition->id)
                        ->where('normalized_name', $normalized)
                        ->where('id', '!=', $participant->id)
                        ->exists();

                    if ($exists) {
                        $fail('Nama peserta sudah ada di lomba ini.');
                    }
                },
            ],
            'short_name' => ['nullable', 'string', 'max:10'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama peserta wajib diisi.',
            'logo.image' => 'Logo harus berupa gambar.',
            'logo.mimes' => 'Logo harus berformat PNG, JPG, JPEG, atau WebP.',
            'logo.max' => 'Logo maksimal 2MB.',
        ];
    }
}
