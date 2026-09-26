<?php

use Illuminate\Support\Facades\Http;

test('n_not_evaluable > 0 menampilkan tombol Lihat detail pada kartu ringkasan', function () {
    Http::fake();

    $mockReviewResult = [
        'status' => 'success',
        'metadata' => [
            'id_trans' => 'TRX-101',
            'nama_kegiatan' => 'Kegiatan Evaluasi 1',
            'tahun_kegiatan' => '2026',
            'instansi' => 'BPS RI',
        ],
        'rbs' => [
            'n_findings' => 1,
            'n_not_evaluable' => 17,
            'findings' => [
                [
                    'rule_id' => 'R-VI-05',
                    'severity' => 'warning',
                    'message' => 'Jumlah supervisor melebihi enumerator.',
                ],
            ],
            'not_evaluable' => [
                [
                    'rule_id' => 'R-VI-01',
                    'reason' => 'activity_type_unavailable',
                    'applicability' => 'unknown',
                    'message' => 'Informasi jenis kegiatan belum cukup.',
                ],
            ],
        ],
        'hybrid_review' => '<p>Review AI</p>',
        'processing_time_seconds' => 1.5,
    ];

    $response = $this->withSession([
        'review_result' => $mockReviewResult,
        'preview' => [
            'id_trans' => 'TRX-101',
            'nama_kegiatan' => 'Kegiatan Evaluasi 1',
            'tahun_kegiatan' => '2026',
            'instansi' => 'BPS RI',
        ],
    ])->get(route('review.index'));

    $response->assertStatus(200);
    $response->assertSee('Belum Dapat Dievaluasi');
    $response->assertSee('17');
    $response->assertSee('Lihat detail');
    $response->assertSee('id="btn-open-not-evaluable"', false);
    $response->assertDontSee('Semua aturan yang relevan dapat dievaluasi.');
});

test('seluruh rule not_evaluable tersedia pada modal dengan penomoran, pesan, dan alasan', function () {
    Http::fake();

    $mockReviewResult = [
        'status' => 'success',
        'rbs' => [
            'n_findings' => 0,
            'n_not_evaluable' => 3,
            'findings' => [],
            'not_evaluable' => [
                [
                    'rule_id' => 'R-VI-01',
                    'reason' => 'activity_type_unavailable',
                    'applicability' => 'unknown',
                    'message' => 'Informasi jenis kegiatan belum cukup untuk menentukan applicability.',
                ],
                [
                    'rule_id' => 'R-V-03A',
                    'reason' => 'sampling_method_unavailable',
                    'applicability' => 'unknown',
                    'message' => 'Metode sampling belum tersedia sehingga aturan tidak dapat dievaluasi.',
                ],
                [
                    'rule_id' => 'R-V-14A',
                    'reason' => 'unit_sampel_not_found',
                    'applicability' => 'unknown',
                    'message' => null,
                ],
            ],
        ],
    ];

    $response = $this->withSession([
        'review_result' => $mockReviewResult,
    ])->get(route('review.index'));

    $response->assertStatus(200);

    // Judul & subteks modal
    $response->assertSee('Aturan Belum Dapat Dievaluasi');
    $response->assertSee('Daftar aturan RBS yang belum dapat dievaluasi pada formulir ini.');
    $response->assertSee('Aturan berikut belum dapat dievaluasi karena informasi yang diperlukan tidak tersedia atau kondisi evaluasinya tidak terpenuhi.');

    // Item 1 (activity_type_unavailable -> label dan deskripsi ramah pengguna)
    $response->assertSee('R-VI-01');
    $response->assertSee('Jenis kegiatan belum dapat ditentukan');
    $response->assertSee('Sistem belum memperoleh informasi yang cukup untuk menentukan jenis kegiatan statistik pada formulir ini. Akibatnya, aturan R-VI-01 belum dapat dievaluasi.');
    $response->assertSee('activity_type_unavailable');

    // Item 2 (sampling_method_unavailable -> label dan deskripsi ramah pengguna)
    $response->assertSee('R-V-03A');
    $response->assertSee('Metode sampling belum tersedia');
    $response->assertSee('Informasi mengenai metode pengambilan sampel belum tersedia atau belum dapat dikenali. Akibatnya, aturan R-V-03A belum dapat dievaluasi.');
    $response->assertSee('sampling_method_unavailable');

    // Item 3 (unmapped code -> fallback)
    $response->assertSee('R-V-14A');
    $response->assertSee('Informasi belum mencukupi');
    $response->assertSee('Informasi yang diperlukan untuk mengevaluasi aturan ini belum tersedia atau belum dapat ditentukan. Akibatnya, aturan R-V-14A belum dapat dievaluasi.');
    $response->assertSee('unit_sampel_not_found');

    // Search bar
    $response->assertSee('placeholder="Cari rule..."', false);
});

test('rule_id, message, dan reason ter-escape dengan aman untuk mencegah XSS', function () {
    Http::fake();

    $mockReviewResult = [
        'status' => 'success',
        'rbs' => [
            'n_findings' => 0,
            'n_not_evaluable' => 1,
            'findings' => [],
            'not_evaluable' => [
                [
                    'rule_id' => '<script>alert("xss-rule")</script>',
                    'reason' => '<b>xss-bold-reason</b>',
                    'applicability' => '<img src=x onerror=alert(1)>',
                    'message' => '<script>alert("xss-msg")</script>',
                ],
            ],
        ],
    ];

    $response = $this->withSession([
        'review_result' => $mockReviewResult,
    ])->get(route('review.index'));

    $response->assertStatus(200);

    // Pastikan tag HTML berbahaya tidak di-render mentah
    $response->assertDontSee('<script>alert("xss-rule")</script>', false);
    $response->assertDontSee('<b>xss-bold-reason</b>', false);
    $response->assertDontSee('<img src=x onerror=alert(1)>', false);
    $response->assertDontSee('<script>alert("xss-msg")</script>', false);

    // Pastikan entitas HTML ter-escape
    $response->assertSee('&lt;script&gt;alert(&quot;xss-rule&quot;)&lt;/script&gt;', false);
    $response->assertSee('&lt;b&gt;xss-bold-reason&lt;/b&gt;', false);
});

test('nested evidence pada not_evaluable aman dan dapat dibuka lewat collapsible', function () {
    Http::fake();

    $mockReviewResult = [
        'status' => 'success',
        'rbs' => [
            'n_findings' => 0,
            'n_not_evaluable' => 1,
            'findings' => [],
            'not_evaluable' => [
                [
                    'rule_id' => 'R-VI-01',
                    'reason' => 'activity_type_unavailable',
                    'evidence' => [
                        'nested_level' => [
                            'malicious_key' => '<script>alert("nested-evidence")</script>',
                            'count' => 42,
                        ],
                    ],
                ],
            ],
        ],
    ];

    $response = $this->withSession([
        'review_result' => $mockReviewResult,
    ])->get(route('review.index'));

    $response->assertStatus(200);
    $response->assertSee('Lihat bukti');
    $response->assertDontSee('<script>alert("nested-evidence")</script>', false);
    $response->assertSee('&quot;count&quot;: 42', false);
});

test('n_not_evaluable = 0 tidak menampilkan tombol detail dan menampilkan teks keterangan', function () {
    Http::fake();

    $mockReviewResult = [
        'status' => 'success',
        'rbs' => [
            'n_findings' => 1,
            'n_not_evaluable' => 0,
            'findings' => [
                [
                    'rule_id' => 'R-VI-05',
                    'severity' => 'warning',
                    'message' => 'Contoh temuan.',
                ],
            ],
            'not_evaluable' => [],
        ],
    ];

    $response = $this->withSession([
        'review_result' => $mockReviewResult,
    ])->get(route('review.index'));

    $response->assertStatus(200);
    $response->assertSee('Belum Dapat Dievaluasi');
    $response->assertSee('0');
    $response->assertDontSee('id="btn-open-not-evaluable"', false);
    $response->assertSee('Semua aturan yang relevan dapat dievaluasi.');
    $response->assertDontSee('id="modal-not-evaluable"', false);
});

test('halaman hasil tetap tidak melakukan request FastAPI ulang saat dibuka', function () {
    Http::fake();

    $mockReviewResult = [
        'status' => 'success',
        'rbs' => [
            'n_findings' => 1,
            'n_not_evaluable' => 2,
            'findings' => [
                ['rule_id' => 'R-01', 'message' => 'Finding 1'],
            ],
            'not_evaluable' => [
                ['rule_id' => 'R-02', 'reason' => 'reason 2'],
            ],
        ],
    ];

    $response = $this->withSession([
        'review_result' => $mockReviewResult,
    ])->get(route('review.index'));

    $response->assertStatus(200);
    Http::assertNothingSent();
});

test('field internal pipeline tidak bocor pada tampilan modal', function () {
    Http::fake();

    $mockReviewResult = [
        'status' => 'success',
        'rbs' => [
            'n_findings' => 0,
            'n_not_evaluable' => 1,
            'findings' => [],
            'not_evaluable' => [
                [
                    'rule_id' => 'R-VI-01',
                    'rule_version' => 'pipeline_secret_internal_v99',
                    'block' => 'blok_vi_internal_raw',
                    'violation' => false,
                    'reason' => 'activity_type_unavailable',
                    'message' => 'Keterangan publik',
                ],
            ],
        ],
    ];

    $response = $this->withSession([
        'review_result' => $mockReviewResult,
    ])->get(route('review.index'));

    $response->assertStatus(200);
    $response->assertSee('Keterangan publik');
    $response->assertDontSee('pipeline_secret_internal_v99');
});

test('fallback pesan ditampilkan jika n_not_evaluable > 0 namun array detail belum disediakan backend', function () {
    Http::fake();

    $mockReviewResult = [
        'status' => 'success',
        'rbs' => [
            'n_findings' => 2,
            'n_not_evaluable' => 17,
            'findings' => [],
            // not_evaluable sengaja tidak ada / kosong
        ],
    ];

    $response = $this->withSession([
        'review_result' => $mockReviewResult,
    ])->get(route('review.index'));

    $response->assertStatus(200);
    $response->assertSee('Lihat detail');
    $response->assertSee('Detail aturan belum tersedia pada response pemeriksaan.');
    $response->assertSee('Backend response saat ini hanya menyertakan jumlah ringkasan aturan (17 aturan).');
});

test('menampilkan graceful fallback dan tidak mengeksekusi python lokal jika backend hanya menyertakan n_not_evaluable', function () {
    $rawJson = (string) file_get_contents(base_path('sample_romantik.json'));
    $mockReviewResult = [
        'status' => 'success',
        'rbs' => [
            'n_findings' => 5,
            'n_not_evaluable' => 17,
            'findings' => [],
            // not_evaluable tidak disediakan oleh API
        ],
    ];

    $response = $this->withSession([
        'review_result' => $mockReviewResult,
        'romantik_json' => $rawJson,
    ])->get(route('review.index'));

    $response->assertStatus(200);
    $response->assertSee('Aturan Belum Dapat Dievaluasi');
    $response->assertSee('Terdapat 17 aturan yang belum dapat dievaluasi, tetapi detail aturan belum tersedia pada respons pemeriksaan.');
    $response->assertDontSee('Semua aturan yang relevan dapat dievaluasi.');
});
