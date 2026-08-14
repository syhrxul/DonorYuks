<?php

namespace Database\Seeders;

use App\Models\Post;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    /**
     * Membuat contoh artikel edukasi & berita seputar donor darah.
     */
    public function run(): void
    {
        $posts = [
            [
                'title' => 'Syarat dan Ketentuan Donor Darah',
                'slug' => 'syarat-dan-ketentuan-donor-darah',
                'category' => 'edukasi',
                'excerpt' => 'Apa saja persyaratan sebelum mendonorkan darah? Simak panduan lengkapnya.',
                'content' => "Sebelum mendonorkan darah, ada beberapa syarat dasar yang perlu diperhatikan:\n\n1. Sehat jasmani dan rohani.\n2. Usia 17-60 tahun (berat badan minimal 45 kg).\n3. Tekanan darah normal dan kadar HB sesuai standar.\n4. Jarak donor terakhir minimal 2-3 bulan.\n\nPastikan juga beristirahat cukup dan tidak dalam kondisi demam agar proses donor berjalan lancar.",
                'author' => 'PMI & DonorYuks',
            ],
            [
                'title' => 'Mengapa Golongan Darah O- Disebut Donor Universal?',
                'slug' => 'mengapa-golongan-darah-o-minus-donor-universal',
                'category' => 'edukasi',
                'excerpt' => 'O- dapat didonorkan ke semua golongan darah. Kenapa demikian?',
                'content' => "Golongan darah O- disebut donor universal karena sel darah merahnya tidak memiliki antigen A, B, maupun faktor Rhesus (Rh).\n\nAkibatnya, darah O- tidak akan memicu reaksi penolakan saat diterima oleh penerima golongan darah apa pun, menjadikannya sangat penting dalam kondisi darurat.",
                'author' => 'DonorYuks',
            ],
            [
                'title' => 'Tips Nutrisi Sebelum dan Sesudah Donor Darah',
                'slug' => 'tips-nutrisi-sebelum-sesudah-donor-darah',
                'category' => 'tips',
                'excerpt' => 'Jaga stamina dan percepat pemulihan dengan pola makan yang tepat.',
                'content' => "Sebelum donor:\n- Makan makanan bergizi 2-3 jam sebelum donor.\n- Minum air putih minimal 500 ml.\n- Hindari alkohol 24 jam sebelumnya.\n\nSesudah donor:\n- Konsumsi camilan manis atau jus untuk mengembalikan energi.\n- Perbanyak asupan zat besi, seperti daging merah, bayam, dan kacang-kacangan.",
                'author' => 'DonorYuks',
            ],
            [
                'title' => 'Program Donor Darah Nasional Merdeka di Jakarta',
                'slug' => 'program-donor-darah-nasional-merdeka-jakarta',
                'category' => 'berita',
                'excerpt' => 'Ribuan pendonor diharapkan hadir pada event donor bulanan mendatang.',
                'content' => "PMI bekerja sama dengan DonorYuks menggelar Program Donor Darah Nasional di Jakarta. Event ini bertujuan memenuhi stok darah dan sekaligus mengedukasi generasi muda akan pentingnya donor darah.\n\nPendaftaran dapat dilakukan melalui aplikasi DonorYuks pada menu Event Donor, dengan kuota terbatas.",
                'author' => 'DonorYuks News',
            ],
        ];

        foreach ($posts as $post) {
            Post::updateOrCreate(['slug' => $post['slug']], [
                ...$post,
                'published_at' => now()->subDays(rand(1, 10)),
            ]);
        }

        Post::factory()->count(5)->create();
    }
}
