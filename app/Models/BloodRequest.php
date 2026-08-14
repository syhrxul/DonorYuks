<?php

namespace App\Models;

use Database\Factories\BloodRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'patient_name',
    'blood_type',
    'bags_needed',
    'bags_fulfilled',
    'hospital_name',
    'latitude',
    'longitude',
    'urgency_level',
    'medical_reference_proof',
    'status',
])]
class BloodRequest extends Model
{
    /** @use HasFactory<BloodRequestFactory> */
    use HasFactory;

    /**
     * Mengonversi atribut menjadi tipe data tertentu.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'bags_needed' => 'integer',
            'bags_fulfilled' => 'integer',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    /**
     * Pemohon (pengguna) yang membuat permohonan darah.
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Donasi yang terkait dengan permohonan ini.
     */
    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }
}
