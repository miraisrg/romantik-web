<?php

use App\Services\RuleCatalogService;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

uses(TestCase::class);

test('RuleCatalogService berhasil membaca JSON katalog dan mengembalikan array', function () {
    $service = new RuleCatalogService;
    $rules = $service->all();

    expect($rules)->toBeArray()
        ->and($rules)->not->toBeEmpty();
});

test('jumlah rule pada katalog adalah tepat 40 aturan', function () {
    $service = new RuleCatalogService;

    expect($service->count())->toBe(40)
        ->and(count($service->all()))->toBe(40);
});

test('seluruh rule_id pada katalog bernilai unik', function () {
    $service = new RuleCatalogService;
    $rules = $service->all();

    $ruleIds = array_map(fn ($r) => $r['rule_id'] ?? null, $rules);

    expect(count($ruleIds))->toBe(40)
        ->and(count(array_unique($ruleIds)))->toBe(40);
});

test("find('R-VI-05') mengembalikan metadata yang benar", function () {
    $service = new RuleCatalogService;
    $rule = $service->find('R-VI-05');

    expect($rule)->not->toBeNull()
        ->and($rule['rule_id'])->toBe('R-VI-05')
        ->and($rule['title'])->toBe('Perbandingan Jumlah Supervisor dan Enumerator')
        ->and($rule['section'])->toBe('Blok VI – Pengumpulan Data')
        ->and($rule['severity'])->toBe('warning')
        ->and($rule['check_type'])->toBe('Logika bisnis')
        ->and($rule['main_field'])->toContain('jumlah_supervisor')
        ->and($rule['main_field'])->toContain('jumlah_enumerator')
        ->and($rule['description'])->toBe('Memeriksa agar jumlah supervisor tidak melebihi enumerator.')
        ->and($rule['applicability'])->toBe('Kedua jumlah petugas berhasil diparse.')
        ->and($rule['violation_condition'])->toBe('Jumlah supervisor lebih besar daripada jumlah enumerator.')
        ->and($rule['pass_condition'])->toBe('Jumlah supervisor lebih kecil atau sama dengan jumlah enumerator.')
        ->and($rule['ui_message'])->toBe('Jumlah supervisor melebihi jumlah enumerator.');
});

test('find case-insensitive untuk rule_id seperti r-vi-05', function () {
    $service = new RuleCatalogService;
    $rule = $service->find('r-vi-05');

    expect($rule)->not->toBeNull()
        ->and($rule['rule_id'])->toBe('R-VI-05');
});

test('find dengan rule tidak dikenal menghasilkan null dan fallback aman tanpa crash', function () {
    Log::shouldReceive('warning')
        ->once()
        ->with("Rule ID 'R-UNKNOWN-99' tidak ditemukan pada katalog aturan RBS.");

    $service = new RuleCatalogService;
    $result = $service->find('R-UNKNOWN-99');

    expect($result)->toBeNull();
});

test('find dengan string kosong atau strip menghasilkan null tanpa warning', function () {
    $service = new RuleCatalogService;

    expect($service->find(''))->toBeNull()
        ->and($service->find('-'))->toBeNull()
        ->and($service->find('   '))->toBeNull();
});

test('sections mengembalikan daftar blok unik yang terurut', function () {
    $service = new RuleCatalogService;
    $sections = $service->sections();

    expect($sections)->toBeArray()
        ->and($sections)->toContain('Identitas Kegiatan')
        ->and($sections)->toContain('Blok I – Penyelenggara')
        ->and($sections)->toContain('Blok VI – Pengumpulan Data')
        ->and(count($sections))->toBe(count(array_unique($sections)));
});

test('bila berkas JSON tidak ditemukan atau rusak, service fallback aman dengan log error', function () {
    Log::shouldReceive('error')->atLeast()->once();
    Log::shouldReceive('warning')->atLeast()->once();

    $nonExistentService = new RuleCatalogService('path/to/non_existent_file.json');

    expect($nonExistentService->all())->toBe([])
        ->and($nonExistentService->count())->toBe(0)
        ->and($nonExistentService->sections())->toBe([])
        ->and($nonExistentService->metadata())->toBe([])
        ->and($nonExistentService->find('R-VI-05'))->toBeNull();
});
