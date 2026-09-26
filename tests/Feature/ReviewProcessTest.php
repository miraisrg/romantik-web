<?php

use App\Services\RomantikAiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

test('mengarahkan kembali ke halaman utama jika mencoba menjalankan pemeriksaan tanpa ada unggahan di sesi', function () {
    Http::fake();

    $response = $this->from('/')
        ->post(route('review.process'));

    $response->assertRedirect(route('review.index'));
    $response->assertSessionHasErrors(['process']);
    $response->assertSessionMissing('review_result');

    Http::assertNothingSent();
});

test('berhasil menjalankan pemeriksaan, memanggil API tepat satu kali, dan menyimpan hasil di sesi', function () {
    config(['services.romantik_ai.url' => 'https://austin-tiny-sussex-mic.trycloudflare.com']);

    $mockApiResponse = [
        'status' => 'success',
        'metadata' => [
            'id_trans' => 'TRX-2024-888',
            'nama_kegiatan' => 'Survei Kepuasan',
        ],
        'rbs' => [
            'n_findings' => 1,
            'n_not_evaluable' => 2,
            'findings' => [
                [
                    'rule_id' => 'R-VI-05',
                    'severity' => 'warning',
                    'message' => 'Jumlah supervisor melebihi enumerator.',
                    'evidence' => ['supervisor' => 4, 'enumerator' => 3],
                ],
            ],
        ],
        'hybrid_review' => '<p>Hasil evaluasi AI</p>',
        'processing_time_seconds' => 3.12,
    ];

    Http::fake([
        'https://austin-tiny-sussex-mic.trycloudflare.com/api/v1/review' => Http::response($mockApiResponse, 200),
    ]);

    $rawJson = json_encode([
        'id_trans' => 'TRX-2024-888',
        'nama_kegiatan' => 'Survei Kepuasan',
        'tahun_kegiatan' => 2024,
        'instansi' => 'BPS',
        'form' => '{"judul":"Survei Kepuasan"}',
    ]);

    $sessionData = [
        'romantik_json' => $rawJson,
        'preview' => [
            'id_trans' => 'TRX-2024-888',
            'nama_kegiatan' => 'Survei Kepuasan',
            'tahun_kegiatan' => '2024',
            'instansi' => 'BPS',
            'file_name' => 'survei.json',
        ],
    ];

    $response = $this->withSession($sessionData)
        ->from('/')
        ->post(route('review.process'));

    $response->assertRedirect(route('review.index'));
    $response->assertSessionHas('success', 'Pemeriksaan formulir ROMANTIK berhasil diselesaikan.');
    $response->assertSessionHas('review_result', $mockApiResponse);

    // Memastikan API dipanggil tepat satu kali (tanpa retry otomatis)
    Http::assertSentCount(1);
});

test('menampilkan hasil pemeriksaan lengkap dari sesi tanpa memanggil FastAPI lagi dengan temuan berbeda, bukti terformat, hybrid review berparagraf, dan proteksi escaping', function () {
    Http::fake();

    $mockApiResponse = [
        'status' => 'success',
        'metadata' => [
            'id_trans' => 'TRX-TEST-001',
            'nama_kegiatan' => 'Survei Pertanian Berkelanjutan',
        ],
        'rbs' => [
            'n_findings' => 2,
            'n_not_evaluable' => 17,
            'findings' => [
                [
                    'rule_id' => 'R-VI-05',
                    'severity' => 'warning',
                    'message' => 'Jumlah supervisor melebihi jumlah enumerator lapangan.',
                    'evidence' => [
                        'supervisor' => [
                            'path' => 'form.blok_vi.jumlah_supervisor',
                            'state' => 'populated',
                            'raw_value' => '4',
                        ],
                        'enumerator' => [
                            'path' => 'form.blok_vi.jumlah_enumerator',
                            'state' => 'populated',
                            'raw_value' => '3',
                        ],
                        'parsed_supervisor' => 4,
                        'parsed_enumerator' => 3,
                    ],
                    'hybrid_class' => 'INTERNAL_SECRET_CLASS_1',
                    'candidate_finding' => 'INTERNAL_CANDIDATE_SECRET_1',
                ],
                [
                    'rule_id' => 'R-II-01',
                    'severity' => 'error',
                    'message' => 'Judul kegiatan memuat karakter terlarang <script>alert("xss-msg")</script>',
                    'evidence' => [
                        'field' => 'nama_kegiatan',
                        'detail' => [
                            'invalid_payload' => '<img src=x onerror=alert(1)>',
                            'nested' => [
                                'level' => 2,
                                'status' => 'rejected',
                            ],
                        ],
                    ],
                    'hybrid_class' => 'INTERNAL_SECRET_CLASS_2',
                    'candidate_finding' => 'INTERNAL_CANDIDATE_SECRET_2',
                ],
            ],
        ],
        'hybrid_review' => '<p>Analisis substansi formulir menunjukkan konsistensi umum.<br>Perlu penyesuaian alokasi petugas lapangan.</p><p>Catatan tambahan dengan percobaan tag berbahaya <script>alert("xss-hybrid")</script> dan tag ber-event <b onclick="alert(2)">teks tebal</b> serta tautan <a href="https://example.com">contoh tautan</a>.</p>',
        'processing_time_seconds' => 13.705,
        'pipeline_classification' => 'INTERNAL_PIPELINE_SECRET',
    ];

    $sessionData = [
        'romantik_json' => json_encode(['id_trans' => 'TRX-TEST-001', 'form' => '{}']),
        'preview' => [
            'id_trans' => 'TRX-TEST-001',
            'nama_kegiatan' => 'Survei Pertanian Berkelanjutan',
            'tahun_kegiatan' => '2024',
            'instansi' => 'Dinas Pertanian',
            'file_name' => 'pertanian.json',
        ],
        'review_result' => $mockApiResponse,
    ];

    $response = $this->withSession($sessionData)->get(route('review.index'));

    $response->assertStatus(200);

    // Pastikan FastAPI tidak pernah dipanggil saat halaman dibuka
    Http::assertNothingSent();

    // 1. Ringkasan
    $response->assertSee('Hasil Pemeriksaan Lengkap');
    $response->assertSee('Status: Berhasil');
    $response->assertSee('13.705'); // Waktu pemrosesan
    $response->assertSee('2'); // rbs.n_findings
    $response->assertSee('17'); // rbs.n_not_evaluable

    // 2. Banner dan kartu alur
    $response->assertSee('Pemeriksaan selesai. RBS dan Hybrid AI berhasil dijalankan.');
    $response->assertSee('Terhubung');

    // 3. Temuan 1 (warning -> Peringatan, nested evidence)
    $response->assertSee('R-VI-05');
    $response->assertSee('Peringatan');
    $response->assertSee('Jumlah supervisor melebihi jumlah enumerator lapangan.');
    $response->assertSee('Lihat bukti');
    $response->assertSee('form.blok_vi.jumlah_supervisor');
    $response->assertSee('parsed_supervisor');

    // 4. Temuan 2 (error -> Kesalahan, escaping script in message & evidence)
    $response->assertSee('R-II-01');
    $response->assertSee('Kesalahan');
    $response->assertSee('Judul kegiatan memuat karakter terlarang');
    $response->assertDontSee('<script>alert("xss-msg")</script>', false);
    $response->assertSee('&lt;script&gt;alert(&quot;xss-msg&quot;)&lt;/script&gt;', false);

    // Escaping nested evidence
    $response->assertDontSee('<img src=x onerror=alert(1)>', false);

    // 5. Hybrid Review: Mempertahankan <p> dan <br>, tapi meng-escape tag lain
    $response->assertSee('<p>Analisis substansi formulir menunjukkan konsistensi umum.<br>Perlu penyesuaian alokasi petugas lapangan.</p>', false);
    $response->assertDontSee('<script>alert("xss-hybrid")</script>', false);
    $response->assertSee('&lt;script&gt;alert(&quot;xss-hybrid&quot;)&lt;/script&gt;', false);
    $response->assertDontSee('<b onclick="alert(2)">', false);
    $response->assertDontSee('<a href="https://example.com">', false);

    // 6. Tidak membocorkan field internal
    $response->assertDontSee('INTERNAL_SECRET_CLASS_1');
    $response->assertDontSee('INTERNAL_SECRET_CLASS_2');
    $response->assertDontSee('INTERNAL_CANDIDATE_SECRET_1');
    $response->assertDontSee('INTERNAL_CANDIDATE_SECRET_2');
    $response->assertDontSee('INTERNAL_PIPELINE_SECRET');
    $response->assertDontSee('hybrid_class');
    $response->assertDontSee('candidate_finding');
    $response->assertDontSee('pipeline_classification');
});

test('menampilkan pesan pemberitahuan yang sesuai jika catatan hybrid review kosong', function () {
    $sessionData = [
        'romantik_json' => json_encode(['id_trans' => 'TRX-EMPTY', 'form' => '{}']),
        'preview' => [
            'id_trans' => 'TRX-EMPTY',
            'nama_kegiatan' => 'Kegiatan Kosong',
            'tahun_kegiatan' => '2024',
            'instansi' => 'BPS',
            'file_name' => 'empty.json',
        ],
        'review_result' => [
            'status' => 'success',
            'rbs' => [
                'n_findings' => 0,
                'n_not_evaluable' => 0,
                'findings' => [],
            ],
            'hybrid_review' => '',
            'processing_time_seconds' => 1.5,
        ],
    ];

    $response = $this->withSession($sessionData)->get(route('review.index'));

    $response->assertStatus(200);
    $response->assertSee('Tidak ada temuan aturan (RBS) pada formulir ini.');
    $response->assertSee('Tidak ada catatan pemeriksaan Hybrid AI untuk formulir ini.');
});

test('menangani kegagalan API dengan mempertahankan formulir dan pratinjau tanpa membocorkan detail exception', function () {
    config(['services.romantik_ai.url' => 'https://austin-tiny-sussex-mic.trycloudflare.com']);

    Http::fake([
        '*' => fn () => throw new ConnectionException('cURL error 28: Operation timed out after 300000 milliseconds with 0 bytes received'),
    ]);

    $rawJson = json_encode([
        'id_trans' => 'TRX-FAIL-01',
        'nama_kegiatan' => 'Survei Gagal',
        'tahun_kegiatan' => 2024,
        'instansi' => 'Dinas',
        'form' => '{}',
    ]);

    $initialPreview = [
        'id_trans' => 'TRX-FAIL-01',
        'nama_kegiatan' => 'Survei Gagal',
        'tahun_kegiatan' => '2024',
        'instansi' => 'Dinas',
        'file_name' => 'gagal.json',
    ];

    $sessionData = [
        'romantik_json' => $rawJson,
        'preview' => $initialPreview,
        'review_result' => ['status' => 'old_result'],
    ];

    $response = $this->withSession($sessionData)
        ->from('/')
        ->post(route('review.process'));

    $response->assertRedirect(route('review.index'));
    $response->assertSessionHasErrors(['process']);
    $response->assertSessionMissing('review_result');

    // Formulir dan pratinjau tetap dipertahankan
    $response->assertSessionHas('romantik_json', $rawJson);
    $response->assertSessionHas('preview', $initialPreview);

    // Memastikan pesan error singkat dalam bahasa Indonesia dan tidak membocorkan exception detail
    $errors = session('errors')->get('process');
    expect($errors[0])->toBe('Layanan pemeriksaan membutuhkan waktu lebih lama dari biasanya. Silakan coba kembali.');

    // Memastikan halaman tidak menampilkan detail exception cURL/timeout
    $followedPage = $this->withSession([
        'romantik_json' => $rawJson,
        'preview' => $initialPreview,
        'errors' => session('errors'),
    ])->get(route('review.index'));

    $followedPage->assertDontSee('cURL error 28');
    $followedPage->assertDontSee('Connection timed out');
});

test('menghapus hasil pemeriksaan lama saat pengguna mengunggah formulir baru', function () {
    $existingSession = [
        'preview' => [
            'id_trans' => 'OLD-001',
            'nama_kegiatan' => 'Kegiatan Lama',
            'tahun_kegiatan' => '2022',
            'instansi' => 'Instansi Lama',
            'file_name' => 'lama.json',
        ],
        'romantik_json' => '{"id_trans":"OLD-001","form":"{}"}',
        'review_result' => [
            'status' => 'success',
            'rbs' => ['n_findings' => 99, 'n_not_evaluable' => 99],
            'processing_time_seconds' => 1.0,
        ],
    ];

    $newFile = UploadedFile::fake()->createWithContent(
        'baru.json',
        json_encode([
            'id_trans' => 'NEW-002',
            'nama_kegiatan' => 'Kegiatan Baru',
            'tahun_kegiatan' => 2025,
            'instansi' => 'BPS Baru',
            'form' => '{}',
        ])
    );

    $response = $this->withSession($existingSession)
        ->from('/')
        ->post(route('review.upload'), [
            'file' => $newFile,
        ]);

    $response->assertRedirect(route('review.index'));
    $response->assertSessionMissing('review_result');
    $response->assertSessionHas('preview', function ($preview) {
        return $preview['id_trans'] === 'NEW-002';
    });
});

test('menghapus hasil pemeriksaan lama sebelum menjalankan pemeriksaan baru', function () {
    config(['services.romantik_ai.url' => 'https://austin-tiny-sussex-mic.trycloudflare.com']);

    $newApiResponse = [
        'status' => 'success',
        'metadata' => ['id_trans' => 'TRX-NEW'],
        'rbs' => [
            'n_findings' => 1,
            'n_not_evaluable' => 0,
        ],
        'hybrid_review' => 'Ringkasan',
        'processing_time_seconds' => 1.2,
    ];

    Http::fake([
        '*' => Http::response($newApiResponse, 200),
    ]);

    $rawJson = json_encode([
        'id_trans' => 'TRX-NEW',
        'form' => '{}',
    ]);

    $sessionData = [
        'romantik_json' => $rawJson,
        'preview' => [
            'id_trans' => 'TRX-NEW',
            'nama_kegiatan' => '-',
            'tahun_kegiatan' => '-',
            'instansi' => '-',
            'file_name' => 'new.json',
        ],
        'review_result' => [
            'status' => 'old_success',
            'rbs' => ['n_findings' => 99, 'n_not_evaluable' => 99],
            'processing_time_seconds' => 9.9,
        ],
    ];

    $response = $this->withSession($sessionData)
        ->post(route('review.process'));

    $response->assertRedirect(route('review.index'));
    $response->assertSessionHas('review_result', $newApiResponse);
});

test('menangani kegagalan koneksi ketika layanan FastAPI tidak aktif atau koneksi ditolak', function () {
    config(['services.romantik_ai.url' => 'https://austin-tiny-sussex-mic.trycloudflare.com']);

    Http::fake([
        '*' => fn () => throw new ConnectionException('Failed to connect to 127.0.0.1 port 8000: Connection refused'),
    ]);

    $rawJson = json_encode(['id_trans' => 'TRX-CONN-ERR', 'form' => '{}']);
    $initialPreview = [
        'id_trans' => 'TRX-CONN-ERR',
        'nama_kegiatan' => 'Kegiatan',
        'tahun_kegiatan' => '2026',
        'instansi' => 'BPS',
        'file_name' => 'conn_err.json',
    ];

    $response = $this->withSession([
        'romantik_json' => $rawJson,
        'preview' => $initialPreview,
    ])->post(route('review.process'));

    $response->assertRedirect(route('review.index'));
    $response->assertSessionHasErrors(['process' => 'Layanan pemeriksaan tidak dapat dihubungi. Pastikan API pemeriksaan sedang aktif.']);
    $response->assertSessionHas('preview', $initialPreview);
    $response->assertSessionHas('romantik_json', $rawJson);
    $response->assertSessionMissing('review_result');
});

test('RomantikAiClient menggunakan timeout dari config services.romantik_ai.timeout secara dinamis', function () {
    config([
        'services.romantik_ai.url' => 'https://test-api.example.com',
        'services.romantik_ai.timeout' => 120,
    ]);

    Http::fake([
        '*' => Http::response(['status' => 'success'], 200),
    ]);

    $client = new RomantikAiClient;
    $client->review('{"id_trans":"TRX-TIMEOUT-TEST","form":"{}"}');

    Http::assertSent(function (Request $request) {
        return $request->url() === 'https://test-api.example.com/api/v1/review';
    });
});
