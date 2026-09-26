<?php

use Illuminate\Support\Facades\Http;

test('halaman utama berhasil dirender saat belum ada file yang diunggah', function () {
    $response = $this->get(route('review.index'));

    $response->assertStatus(200);
    $response->assertSee('Pemeriksaan Formulir ROMANTIK');
    $response->assertSee('Pilih Berkas JSON Formulir ROMANTIK');
    $response->assertSee('Unggah Berkas Formulir');
    $response->assertDontSee('Pratinjau Formulir Terunggah');
    $response->assertDontSee('Hasil Pemeriksaan Lengkap');
});

test('halaman berhasil dirender saat preview formulir tersedia di sesi', function () {
    $sessionData = [
        'romantik_json' => json_encode(['id_trans' => 'TRX-123', 'form' => '{}']),
        'preview' => [
            'id_trans' => 'TRX-123',
            'nama_kegiatan' => 'Kompilasi Data Uji',
            'tahun_kegiatan' => '2026',
            'instansi' => 'Badan Pusat Statistik',
            'file_name' => 'sample_uji.json',
        ],
    ];

    $response = $this->withSession($sessionData)->get(route('review.index'));

    $response->assertStatus(200);
    $response->assertSee('Formulir siap diperiksa');
    $response->assertSee('Kompilasi Data Uji');
    $response->assertSee('Badan Pusat Statistik');
    $response->assertSee('2026');
    $response->assertSee('TRX-123');
    $response->assertSee('Jalankan Pemeriksaan');
    $response->assertSee('Ganti berkas');
    $response->assertDontSee('Hasil Pemeriksaan Lengkap');
});

test('halaman berhasil dirender saat hasil pemeriksaan tersedia dan seluruh finding tetap tampil tanpa memanggil FastAPI', function () {
    Http::fake();

    $mockReviewResult = [
        'status' => 'success',
        'metadata' => [
            'id_trans' => 'TRX-555',
            'nama_kegiatan' => 'Survei Uji Lengkap',
            'tahun_kegiatan' => '2026',
            'instansi' => 'BPS RI',
        ],
        'rbs' => [
            'n_findings' => 3,
            'n_not_evaluable' => 15,
            'findings' => [
                [
                    'rule_id' => 'R-VI-05',
                    'severity' => 'warning',
                    'message' => 'Temuan pertama tentang supervisor.',
                    'evidence' => ['key1' => 'val1'],
                ],
                [
                    'rule_id' => 'R-VI-02',
                    'severity' => 'warning',
                    'message' => 'Temuan kedua tentang CAPI/CAWI.',
                    'evidence' => ['key2' => 'val2'],
                ],
                [
                    'rule_id' => 'R-II-02',
                    'severity' => 'error',
                    'message' => 'Temuan ketiga tentang nomor telepon.',
                    'evidence' => ['key3' => 'val3'],
                ],
            ],
        ],
        'hybrid_review' => '<p>Evaluasi model AI.</p>',
        'processing_time_seconds' => 5.23,
    ];

    $sessionData = [
        'romantik_json' => json_encode(['id_trans' => 'TRX-555', 'form' => '{}']),
        'preview' => [
            'id_trans' => 'TRX-555',
            'nama_kegiatan' => 'Survei Uji Lengkap',
            'tahun_kegiatan' => '2026',
            'instansi' => 'BPS RI',
            'file_name' => 'sample_uji.json',
        ],
        'review_result' => $mockReviewResult,
    ];

    $response = $this->withSession($sessionData)->get(route('review.index'));

    $response->assertStatus(200);

    // Pastikan FastAPI tidak pernah dipanggil
    Http::assertNothingSent();

    // Pastikan Hasil Pemeriksaan Lengkap tampil
    $response->assertSee('Hasil Pemeriksaan Lengkap');
    $response->assertSee('Temuan RBS');
    $response->assertSee('Belum Dapat Dievaluasi');
    $response->assertSee('Waktu Proses');
    $response->assertSee('5.23');

    // Pastikan seluruh finding tampil dengan penomoran
    $response->assertSee('Temuan #1');
    $response->assertSee('R-VI-05');
    $response->assertSee('Temuan pertama tentang supervisor.');

    $response->assertSee('Temuan #2');
    $response->assertSee('R-VI-02');
    $response->assertSee('Temuan kedua tentang CAPI/CAWI.');

    $response->assertSee('Temuan #3');
    $response->assertSee('R-II-02');
    $response->assertSee('Temuan ketiga tentang nomor telepon.');

    // Pastikan Catatan Hybrid AI tampil
    $response->assertSee('Catatan Pemeriksaan Hybrid AI');
    $response->assertSee('<p>Evaluasi model AI.</p>', false);
});

test('tombol reset ganti berkas menghapus seluruh sesi upload dan hasil pemeriksaan lalu kembali ke halaman awal', function () {
    $sessionData = [
        'romantik_json' => json_encode(['id_trans' => 'TRX-RESET']),
        'preview' => [
            'id_trans' => 'TRX-RESET',
            'nama_kegiatan' => 'Kegiatan Yang Akan Direset',
            'tahun_kegiatan' => '2026',
            'instansi' => 'Instansi',
            'file_name' => 'reset.json',
        ],
        'review_result' => [
            'status' => 'success',
            'rbs' => ['n_findings' => 1, 'n_not_evaluable' => 0, 'findings' => []],
        ],
    ];

    $response = $this->withSession($sessionData)->get(route('review.reset'));

    $response->assertRedirect(route('review.index'));
    $response->assertSessionMissing('preview');
    $response->assertSessionMissing('romantik_json');
    $response->assertSessionMissing('review_result');
    $response->assertSessionMissing('romantik_data');

    // Mengunjungi halaman setelah reset menampilkan formulir unggah baru yang bersih
    $followed = $this->get(route('review.index'));
    $followed->assertStatus(200);
    $followed->assertSee('Pilih Berkas JSON Formulir ROMANTIK');
    $followed->assertDontSee('Formulir yang Diperiksa');
    $followed->assertDontSee('Hasil Pemeriksaan Lengkap');
});

test('logo dan judul pada header berupa tautan yang mengarah ke halaman utama review.index', function () {
    $response = $this->get(route('review.index'));

    $response->assertStatus(200);
    $response->assertSee('href="'.route('review.index').'"', false);
    $response->assertSee('ROMANTIK Web');
});
