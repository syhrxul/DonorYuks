<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBloodRequestRequest extends FormRequest
{
    /**
     * Izinkan request hanya untuk pengguna terautentikasi.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Aturan validasi pembuatan permohonan darah baru.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'patient_name' => ['required', 'string', 'max:255'],
            'blood_type' => ['required', Rule::in(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])],
            'bags_needed' => ['required', 'integer', 'min:1', 'max:100'],
            'hospital_name' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'urgency_level' => ['required', Rule::in(['normal', 'urgent', 'critical'])],
            'medical_reference_proof' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
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
            'blood_type.in' => 'Golongan darah tidak valid.',
            'urgency_level.in' => 'Tingkat urgensi tidak valid.',
            'medical_reference_proof.mimes' => 'Bukti medis harus berformat PDF, JPG, atau PNG.',
            'medical_reference_proof.max' => 'Ukuran bukti medis maksimal 5MB.',
        ];
    }
}
