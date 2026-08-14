<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BloodyChatRequest extends FormRequest
{
    /**
     * Izinkan request hanya untuk pengguna terautentikasi.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Aturan validasi input chat Bloody.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'min:1', 'max:2000'],
        ];
    }

    /**
     * Pesan kustom untuk validasi.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'message.required' => 'Pertanyaan tidak boleh kosong.',
            'message.max' => 'Pertanyaan terlalu panjang (maksimal 2000 karakter).',
        ];
    }
}
