<?php

use App\Exceptions\RomantikAiException;
use App\Services\RomantikAiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

test('berhasil memanggil endpoint FastAPI POST /api/v1/review dengan pembungkus form', function () {
    config(['services.romantik_ai.url' => 'https://austin-tiny-sussex-mic.trycloudflare.com']);

    $mockResponse = [
        'status' => 'success',
        'metadata' => [
            'id_trans' => 'TRX-101',
            'nama_kegiatan' => 'Survei Uji Coba',
        ],
        'rbs' => [
            'total_checks' => 10,
            'findings' => [],
        ],
        'hybrid_review' => [
            'summary' => 'Formulir telah sesuai standar.',
        ],
        'processing_time_seconds' => 1.45,
    ];

    Http::fake([
        'https://austin-tiny-sussex-mic.trycloudflare.com/api/v1/review' => Http::response($mockResponse, 200),
    ]);

    $rawJson = json_encode([
        'id_trans' => 'TRX-101',
        'nama_kegiatan' => 'Survei Uji Coba',
        'tahun_kegiatan' => 2024,
        'instansi' => 'BPS RI',
        'form' => '{"judul":"Survei Uji Coba"}',
    ]);

    $client = new RomantikAiClient;
    $result = $client->review($rawJson);

    expect($result)->toBeArray()
        ->and($result['status'])->toBe('success')
        ->and($result['metadata']['id_trans'])->toBe('TRX-101')
        ->and($result['rbs']['total_checks'])->toBe(10)
        ->and($result['hybrid_review']['summary'])->toBe('Formulir telah sesuai standar.')
        ->and($result['processing_time_seconds'])->toBe(1.45);

    Http::assertSent(function (Request $request) {
        $hasCorrectUrl = $request->url() === 'https://austin-tiny-sussex-mic.trycloudflare.com/api/v1/review';
        $hasCorrectMethod = $request->method() === 'POST';
        $hasAcceptJson = $request->hasHeader('Accept', 'application/json');

        $body = json_decode($request->body(), false);
        $hasFormWrapper = isset($body->form) && is_object($body->form);
        $hasPreservedId = isset($body->form->id_trans) && $body->form->id_trans === 'TRX-101';
        $hasPreservedFormString = isset($body->form->form) && $body->form->form === '{"judul":"Survei Uji Coba"}';

        return $hasCorrectUrl
            && $hasCorrectMethod
            && $hasAcceptJson
            && $hasFormWrapper
            && $hasPreservedId
            && $hasPreservedFormString;
    });
});

test('memastikan pembungkus form melestarikan objek {} dan array [] tanpa konversi', function () {
    config(['services.romantik_ai.url' => 'https://austin-tiny-sussex-mic.trycloudflare.com']);

    Http::fake([
        '*' => Http::response(['status' => 'success'], 200),
    ]);

    // Format JSON memuat obj_kosong sebagai {} dan arr_kosong sebagai [], serta string form
    $rawJson = '{"id_trans":"TRX-STRUCT","obj_kosong":{},"arr_kosong":[],"form":"{\"nested\":123}"}';

    $client = new RomantikAiClient;
    $client->review($rawJson);

    Http::assertSent(function (Request $request) {
        $sentBody = $request->body();
        $decoded = json_decode($sentBody, false);

        // Memastikan obj_kosong tetap stdClass ({}) dan arr_kosong tetap array ([])
        $isObjectPreserved = is_object($decoded->form->obj_kosong) && ($decoded->form->obj_kosong instanceof stdClass);
        $isArrayPreserved = is_array($decoded->form->arr_kosong);
        $isFormStringPreserved = is_string($decoded->form->form) && $decoded->form->form === '{"nested":123}';

        // Memastikan representasi string payload tetap {} dan []
        $hasLiteralObject = str_contains($sentBody, '"obj_kosong":{}');
        $hasLiteralArray = str_contains($sentBody, '"arr_kosong":[]');

        return $isObjectPreserved
            && $isArrayPreserved
            && $isFormStringPreserved
            && $hasLiteralObject
            && $hasLiteralArray;
    });
});

test('melempar RomantikAiException jika URL layanan belum dikonfigurasi', function () {
    config(['services.romantik_ai.url' => null]);

    $client = new RomantikAiClient;

    expect(fn () => $client->review('{"id_trans":"1","form":"{}"}'))
        ->toThrow(RomantikAiException::class, 'URL layanan ROMANTIK AI belum dikonfigurasi.');
});

test('melempar RomantikAiException jika string JSON tidak valid atau bukan objek tunggal', function () {
    config(['services.romantik_ai.url' => 'https://austin-tiny-sussex-mic.trycloudflare.com']);

    $client = new RomantikAiClient;

    // JSON syntax error
    expect(fn () => $client->review('{ id_trans: 123, invalid }'))
        ->toThrow(RomantikAiException::class, 'Format berkas JSON formulir tidak valid');

    // JSON berupa array numerik / list
    expect(fn () => $client->review('[{"id_trans": "TRX-1"}]'))
        ->toThrow(RomantikAiException::class, 'Akar JSON formulir harus berupa satu objek.');
});

test('melempar RomantikAiException saat terjadi kegagalan koneksi atau timeout', function () {
    config(['services.romantik_ai.url' => 'https://austin-tiny-sussex-mic.trycloudflare.com']);

    Http::fake([
        '*' => fn () => throw new ConnectionException('Connection timed out after 300 seconds'),
    ]);

    $client = new RomantikAiClient;

    expect(fn () => $client->review('{"id_trans":"1","form":"{}"}'))
        ->toThrow(RomantikAiException::class, 'Gagal terhubung atau batas waktu tercapai saat menghubungi layanan ROMANTIK AI');
});

test('melempar RomantikAiException saat layanan mengembalikan HTTP non-2xx', function () {
    config(['services.romantik_ai.url' => 'https://austin-tiny-sussex-mic.trycloudflare.com']);

    Http::fake([
        '*' => Http::response([
            'detail' => 'Internal error during model inference',
        ], 500),
    ]);

    $client = new RomantikAiClient;

    expect(fn () => $client->review('{"id_trans":"1","form":"{}"}'))
        ->toThrow(RomantikAiException::class, 'Layanan ROMANTIK AI mengembalikan status HTTP 500: Internal error during model inference');
});

test('melempar RomantikAiException saat respons layanan bukan berupa objek JSON', function () {
    config(['services.romantik_ai.url' => 'https://austin-tiny-sussex-mic.trycloudflare.com']);

    $client = new RomantikAiClient;

    // Respons berupa HTML (misal 502/Cloudflare tunnel error berstatus 200 atau teks polos)
    Http::fake([
        '*' => Http::response('<html>502 Bad Gateway</html>', 200),
    ]);

    expect(fn () => $client->review('{"id_trans":"1","form":"{}"}'))
        ->toThrow(RomantikAiException::class, 'Respons dari layanan ROMANTIK AI bukan berupa objek JSON yang valid.');

    // Respons berupa JSON array list, bukan JSON objek
    Http::fake([
        '*' => Http::response('["item1", "item2"]', 200),
    ]);

    expect(fn () => $client->review('{"id_trans":"1","form":"{}"}'))
        ->toThrow(RomantikAiException::class, 'Respons dari layanan ROMANTIK AI bukan berupa objek JSON yang valid.');
});
