<?php

namespace App\Services;

use App\Exceptions\RomantikAiException;
use App\Exceptions\RomantikAiTimeoutException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use JsonException;
use Throwable;

class RomantikAiClient
{
    /**
     * Inisialisasi client layanan ROMANTIK AI.
     *
     * @param  string|null  $baseUrl  URL dasar layanan FastAPI (opsional, default dari config)
     * @param  int|null  $timeout  Batas waktu permintaan dalam detik (opsional, default dari config)
     */
    public function __construct(
        protected ?string $baseUrl = null,
        protected ?int $timeout = null,
    ) {}

    /**
     * Mengirim berkas JSON formulir ROMANTIK ke endpoint /api/v1/review FastAPI.
     *
     * @param  string  $rawJson  String JSON asli dari berkas formulir
     * @return array<string, mixed>
     *
     * @throws RomantikAiException
     */
    public function review(string $rawJson): array
    {
        $baseUrl = $this->baseUrl ?? config('services.romantik_ai.url');
        $timeout = $this->timeout ?? (int) config('services.romantik_ai.timeout', 120);

        if (empty($baseUrl) || ! is_string($baseUrl)) {
            throw new RomantikAiException('URL layanan ROMANTIK AI belum dikonfigurasi.');
        }

        try {
            $decoded = json_decode($rawJson, false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RomantikAiException('Format berkas JSON formulir tidak valid: '.$e->getMessage(), 0, $e);
        }

        if (! is_object($decoded)) {
            throw new RomantikAiException('Akar JSON formulir harus berupa satu objek.');
        }

        $endpoint = rtrim($baseUrl, '/').'/api/v1/review';

        try {
            $response = Http::acceptJson()
                ->timeout($timeout)
                ->post($endpoint, [
                    'form' => $decoded,
                ]);
        } catch (ConnectionException $e) {
            $message = strtolower($e->getMessage());
            $isTimeout = str_contains($message, 'timed out')
                || str_contains($message, 'timeout')
                || str_contains($message, 'curl error 28');

            if ($isTimeout) {
                throw new RomantikAiTimeoutException('Gagal terhubung atau batas waktu tercapai saat menghubungi layanan ROMANTIK AI: '.$e->getMessage(), 0, $e);
            }

            throw new RomantikAiException('Gagal terhubung ke layanan ROMANTIK AI: '.$e->getMessage(), 0, $e);
        } catch (Throwable $e) {
            $message = strtolower($e->getMessage());
            $isTimeout = str_contains($message, 'timed out')
                || str_contains($message, 'timeout')
                || str_contains($message, 'maximum execution time');

            if ($isTimeout) {
                throw new RomantikAiTimeoutException('Batas waktu pemrosesan tercapai saat menghubungi layanan ROMANTIK AI: '.$e->getMessage(), 0, $e);
            }

            throw new RomantikAiException('Terjadi kesalahan saat memanggil layanan ROMANTIK AI: '.$e->getMessage(), 0, $e);
        }

        if (! $response->successful()) {
            $status = $response->status();
            $detail = null;
            $data = $response->json();
            if (is_array($data) && isset($data['detail']) && is_string($data['detail'])) {
                $detail = $data['detail'];
            }

            $message = $detail !== null
                ? "Layanan ROMANTIK AI mengembalikan status HTTP {$status}: {$detail}"
                : "Layanan ROMANTIK AI mengembalikan status HTTP {$status}.";

            throw new RomantikAiException($message, $status);
        }

        $responseObject = $response->object();
        if (! is_object($responseObject)) {
            throw new RomantikAiException('Respons dari layanan ROMANTIK AI bukan berupa objek JSON yang valid.');
        }

        /** @var array<string, mixed> $result */
        $result = $response->json();

        return $result;
    }
}
