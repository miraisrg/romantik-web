<?php

namespace App\Http\Controllers;

use App\Exceptions\RomantikAiException;
use App\Exceptions\RomantikAiTimeoutException;
use App\Services\RbsFallbackResolver;
use App\Services\RbsNotEvaluableFormatter;
use App\Services\ReviewHtmlFormatter;
use App\Services\RomantikAiClient;
use App\Services\RuleCatalogService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class ReviewController extends Controller
{
    /**
     * Menampilkan halaman utama peninjauan formulir ROMANTIK.
     */
    public function index(): View
    {
        /** @var array<string, string>|null $preview */
        $preview = session('preview');

        /** @var array<string, mixed>|null $reviewResult */
        $reviewResult = session('review_result');

        if (
            is_array($reviewResult)
            && isset($reviewResult['rbs'])
            && is_array($reviewResult['rbs'])
            && (int) ($reviewResult['rbs']['n_not_evaluable'] ?? 0) > 0
            && empty($reviewResult['rbs']['not_evaluable'])
            && empty($reviewResult['rbs']['not_evaluable_rules'])
        ) {
            $rawJson = session('romantik_json');
            if (is_string($rawJson) && trim($rawJson) !== '') {
                $fallbackRules = app(RbsFallbackResolver::class)->resolve($rawJson);
                if (! empty($fallbackRules)) {
                    $reviewResult['rbs']['not_evaluable'] = $fallbackRules;
                    session()->put('review_result', $reviewResult);
                }
            }
        }

        $formattedHybridReview = null;
        if ($reviewResult !== null && is_array($reviewResult)) {
            $formattedHybridReview = ReviewHtmlFormatter::format($reviewResult['hybrid_review'] ?? null);
        }

        return view('review.index', [
            'preview' => $preview,
            'reviewResult' => $reviewResult,
            'formattedHybridReview' => $formattedHybridReview,
        ]);
    }

    /**
     * Memproses pengunggahan dan validasi berkas JSON formulir ROMANTIK.
     */
    public function upload(Request $request): RedirectResponse
    {
        // Hapus pratinjau, data formulir, dan hasil pemeriksaan lama sebelum memproses unggahan baru
        session()->forget(['preview', 'romantik_json', 'romantik_data', 'review_result']);

        // 1. Cek apakah berkas ada di request atau ada error upload di level PHP
        if (! $request->hasFile('file')) {
            $rawFile = $request->file('file');
            if ($rawFile && ! $rawFile->isValid()) {
                $errorMessage = match ($rawFile->getError()) {
                    UPLOAD_ERR_INI_SIZE => 'Ukuran berkas melebihi batas upload PHP.',
                    UPLOAD_ERR_FORM_SIZE => 'Ukuran berkas melebihi batas yang diizinkan.',
                    UPLOAD_ERR_PARTIAL => 'Berkas hanya terunggah sebagian. Silakan coba lagi.',
                    UPLOAD_ERR_NO_TMP_DIR => 'Folder sementara upload PHP tidak tersedia.',
                    UPLOAD_ERR_CANT_WRITE => 'Berkas gagal ditulis ke penyimpanan sementara.',
                    UPLOAD_ERR_NO_FILE => 'Silakan pilih berkas JSON formulir ROMANTIK untuk diunggah.',
                    default => 'Berkas gagal diunggah ke server.',
                };

                return back()->withErrors(['file' => $errorMessage])->withInput();
            }

            return back()->withErrors(['file' => 'Silakan pilih berkas JSON formulir ROMANTIK untuk diunggah.'])->withInput();
        }

        $file = $request->file('file');

        // 2. Cek apakah berkas valid di level PHP
        if (! $file->isValid()) {
            $errorMessage = match ($file->getError()) {
                UPLOAD_ERR_INI_SIZE => 'Ukuran berkas melebihi batas upload PHP.',
                UPLOAD_ERR_FORM_SIZE => 'Ukuran berkas melebihi batas yang diizinkan.',
                UPLOAD_ERR_PARTIAL => 'Berkas hanya terunggah sebagian. Silakan coba lagi.',
                UPLOAD_ERR_NO_TMP_DIR => 'Folder sementara upload PHP tidak tersedia.',
                UPLOAD_ERR_CANT_WRITE => 'Berkas gagal ditulis ke penyimpanan sementara.',
                default => 'Berkas gagal diunggah ke server.',
            };

            return back()->withErrors(['file' => $errorMessage])->withInput();
        }

        // 3. Validasi berkas: wajib file, maksimal 10 MB (10240 KB)
        $request->validate([
            'file' => ['required', 'file', 'max:10240'],
        ], [
            'file.required' => 'Silakan pilih berkas JSON formulir ROMANTIK untuk diunggah.',
            'file.file' => 'Berkas yang diunggah tidak valid.',
            'file.max' => 'Ukuran berkas melebihi batas 10 MB yang diizinkan.',
            'file.uploaded' => 'Berkas gagal diunggah ke server.',
        ]);

        $extension = strtolower((string) $file->getClientOriginalExtension());
        if ($extension !== 'json') {
            return back()->withErrors([
                'file' => 'Format berkas tidak didukung. Harap unggah berkas dengan ekstensi .json.',
            ])->withInput();
        }

        $rawContent = file_get_contents($file->getRealPath());
        if ($rawContent === false || trim($rawContent) === '') {
            return back()->withErrors([
                'file' => 'Berkas harus berupa JSON yang valid.',
            ])->withInput();
        }

        /** @var mixed $decoded */
        $decoded = json_decode($rawContent, false);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return back()->withErrors([
                'file' => 'Berkas harus berupa JSON yang valid.',
            ])->withInput();
        }

        // Akar JSON harus berupa satu objek formulir ROMANTIK (bukan array/daftar atau skalar)
        if (! is_object($decoded)) {
            return back()->withErrors([
                'file' => 'Akar berkas JSON harus berupa satu objek formulir ROMANTIK, bukan daftar (array) atau data skalar.',
            ])->withInput();
        }

        // Tolak tipe array/objek untuk empat nilai metadata yang ditampilkan sebagai teks
        $metadataKeys = ['id_trans', 'nama_kegiatan', 'tahun_kegiatan', 'instansi'];
        foreach ($metadataKeys as $key) {
            if (property_exists($decoded, $key) && (is_array($decoded->$key) || is_object($decoded->$key))) {
                return back()->withErrors([
                    'file' => "Nilai metadata '{$key}' tidak boleh berupa array atau objek.",
                ])->withInput();
            }
        }

        // Wajib memiliki id_trans berupa string atau angka
        if (! property_exists($decoded, 'id_trans') || ! (is_string($decoded->id_trans) || is_int($decoded->id_trans) || is_float($decoded->id_trans)) || trim((string) $decoded->id_trans) === '') {
            return back()->withErrors([
                'file' => "Berkas JSON wajib memiliki field 'id_trans' berupa string atau angka.",
            ])->withInput();
        }

        // Wajib memiliki field form berupa string atau objek
        if (! property_exists($decoded, 'form') || ! (is_string($decoded->form) || is_object($decoded->form))) {
            return back()->withErrors([
                'file' => "Berkas JSON wajib memiliki field 'form' berupa string atau objek.",
            ])->withInput();
        }

        $formatText = static function (mixed $value): string {
            if ($value === null) {
                return '-';
            }
            $text = trim((string) $value);

            return $text === '' ? '-' : $text;
        };

        $preview = [
            'id_trans' => (string) $decoded->id_trans,
            'nama_kegiatan' => property_exists($decoded, 'nama_kegiatan') ? $formatText($decoded->nama_kegiatan) : '-',
            'tahun_kegiatan' => property_exists($decoded, 'tahun_kegiatan') ? $formatText($decoded->tahun_kegiatan) : '-',
            'instansi' => property_exists($decoded, 'instansi') ? $formatText($decoded->instansi) : '-',
            'file_name' => $file->getClientOriginalName(),
        ];

        session()->put('preview', $preview);
        session()->put('romantik_json', $rawContent);

        return redirect()->route('review.index')
            ->with('success', 'Berkas formulir ROMANTIK berhasil diunggah.');
    }

    /**
     * Menjalankan proses pemeriksaan formulir ROMANTIK menggunakan RomantikAiClient.
     */
    public function process(RomantikAiClient $client): RedirectResponse
    {
        // Pastikan batas waktu eksekusi skrip PHP cukup untuk proses inferensi AI (120 detik)
        @set_time_limit((int) config('services.romantik_ai.timeout', 120));

        // Hapus hasil pemeriksaan lama sebelum pemanggilan baru
        session()->forget('review_result');

        /** @var string|null $rawJson */
        $rawJson = session('romantik_json');

        if (empty($rawJson) || ! is_string($rawJson)) {
            return redirect()->route('review.index')
                ->withErrors(['process' => 'Silakan unggah berkas formulir ROMANTIK terlebih dahulu sebelum menjalankan pemeriksaan.']);
        }

        try {
            $result = $client->review($rawJson);
        } catch (RomantikAiTimeoutException) {
            return redirect()->route('review.index')
                ->withErrors(['process' => 'Layanan pemeriksaan membutuhkan waktu lebih lama dari biasanya. Silakan coba kembali.']);
        } catch (RomantikAiException) {
            return redirect()->route('review.index')
                ->withErrors(['process' => 'Layanan pemeriksaan tidak dapat dihubungi. Pastikan API pemeriksaan sedang aktif.']);
        } catch (Throwable) {
            return redirect()->route('review.index')
                ->withErrors(['process' => 'Pemeriksaan formulir dengan AI gagal diproses. Silakan coba beberapa saat lagi.']);
        }

        session()->put('review_result', $result);

        return redirect()->route('review.index')
            ->with('success', 'Pemeriksaan formulir ROMANTIK berhasil diselesaikan.');
    }

    /**
     * Mengatur ulang sesi formulir dan hasil pemeriksaan untuk kembali ke state awal unggah berkas baru.
     */
    public function reset(): RedirectResponse
    {
        session()->forget(['preview', 'romantik_json', 'romantik_data', 'review_result']);

        return redirect()->route('review.index');
    }

    /**
     * Mengunduh hasil pemeriksaan satu formulir dalam format PDF.
     */
    public function downloadPdf(RuleCatalogService $catalogService): Response|RedirectResponse
    {
        /** @var array<string, mixed>|null $reviewResult */
        $reviewResult = session('review_result');

        if (! is_array($reviewResult) || empty($reviewResult)) {
            return redirect()->route('review.index')
                ->withErrors(['download' => 'Hasil pemeriksaan belum tersedia. Jalankan pemeriksaan terlebih dahulu.']);
        }

        if (
            isset($reviewResult['rbs'])
            && is_array($reviewResult['rbs'])
            && (int) ($reviewResult['rbs']['n_not_evaluable'] ?? 0) > 0
            && empty($reviewResult['rbs']['not_evaluable'])
            && empty($reviewResult['rbs']['not_evaluable_rules'])
        ) {
            $rawJson = session('romantik_json');
            if (is_string($rawJson) && trim($rawJson) !== '') {
                $fallbackRules = app(RbsFallbackResolver::class)->resolve($rawJson);
                if (! empty($fallbackRules)) {
                    $reviewResult['rbs']['not_evaluable'] = $fallbackRules;
                    session()->put('review_result', $reviewResult);
                }
            }
        }

        /** @var array<string, string>|null $preview */
        $preview = session('preview');
        if (! is_array($preview)) {
            $preview = [];
        }

        // Tentukan nama file yang aman dan informatif
        $rawIdTrans = $preview['id_trans'] ?? ($reviewResult['id_trans'] ?? null);
        $cleanIdTrans = null;
        if ($rawIdTrans !== null && trim((string) $rawIdTrans) !== '') {
            $sanitized = preg_replace('/[^a-zA-Z0-9_-]/', '', trim((string) $rawIdTrans));
            if ($sanitized !== '') {
                $cleanIdTrans = $sanitized;
            }
        }

        $filename = $cleanIdTrans !== null
            ? "hasil-pemeriksaan-romantik-{$cleanIdTrans}.pdf"
            : 'hasil-pemeriksaan-romantik.pdf';

        // A. Identitas Formulir
        $identity = [
            'id_trans' => $preview['id_trans'] ?? ($reviewResult['id_trans'] ?? '-'),
            'nama_kegiatan' => $preview['nama_kegiatan'] ?? ($reviewResult['nama_kegiatan'] ?? '-'),
            'tahun_kegiatan' => $preview['tahun_kegiatan'] ?? ($reviewResult['tahun_kegiatan'] ?? '-'),
            'instansi' => $preview['instansi'] ?? ($reviewResult['instansi'] ?? '-'),
            'file_name' => $preview['file_name'] ?? null,
        ];

        // B. Ringkasan Pemeriksaan
        $rbsData = is_array($reviewResult['rbs'] ?? null) ? $reviewResult['rbs'] : [];
        $rawFindings = is_array($rbsData['findings'] ?? null) ? $rbsData['findings'] : [];
        $nFindings = isset($rbsData['n_findings']) ? (int) $rbsData['n_findings'] : count($rawFindings);

        $rawNotEvaluable = $rbsData['not_evaluable'] ?? $rbsData['not_evaluable_rules'] ?? null;
        if ($rawNotEvaluable === null && ! empty($rbsData['results']) && is_array($rbsData['results'])) {
            $rawNotEvaluable = array_values(array_filter($rbsData['results'], fn ($r) => is_array($r) && ($r['status'] ?? '') === 'not_evaluable'));
        }
        if ($rawNotEvaluable === null && ! empty($rbsData['rule_results']) && is_array($rbsData['rule_results'])) {
            $rawNotEvaluable = array_values(array_filter($rbsData['rule_results'], fn ($r) => is_array($r) && ($r['status'] ?? '') === 'not_evaluable'));
        }
        if (! is_array($rawNotEvaluable)) {
            $rawNotEvaluable = [];
        }
        $nNotEvaluable = isset($rbsData['n_not_evaluable']) ? (int) $rbsData['n_not_evaluable'] : count($rawNotEvaluable);

        $statusRaw = (string) ($reviewResult['status'] ?? 'success');
        $statusText = strtolower($statusRaw) === 'success' ? 'Berhasil' : ucfirst($statusRaw);

        $processingTimeSeconds = $reviewResult['processing_time_seconds'] ?? null;
        $processingTimeString = $processingTimeSeconds !== null ? "{$processingTimeSeconds} detik" : '-';

        $summary = [
            'n_findings' => $nFindings,
            'n_not_evaluable' => $nNotEvaluable,
            'processing_time' => $processingTimeString,
            'status' => $statusText,
        ];

        // C. Temuan Rule-Based System
        $findings = [];
        foreach ($rawFindings as $index => $finding) {
            if (! is_array($finding)) {
                continue;
            }

            $ruleId = trim((string) ($finding['rule_id'] ?? '-'));
            $catalogRule = ($ruleId !== '' && $ruleId !== '-') ? $catalogService->find($ruleId) : null;

            $severityRaw = strtolower((string) ($finding['severity'] ?? 'warning'));
            $severityLabel = match ($severityRaw) {
                'error', 'kesalahan' => 'Kesalahan',
                'warning', 'peringatan' => 'Peringatan',
                default => ucfirst($severityRaw),
            };

            $evidenceJson = null;
            if (isset($finding['evidence']) && ! empty($finding['evidence'])) {
                $evidenceJson = json_encode(
                    $finding['evidence'],
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                );
            }

            $findings[] = [
                'index' => $index + 1,
                'rule_id' => $ruleId,
                'title' => $catalogRule['title'] ?? ($finding['title'] ?? 'Aturan Belum Teridentifikasi'),
                'severity_label' => $severityLabel,
                'severity_type' => $severityRaw,
                'message' => (string) ($finding['message'] ?? '-'),
                'description' => $catalogRule['description'] ?? 'Informasi aturan belum tersedia di katalog.',
                'violation_condition' => $catalogRule['violation_condition'] ?? 'Informasi aturan belum tersedia di katalog.',
                'evidence_json' => $evidenceJson,
            ];
        }

        // D. Aturan Belum Dapat Dievaluasi
        $notEvaluable = [];
        foreach ($rawNotEvaluable as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $ruleId = trim((string) ($item['rule_id'] ?? '-'));
            $catalogRule = ($ruleId !== '' && $ruleId !== '-') ? $catalogService->find($ruleId) : null;

            $formattedReason = RbsNotEvaluableFormatter::formatRule($item);

            $evidenceJson = null;
            if (isset($item['evidence']) && ! empty($item['evidence'])) {
                $evidenceJson = json_encode(
                    $item['evidence'],
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                );
            }

            $notEvaluable[] = [
                'index' => $index + 1,
                'rule_id' => $ruleId,
                'title' => $catalogRule['title'] ?? ($item['title'] ?? 'Aturan Belum Teridentifikasi'),
                'description' => $catalogRule['description'] ?? 'Informasi aturan belum tersedia di katalog.',
                'reason' => $formattedReason['description'],
                'applicability' => $catalogRule['applicability'] ?? 'Kondisi evaluasi aturan belum dapat dipastikan.',
                'evidence_json' => $evidenceJson,
            ];
        }

        // E. Catatan Pemeriksaan Hybrid AI
        $formattedHybridReview = ReviewHtmlFormatter::format($reviewResult['hybrid_review'] ?? null);

        // F. Waktu Unduh
        $downloadedAt = now()->setTimezone(config('app.timezone', 'Asia/Jakarta'))->format('d/m/Y H:i:s').' WIB';

        try {
            $pdf = Pdf::loadView('review.pdf', [
                'identity' => $identity,
                'summary' => $summary,
                'findings' => $findings,
                'notEvaluable' => $notEvaluable,
                'formattedHybridReview' => $formattedHybridReview,
                'downloadedAt' => $downloadedAt,
            ])->setPaper('a4', 'portrait');

            return $pdf->download($filename);
        } catch (Throwable $e) {
            Log::error('PDF hasil pemeriksaan gagal dibuat: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            return redirect()->route('review.index')
                ->withErrors(['download' => 'PDF hasil pemeriksaan gagal dibuat. Silakan coba kembali.']);
        }
    }
}
