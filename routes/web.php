<?php

use App\Http\Controllers\ReviewController;
use App\Http\Controllers\RuleCatalogController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ReviewController::class, 'index'])->name('review.index');
Route::post('/review/upload', [ReviewController::class, 'upload'])->name('review.upload');
Route::post('/review/process', [ReviewController::class, 'process'])->name('review.process');
Route::match(['GET', 'POST'], '/review/reset', [ReviewController::class, 'reset'])->name('review.reset');
Route::get('/review/download/pdf', [ReviewController::class, 'downloadPdf'])->name('review.download.pdf');

Route::get('/rules', [RuleCatalogController::class, 'index'])->name('rules.index');

if (app()->environment('local')) {
    Route::get('/dev/load-demo-result', function () {
        $sampleResult = json_decode((string) file_get_contents(base_path('live_fastapi_result.json')), true);
        session()->put('preview', [
            'id_trans' => '56219',
            'nama_kegiatan' => 'Kompilasi Data Pelaporan Gratifikasi',
            'tahun_kegiatan' => '2026',
            'instansi' => 'Komisi Pemberantasan Korupsi',
            'file_name' => 'sample_romantik.json',
        ]);
        session()->put('review_result', $sampleResult);

        return redirect()->route('review.index');
    })->name('dev.load-demo-result');
}
