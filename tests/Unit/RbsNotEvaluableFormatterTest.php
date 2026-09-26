<?php

use App\Services\RbsNotEvaluableFormatter;

test('menerjemahkan activity_type_unavailable dengan benar sesuai spesifikasi', function () {
    $result = RbsNotEvaluableFormatter::format(
        ruleId: 'R-ID-03',
        reason: 'activity_type_unavailable',
        message: null,
        applicability: 'unknown',
        evidence: [
            'title' => [
                'path' => 'nama_kegiatan',
                'state' => 'populated',
                'raw_value' => 'Kompilasi Data Pelaporan Gratifikasi',
            ],
            'activity' => [
                'value' => null,
                'basis' => 'insufficient_information',
                'evidence_paths' => [],
            ],
        ]
    );

    expect($result['title'])->toBe('Jenis kegiatan belum dapat ditentukan')
        ->and($result['description'])->toContain('Sistem belum memperoleh informasi yang cukup untuk menentukan jenis kegiatan statistik pada formulir ini.')
        ->and($result['description'])->toContain('Akibatnya, aturan R-ID-03 belum dapat dievaluasi.')
        ->and($result['description'])->not->toContain('activity_type_unavailable');
});

test('menerjemahkan reason nyata lain dari data seperti historical_submission_date_unreliable', function () {
    $result = RbsNotEvaluableFormatter::format(
        ruleId: 'R-III-04',
        reason: 'historical_submission_date_unreliable',
        message: null,
        applicability: 'applicable',
        evidence: [
            'evaluation_context' => 'historical_final_form',
            'submission_date_used' => false,
        ]
    );

    expect($result['title'])->toBe('Tanggal pengajuan historis tidak dapat dipastikan')
        ->and($result['description'])->toContain('Informasi tanggal pengajuan formulir historis belum memadai atau tidak dapat diverifikasi secara andal')
        ->and($result['description'])->toContain('Akibatnya, aturan R-III-04 belum dapat dievaluasi.');
});

test('menerjemahkan sampling_method_unavailable dan insufficient_information dengan tepat', function () {
    $resSampling = RbsNotEvaluableFormatter::format(
        ruleId: 'R-V-03A',
        reason: 'sampling_method_unavailable'
    );
    expect($resSampling['title'])->toBe('Metode sampling belum tersedia')
        ->and($resSampling['description'])->toContain('Informasi mengenai metode pengambilan sampel belum tersedia atau belum dapat dikenali.')
        ->and($resSampling['description'])->toContain('Akibatnya, aturan R-V-03A belum dapat dievaluasi.');

    $resInsufficient = RbsNotEvaluableFormatter::format(
        ruleId: 'R-VII-03',
        reason: 'insufficient_information'
    );
    expect($resInsufficient['title'])->toBe('Informasi belum mencukupi')
        ->and($resInsufficient['description'])->toContain('Informasi yang diperlukan untuk mengevaluasi aturan ini belum tersedia secara memadai pada formulir.')
        ->and($resInsufficient['description'])->toContain('Akibatnya, aturan R-VII-03 belum dapat dievaluasi.');
});

test('menggunakan fallback deterministik jika reason code tidak dikenal atau bernilai null', function () {
    $unknownResult = RbsNotEvaluableFormatter::format(
        ruleId: 'R-99-99',
        reason: 'unknown_unmapped_code_xyz'
    );

    expect($unknownResult['title'])->toBe('Informasi belum mencukupi')
        ->and($unknownResult['description'])->toBe('Informasi yang diperlukan untuk mengevaluasi aturan ini belum tersedia atau belum dapat ditentukan. Akibatnya, aturan R-99-99 belum dapat dievaluasi.')
        ->and($unknownResult['description'])->not->toContain('unknown_unmapped_code_xyz');

    $nullResult = RbsNotEvaluableFormatter::format(
        ruleId: null,
        reason: null
    );

    expect($nullResult['title'])->toBe('Informasi belum mencukupi')
        ->and($nullResult['description'])->toBe('Informasi yang diperlukan untuk mengevaluasi aturan ini belum tersedia atau belum dapat ditentukan. Akibatnya, aturan ini belum dapat dievaluasi.');
});

test('evidence dengan field populated tidak salah menyatakan field kosong pada judul', function () {
    // Kasus 1: reason title_unavailable_or_unrecognized tapi judul sebenarnya terisi
    $resultWithPopulatedTitle = RbsNotEvaluableFormatter::format(
        ruleId: 'R-ID-03',
        reason: 'title_unavailable_or_unrecognized',
        evidence: [
            'title' => [
                'path' => 'nama_kegiatan',
                'state' => 'populated',
                'raw_value' => 'Survei Kepuasan Layanan 2026',
            ],
        ]
    );

    expect($resultWithPopulatedTitle['title'])->toBe('Pola judul kegiatan belum dapat dikenali')
        ->and($resultWithPopulatedTitle['description'])->toContain('Judul kegiatan statistik telah terisi pada formulir')
        ->and($resultWithPopulatedTitle['description'])->not->toContain('belum diisi')
        ->and($resultWithPopulatedTitle['description'])->not->toContain('kosong');

    // Kasus 2: reason title_unavailable_or_unrecognized tanpa evidence / field empty
    $resultWithoutEvidence = RbsNotEvaluableFormatter::format(
        ruleId: 'R-ID-03',
        reason: 'title_unavailable_or_unrecognized',
        evidence: [
            'title' => [
                'path' => 'nama_kegiatan',
                'state' => 'empty',
                'raw_value' => '',
            ],
        ]
    );

    expect($resultWithoutEvidence['title'])->toBe('Judul kegiatan belum tersedia atau tidak dikenali')
        ->and($resultWithoutEvidence['description'])->toContain('belum terisi atau tidak dapat diidentifikasi');
});

test('formatRule helper menerima array dan memformat secara konsisten', function () {
    $rule = [
        'rule_id' => 'R-VI-01',
        'reason' => 'activity_type_unavailable',
        'applicability' => 'unknown',
        'evidence' => [
            'pilot_test' => [
                'state' => 'populated',
                'raw_value' => false,
            ],
        ],
    ];

    $formatted = RbsNotEvaluableFormatter::formatRule($rule);

    expect($formatted['title'])->toBe('Jenis kegiatan belum dapat ditentukan')
        ->and($formatted['description'])->toBe('Sistem belum memperoleh informasi yang cukup untuk menentukan jenis kegiatan statistik pada formulir ini. Akibatnya, aturan R-VI-01 belum dapat dievaluasi.');
});
