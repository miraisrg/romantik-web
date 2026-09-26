<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class RuleCatalogService
{
    /**
     * Cache daftar aturan setelah dimuat dari berkas.
     *
     * @var array<int, array<string, mixed>>|null
     */
    private ?array $rules = null;

    /**
     * Cache index aturan berdasarkan rule_id (case-insensitive) untuk pencarian cepat.
     *
     * @var array<string, array<string, mixed>>|null
     */
    private ?array $rulesById = null;

    /**
     * Cache metadata katalog dari berkas.
     *
     * @var array<string, mixed>|null
     */
    private ?array $metadata = null;

    /**
     * Cache daftar bagian/blok unik yang terdapat di katalog.
     *
     * @var array<int, string>|null
     */
    private ?array $sections = null;

    /**
     * Lokasi path berkas katalog JSON.
     */
    private string $filePath;

    /**
     * Membuat instance RuleCatalogService baru.
     */
    public function __construct(?string $filePath = null)
    {
        $this->filePath = $filePath ?? storage_path('app/data/rule_catalog.json');
    }

    /**
     * Mengembalikan seluruh aturan yang terdaftar di katalog.
     *
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        $this->load();

        return $this->rules ?? [];
    }

    /**
     * Mencari aturan berdasarkan kode rule_id (misal: 'R-VI-05').
     *
     * @return array<string, mixed>|null
     */
    public function find(string $ruleId): ?array
    {
        $this->load();

        $key = strtoupper(trim($ruleId));

        if ($key === '' || $key === '-') {
            return null;
        }

        if (isset($this->rulesById[$key])) {
            return $this->rulesById[$key];
        }

        Log::warning("Rule ID '{$ruleId}' tidak ditemukan pada katalog aturan RBS.");

        return null;
    }

    /**
     * Mengembalikan daftar nama bagian/blok unik dari seluruh aturan.
     *
     * @return array<int, string>
     */
    public function sections(): array
    {
        $this->load();

        return $this->sections ?? [];
    }

    /**
     * Mengembalikan metadata katalog aturan RBS.
     *
     * @return array<string, mixed>
     */
    public function metadata(): array
    {
        $this->load();

        return $this->metadata ?? [];
    }

    /**
     * Mengembalikan jumlah total aturan yang berhasil dimuat.
     */
    public function count(): int
    {
        $this->load();

        return count($this->rules ?? []);
    }

    /**
     * Memuat berkas katalog JSON dan melakukan inisialisasi cache internal.
     */
    private function load(): void
    {
        // Hindari membaca ulang berkas jika sudah pernah dimuat dalam request ini
        if ($this->rules !== null) {
            return;
        }

        if (! file_exists($this->filePath)) {
            Log::error("Berkas rule_catalog.json tidak ditemukan di: {$this->filePath}");
            $this->initializeEmpty();

            return;
        }

        $rawContent = @file_get_contents($this->filePath);

        if ($rawContent === false || trim($rawContent) === '') {
            Log::error("Gagal membaca isi berkas rule_catalog.json atau berkas kosong: {$this->filePath}");
            $this->initializeEmpty();

            return;
        }

        /** @var mixed $decoded */
        $decoded = json_decode($rawContent, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            Log::error('Format JSON rule_catalog.json tidak valid: '.json_last_error_msg());
            $this->initializeEmpty();

            return;
        }

        // Validasi struktur dasar
        $rawRules = $decoded['rules'] ?? null;
        if (! is_array($rawRules)) {
            Log::error("Struktur rule_catalog.json tidak valid: key 'rules' tidak ditemukan atau bukan array.");
            $this->initializeEmpty();

            return;
        }

        $this->metadata = is_array($decoded['metadata'] ?? null) ? $decoded['metadata'] : [];
        $this->rules = [];
        $this->rulesById = [];
        $uniqueSections = [];

        foreach ($rawRules as $rule) {
            if (! is_array($rule) || empty($rule['rule_id'])) {
                continue;
            }

            $ruleId = trim((string) $rule['rule_id']);
            $ruleKey = strtoupper($ruleId);

            $this->rules[] = $rule;
            $this->rulesById[$ruleKey] = $rule;

            if (! empty($rule['section']) && is_string($rule['section'])) {
                $sectionName = trim($rule['section']);
                if (! in_array($sectionName, $uniqueSections, true)) {
                    $uniqueSections[] = $sectionName;
                }
            }
        }

        $this->sections = $uniqueSections;
    }

    /**
     * Inisialisasi fallback aman saat berkas tidak dapat dimuat.
     */
    private function initializeEmpty(): void
    {
        $this->rules = [];
        $this->rulesById = [];
        $this->metadata = [];
        $this->sections = [];
    }
}
