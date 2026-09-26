<?php

namespace App\Services;

class RbsNotEvaluableFormatter
{
    /**
     * Pemetaan deterministik kode alasan internal RBS ke judul dan deskripsi ramah pengguna (Bahasa Indonesia).
     *
     * @var array<string, array{title: string, description: string}>
     */
    private const REASON_MAP = [
        // 1. Jenis Kegiatan & Karakteristik Kegiatan (Group 01, 03, 04, 09, 10, 11)
        'activity_type_unavailable' => [
            'title' => 'Jenis kegiatan belum dapat ditentukan',
            'description' => 'Sistem belum memperoleh informasi yang cukup untuk menentukan jenis kegiatan statistik pada formulir ini.',
        ],
        'activity_recurrence_unavailable_or_unrecognized' => [
            'title' => 'Keberulangan kegiatan belum dikenali',
            'description' => 'Informasi apakah kegiatan bersifat berulang atau satu kali belum dapat dipastikan dari isian formulir.',
        ],
        'frequency_unavailable_or_format_unrecognized' => [
            'title' => 'Frekuensi kegiatan belum dikenali',
            'description' => 'Informasi frekuensi penyelenggaraan kegiatan statistik belum tersedia atau tidak sesuai format baku.',
        ],
        'collection_type_unavailable_or_unrecognized' => [
            'title' => 'Tipe pengumpulan data belum dikenali',
            'description' => 'Informasi mengenai cara atau tipe pengumpulan data belum tersedia atau belum dapat dikenali.',
        ],
        'secondary_data_selection_unavailable_or_unrecognized' => [
            'title' => 'Pilihan data sekunder belum dikenali',
            'description' => 'Informasi pemanfaatan data sekunder pada kegiatan statistik belum tersedia atau tidak dapat diidentifikasi.',
        ],
        'activity_year_unavailable_or_invalid' => [
            'title' => 'Tahun kegiatan belum tersedia atau tidak valid',
            'description' => 'Informasi tahun pelaksanaan kegiatan statistik belum terisi atau tahun yang tercantum tidak valid.',
        ],

        // 2. Linimasa & Tanggal Kegiatan (Group 07, 11)
        'historical_submission_date_unreliable' => [
            'title' => 'Tanggal pengajuan historis tidak dapat dipastikan',
            'description' => 'Informasi tanggal pengajuan formulir historis belum memadai atau tidak dapat diverifikasi secara andal untuk evaluasi aturan linimasa.',
        ],
        'required_schedule_dates_unavailable_or_unrecognized' => [
            'title' => 'Jadwal kegiatan belum tersedia atau tidak dikenali',
            'description' => 'Informasi tanggal pelaksanaan atau linimasa kegiatan belum terisi lengkap atau format tanggal tidak dikenali.',
        ],
        'collection_schedule_reversed' => [
            'title' => 'Jadwal pengumpulan data terbalik',
            'description' => 'Tanggal awal dan akhir jadwal pengumpulan data tercatat terbalik sehingga linimasa tidak dapat dievaluasi.',
        ],
        'collection_schedule_incomplete_or_unrecognized' => [
            'title' => 'Jadwal pengumpulan data tidak lengkap',
            'description' => 'Informasi linimasa pengumpulan data belum lengkap atau format tanggal tidak dapat dikenali.',
        ],

        // 3. Metode Sampling, Rancangan, Fraksi & Kesalahan Sampling (Group 03, 04, 10)
        'sampling_method_unavailable' => [
            'title' => 'Metode sampling belum tersedia',
            'description' => 'Informasi mengenai metode pengambilan sampel belum tersedia atau belum dapat dikenali.',
        ],
        'sampling_method_unavailable_or_unrecognized' => [
            'title' => 'Metode sampling belum tersedia atau tidak dikenali',
            'description' => 'Informasi mengenai metode pengambilan sampel belum tersedia atau tidak dikenali dalam format yang diharapkan.',
        ],
        'sampling_design_unavailable_or_unrecognized' => [
            'title' => 'Rancangan sampling belum tersedia atau tidak dikenali',
            'description' => 'Informasi rancangan penarikan sampel statistik belum tersedia atau tidak dapat dikenali oleh sistem.',
        ],
        'sampling_fraction_unavailable_or_conflicting' => [
            'title' => 'Fraksi sampling belum tersedia atau bertentangan',
            'description' => 'Informasi fraksi atau rasio sampel belum tersedia atau memiliki nilai yang bertentangan dengan jumlah populasi atau sampel.',
        ],
        'sampling_fraction_format_unrecognized' => [
            'title' => 'Format fraksi sampling tidak dikenali',
            'description' => 'Format penulisan angka atau persentase fraksi sampling tidak dapat dipahami oleh sistem evaluasi.',
        ],
        'sampling_block_unavailable_or_unrecognized' => [
            'title' => 'Blok sampling belum tersedia atau tidak dikenali',
            'description' => 'Bagian atau blok isian metode penarikan sampel belum terisi atau belum dapat dikenali.',
        ],
        'sampling_block_has_no_informative_content' => [
            'title' => 'Blok sampling tidak memuat informasi yang cukup',
            'description' => 'Bagian isian metode penarikan sampel telah diisi tetapi belum memuat rincian substantif untuk dievaluasi.',
        ],
        'sampling_fields_unavailable_or_conflicting' => [
            'title' => 'Rincian sampling belum tersedia atau bertentangan',
            'description' => 'Kolom-kolom yang berkaitan dengan metode sampling belum terisi lengkap atau nilainya saling bertentangan.',
        ],
        'sampling_error_unavailable_or_conflicting' => [
            'title' => 'Sampling error belum tersedia atau bertentangan',
            'description' => 'Informasi mengenai tingkat kesalahan sampel (sampling error) belum terisi atau bertentangan.',
        ],
        'sampling_error_format_unrecognized' => [
            'title' => 'Format sampling error tidak dikenali',
            'description' => 'Format penulisan angka sampling error tidak dapat dikenali sebagai persentase yang valid.',
        ],
        'sampling_error_empty_marker' => [
            'title' => 'Sampling error ditandai kosong',
            'description' => 'Kolom sampling error diisi tanda kosong atau strip sehingga nilainya belum dapat dievaluasi.',
        ],
        'sampling_error_unit_or_format_unrecognized' => [
            'title' => 'Satuan atau format sampling error tidak dikenali',
            'description' => 'Satuan persentase atau format angka pada sampling error tidak dapat dikenali secara pasti.',
        ],
        'sampling_error_percent_not_unambiguous' => [
            'title' => 'Nilai persentase sampling error bermakna ganda',
            'description' => 'Nilai persentase kesalahan sampel tidak dapat ditentukan secara pasti karena format penulisan ambigu.',
        ],

        // 4. Cakupan Wilayah (Group 01)
        'coverage_unavailable_or_unrecognized' => [
            'title' => 'Cakupan wilayah belum tersedia atau tidak dikenali',
            'description' => 'Informasi cakupan wilayah kegiatan statistik belum terisi atau tidak dapat dikenali secara pasti.',
        ],
        'region_list_unavailable_or_unreadable' => [
            'title' => 'Daftar wilayah belum tersedia atau tidak terbaca',
            'description' => 'Daftar wilayah yang menjadi lingkup kegiatan belum tersedia atau formatnya tidak dapat dibaca oleh sistem.',
        ],

        // 5. Petugas Lapangan, Supervisi, Uji Coba, Pelatihan, Fasilitas & Wawancara (Group 01, 02)
        'pilot_test_unavailable_or_unrecognized' => [
            'title' => 'Informasi uji coba belum tersedia',
            'description' => 'Keterangan mengenai pelaksanaan uji coba (pilot test) rancangan instrumen belum tersedia atau tidak dapat dikenali.',
        ],
        'staff_counts_unavailable_or_invalid' => [
            'title' => 'Jumlah petugas belum tersedia atau tidak valid',
            'description' => 'Informasi jumlah petugas lapangan (supervisor atau enumerator) belum tersedia atau nilainya tidak valid untuk dievaluasi.',
        ],
        'supervisor_count_unavailable_or_invalid' => [
            'title' => 'Jumlah supervisor belum tersedia atau tidak valid',
            'description' => 'Informasi jumlah supervisor atau pengawas lapangan belum tersedia atau nilainya tidak valid untuk dievaluasi.',
        ],
        'supervision_unavailable_or_unrecognized' => [
            'title' => 'Keterangan pengawasan belum tersedia',
            'description' => 'Informasi mengenai pelaksanaan supervisi atau pengawasan lapangan belum tersedia atau tidak dikenali.',
        ],
        'collection_facilities_unavailable_or_unrecognized' => [
            'title' => 'Fasilitas pengumpulan data belum dikenali',
            'description' => 'Informasi sarana atau fasilitas pengumpulan data belum tersedia atau belum dapat dikenali oleh sistem.',
        ],
        'interview_selection_unavailable_or_unrecognized' => [
            'title' => 'Metode wawancara belum dikenali',
            'description' => 'Informasi mengenai tata cara atau metode wawancara belum tersedia atau formatnya tidak dikenali.',
        ],
        'education_unavailable_or_ambiguous' => [
            'title' => 'Persyaratan pendidikan belum jelas',
            'description' => 'Informasi mengenai kualifikasi atau persyaratan pendidikan petugas belum tersedia atau bermakna ganda.',
        ],
        'education_level_requires_rule_confirmation' => [
            'title' => 'Tingkat pendidikan memerlukan konfirmasi aturan',
            'description' => 'Kualifikasi pendidikan petugas yang tercatat memerlukan konfirmasi lebih lanjut terhadap aturan penetapan petugas.',
        ],
        'collection_methods_unavailable_or_unrecognized' => [
            'title' => 'Metode pengumpulan data belum dikenali',
            'description' => 'Informasi metode pengumpulan data belum terisi secara memadai atau tidak sesuai pilihan baku.',
        ],
        'training_unavailable_or_unrecognized' => [
            'title' => 'Informasi pelatihan petugas belum tersedia',
            'description' => 'Keterangan mengenai pelatihan bagi petugas lapangan belum tersedia atau tidak dapat diidentifikasi.',
        ],

        // 6. Instansi & Judul Formulir (Group 06, 11)
        'institution_name_unavailable' => [
            'title' => 'Nama instansi penyelenggara belum tersedia',
            'description' => 'Informasi nama instansi penyelenggara kegiatan statistik belum terisi pada formulir.',
        ],
        'institution_name_format_unrecognized' => [
            'title' => 'Format nama instansi belum dikenali',
            'description' => 'Format penulisan nama instansi penyelenggara belum dapat dikenali secara pasti oleh sistem verifikasi.',
        ],
        'title_unavailable_or_unrecognized' => [
            'title' => 'Judul kegiatan belum tersedia atau tidak dikenali',
            'description' => 'Judul kegiatan statistik belum terisi atau tidak dapat diidentifikasi secara memadai.',
        ],

        // 7. Variabel & Kolom Umum (Group 03, 06, 08)
        'required_field_unavailable_or_conflicting' => [
            'title' => 'Kolom wajib belum tersedia atau bertentangan',
            'description' => 'Kolom isian wajib yang dibutuhkan aturan ini belum tersedia atau memiliki nilai yang saling bertentangan.',
        ],
        'required_fields_unavailable' => [
            'title' => 'Kolom wajib belum tersedia',
            'description' => 'Satu atau lebih kolom isian wajib yang diperlukan untuk evaluasi aturan ini belum terisi pada formulir.',
        ],
        'variables_unavailable_or_not_structured' => [
            'title' => 'Daftar variabel belum tersedia atau tidak terstruktur',
            'description' => 'Rincian variabel statistik belum tersedia atau belum tersusun dalam format terstruktur yang dapat dievaluasi.',
        ],
        'variable_list_empty' => [
            'title' => 'Daftar variabel belum terisi',
            'description' => 'Tabel atau daftar variabel statistik pada formulir ini masih kosong.',
        ],
        'variable_fields_unavailable_or_unrecognized' => [
            'title' => 'Atribut variabel belum tersedia atau tidak dikenali',
            'description' => 'Atribut variabel seperti definisi, satuan, atau tipe data belum terisi memadai atau formatnya tidak dikenali.',
        ],
        'analysis_unavailable_or_unrecognized' => [
            'title' => 'Metode analisis belum tersedia atau tidak dikenali',
            'description' => 'Informasi mengenai metode analisis data statistik belum terisi atau formatnya tidak dikenali.',
        ],

        // 8. Basis/Alasan Umum
        'insufficient_information' => [
            'title' => 'Informasi belum mencukupi',
            'description' => 'Informasi yang diperlukan untuk mengevaluasi aturan ini belum tersedia secara memadai pada formulir.',
        ],
    ];

    /**
     * Menerjemahkan hasil not_evaluable RBS menjadi judul dan deskripsi dalam bahasa Indonesia.
     *
     * @param  string|null  $ruleId  ID aturan RBS (misal: R-ID-03)
     * @param  string|null  $reason  Kode alasan internal dari RBS (misal: activity_type_unavailable)
     * @param  string|null  $message  Pesan tambahan jika tersedia
     * @param  string|null  $applicability  Status applicability (misal: unknown, applicable)
     * @param  array<string, mixed>|null  $evidence  Bukti evaluasi aturan
     * @return array{title: string, description: string}
     */
    public static function format(
        ?string $ruleId = null,
        ?string $reason = null,
        ?string $message = null,
        ?string $applicability = null,
        ?array $evidence = null
    ): array {
        $cleanReason = strtolower(trim((string) $reason));
        $cleanRuleId = trim((string) $ruleId);

        // 1. Tentukan judul dan deskripsi dasar dari mapping deterministik
        if ($cleanReason !== '' && isset(self::REASON_MAP[$cleanReason])) {
            $mapped = self::REASON_MAP[$cleanReason];
            $title = $mapped['title'];
            $baseDescription = $mapped['description'];
        } else {
            // Fallback deterministik jika reason code belum dipetakan atau kosong
            $title = 'Informasi belum mencukupi';
            $baseDescription = 'Informasi yang diperlukan untuk mengevaluasi aturan ini belum tersedia atau belum dapat ditentukan.';
        }

        // 2. Perjelas keterangan menggunakan evidence secara aman tanpa salah menyatakan field kosong
        $refined = self::refineWithEvidence($cleanReason, $title, $baseDescription, $evidence);
        $title = $refined['title'];
        $baseDescription = $refined['description'];

        // 3. Tambahkan kalimat penjelas konsekuensi evaluasi aturan
        $consequence = ($cleanRuleId !== '' && $cleanRuleId !== '-')
            ? "Akibatnya, aturan {$cleanRuleId} belum dapat dievaluasi."
            : 'Akibatnya, aturan ini belum dapat dievaluasi.';

        $description = rtrim($baseDescription, '. ').'. '.$consequence;

        return [
            'title' => $title,
            'description' => $description,
        ];
    }

    /**
     * Helper untuk memformat array aturan utuh dari response RBS.
     *
     * @param  array<string, mixed>  $rule
     * @return array{title: string, description: string}
     */
    public static function formatRule(array $rule): array
    {
        return self::format(
            isset($rule['rule_id']) ? (string) $rule['rule_id'] : null,
            isset($rule['reason']) ? (string) $rule['reason'] : null,
            isset($rule['message']) ? (string) $rule['message'] : null,
            isset($rule['applicability']) ? (string) $rule['applicability'] : null,
            isset($rule['evidence']) && is_array($rule['evidence']) ? $rule['evidence'] : null
        );
    }

    /**
     * Mengembalikan seluruh mapping reason code yang terdaftar (untuk keperluan audit dan dokumentasi).
     *
     * @return array<string, array{title: string, description: string}>
     */
    public static function getRegisteredReasonMap(): array
    {
        return self::REASON_MAP;
    }

    /**
     * Memperjelas judul dan deskripsi berdasarkan data evidence tanpa asumsi spekulatif.
     *
     * @param  array<string, mixed>|null  $evidence
     * @return array{title: string, description: string}
     */
    private static function refineWithEvidence(
        string $reason,
        string $title,
        string $baseDescription,
        ?array $evidence
    ): array {
        if ($evidence === null || empty($evidence)) {
            return [
                'title' => $title,
                'description' => $baseDescription,
            ];
        }

        // Contoh: Kasus judul kegiatan (R-ID-03 atau evaluate_title_rule)
        // Jika title terisi (populated), jangan pernah mengatakan judul kegiatan belum diisi
        if ($reason === 'title_unavailable_or_unrecognized') {
            if (self::isFieldPopulated($evidence, 'title')) {
                return [
                    'title' => 'Pola judul kegiatan belum dapat dikenali',
                    'description' => 'Judul kegiatan statistik telah terisi pada formulir, namun polanya belum dapat dikenali secara pasti oleh sistem untuk penentuan aturan.',
                ];
            }
        }

        // Kasus nama instansi
        if ($reason === 'institution_name_unavailable') {
            if (self::isFieldPopulated($evidence, 'institution') || self::isFieldPopulated($evidence, 'instansi')) {
                return [
                    'title' => 'Format nama instansi belum dikenali',
                    'description' => 'Nama instansi penyelenggara telah terisi pada formulir, namun polanya belum dapat dikenali secara pasti oleh sistem verifikasi.',
                ];
            }
        }

        // Kasus uji coba (pilot test)
        if ($reason === 'pilot_test_unavailable_or_unrecognized') {
            if (self::isFieldPopulated($evidence, 'pilot_test')) {
                return [
                    'title' => 'Keterangan uji coba memerlukan verifikasi',
                    'description' => 'Keterangan mengenai uji coba instrumen telah terisi, namun format atau konteks kegiatannya belum memenuhi syarat evaluasi aturan.',
                ];
            }
        }

        return [
            'title' => $title,
            'description' => $baseDescription,
        ];
    }

    /**
     * Memeriksa apakah suatu field dalam evidence berstatus 'populated' atau memiliki nilai non-kosong.
     *
     * @param  array<string, mixed>  $evidence
     */
    public static function isFieldPopulated(array $evidence, string $fieldKey): bool
    {
        if (! isset($evidence[$fieldKey])) {
            return false;
        }

        $val = $evidence[$fieldKey];
        if (is_array($val)) {
            if (isset($val['state']) && $val['state'] === 'populated') {
                return true;
            }
            if (isset($val['raw_value']) && $val['raw_value'] !== null && $val['raw_value'] !== '') {
                return true;
            }
        }

        return false;
    }
}
