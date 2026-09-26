<?php

use App\Services\RuleCatalogService;
use Illuminate\Support\Facades\Http;

test('halaman /rules dapat dibuka dengan status 200 dan navigasi header', function () {
    $response = $this->get(route('rules.index'));

    $response->assertStatus(200);
    $response->assertSee('Katalog Aturan RBS');
    $response->assertSee('Daftar aturan deterministik yang digunakan dalam pemeriksaan formulir ROMANTIK.');
    $response->assertSee('40 aturan');
    $response->assertSee('Pemeriksaan');
    $response->assertSee('Katalog Aturan RBS');
});

test('R-VI-05 tampil di halaman katalog beserta elemennya', function () {
    $response = $this->get(route('rules.index'));

    $response->assertStatus(200);
    $response->assertSee('R-VI-05');
    $response->assertSee('Perbandingan Jumlah Supervisor dan Enumerator');
    $response->assertSee('Blok VI – Pengumpulan Data');
    $response->assertSee('Logika bisnis');
    $response->assertSee('Peringatan');
    $response->assertSee('Memeriksa agar jumlah supervisor tidak melebihi enumerator.');
    $response->assertSee('Lihat Detail');
});

test('title dan description pada kartu aturan ter-escape dengan aman', function () {
    // Buat service palsu yang memiliki teks XSS untuk menguji blade escaping
    $mockService = Mockery::mock(RuleCatalogService::class);
    $mockService->shouldReceive('all')->andReturn([
        [
            'rule_id' => 'R-XSS-01',
            'title' => '<script>alert("xss-title")</script>',
            'section' => '<b onmouseover=alert(1)>Blok XSS</b>',
            'check_type' => 'Logika bisnis',
            'description' => '<img src="x" onerror="alert(\'xss-desc\')">',
            'applicability' => 'Seluruh formulir',
            'violation_condition' => 'Kondisi melanggar',
            'pass_condition' => 'Kondisi lolos',
            'not_evaluable_condition' => 'Kondisi belum dapat dinilai',
            'severity' => 'error',
            'ui_message' => 'Pesan UI',
            'main_field' => ['field_xss'],
        ],
    ]);
    $mockService->shouldReceive('sections')->andReturn(['Blok XSS']);
    $mockService->shouldReceive('metadata')->andReturn(['name' => 'Katalog Test']);
    $mockService->shouldReceive('count')->andReturn(1);
    $mockService->shouldReceive('find')->with('R-XSS-01')->andReturn([
        'rule_id' => 'R-XSS-01',
        'title' => '<script>alert("xss-title")</script>',
    ]);

    $this->app->instance(RuleCatalogService::class, $mockService);

    $response = $this->get(route('rules.index'));

    $response->assertStatus(200);
    $response->assertDontSee('<script>alert("xss-title")</script>', false);
    $response->assertDontSee('<img src="x" onerror="alert(\'xss-desc\')">', false);
    $response->assertSee(e('<script>alert("xss-title")</script>'), false);
    $response->assertSee(e('<img src="x" onerror="alert(\'xss-desc\')">'), false);
});

test('halaman katalog tidak memanggil FastAPI atau jaringan eksternal', function () {
    Http::fake();

    $response = $this->get(route('rules.index'));

    $response->assertStatus(200);
    Http::assertNothingSent();
});

test('halaman review tetap berjalan normal dengan navigasi header baru', function () {
    $response = $this->get(route('review.index'));

    $response->assertStatus(200);
    $response->assertSee('ROMANTIK Web');
    $response->assertSee('Pemeriksaan');
    $response->assertSee('Katalog Aturan RBS');
});

test('rule_id pada temuan RBS memiliki link menuju katalog aturan', function () {
    $mockReviewResult = [
        'status' => 'success',
        'rbs' => [
            'n_findings' => 1,
            'n_not_evaluable' => 0,
            'findings' => [
                [
                    'rule_id' => 'R-VI-05',
                    'severity' => 'warning',
                    'message' => 'Jumlah supervisor melebihi enumerator.',
                ],
            ],
            'not_evaluable' => [],
        ],
    ];

    $response = $this->withSession([
        'review_result' => $mockReviewResult,
    ])->get(route('review.index'));

    $response->assertStatus(200);
    $response->assertSee(route('rules.index', ['rule' => 'R-VI-05']));
    $response->assertSee('R-VI-05');
});

test('rule_id pada not_evaluable memiliki metadata katalog seperti nama dan fungsi rule', function () {
    $mockReviewResult = [
        'status' => 'success',
        'rbs' => [
            'n_findings' => 0,
            'n_not_evaluable' => 1,
            'findings' => [],
            'not_evaluable' => [
                [
                    'rule_id' => 'R-ID-03',
                    'reason' => 'activity_type_unavailable',
                    'applicability' => 'unknown',
                    'message' => 'Informasi jenis kegiatan belum cukup.',
                ],
            ],
        ],
    ];

    $response = $this->withSession([
        'review_result' => $mockReviewResult,
    ])->get(route('review.index'));

    $response->assertStatus(200);
    // Rule ID & status
    $response->assertSee('R-ID-03');
    $response->assertSee('Belum Dapat Dievaluasi');

    // Metadata dari katalog rule_catalog.json
    $response->assertSee('Pencantuman Istilah Kompilasi pada Judul');
    $response->assertSee('Apa yang diperiksa:');
    $response->assertSee("Untuk kegiatan kompilasi, judul diharapkan mencerminkan istilah 'Kompilasi Data' atau 'Kompilasi'.");

    // Alasan runtime dari respons/formatter
    $response->assertSee('Mengapa belum dapat dievaluasi:');
    $response->assertSee('Jenis kegiatan belum dapat ditentukan');
    $response->assertSee('Sistem belum memperoleh informasi yang cukup untuk menentukan jenis kegiatan statistik pada formulir ini. Akibatnya, aturan R-ID-03 belum dapat dievaluasi.');

    // Link ke detail katalog aturan
    $response->assertSee('Lihat Detail Aturan');
    $response->assertSee(route('rules.index', ['rule' => 'R-ID-03']));
});

test('bila katalog tidak memiliki rule, halaman tidak crash dan menampilkan fallback yang aman', function () {
    $mockReviewResult = [
        'status' => 'success',
        'rbs' => [
            'n_findings' => 0,
            'n_not_evaluable' => 1,
            'findings' => [],
            'not_evaluable' => [
                [
                    'rule_id' => 'R-UNKNOWN-999',
                    'reason' => 'unknown_reason_code',
                    'applicability' => 'unknown',
                    'message' => 'Custom reason text.',
                ],
            ],
        ],
    ];

    $response = $this->withSession([
        'review_result' => $mockReviewResult,
    ])->get(route('review.index'));

    $response->assertStatus(200);
    $response->assertSee('R-UNKNOWN-999');
    $response->assertSee('Informasi aturan belum tersedia di katalog.');
    $response->assertSee('Belum Dapat Dievaluasi');
    $response->assertDontSee('Lihat Detail Aturan'); // Tidak menampilkan link jika rule tidak ada di katalog
});

test('halaman /rules dengan query parameter rule memproses selectedRule tanpa error', function () {
    $response = $this->get(route('rules.index', ['rule' => 'R-VI-05']));

    $response->assertStatus(200);
    $response->assertSee('R-VI-05');
});
