<?php

namespace App\Models;

use Database\Factories\DonorEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'title',
    'organizer',
    'description',
    'location_name',
    'latitude',
    'longitude',
    'event_date',
    'quota',
])]
class DonorEvent extends Model
{
    /** @use HasFactory<DonorEventFactory> */
    use HasFactory;

    /**
     * Mengonversi atribut menjadi tipe data tertentu.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'event_date' => 'datetime',
            'quota' => 'integer',
        ];
    }

    /**
     * Donasi yang terdaftar pada event ini.
     */
    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }
}
