<?php

use App\Services\ReviewHtmlFormatter;
use App\Services\RuleCatalogService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

test('1. route download PDF tersedia', function () {
    expect(route('review.download.pdf'))->toBe(url('/review/download/pdf'));
});

test('2. tanpa review_result redirect aman ke halaman review dengan pesan kesalahan', function () {
    $response = $this->get(route('review.download.pdf'));

    $response->assertRedirect(route('review.index'));
    $response->assertSessionHasErrors([
        'download' => 'Hasil pemeriksaan belum tersedia. Jalankan pemeriksaan terlebih dahulu.',
    ]);
});

test('3. dengan review_result response PDF sukses', function () {
    Http::fake();

    $mockReviewResult = [
        'status' => 'success',
        'id_trans' => '56219',
        'rbs' => [
            'n_findings' => 1,
            'n_not_evaluable' => 1,
            'findings' => [
                [
                    'rule_id' => 'R-VI-05',
                    'severity' => 'warning',
                    'message' => 'Jumlah supervisor melebihi enumerator.',
                ],
            ],
            'not_evaluable' => [
                [
                    'rule_id' => 'R-ID-03',
                    'reason' => 'title_unavailable_or_unrecognized',
                    'applicability' => 'unknown',
                ],
            ],
        ],
        'hybrid_review' => '<p>Catatan Hybrid AI untuk pengujian.</p>',
        'processing_time_seconds' => 3.25,
    ];

    $response = $this->withSession([
        'review_result' => $mockReviewResult,
        'preview' => [
            'id_trans' => '56219',
            'nama_kegiatan' => 'Kompilasi Data Pelaporan Gratifikasi',
            'tahun_kegiatan' => '2026',
            'instansi' => 'Komisi Pemberantasan Korupsi',
            'file_name' => 'sample_romantik.json',
        ],
    ])->get(route('review.download.pdf'));

    $response->assertOk();
    expect($response->getContent())->toStartWith('%PDF');
});

test('4. Content-Type response adalah application/pdf', function () {
    Http::fake();

    $mockReviewResult = [
        'status' => 'success',
        'rbs' => [
            'n_findings' => 0,
            'n_not_evaluable' => 0,
            'findings' => [],
            'not_evaluable' => [],
        ],
    ];

    $response = $this->withSession([
        'review_result' => $mockReviewResult,
    ])->get(route('review.download.pdf'));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('application/pdf');
});

test('5. filename informatif dan aman sesuai format yang ditentukan', function () {
    Http::fake();

    $mockReviewResult = [
        'status' => 'success',
        'rbs' => [
            'n_findings' => 0,
            'n_not_evaluable' => 0,
            'findings' => [],
            'not_evaluable' => [],
        ],
    ];

    // Kasus dengan id_trans
    $responseWithId = $this->withSession([
        'review_result' => $mockReviewResult,
        'preview' => [
            'id_trans' => '56219',
        ],
    ])->get(route('review.download.pdf'));

    $responseWithId->assertOk();
    $dispositionWithId = (string) $responseWithId->headers->get('Content-Disposition');
    expect($dispositionWithId)->toContain('hasil-pemeriksaan-romantik-56219.pdf');

    // Kasus dengan id_trans berkarakter khusus (sanitized)
    $responseSanitized = $this->withSession([
        'review_result' => $mockReviewResult,
        'preview' => [
            'id_trans' => '56219/hack/..',
        ],
    ])->get(route('review.download.pdf'));

    $responseSanitized->assertOk();
    $dispositionSanitized = (string) $responseSanitized->headers->get('Content-Disposition');
    expect($dispositionSanitized)->toContain('hasil-pemeriksaan-romantik-56219hack.pdf');

    // Kasus tanpa id_trans
    $responseWithoutId = $this->withSession([
        'review_result' => $mockReviewResult,
        'preview' => [],
    ])->get(route('review.download.pdf'));

    $responseWithoutId->assertOk();
    $dispositionWithoutId = (string) $responseWithoutId->headers->get('Content-Disposition');
    expect($dispositionWithoutId)->toContain('hasil-pemeriksaan-romantik.pdf');
});

test('6. PDF tidak memanggil FastAPI saat diunduh', function () {
    Http::fake();

    $mockReviewResult = [
        'status' => 'success',
        'rbs' => [
            'n_findings' => 0,
            'n_not_evaluable' => 0,
            'findings' => [],
            'not_evaluable' => [],
        ],
    ];

    $response = $this->withSession([
        'review_result' => $mockReviewResult,
    ])->get(route('review.download.pdf'));

    $response->assertOk();
    Http::assertNothingSent();
});

test('7. seluruh finding digunakan dalam data export view PDF', function () {
    $catalog = app(RuleCatalogService::class);

    $findingsData = [
        [
            'rule_id' => 'R-VI-05',
            'severity' => 'warning',
            'message' => 'Jumlah supervisor melebihi enumerator.',
            'evidence' => ['supervisor' => 10, 'enumerator' => 5],
        ],
        [
            'rule_id' => 'R-VI-01',
            'severity' => 'error',
            'message' => 'Metode pengumpulan data tidak valid.',
            'evidence' => ['method' => 'unknown'],
        ],
    ];

    $viewHtml = view('review.pdf', [
        'identity' => [
            'id_trans' => '56219',
            'nama_kegiatan' => 'Kegiatan Sampel',
            'tahun_kegiatan' => '2026',
            'instansi' => 'KPK',
            'file_name' => 'sample.json',
        ],
        'summary' => [
            'n_findings' => 2,
            'n_not_evaluable' => 0,
            'processing_time' => '2.5 detik',
            'status' => 'Berhasil',
        ],
        'findings' => [
            [
                'index' => 1,
                'rule_id' => 'R-VI-05',
                'title' => 'Perbandingan Jumlah Supervisor dan Enumerator',
                'severity_label' => 'Peringatan',
                'severity_type' => 'warning',
                'message' => 'Jumlah supervisor melebihi enumerator.',
                'description' => 'Memeriksa agar jumlah supervisor tidak melebihi enumerator.',
                'violation_condition' => 'Jumlah supervisor lebih besar daripada jumlah enumerator.',
                'evidence_json' => json_encode(['supervisor' => 10, 'enumerator' => 5], JSON_PRETTY_PRINT),
            ],
            [
                'index' => 2,
                'rule_id' => 'R-VI-01',
                'title' => 'Pemeriksaan Metode Pengumpulan Data',
                'severity_label' => 'Kesalahan',
                'severity_type' => 'error',
                'message' => 'Metode pengumpulan data tidak valid.',
                'description' => 'Memastikan metode pengumpulan data sesuai standar.',
                'violation_condition' => 'Metode tidak terdaftar.',
                'evidence_json' => json_encode(['method' => 'unknown'], JSON_PRETTY_PRINT),
            ],
        ],
        'notEvaluable' => [],
        'formattedHybridReview' => '<p>Catatan AI</p>',
        'downloadedAt' => '25/09/2026 22:30:00 WIB',
    ])->render();

    expect($viewHtml)->toContain('Temuan #1')
        ->toContain('R-VI-05')
        ->toContain('Perbandingan Jumlah Supervisor dan Enumerator')
        ->toContain('Peringatan')
        ->toContain('Jumlah supervisor melebihi enumerator.')
        ->toContain('Temuan #2')
        ->toContain('R-VI-01')
        ->toContain('Kesalahan')
        ->toContain('Metode pengumpulan data tidak valid.');
});

test('8. not_evaluable digunakan dalam data export view PDF', function () {
    $viewHtml = view('review.pdf', [
        'identity' => [
            'id_trans' => '56219',
            'nama_kegiatan' => 'Kegiatan Sampel',
            'tahun_kegiatan' => '2026',
            'instansi' => 'KPK',
            'file_name' => 'sample.json',
        ],
        'summary' => [
            'n_findings' => 0,
            'n_not_evaluable' => 1,
            'processing_time' => '1.5 detik',
            'status' => 'Berhasil',
        ],
        'findings' => [],
        'notEvaluable' => [
            [
                'index' => 1,
                'rule_id' => 'R-ID-03',
                'title' => 'Pencantuman Istilah Kompilasi pada Judul',
                'description' => 'Memeriksa pencantuman istilah kompilasi pada judul kegiatan kompilasi produk administrasi.',
                'reason' => 'Judul kegiatan statistik telah terisi pada formulir, namun polanya belum dapat dikenali secara pasti oleh sistem untuk penentuan aturan. Akibatnya, aturan R-ID-03 belum dapat dievaluasi.',
                'applicability' => 'Kegiatan merupakan kompilasi produk administrasi.',
                'evidence_json' => json_encode(['title' => 'Kompilasi Data Pelaporan Gratifikasi'], JSON_PRETTY_PRINT),
            ],
        ],
        'formattedHybridReview' => '<p>Catatan AI</p>',
        'downloadedAt' => '25/09/2026 22:30:00 WIB',
    ])->render();

    expect($viewHtml)->toContain('ATURAN BELUM DAPAT DIEVALUASI')
        ->toContain('R-ID-03')
        ->toContain('Pencantuman Istilah Kompilasi pada Judul')
        ->toContain('Belum Dapat Dievaluasi')
        ->toContain('Kegiatan merupakan kompilasi produk administrasi.')
        ->toContain('Judul kegiatan statistik telah terisi pada formulir');
});

test('9. metadata katalog dapat digunakan untuk memperkaya finding dan not_evaluable', function () {
    $catalog = app(RuleCatalogService::class);
    $ruleVI05 = $catalog->find('R-VI-05');
    expect($ruleVI05)->not->toBeNull();
    expect($ruleVI05['title'])->toBe('Perbandingan Jumlah Supervisor dan Enumerator');
    expect($ruleVI05['violation_condition'])->not->toBeEmpty();
});

test('10. unknown rule menggunakan fallback yang ramah tanpa menyebabkan crash', function () {
    Http::fake();

    $mockReviewResult = [
        'status' => 'success',
        'rbs' => [
            'n_findings' => 1,
            'n_not_evaluable' => 1,
            'findings' => [
                [
                    'rule_id' => 'R-UNKNOWN-99',
                    'severity' => 'warning',
                    'message' => 'Pesan aturan tak dikenal.',
                ],
            ],
            'not_evaluable' => [
                [
                    'rule_id' => 'R-UNKNOWN-88',
                    'reason' => 'unknown_reason_code',
                    'applicability' => 'unknown',
                ],
            ],
        ],
        'hybrid_review' => '<p>Uji fallback</p>',
    ];

    $response = $this->withSession([
        'review_result' => $mockReviewResult,
    ])->get(route('review.download.pdf'));

    $response->assertOk();
    expect($response->getContent())->toStartWith('%PDF');
});

test('11. script dan tag berbahaya di-escape dengan aman pada PDF', function () {
    $viewHtml = view('review.pdf', [
        'identity' => [
            'id_trans' => '56219',
            'nama_kegiatan' => '<script>alert("xss_kegiatan")</script>',
            'tahun_kegiatan' => '2026',
            'instansi' => '<b onmouseover="alert(1)">Instansi XSS</b>',
            'file_name' => 'hack.json',
        ],
        'summary' => [
            'n_findings' => 1,
            'n_not_evaluable' => 0,
            'processing_time' => '1 detik',
            'status' => 'Berhasil',
        ],
        'findings' => [
            [
                'index' => 1,
                'rule_id' => '<script>R-XSS</script>',
                'title' => '<img src=x onerror=alert(2)>',
                'severity_label' => 'Peringatan',
                'severity_type' => 'warning',
                'message' => '<script>alert("xss_message")</script>',
                'description' => '<b>Deskripsi</b>',
                'violation_condition' => '<script>alert(3)</script>',
                'evidence_json' => '{"key": "<script>alert(4)</script>"}',
            ],
        ],
        'notEvaluable' => [],
        'formattedHybridReview' => ReviewHtmlFormatter::format('<script>alert("xss_ai")</script><p>Paragraf aman</p>'),
        'downloadedAt' => '25/09/2026 22:30:00 WIB',
    ])->render();

    expect($viewHtml)->not->toContain('<script>alert("xss_kegiatan")</script>')
        ->not->toContain('<script>alert("xss_message")</script>')
        ->not->toContain('<script>alert("xss_ai")</script>')
        ->not->toContain('<script>R-XSS</script>')
        ->toContain('&lt;script&gt;alert(&quot;xss_kegiatan&quot;)&lt;/script&gt;')
        ->toContain('&lt;script&gt;alert(&quot;xss_message&quot;)&lt;/script&gt;')
        ->toContain('<p>Paragraf aman</p>');
});

test('12. Hybrid AI menggunakan formatter aman dan menjaga paragraf', function () {
    $rawHybrid = '<p>Pemeriksaan pertama.</p><p>Pemeriksaan kedua dengan <script>evil()</script> dan line break.<br>Lanjutan.</p>';
    $formatted = ReviewHtmlFormatter::format($rawHybrid);

    expect($formatted)->toContain('<p>Pemeriksaan pertama.</p>')
        ->toContain('<p>Pemeriksaan kedua dengan &lt;script&gt;evil()&lt;/script&gt; dan line break.<br>Lanjutan.</p>')
        ->not->toContain('<script>');
});

test('13. field internal pipeline tidak masuk ke dalam data view PDF', function () {
    Http::fake();

    $mockReviewResult = [
        'status' => 'success',
        'hybrid_class' => 'CONFIRMED_FINDINGS',
        'candidate_finding' => ['internal_candidate' => true],
        'confirmed_finding' => ['internal_confirmed' => true],
        'likely_resolved' => ['internal_resolved' => true],
        'workflow_temporal' => ['internal_workflow' => true],
        'classification_pipeline' => 'hybrid_v1',
        'candidate_assessment' => 'internal assessment note',
        'internal_prompt' => 'SECRET_PROMPT_KEY_12345',
        'token' => 'super_secret_token_abcde',
        'model_path' => '/models/gemini/internal-weights',
        'url_api' => 'https://tunnel.internal.ai/predict',
        'tunnel' => 'https://secret-tunnel.ngrok.io',
        'stack_trace' => 'Traceback (most recent call last): File main.py',
        'rbs' => [
            'n_findings' => 0,
            'n_not_evaluable' => 0,
            'findings' => [],
            'not_evaluable' => [],
        ],
        'hybrid_review' => '<p>Review aman tanpa bocoran field internal.</p>',
    ];

    $response = $this->withSession([
        'review_result' => $mockReviewResult,
        'preview' => [
            'id_trans' => '56219',
            'nama_kegiatan' => 'Kegiatan Bebas Field Internal',
        ],
    ])->get(route('review.download.pdf'));

    $response->assertOk();

    // Pastikan string-string internal yang sensitif tidak dikirimkan ke PDF
    $viewHtml = view('review.pdf', [
        'identity' => [
            'id_trans' => '56219',
            'nama_kegiatan' => 'Kegiatan Bebas Field Internal',
            'tahun_kegiatan' => '2026',
            'instansi' => 'KPK',
            'file_name' => null,
        ],
        'summary' => [
            'n_findings' => 0,
            'n_not_evaluable' => 0,
            'processing_time' => '1.2 detik',
            'status' => 'Berhasil',
        ],
        'findings' => [],
        'notEvaluable' => [],
        'formattedHybridReview' => ReviewHtmlFormatter::format($mockReviewResult['hybrid_review']),
        'downloadedAt' => '25/09/2026 22:30:00 WIB',
    ])->render();

    expect($viewHtml)->not->toContain('SECRET_PROMPT_KEY_12345')
        ->not->toContain('super_secret_token_abcde')
        ->not->toContain('/models/gemini/internal-weights')
        ->not->toContain('https://tunnel.internal.ai/predict')
        ->not->toContain('https://secret-tunnel.ngrok.io')
        ->not->toContain('Traceback (most recent call last)')
        ->not->toContain('hybrid_class')
        ->not->toContain('candidate_finding')
        ->not->toContain('workflow_temporal')
        ->not->toContain('classification_pipeline')
        ->not->toContain('candidate_assessment');
});

test('14. tombol Unduh Hasil PDF hanya tampil ketika review_result tersedia', function () {
    // 1. Belum upload -> tidak ada tombol
    $resInitial = $this->get(route('review.index'));
    $resInitial->assertOk();
    $resInitial->assertDontSee('Unduh Hasil PDF');
    $resInitial->assertDontSee('id="btn-download-pdf"', false);

    // 2. Baru preview formulir -> tidak ada tombol
    $resPreview = $this->withSession([
        'preview' => [
            'id_trans' => '56219',
            'nama_kegiatan' => 'Kegiatan Baru Pratinjau',
            'tahun_kegiatan' => '2026',
            'instansi' => 'KPK',
            'file_name' => 'sample.json',
        ],
    ])->get(route('review.index'));
    $resPreview->assertOk();
    $resPreview->assertDontSee('Unduh Hasil PDF');
    $resPreview->assertDontSee('id="btn-download-pdf"', false);

    // 3. Selesai pemeriksaan (review_result tersedia) -> tombol tampil
    $resCompleted = $this->withSession([
        'preview' => [
            'id_trans' => '56219',
            'nama_kegiatan' => 'Kegiatan Selesai Diperiksa',
            'tahun_kegiatan' => '2026',
            'instansi' => 'KPK',
            'file_name' => 'sample.json',
        ],
        'review_result' => [
            'status' => 'success',
            'rbs' => [
                'n_findings' => 0,
                'n_not_evaluable' => 0,
                'findings' => [],
                'not_evaluable' => [],
            ],
            'hybrid_review' => '<p>Selesai</p>',
        ],
    ])->get(route('review.index'));
    $resCompleted->assertOk();
    $resCompleted->assertSee('Unduh Hasil PDF');
    $resCompleted->assertSee('id="btn-download-pdf"', false);
    $resCompleted->assertSee(route('review.download.pdf'));
});

test('15. jika pembuatan PDF mengalami kegagalan sistem redirect aman tanpa menghapus review_result', function () {
    Pdf::shouldReceive('loadView')
        ->once()
        ->andThrow(new RuntimeException('Simulated PDF engine failure'));

    Log::shouldReceive('error')->once();

    $mockResult = [
        'status' => 'success',
        'rbs' => ['n_findings' => 0, 'n_not_evaluable' => 0],
    ];

    $response = $this->withSession([
        'review_result' => $mockResult,
    ])->get(route('review.download.pdf'));

    $response->assertRedirect(route('review.index'));
    $response->assertSessionHasErrors([
        'download' => 'PDF hasil pemeriksaan gagal dibuat. Silakan coba kembali.',
    ]);
    $response->assertSessionHas('review_result');
});
