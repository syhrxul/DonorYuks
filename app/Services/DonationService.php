<?php

namespace App\Services;

use App\Models\Donation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Layanan bisnis modul donasi: verifikasi penyelesaian donor
 * yang menambahkan poin dan memperbarui jumlah kantong terpenuhi.
 */
class DonationService
{
    /**
     * Jumlah poin yang diperoleh pendonor per donasi terselesaikan.
     */
    public const POINTS_PER_DONATION = 50;

    /**
     * Memverifikasi donasi telah selesai dilakukan.
     * Update status donasi, tambah poin pendonor, dan update jumlah kantong.
     *
     * @return array{donation: Donation, points_earned: int}
     */
    public function complete(User $user, Donation $donation): array
    {
        if ($donation->donor_id !== $user->id) {
            throw new RuntimeException('Donasi ini bukan milik Anda.');
        }

        if ($donation->status !== 'confirmed') {
            throw new RuntimeException('Donasi hanya dapat diselesaikan dari status confirmed.');
        }

        return DB::transaction(function () use ($user, $donation) {
            $donation->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            $user->increment('points', self::POINTS_PER_DONATION);
            $user->update(['last_donated_at' => now()]);

            if ($donation->type === 'personal' && ! is_null($donation->blood_request_id)) {
                $this->fulfillBloodRequest($donation);
            }

            $donation->refresh();

            return [
                'donation' => $donation,
                'points_earned' => self::POINTS_PER_DONATION,
            ];
        });
    }

    /**
     * Membatalkan donasi yang belum/baru dikonfirmasi.
     * Jika permohonan terkait tidak lagi memiliki donor aktif,
     * status permohonan dikembalikan ke "open" agar bisa dicari donor lain.
     *
     * @throws RuntimeException
     */
    public function cancel(User $user, Donation $donation): Donation
    {
        if ($donation->donor_id !== $user->id) {
            throw new RuntimeException('Donasi ini bukan milik Anda.');
        }

        if (! in_array($donation->status, ['matched', 'confirmed'], true)) {
            throw new RuntimeException('Donasi yang sudah selesai atau dibatalkan tidak dapat dibatalkan.');
        }

        $donation->update(['status' => 'cancelled']);

        if ($donation->type === 'personal' && ! is_null($donation->blood_request_id)) {
            $this->reopenBloodRequestIfUnmatched($donation);
        }

        return $donation->refresh();
    }

    /**
     * Mengembalikan permohonan ke "open" bila tidak ada lagi donor aktif.
     */
    private function reopenBloodRequestIfUnmatched(Donation $donation): void
    {
        $bloodRequest = $donation->bloodRequest;

        if (is_null($bloodRequest) || $bloodRequest->status !== 'matched') {
            return;
        }

        $hasActiveDonor = Donation::where('blood_request_id', $bloodRequest->id)
            ->whereIn('status', ['confirmed', 'completed'])
            ->exists();

        if (! $hasActiveDonor) {
            $bloodRequest->update(['status' => 'open']);
        }
    }

    /**
     * Menambah jumlah kantong terpenuhi pada permohonan darah,
     * lalu menandai permohonan selesai bila kantong sudah terpenuhi.
     */
    private function fulfillBloodRequest(Donation $donation): void
    {
        $bloodRequest = $donation->bloodRequest;

        if (is_null($bloodRequest)) {
            return;
        }

        $bloodRequest->increment('bags_fulfilled');
        $bloodRequest->refresh();

        if ($bloodRequest->bags_fulfilled >= $bloodRequest->bags_needed) {
            $bloodRequest->update(['status' => 'completed']);
        }
    }
}
