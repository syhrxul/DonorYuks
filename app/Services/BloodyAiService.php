<?php

namespace App\Services;

use App\Models\ChatHistory;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Layanan AI Chatbot "Bloody".
 *
 * Menghubungkan pertanyaan pengguna ke API LLM (OpenAI / Gemini) dengan
 * injeksi System Prompt agar Bloody merespons sebagai asisten kesehatan
 * donor yang edukatif. Jika provider tidak dikonfigurasi (belum ada API key)
 * atau API gagal diakses, layanan ini kembali ke mode mock yang aman.
 * Setiap percakapan disimpan sebagai riwayat agar pengguna dapat menelusurinya.
 */
class BloodyAiService
{
    /**
     * Mengirimkan pertanyaan pengguna ke AI, menyimpan, lalu mengembalikan riwayat.
     */
    public function chat(User $user, string $message): ChatHistory
    {
        $provider = (string) config('donoryuks.ai.provider', 'mock');
        $answer = $this->fallbackAnswer($message);

        if ($provider !== 'mock' && $this->hasCredentials($provider)) {
            try {
                $answer = match ($provider) {
                    'openai' => $this->chatWithOpenAi($message),
                    'gemini' => $this->chatWithGemini($message),
                    default => $this->fallbackAnswer($message),
                };
            } catch (ConnectionException $e) {
                Log::warning('Bloody AI provider unreachable.', ['provider' => $provider, 'error' => $e->getMessage()]);
            } catch (\Throwable $e) {
                Log::error('Bloody AI provider error.', ['provider' => $provider, 'error' => $e->getMessage()]);
            }
        }

        return ChatHistory::create([
            'user_id' => $user->id,
            'message' => $message,
            'answer' => $answer,
            'provider' => $provider,
        ]);
    }

    /**
     * Riwayat percakapan Bloody milik pengguna (terbaru dahulu).
     */
    public function history(User $user)
    {
        return $user->chatHistories()
            ->latest()
            ->paginate((int) request('per_page', 15));
    }

    /**
     * Memanggil OpenAI Chat Completions API.
     */
    private function chatWithOpenAi(string $message): string
    {
        $response = Http::timeout(config('donoryuks.ai.timeout'))
            ->withToken((string) config('donoryuks.ai.openai.api_key'))
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('donoryuks.ai.openai.model'),
                'messages' => [
                    ['role' => 'system', 'content' => $this->systemPrompt()],
                    ['role' => 'user', 'content' => $message],
                ],
                'temperature' => 0.7,
            ])
            ->throw();

        return (string) $response->json('choices.0.message.content');
    }

    /**
     * Memanggil Google Gemini generateContent API.
     */
    private function chatWithGemini(string $message): string
    {
        $model = (string) config('donoryuks.ai.gemini.model');
        $apiKey = (string) config('donoryuks.ai.gemini.api_key');

        $response = Http::timeout(config('donoryuks.ai.timeout'))
            ->withQueryParameters(['key' => $apiKey])
            ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", [
                'system_instruction' => ['parts' => [['text' => $this->systemPrompt()]]],
                'contents' => [['parts' => [['text' => $message]]]],
            ])
            ->throw();

        return (string) $response->json('candidates.0.content.parts.0.text');
    }

    /**
     * System Prompt yang mengatur persona Bloody.
     */
    private function systemPrompt(): string
    {
        return (string) config('donoryuks.ai.system_prompt');
    }

    /**
     * Mengecek apakah kredensial provider tersedia.
     */
    private function hasCredentials(string $provider): bool
    {
        $apiKey = config("donoryuks.ai.{$provider}.api_key");

        return is_string($apiKey) && $apiKey !== '';
    }

    /**
     * Jawaban pendidikan statis saat mode mock / API tidak tersedia.
     */
    private function fallbackAnswer(string $message): string
    {
        $topic = strtolower($message);

        return match (true) {
            str_contains($topic, 'hb') || str_contains($topic, 'hemoglobin') || str_contains($topic, 'kadar') => 'Halo, saya Bloody! 😊 Kadar hemoglobin (HB) donor biasanya harus berada di atas nilai minimal standar (umumnya 12,5 g/dL untuk wanita dan 13 g/dL untuk pria). '.
                'Bila HB Anda di bawah ambang, sebaiknya perbanyak asupan zat besi (bayam, daging merah, hati ayam) dan istirahat cukup sebelum donor. '.
                'Untuk kepastian syarat terbaru, silakan cek langsung ke petugas PMI/rumah sakit ya.',

            str_contains($topic, 'haid') || str_contains($topic, 'menstruasi') => 'Hai! Saya Bloody 👋 Umumnya donor darah tetap boleh dilakukan saat sedang haid, selama kondisi badan prima dan tidak merasa lemas/nyeri yang mengganggu. '.
                'Karena tubuh kehilangan darah saat haid, pastikan HB tetap normal dan cukup hidrasi sebelum mendonor. Bila ragu, konsultasikan dengan petugas medis ya.',

            str_contains($topic, 'jeda') || str_contains($topic, 'jarak') || str_contains($topic, 'berapa kali') => 'Halo! Saya Bloody 🙂 Interval minimal antar donor darah umumnya 2-3 bulan (sekitar 90 hari) untuk menjaga kesehatan pendonor. '.
                'Jika Anda baru saja donor, tunggu sampai jeda tersebut sebelum donor berikutnya ya. Tetap jaga pola makan sehat di antara periode donor.',

            str_contains($topic, 'nutrisi') || str_contains($topic, 'makan') || str_contains($topic, 'sarapan') => 'Halo! Saya Bloody 💪 Sebelum donor darah, konsumsi makanan bergizi (karbohidrat + protein), jangan donor dalam keadaan perut kosong, dan minum air putih yang cukup (minimal 500 ml). '.
                'Setelah donor, beri jeda sejenak, makan camilan manis/bergizi, dan hindari aktivitas berat selama beberapa jam. Semangat mendonor!',

            default => 'Halo, saya Bloody! 👋 Saya asisten kesehatan pendamping pendonor darah dari DonorYuks. '.
                'Saya bisa bantu menjawab seputar kondisi HB, jadwal jeda donor, donor saat haid, atau nutrisi sebelum/sesudah donor. '.
                'Silakan tanyakan, ya! Kalau kondisi Anda ragu atau serius, jangan lupa konsultasi ke petugas medis/PMI.',
        };
    }
}
