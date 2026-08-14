<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'donor_id',
    'blood_request_id',
    'donor_event_id',
    'type',
    'status',
    'ticket_code',
    'completed_at',
])]
class Donation extends Model
{
    /**
     * Mengonversi atribut menjadi tipe data tertentu.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Pendonor yang melakukan donasi.
     */
    public function donor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'donor_id');
    }

    /**
     * Permohonan darah terkait (jika tipe personal).
     */
    public function bloodRequest(): BelongsTo
    {
        return $this->belongsTo(BloodRequest::class);
    }

    /**
     * Event donor terkait (jika tipe event).
     */
    public function donorEvent(): BelongsTo
    {
        return $this->belongsTo(DonorEvent::class);
    }
}
