<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pemeriksaan Formulir ROMANTIK</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 antialiased flex flex-col justify-between">
    @php
        $activePreview = $preview ?? session('preview');
        $activeReviewResult = $reviewResult ?? session('review_result');
        $statusText = ($activeReviewResult['status'] ?? '') === 'success' ? 'Berhasil' : ucfirst($activeReviewResult['status'] ?? 'Berhasil');

        $rbsData = $activeReviewResult['rbs'] ?? [];
        $nNotEvaluable = (int) ($rbsData['n_not_evaluable'] ?? 0);
        $notEvaluableRules = $rbsData['not_evaluable'] ?? $rbsData['not_evaluable_rules'] ?? null;
        if ($notEvaluableRules === null && !empty($rbsData['results']) && is_array($rbsData['results'])) {
            $notEvaluableRules = array_values(array_filter($rbsData['results'], fn($r) => ($r['status'] ?? '') === 'not_evaluable'));
        }
        if ($notEvaluableRules === null && !empty($rbsData['rule_results']) && is_array($rbsData['rule_results'])) {
            $notEvaluableRules = array_values(array_filter($rbsData['rule_results'], fn($r) => ($r['status'] ?? '') === 'not_evaluable'));
        }
        $hasNotEvaluableDetail = is_array($notEvaluableRules);
        $notEvaluableRules = $hasNotEvaluableDetail ? $notEvaluableRules : [];
        $notEvaluableCount = count($notEvaluableRules);
    @endphp

    <!-- 3. Toast Notifikasi Sementara Sukses (Pojok Kanan Atas) -->
    @if ($activeReviewResult)
        <div
            id="toast-success"
            role="status"
            aria-live="polite"
            class="fixed top-4 right-4 sm:top-5 sm:right-5 z-50 max-w-sm w-[calc(100%-2rem)] sm:w-auto flex items-center gap-3 rounded-xl border border-emerald-200 bg-white/95 backdrop-blur-xs p-3.5 shadow-lg ring-1 ring-emerald-500/10 transition-all duration-300 transform translate-y-0 opacity-100"
        >
            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 font-bold text-xs" aria-hidden="true">
                ✓
            </span>
            <div class="text-xs sm:text-sm font-medium text-slate-800 leading-snug">
                Pemeriksaan selesai. RBS dan Hybrid AI berhasil dijalankan.
            </div>
            <button
                type="button"
                id="btn-close-toast"
                aria-label="Tutup notifikasi"
                class="ml-auto -mr-1 -my-1 p-1.5 text-slate-400 hover:text-slate-600 rounded-lg focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-emerald-500 cursor-pointer transition-colors"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    @endif

    <!-- 2. Header Aplikasi (Logo, Judul, dan Navigasi) -->
    @include('partials.header')

    <!-- Konten Utama -->
    <main class="max-w-5xl mx-auto px-4 sm:px-6 py-6 w-full flex-1">
        <!-- Banner Status untuk Kondisi Belum Ada File / Siap Diperiksa -->
        @if (!$activeReviewResult)
            @if ($activePreview)
                <div class="mb-5 rounded-xl border border-sky-200 bg-sky-50/80 px-4 py-2.5 text-sky-950 flex flex-wrap items-center justify-between gap-2 shadow-2xs">
                    <div class="flex items-center gap-2.5">
                        <svg class="w-4 h-4 text-sky-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        <p class="text-xs sm:text-sm font-semibold text-sky-900">
                            Formulir siap diperiksa. Klik tombol "Jalankan Pemeriksaan" untuk memulai evaluasi.
                        </p>
                    </div>
                    <span class="text-[11px] font-medium text-sky-700 bg-sky-100/70 px-2.5 py-0.5 rounded-full border border-sky-200">
                        Penanda Status: Fitur Unggah Aktif &mdash; Pemeriksaan AI Belum Terhubung
                    </span>
                </div>
            @else
                <div class="mb-5 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-slate-800 flex flex-wrap items-center justify-between gap-2 shadow-2xs">
                    <div class="flex items-center gap-2.5">
                        <svg class="w-4 h-4 text-indigo-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                        </svg>
                        <p class="text-xs sm:text-sm font-semibold text-slate-800">
                            Silakan unggah satu berkas formulir ROMANTIK berformat .json untuk memulai pemeriksaan.
                        </p>
                    </div>
                    <span class="text-[11px] font-medium text-slate-600 bg-slate-100 px-2.5 py-0.5 rounded-full border border-slate-200">
                        Penanda Status: Fitur Unggah Aktif &mdash; Pemeriksaan AI Belum Terhubung
                    </span>
                </div>
            @endif
        @endif

        <!-- Pesan Notifikasi Sukses Flash -->
        @if (session('success') && !$activeReviewResult)
            <div class="max-w-2xl mx-auto mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-emerald-900 flex items-center gap-2.5 shadow-2xs">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <div class="text-xs sm:text-sm font-medium">
                    {{ session('success') }}
                </div>
            </div>
        @endif

        <!-- Pesan Kesalahan / Validasi -->
        @if ($errors->any())
            <div class="max-w-2xl mx-auto mb-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-rose-900 shadow-2xs">
                <div class="flex items-start gap-2.5">
                    <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                    </svg>
                    <div>
                        <p class="text-sm font-semibold text-rose-900">Terjadi Kesalahan</p>
                        <ul class="mt-1 list-disc list-inside text-xs sm:text-sm text-rose-800 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <!-- Judul Halaman (Ringkas, tidak mengulang deskripsi saat hasil ada) -->
        <div class="text-center max-w-2xl mx-auto {{ $activeReviewResult ? 'mb-4' : 'mb-6' }}">
            <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900">
                Pemeriksaan Formulir ROMANTIK
            </h2>
            @if (!$activeReviewResult)
                <p class="mt-1.5 text-xs sm:text-sm text-slate-600 leading-relaxed">
                    Pemeriksaan otomatis rekomendasi kegiatan statistik dengan validasi deterministik <strong class="text-slate-800">Rule-Based System (RBS)</strong> dan evaluasi kontekstual <strong class="text-slate-800">Hybrid AI</strong>.
                </p>
            @endif
        </div>

        {{-- JIKA HASIL PEMERIKSAAN SUDAH TERSEDIA, TAMPILKAN HASIL DI BAGIAN ATAS --}}
        @if ($activeReviewResult)
            <!-- B. Hasil Pemeriksaan Lengkap -->
            <section class="max-w-3xl mx-auto mb-5 rounded-2xl border border-slate-200 bg-white p-4.5 sm:p-5 shadow-xs">
                <!-- Header Kartu Hasil -->
                <div class="flex flex-wrap items-center justify-between gap-2.5 pb-3 border-b border-slate-100 mb-3.5">
                    <div class="flex items-center gap-2">
                        <div class="w-2.5 h-2.5 rounded-full bg-emerald-500 ring-2 ring-emerald-100"></div>
                        <h3 class="text-base sm:text-lg font-bold text-slate-900 tracking-tight">Hasil Pemeriksaan Lengkap</h3>
                    </div>
                    <div class="flex items-center gap-2">
                        <a
                            href="{{ route('review.download.pdf') }}"
                            id="btn-download-pdf"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 hover:text-slate-900 hover:border-slate-300 transition-colors focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-indigo-500 cursor-pointer"
                            title="Unduh hasil pemeriksaan dalam format PDF"
                        >
                            <svg class="w-3.5 h-3.5 text-slate-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                            </svg>
                            <span>Unduh Hasil PDF</span>
                        </a>
                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                            Status: {{ $statusText }}
                        </span>
                    </div>
                </div>

                <!-- 3 Kartu Ringkasan Metrik -->
                <dl class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 mb-4 text-left">
                    <div class="rounded-xl bg-slate-50 p-3 border border-slate-200/70 flex flex-col justify-between">
                        <div>
                            <dt class="text-xs font-medium text-slate-500">Temuan RBS</dt>
                            <dd class="mt-0.5 flex items-baseline gap-1.5">
                                <span class="text-2xl font-extrabold text-slate-900 font-mono">{{ $activeReviewResult['rbs']['n_findings'] ?? 0 }}</span>
                                <span class="text-[11px] text-slate-500">temuan (rbs.n_findings)</span>
                            </dd>
                        </div>
                        <div class="mt-2 pt-1.5 border-t border-slate-200/60">
                            <span class="text-[11px] text-slate-400">Deterministik rule-based</span>
                        </div>
                    </div>

                    <div class="rounded-xl bg-slate-50 p-3 border border-slate-200/70 flex flex-col justify-between">
                        <div>
                            <dt class="text-xs font-medium text-slate-500">Belum Dapat Dievaluasi</dt>
                            <dd class="mt-0.5 flex items-baseline gap-1.5">
                                <span class="text-2xl font-extrabold text-slate-900 font-mono">{{ $nNotEvaluable }}</span>
                                <span class="text-[11px] text-slate-500">aturan (rbs.n_not_evaluable)</span>
                            </dd>
                        </div>
                        <div class="mt-2 pt-1.5 border-t border-slate-200/60">
                            @if ($nNotEvaluable > 0)
                                <button
                                    type="button"
                                    id="btn-open-not-evaluable"
                                    class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 hover:underline inline-flex items-center gap-1 cursor-pointer focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-indigo-500 rounded"
                                >
                                    <span>Lihat detail</span>
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                    </svg>
                                </button>
                            @else
                                <p class="text-[11px] text-slate-500">Semua aturan yang relevan dapat dievaluasi.</p>
                            @endif
                        </div>
                    </div>

                    <div class="rounded-xl bg-slate-50 p-3 border border-slate-200/70 flex flex-col justify-between">
                        <div>
                            <dt class="text-xs font-medium text-slate-500">Waktu Proses</dt>
                            <dd class="mt-0.5 flex items-baseline gap-1.5">
                                <span class="text-2xl font-extrabold text-slate-900 font-mono">{{ $activeReviewResult['processing_time_seconds'] ?? '-' }}</span>
                                <span class="text-[11px] text-slate-500">detik pemrosesan</span>
                            </dd>
                        </div>
                        <div class="mt-2 pt-1.5 border-t border-slate-200/60">
                            <span class="text-[11px] text-slate-400">Total pipeline &amp; AI</span>
                        </div>
                    </div>
                </dl>

                <!-- Daftar Temuan RBS -->
                <div class="pt-3.5 border-t border-slate-100">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <div class="w-2 h-2 rounded-full bg-indigo-500"></div>
                            <h4 class="text-sm font-semibold text-slate-900">Daftar Temuan RBS</h4>
                        </div>
                        <span class="text-xs text-slate-500">
                            Total: <strong class="font-mono text-slate-700">{{ count($activeReviewResult['rbs']['findings'] ?? []) }}</strong> temuan
                        </span>
                    </div>

                    @php
                        $findings = $activeReviewResult['rbs']['findings'] ?? [];
                    @endphp

                    @if (!empty($findings) && is_array($findings))
                        <div class="space-y-2.5">
                            @foreach ($findings as $index => $finding)
                                @php
                                    $severity = strtolower((string) ($finding['severity'] ?? ''));
                                @endphp
                                <div class="rounded-xl border border-slate-200/90 bg-white p-3.5 text-left shadow-2xs hover:border-slate-300 transition-colors">
                                    <div class="flex flex-wrap items-center justify-between gap-2 mb-1.5">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-medium text-slate-500">Temuan #{{ $index + 1 }}</span>
                                            @if (!empty($finding['rule_id']) && $finding['rule_id'] !== '-')
                                                <a
                                                    href="{{ route('rules.index', ['rule' => $finding['rule_id']]) }}"
                                                    class="font-mono text-xs font-bold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 hover:text-indigo-800 px-2 py-0.5 rounded border border-indigo-200/80 transition-colors inline-flex items-center gap-1 focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-indigo-500"
                                                    title="Lihat detail aturan {{ $finding['rule_id'] }} di Katalog Aturan RBS"
                                                >
                                                    <span>{{ $finding['rule_id'] }}</span>
                                                    <svg class="w-3 h-3 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                                    </svg>
                                                </a>
                                            @else
                                                <span class="font-mono text-xs font-bold text-slate-900 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
                                                    -
                                                </span>
                                            @endif
                                            @if ($severity === 'error')
                                                <span class="inline-flex items-center rounded-md bg-rose-50 px-2 py-0.5 text-xs font-semibold text-rose-700 ring-1 ring-inset ring-rose-600/20">
                                                    Kesalahan
                                                </span>
                                            @elseif ($severity === 'warning')
                                                <span class="inline-flex items-center rounded-md bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/20">
                                                    Peringatan
                                                </span>
                                            @else
                                                <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700 ring-1 ring-inset ring-slate-600/20">
                                                    {{ ucfirst($finding['severity'] ?? 'Informasi') }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <p class="text-sm font-medium text-slate-900 leading-relaxed">
                                        {{ $finding['message'] ?? '-' }}
                                    </p>

                                    <!-- Bukti (Evidence - Default Tertutup) -->
                                    @if (isset($finding['evidence']) && !empty($finding['evidence']))
                                        <details class="mt-2 text-xs group">
                                            <summary class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-500 hover:text-indigo-600 cursor-pointer select-none transition-colors focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-indigo-500 rounded">
                                                <svg class="w-3.5 h-3.5 transition-transform duration-200 group-open:rotate-90 text-slate-400 group-hover:text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                                </svg>
                                                <span>Lihat bukti</span>
                                            </summary>
                                            <div class="mt-1.5 rounded-lg bg-slate-900 p-3 overflow-x-auto text-slate-100 border border-slate-800">
                                                <pre class="text-xs font-mono leading-relaxed whitespace-pre"><code>{{ json_encode($finding['evidence'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</code></pre>
                                            </div>
                                        </details>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50/50 p-5 text-center text-xs sm:text-sm text-slate-500">
                            <p class="font-medium text-slate-700">Tidak ditemukan temuan deterministik oleh Rule-Based System.</p>
                            <p class="text-xs text-slate-500 mt-0.5">Tidak ada temuan aturan (RBS) pada formulir ini.</p>
                        </div>
                    @endif
                </div>

                <!-- Bagian Catatan Pemeriksaan Hybrid AI -->
                <div class="mt-5 pt-4 border-t border-slate-100">
                    <div class="flex items-center gap-2 mb-1">
                        <div class="w-2 h-2 rounded-full bg-purple-500"></div>
                        <h4 class="text-sm font-semibold text-slate-900">Catatan Pemeriksaan Hybrid AI</h4>
                    </div>
                    <p class="text-[11px] text-slate-500 mb-2.5 text-left">
                        Catatan evaluasi kontekstual yang dihasilkan berdasarkan isi formulir dan konteks temuan pemeriksaan.
                    </p>
                    <div class="rounded-xl bg-slate-50/80 border border-slate-200/80 p-3.5 sm:p-4 text-xs sm:text-sm text-slate-700 leading-relaxed text-left space-y-2 [&_p]:mb-2 [&_p:last-child]:mb-0">
                        @php
                            $renderedHybridReview = $formattedHybridReview ?? \App\Services\ReviewHtmlFormatter::format($activeReviewResult['hybrid_review'] ?? null);
                        @endphp
                        @if (!empty($renderedHybridReview))
                            {!! $renderedHybridReview !!}
                        @else
                            <p class="text-xs text-slate-500 italic">Tidak ada catatan pemeriksaan Hybrid AI untuk formulir ini.</p>
                        @endif
                    </div>
                </div>
            </section>

            <!-- 5. Formulir yang Diperiksa (Section Ringkas Setelah Hasil) -->
            @if ($activePreview)
                <section class="max-w-3xl mx-auto mb-5 rounded-xl border border-slate-200 bg-white p-3.5 sm:p-4 shadow-2xs">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-2.5 mb-2.5">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-slate-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg>
                            <h4 class="text-xs font-semibold text-slate-800 uppercase tracking-wider">
                                Formulir yang Diperiksa
                            </h4>
                        </div>
                        <div class="flex items-center gap-2.5">
                            @if (!empty($activePreview['file_name']))
                                <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-mono text-slate-600">
                                    {{ $activePreview['file_name'] }}
                                </span>
                            @endif
                            <a
                                href="{{ route('review.reset') }}"
                                class="text-xs font-medium text-indigo-600 hover:text-indigo-800 hover:underline inline-flex items-center gap-1 focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-indigo-500 rounded px-1.5 py-0.5 cursor-pointer"
                                title="Hapus formulir ini dan unggah berkas baru"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                </svg>
                                Ganti berkas
                            </a>
                        </div>
                    </div>

                    <!-- Compact Metadata Row -->
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-2 text-left">
                        <div class="sm:col-span-2">
                            <span class="text-[10px] font-medium text-slate-400 block">Nama Kegiatan</span>
                            <span class="text-xs sm:text-sm font-bold text-slate-900 leading-snug">{{ $activePreview['nama_kegiatan'] }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] font-medium text-slate-400 block">Instansi</span>
                            <span class="text-xs font-medium text-slate-800">{{ $activePreview['instansi'] }}</span>
                        </div>
                        <div class="flex items-center justify-between sm:block">
                            <div>
                                <span class="text-[10px] font-medium text-slate-400 block">Tahun</span>
                                <span class="text-xs font-medium text-slate-800">{{ $activePreview['tahun_kegiatan'] }}</span>
                            </div>
                            <div class="sm:mt-1">
                                <span class="text-[10px] font-medium text-slate-400 block">ID Transaksi</span>
                                <span class="text-xs font-mono font-semibold text-slate-600">{{ $activePreview['id_trans'] }}</span>
                            </div>
                        </div>
                    </div>
                </section>
            @endif

            <!-- 6. Alur Sistem Versi Compact Saat Hasil Selesai -->
            <div class="mt-5 pt-3 border-t border-slate-200 text-center">
                <div class="inline-flex flex-wrap items-center justify-center gap-2 sm:gap-5 text-xs font-medium text-slate-600 bg-white border border-slate-200/80 rounded-xl px-4 py-2 shadow-2xs">
                    <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mr-1">Alur Sistem:</span>
                    <span class="inline-flex items-center gap-1.5 text-emerald-700">
                        <span class="w-4 h-4 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold flex items-center justify-center">✓</span>
                        1. Unggah Formulir
                    </span>
                    <span class="text-slate-300 hidden sm:inline">&rarr;</span>
                    <span class="inline-flex items-center gap-1.5 text-emerald-700">
                        <span class="w-4 h-4 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold flex items-center justify-center">✓</span>
                        2. Pemeriksaan RBS
                    </span>
                    <span class="text-slate-300 hidden sm:inline">&rarr;</span>
                    <span class="inline-flex items-center gap-1.5 text-emerald-700">
                        <span class="w-4 h-4 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold flex items-center justify-center">✓</span>
                        3. Catatan Hybrid AI
                    </span>
                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20 ml-1">
                        Terhubung
                    </span>
                </div>
            </div>
        @else
            {{-- KONDISI BELUM ADA HASIL (BELUM ADA FILE ATAU FORMULIR SIAP DIPERIKSA) --}}
            @if ($activePreview)
                <!-- Pratinjau Formulir Siap Diperiksa -->
                <section class="max-w-2xl mx-auto mb-6 rounded-xl border border-slate-200 bg-white p-4.5 sm:p-5 shadow-xs">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-3 mb-3">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                                Formulir siap diperiksa
                            </span>
                            <span class="text-xs text-slate-300">|</span>
                            <span class="text-xs font-medium text-slate-600">Pratinjau Formulir Terunggah</span>
                        </div>
                        <div class="flex items-center gap-2">
                            @if (!empty($activePreview['file_name']))
                                <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-mono text-slate-600">
                                    {{ $activePreview['file_name'] }}
                                </span>
                            @endif
                            <a
                                href="{{ route('review.reset') }}"
                                class="text-xs font-medium text-indigo-600 hover:text-indigo-800 hover:underline cursor-pointer inline-flex items-center gap-1 focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-indigo-500 rounded px-1"
                                title="Batalkan dan unggah berkas baru"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                </svg>
                                Ganti berkas
                            </a>
                        </div>
                    </div>

                    <!-- Informasi Utama: Nama Kegiatan -->
                    <div class="text-left mb-3">
                        <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Nama Kegiatan</span>
                        <h3 class="text-sm sm:text-base font-bold text-slate-900 leading-snug mt-0.5">
                            {{ $activePreview['nama_kegiatan'] }}
                        </h3>
                    </div>

                    <!-- Metadata Sekunder -->
                    <dl class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 text-left border-t border-slate-100 pt-3">
                        <div>
                            <dt class="text-[10px] font-medium text-slate-400">Instansi Penyelenggara</dt>
                            <dd class="mt-0.5 text-xs font-semibold text-slate-800">{{ $activePreview['instansi'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10px] font-medium text-slate-400">Tahun Kegiatan</dt>
                            <dd class="mt-0.5 text-xs font-semibold text-slate-800">{{ $activePreview['tahun_kegiatan'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10px] font-medium text-slate-400">ID Transaksi (id_trans)</dt>
                            <dd class="mt-0.5 text-xs font-mono font-semibold text-slate-600">{{ $activePreview['id_trans'] }}</dd>
                        </div>
                    </dl>

                    <!-- Tombol Utama Jalankan Pemeriksaan -->
                    <div class="mt-4 pt-3 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                        <p class="text-[11px] text-slate-500 leading-relaxed text-left">
                            Menjalankan evaluasi aturan RBS dan inferensi catatan evaluasi Hybrid AI.
                        </p>
                        <form id="form-process" action="{{ route('review.process') }}" method="POST" class="w-full sm:w-auto shrink-0">
                            @csrf
                            <button
                                type="submit"
                                id="btn-process"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-5 py-2.5 text-xs sm:text-sm font-semibold text-white shadow-xs hover:bg-emerald-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600 transition-all cursor-pointer disabled:opacity-75 disabled:cursor-not-allowed"
                            >
                                <svg id="btn-process-icon" class="w-4 h-4 text-emerald-100 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m3.75 13.5 10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z" />
                                </svg>
                                <span id="btn-process-text">Jalankan Pemeriksaan</span>
                            </button>
                        </form>
                    </div>
                </section>
            @else
                <!-- Area Upload Saat Belum Ada Berkas -->
                <div class="max-w-xl mx-auto mb-6">
                    <form action="{{ route('review.upload') }}" method="POST" enctype="multipart/form-data" class="upload-dropzone rounded-xl border-2 border-dashed border-indigo-200 bg-white p-5 sm:p-6 text-center shadow-xs hover:border-indigo-400 transition-colors">
                        @csrf
                        <div class="mx-auto w-10 h-10 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600 mb-2">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                            </svg>
                        </div>

                        <h3 class="text-sm sm:text-base font-semibold text-slate-800">
                            Pilih Berkas JSON Formulir ROMANTIK
                        </h3>
                        <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                            Tarik &amp; letakkan berkas <code class="text-indigo-600 font-semibold">.json</code> ke area ini atau gunakan tombol pemilih berkas (maksimal 1 berkas).
                        </p>

                        <!-- Input Berkas -->
                        <div class="mt-4">
                            <input
                                type="file"
                                name="file"
                                id="file"
                                accept=".json,application/json"
                                required
                                class="block w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer border border-slate-200 rounded-lg p-1.5 focus:outline-hidden focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                        <p class="selected-file-name hidden text-xs font-medium text-emerald-700 mt-2"></p>

                        <!-- Tombol Submit -->
                        <div class="mt-4">
                            <button
                                type="submit"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 rounded-lg bg-indigo-600 px-5 py-2 text-xs sm:text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 transition-colors cursor-pointer"
                            >
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                </svg>
                                Unggah Berkas Formulir
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            <!-- Kartu Alur Sistem 1-2-3 (Versi Awal) -->
            <div class="mt-8 pt-5 border-t border-slate-200">
                <div class="text-center max-w-lg mx-auto mb-3">
                    <h4 class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Alur Kerja Sistem</h4>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 max-w-4xl mx-auto">
                    <!-- Tahap 1 -->
                    <div class="rounded-xl border border-slate-200 bg-white p-3.5 text-left shadow-2xs">
                        <div class="flex items-center justify-between mb-2">
                            <span class="w-6 h-6 rounded-md bg-blue-50 text-blue-700 font-bold text-xs flex items-center justify-center">1</span>
                            @if ($activePreview)
                                <span class="inline-flex items-center rounded-full bg-sky-50 px-2 py-0.5 text-[10px] font-medium text-sky-700 ring-1 ring-inset ring-sky-600/20">Siap</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-600">Aktif</span>
                            @endif
                        </div>
                        <h5 class="font-semibold text-slate-900 text-xs">1. Unggah Formulir</h5>
                        <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                            Membaca berkas JSON formulir ROMANTIK dan memeriksa kelengkapan strukturnya.
                        </p>
                    </div>

                    <!-- Tahap 2 -->
                    <div class="rounded-xl border border-slate-200 bg-white p-3.5 text-left shadow-2xs">
                        <div class="flex items-center justify-between mb-2">
                            <span class="w-6 h-6 rounded-md bg-emerald-50 text-emerald-700 font-bold text-xs flex items-center justify-center">2</span>
                            @if ($activePreview)
                                <span class="inline-flex items-center rounded-full bg-sky-50 px-2 py-0.5 text-[10px] font-medium text-sky-700 ring-1 ring-inset ring-sky-600/20">Siap</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-600">Belum dimulai</span>
                            @endif
                        </div>
                        <h5 class="font-semibold text-slate-900 text-xs">2. Pemeriksaan RBS</h5>
                        <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                            Evaluasi otomatis aturan deterministik (Rule-Based System) atas metadata formulir.
                        </p>
                    </div>

                    <!-- Tahap 3 -->
                    <div class="rounded-xl border border-slate-200 bg-white p-3.5 text-left shadow-2xs">
                        <div class="flex items-center justify-between mb-2">
                            <span class="w-6 h-6 rounded-md bg-purple-50 text-purple-700 font-bold text-xs flex items-center justify-center">3</span>
                            @if ($activePreview)
                                <span class="inline-flex items-center rounded-full bg-sky-50 px-2 py-0.5 text-[10px] font-medium text-sky-700 ring-1 ring-inset ring-sky-600/20">Siap</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-600">Belum dimulai</span>
                            @endif
                        </div>
                        <h5 class="font-semibold text-slate-900 text-xs">3. Catatan Hybrid AI</h5>
                        <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">
                            Catatan evaluasi kontekstual cerdas berdasarkan isi formulir dan konteks temuan.
                        </p>
                    </div>
                </div>
            </div>
        @endif
        @if ($activeReviewResult && $nNotEvaluable > 0)
            <!-- Modal Aturan Belum Dapat Dievaluasi -->
            <div
                id="modal-not-evaluable"
                class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4 overflow-y-auto"
                role="dialog"
                aria-modal="true"
                aria-labelledby="modal-not-evaluable-title"
            >
                <div
                    id="modal-not-evaluable-content"
                    class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl border border-slate-200 flex flex-col max-h-[80vh] overflow-hidden"
                >
                    <!-- Header Modal -->
                    <div class="p-4 sm:p-5 border-b border-slate-200 bg-white shrink-0">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-600 font-mono text-xs font-bold" aria-hidden="true">
                                        ?
                                    </span>
                                    <h3 id="modal-not-evaluable-title" class="text-base sm:text-lg font-bold text-slate-900 tracking-tight">
                                        Aturan Belum Dapat Dievaluasi
                                    </h3>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">
                                    Daftar aturan RBS yang belum dapat dievaluasi pada formulir ini.
                                </p>
                            </div>
                            <button
                                type="button"
                                id="btn-close-modal-header"
                                aria-label="Tutup modal"
                                class="p-1.5 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg cursor-pointer transition-colors focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-indigo-500"
                            >
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Penjelasan Singkat (TUGAS 2) -->
                        <div class="mt-3 rounded-lg bg-slate-50 border border-slate-200/80 p-2.5 sm:p-3 text-xs text-slate-600 leading-relaxed">
                            <p>
                                Aturan berikut belum dapat dievaluasi karena informasi yang diperlukan tidak tersedia atau kondisi evaluasinya tidak terpenuhi.
                            </p>
                        </div>

                        <!-- Search Input (TUGAS 6) -->
                        @if ($notEvaluableCount > 0)
                            <div class="mt-3 relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                    </svg>
                                </div>
                                <input
                                    type="text"
                                    id="input-search-not-evaluable"
                                    placeholder="Cari rule..."
                                    class="block w-full rounded-lg border border-slate-200 bg-slate-50 py-1.5 pl-9 pr-3 text-xs text-slate-900 placeholder:text-slate-400 focus:bg-white focus:outline-hidden focus:ring-2 focus:ring-indigo-500"
                                />
                            </div>
                        @endif
                    </div>

                    <!-- Body Modal (Scrollable) -->
                    <div id="modal-not-evaluable-body" class="p-4 sm:p-5 overflow-y-auto space-y-3 flex-1 bg-slate-50/50">
                        @if ($notEvaluableCount > 0)
                            @php
                                $catalogService = app(\App\Services\RuleCatalogService::class);
                            @endphp
                            @foreach ($notEvaluableRules as $index => $rule)
                                @php
                                    $ruleId = (string) ($rule['rule_id'] ?? '-');
                                    $reason = (string) ($rule['reason'] ?? '');
                                    $message = (string) ($rule['message'] ?? '');
                                    $applicability = (string) ($rule['applicability'] ?? '');
                                    $evidence = $rule['evidence'] ?? null;
                                    $formatted = \App\Services\RbsNotEvaluableFormatter::format(
                                        $ruleId,
                                        $reason,
                                        $message,
                                        $applicability,
                                        is_array($evidence) ? $evidence : null
                                    );
                                    $catalogRule = ($ruleId !== '-' && $ruleId !== '') ? $catalogService->find($ruleId) : null;
                                    $ruleTitle = $catalogRule['title'] ?? 'Informasi aturan belum tersedia di katalog.';
                                    $ruleDescription = $catalogRule['description'] ?? null;
                                    $searchHaystack = strtolower($ruleId . ' ' . $ruleTitle . ' ' . ($ruleDescription ?? '') . ' ' . $formatted['title'] . ' ' . $formatted['description'] . ' ' . $reason);
                                @endphp
                                <div
                                    class="not-evaluable-item rounded-xl border border-slate-200/90 bg-white p-3.5 text-left shadow-2xs hover:border-slate-300 transition-colors"
                                    data-rule-id="{{ strtolower($ruleId) }}"
                                    data-rule-text="{{ $searchHaystack }}"
                                >
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="text-xs font-semibold text-slate-500">{{ $index + 1 }}.</span>
                                        <span class="font-mono text-xs font-bold text-slate-900 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
                                            {{ $ruleId }}
                                        </span>
                                        <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-500/10">
                                            Belum Dapat Dievaluasi
                                        </span>
                                    </div>

                                    <h4 class="text-xs sm:text-sm font-semibold text-slate-900 leading-snug">
                                        {{ $ruleTitle }}
                                    </h4>

                                    @if ($ruleDescription)
                                        <div class="mt-2 text-xs">
                                            <span class="font-semibold text-slate-700 block mb-0.5">Apa yang diperiksa:</span>
                                            <p class="text-slate-600 leading-relaxed">{{ $ruleDescription }}</p>
                                        </div>
                                    @endif

                                    <div class="mt-2 text-xs">
                                        <span class="font-semibold text-slate-700 block mb-0.5">Mengapa belum dapat dievaluasi:</span>
                                        <p class="font-semibold text-slate-800">{{ $formatted['title'] }}</p>
                                        <p class="text-slate-600 leading-relaxed mt-0.5">{{ $formatted['description'] }}</p>
                                    </div>

                                    @if ($catalogRule)
                                        <div class="mt-3 flex flex-wrap items-center gap-2">
                                            <a
                                                href="{{ route('rules.index', ['rule' => $ruleId]) }}"
                                                class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold text-indigo-700 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 rounded-lg border border-indigo-200/80 transition-colors focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-indigo-500"
                                            >
                                                <span>Lihat Detail Aturan</span>
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                                </svg>
                                            </a>
                                        </div>
                                    @endif

                                    <!-- Detail Teknis & Bukti (Collapsible) -->
                                    <details class="mt-2.5 text-xs group">
                                        <summary class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-500 hover:text-indigo-600 cursor-pointer select-none transition-colors focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-indigo-500 rounded">
                                            <svg class="w-3.5 h-3.5 transition-transform duration-200 group-open:rotate-90 text-slate-400 group-hover:text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                            </svg>
                                            <span>Lihat bukti</span>
                                        </summary>
                                        <div class="mt-2 space-y-2">
                                            <!-- Detail Teknis Internal -->
                                            <div class="rounded-lg bg-slate-100/80 p-2.5 text-[11px] font-mono text-slate-600 border border-slate-200/70 space-y-1">
                                                @if (!empty($reason))
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-slate-400 font-sans">Kode alasan internal:</span>
                                                        <span class="font-semibold text-slate-700">{{ $reason }}</span>
                                                    </div>
                                                @endif
                                                @if (!empty($applicability))
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-slate-400 font-sans">Applicability:</span>
                                                        <span class="font-semibold text-slate-700">{{ $applicability }}</span>
                                                    </div>
                                                @endif
                                                @if (!empty($message))
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-slate-400 font-sans">Pesan:</span>
                                                        <span class="font-semibold text-slate-700">{{ $message }}</span>
                                                    </div>
                                                @endif
                                            </div>

                                            @if (isset($evidence) && !empty($evidence))
                                                <div class="rounded-lg bg-slate-900 p-3 overflow-x-auto text-slate-100 border border-slate-800">
                                                    <pre class="text-xs font-mono leading-relaxed whitespace-pre"><code>{{ json_encode($evidence, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</code></pre>
                                                </div>
                                            @endif
                                        </div>
                                    </details>
                                </div>
                            @endforeach

                            <!-- State tidak ditemukan saat mencari -->
                            <div id="search-not-found" class="hidden rounded-xl border border-dashed border-slate-200 bg-white p-6 text-center text-xs sm:text-sm text-slate-500">
                                <p class="font-medium text-slate-700">Tidak ada aturan yang cocok dengan kata kunci.</p>
                                <p class="text-xs text-slate-400 mt-1">Coba gunakan kata kunci rule ID atau alasan lain.</p>
                            </div>
                        @else
                            <!-- Fallback Saat Detail Array Kosong dari Backend (TUGAS 7) -->
                            <div class="rounded-xl border border-dashed border-slate-200 bg-white p-6 text-center text-xs sm:text-sm text-slate-500">
                                <svg class="w-8 h-8 text-slate-300 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                                </svg>
                                <p class="font-medium text-slate-700">Detail aturan belum tersedia pada response pemeriksaan.</p>
                                <p class="text-xs text-slate-400 mt-1">
                                    Backend response saat ini hanya menyertakan jumlah ringkasan aturan ({{ $nNotEvaluable }} aturan).
                                </p>
                            </div>
                        @endif
                    </div>

                    <!-- Footer Modal -->
                    <div class="p-3.5 sm:p-4 border-t border-slate-200 bg-white flex flex-wrap items-center justify-between gap-2 shrink-0">
                        <div class="text-xs text-slate-500">
                            @if ($notEvaluableCount > 0)
                                <span>Total: <strong class="font-mono text-slate-700">{{ $notEvaluableCount }}</strong> aturan</span>
                                @if ($notEvaluableCount !== $nNotEvaluable)
                                    <span class="text-[11px] text-amber-600 block sm:inline sm:ml-1.5">(Ringkasan mencatat {{ $nNotEvaluable }} aturan)</span>
                                @endif
                            @else
                                <span>Ringkasan: <strong class="font-mono text-slate-700">{{ $nNotEvaluable }}</strong> aturan</span>
                            @endif
                        </div>
                        <button
                            type="button"
                            id="btn-close-modal-footer"
                            class="px-4 py-2 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors cursor-pointer focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-indigo-500"
                        >
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </main>

    <!-- 8. Footer Kompak -->
    <footer class="border-t border-slate-200 bg-white py-3.5 text-center text-xs text-slate-500 mt-6">
        <p>&copy; {{ date('Y') }} ROMANTIK Web &mdash; Halaman Awal Prototipe</p>
    </footer>

    <!-- Frontend UI Interactions (Vanilla JS) -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // 1. Toast Notification Auto-Dismiss & Manual Close
            var toast = document.getElementById('toast-success');
            if (toast) {
                function dismissToast() {
                    toast.classList.add('opacity-0', '-translate-y-2');
                    setTimeout(function () {
                        if (toast && toast.parentNode) {
                            toast.parentNode.removeChild(toast);
                        }
                    }, 300);
                }

                var btnClose = document.getElementById('btn-close-toast');
                if (btnClose) {
                    btnClose.addEventListener('click', dismissToast);
                }

                // Otomatis hilang setelah 4 detik
                setTimeout(dismissToast, 4000);
            }

            // 2. State Sedang Memeriksa (Loading state & Prevent Double Submit)
            var formProcess = document.getElementById('form-process');
            var btnProcess = document.getElementById('btn-process');
            var btnProcessText = document.getElementById('btn-process-text');
            var btnProcessIcon = document.getElementById('btn-process-icon');

            if (formProcess && btnProcess) {
                var isProcessing = false;
                formProcess.addEventListener('submit', function (e) {
                    if (isProcessing) {
                        e.preventDefault();
                        return false;
                    }
                    isProcessing = true;
                    btnProcess.disabled = true;
                    btnProcess.classList.add('opacity-75', 'cursor-not-allowed');

                    if (btnProcessIcon) {
                        btnProcessIcon.outerHTML = '<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline-block shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
                    }
                    if (btnProcessText) {
                        btnProcessText.textContent = 'Sedang Memeriksa...';
                    }
                });
            }

            // 3. Drag and Drop & File Display pada Area Upload
            var dropZones = document.querySelectorAll('.upload-dropzone');
            dropZones.forEach(function (zone) {
                var fileInput = zone.querySelector('input[type="file"]');
                var fileNameDisplay = zone.querySelector('.selected-file-name');

                if (fileInput) {
                    fileInput.addEventListener('change', function () {
                        if (this.files && this.files.length > 0 && fileNameDisplay) {
                            fileNameDisplay.textContent = 'Berkas terpilih: ' + this.files[0].name;
                            fileNameDisplay.classList.remove('hidden');
                        }
                    });
                }

                ['dragenter', 'dragover'].forEach(function (eventName) {
                    zone.addEventListener(eventName, function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        zone.classList.add('border-indigo-500', 'bg-indigo-50/40');
                    }, false);
                });

                ['dragleave', 'drop'].forEach(function (eventName) {
                    zone.addEventListener(eventName, function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        zone.classList.remove('border-indigo-500', 'bg-indigo-50/40');
                    }, false);
                });

                zone.addEventListener('drop', function (e) {
                    var dt = e.dataTransfer;
                    var files = dt.files;
                    if (files && files.length > 0 && fileInput) {
                        fileInput.files = files;
                        if (fileNameDisplay) {
                            fileNameDisplay.textContent = 'Berkas terpilih: ' + files[0].name;
                            fileNameDisplay.classList.remove('hidden');
                        }
                    }
                }, false);
            });

            // 4. Modal Aturan Belum Dapat Dievaluasi
            var modalNotEvaluable = document.getElementById('modal-not-evaluable');
            var btnOpenNotEvaluable = document.getElementById('btn-open-not-evaluable');
            var btnCloseModalHeader = document.getElementById('btn-close-modal-header');
            var btnCloseModalFooter = document.getElementById('btn-close-modal-footer');
            var searchInput = document.getElementById('input-search-not-evaluable');
            var ruleItems = document.querySelectorAll('.not-evaluable-item');
            var searchNotFound = document.getElementById('search-not-found');

            if (modalNotEvaluable && btnOpenNotEvaluable) {
                function openModal() {
                    modalNotEvaluable.classList.remove('hidden');
                    document.body.classList.add('overflow-hidden');
                    if (searchInput) {
                        searchInput.value = '';
                        ruleItems.forEach(function (item) {
                            item.classList.remove('hidden');
                        });
                        if (searchNotFound) {
                            searchNotFound.classList.add('hidden');
                        }
                        setTimeout(function () {
                            searchInput.focus();
                        }, 50);
                    } else if (btnCloseModalHeader) {
                        btnCloseModalHeader.focus();
                    }
                }

                function closeModal() {
                    modalNotEvaluable.classList.add('hidden');
                    document.body.classList.remove('overflow-hidden');
                    btnOpenNotEvaluable.focus();
                }

                btnOpenNotEvaluable.addEventListener('click', openModal);

                if (btnCloseModalHeader) {
                    btnCloseModalHeader.addEventListener('click', closeModal);
                }

                if (btnCloseModalFooter) {
                    btnCloseModalFooter.addEventListener('click', closeModal);
                }

                // Klik backdrop menutup modal
                modalNotEvaluable.addEventListener('click', function (e) {
                    if (e.target === modalNotEvaluable) {
                        closeModal();
                    }
                });

                // Tombol Escape menutup modal
                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape' && !modalNotEvaluable.classList.contains('hidden')) {
                        closeModal();
                    }
                });

                // Client-side search / filter
                if (searchInput) {
                    searchInput.addEventListener('input', function () {
                        var query = this.value.trim().toLowerCase();
                        var visibleCount = 0;
                        ruleItems.forEach(function (item) {
                            var ruleId = (item.getAttribute('data-rule-id') || '').toLowerCase();
                            var text = (item.getAttribute('data-rule-text') || '').toLowerCase();
                            if (query === '' || ruleId.indexOf(query) !== -1 || text.indexOf(query) !== -1) {
                                item.classList.remove('hidden');
                                visibleCount++;
                            } else {
                                item.classList.add('hidden');
                            }
                        });

                        if (searchNotFound) {
                            if (visibleCount === 0 && query !== '') {
                                searchNotFound.classList.remove('hidden');
                            } else {
                                searchNotFound.classList.add('hidden');
                            }
                        }
                    });
                }
            }
        });
    </script>
</body>
</html>
