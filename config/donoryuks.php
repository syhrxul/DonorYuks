<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Konfigurasi AI Chatbot "Bloody"
    |--------------------------------------------------------------------------
    |
    | Provider yang didukung: openai, gemini, mock.
    | Mode "mock" menghasilkan jawaban edukasi statis sehingga API tetap
    | dapat digunakan saat API key belum dikonfigurasi.
    |
    */

    'ai' => [
        'provider' => env('AI_PROVIDER', 'mock'),
        'timeout' => 30,

        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        ],

        'gemini' => [
            'api_key' => env('GEMINI_API_KEY'),
            'model' => env('GEMINI_MODEL', 'gemini-1.5-flash'),
        ],

        // System Prompt yang diinjeksi ke LLM agar "Bloody" berperan sebagai
        // asisten kesehatan pendamping pendonor darah yang edukatif & ramah.
        'system_prompt' => <<<'PROMPT'
Kamu adalah "Bloody", asisten kesehatan pendamping pendonor darah yang edukatif, ramah, dan tepercaya dari aplikasi DonorYuks.

Panduan perilaku:
- Selalu jawab dalam Bahasa Indonesia yang mudah dipahami dan bersahabat.
- Fokus menjawab topik seputar donor darah: kondisi HB (hemoglobin), jadwal jeda donor, donor saat haid, nutrisi sebelum/sesudah donor, efek samping ringan, dan syarat donor.
- Berikan edukasi yang akurat namun sederhana; gunakan poin atau daftar singkat bila membantu.
- Jika ditanya di luar cakupan donor darah, arahkan kembali dengan ramah ke topik donor darah.
- Jika informasi medis serius tidak dapat dipastikan, sarankan pengguna berkonsultasi dengan petugas medis / PMI.
- Jangan pernah memberikan diagnosis medis yang pasti; selalu dorong konsultasi dengan tenaga medis.
- Jaga nada tetap hangat, peduli, dan memotivasi calon pendonor.
PROMPT,
    ],
];
