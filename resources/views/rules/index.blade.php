<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Katalog Aturan RBS - ROMANTIK Web</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 antialiased flex flex-col justify-between">
    @include('partials.header')

    <!-- Konten Utama Halaman Katalog -->
    <main class="max-w-5xl mx-auto px-4 sm:px-6 py-6 w-full flex-1">
        <!-- Header Judul dan Keterangan Halaman -->
        <div class="mb-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div class="flex items-center gap-2.5">
                        <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
                            Katalog Aturan RBS
                        </h1>
                        <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-700/10">
                            {{ $totalRules }} aturan
                        </span>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-600 mt-1 leading-relaxed">
                        Daftar aturan deterministik yang digunakan dalam pemeriksaan formulir ROMANTIK.
                    </p>
                </div>
                <a
                    href="{{ route('review.index') }}"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-600 hover:text-slate-900 bg-white hover:bg-slate-100 rounded-lg border border-slate-200 transition-colors shadow-2xs"
                >
                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                    <span>Halaman Pemeriksaan</span>
                </a>
            </div>
        </div>

        <!-- Toolbar Pencarian & Filter -->
        <div class="mb-5 rounded-2xl border border-slate-200/90 bg-white p-3.5 sm:p-4 shadow-2xs space-y-3">
            <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
                <!-- Search Box -->
                <div class="sm:col-span-6 relative">
                    <label for="input-search-rules" class="sr-only">Cari kode, nama, atau keterangan rule</label>
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                    </div>
                    <input
                        type="text"
                        id="input-search-rules"
                        placeholder="Cari kode, nama, atau keterangan rule..."
                        class="block w-full rounded-lg border border-slate-200 bg-slate-50/70 py-2 pl-9 pr-8 text-xs sm:text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:outline-hidden focus:ring-2 focus:ring-indigo-500 transition-colors"
                    />
                    <button
                        type="button"
                        id="btn-clear-search"
                        class="hidden absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400 hover:text-slate-600 cursor-pointer"
                        aria-label="Bersihkan pencarian"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Filter Bagian / Blok -->
                <div class="sm:col-span-3">
                    <label for="select-filter-section" class="sr-only">Filter Bagian Formulir</label>
                    <select
                        id="select-filter-section"
                        class="block w-full rounded-lg border border-slate-200 bg-slate-50/70 py-2 px-3 text-xs sm:text-sm text-slate-800 focus:bg-white focus:outline-hidden focus:ring-2 focus:ring-indigo-500 transition-colors cursor-pointer"
                    >
                        <option value="">Semua Bagian</option>
                        @foreach ($sections as $section)
                            <option value="{{ $section }}">{{ $section }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Tingkat Temuan / Severity -->
                <div class="sm:col-span-3">
                    <label for="select-filter-severity" class="sr-only">Filter Tingkat Temuan</label>
                    <select
                        id="select-filter-severity"
                        class="block w-full rounded-lg border border-slate-200 bg-slate-50/70 py-2 px-3 text-xs sm:text-sm text-slate-800 focus:bg-white focus:outline-hidden focus:ring-2 focus:ring-indigo-500 transition-colors cursor-pointer"
                    >
                        <option value="">Semua Tingkat</option>
                        <option value="error">Kesalahan</option>
                        <option value="warning">Peringatan</option>
                    </select>
                </div>
            </div>

            <!-- Baris Informasi Hasil & Reset Filter -->
            <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-slate-100 text-xs">
                <div class="text-slate-500">
                    Menampilkan <strong id="visible-count" class="font-mono text-slate-800">{{ $totalRules }}</strong> dari <span class="font-mono">{{ $totalRules }}</span> aturan
                    <span id="active-filter-indicator" class="hidden text-slate-400 ml-1">(tersaring)</span>
                </div>
                <button
                    type="button"
                    id="btn-reset-filters"
                    class="hidden inline-flex items-center gap-1 text-xs font-semibold text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 px-2.5 py-1 rounded-md transition-colors cursor-pointer focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-rose-500"
                >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                    <span>Reset Filter</span>
                </button>
            </div>
        </div>

        <!-- Daftar Aturan RBS (Format List / Card Lebar Padat) -->
        <div id="rules-container" class="space-y-3">
            @forelse ($rules as $rule)
                @php
                    $ruleId = (string) ($rule['rule_id'] ?? '');
                    $title = (string) ($rule['title'] ?? '');
                    $section = (string) ($rule['section'] ?? '');
                    $checkType = (string) ($rule['check_type'] ?? '');
                    $severity = strtolower((string) ($rule['severity'] ?? ''));
                    $description = (string) ($rule['description'] ?? '');
                    $searchData = strtolower($ruleId . ' ' . $title . ' ' . $description . ' ' . $section . ' ' . $checkType);
                @endphp
                <div
                    id="rule-card-{{ $ruleId }}"
                    class="rule-card rounded-xl border border-slate-200/90 bg-white p-4 text-left shadow-2xs hover:border-slate-300 transition-all duration-200"
                    data-rule-id="{{ $ruleId }}"
                    data-section="{{ $section }}"
                    data-severity="{{ $severity }}"
                    data-check-type="{{ $checkType }}"
                    data-search="{{ $searchData }}"
                >
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-mono text-xs font-bold text-slate-900 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
                                {{ $ruleId }}
                            </span>
                            @if ($severity === 'error')
                                <span class="inline-flex items-center gap-1 rounded-md bg-rose-50 px-2 py-0.5 text-xs font-semibold text-rose-700 ring-1 ring-inset ring-rose-600/20">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                    Kesalahan
                                </span>
                            @elseif ($severity === 'warning')
                                <span class="inline-flex items-center gap-1 rounded-md bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/20">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                    Peringatan
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700 ring-1 ring-inset ring-slate-600/20">
                                    {{ ucfirst($severity) }}
                                </span>
                            @endif
                            <span class="text-xs text-slate-500 font-medium bg-slate-50 px-2 py-0.5 rounded border border-slate-200/70">
                                {{ $section }}
                            </span>
                            <span class="text-[11px] text-slate-600 bg-slate-100/70 px-2 py-0.5 rounded">
                                {{ $checkType }}
                            </span>
                        </div>

                        <button
                            type="button"
                            class="btn-open-detail inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold text-indigo-700 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 rounded-lg border border-indigo-200/80 transition-colors cursor-pointer focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-indigo-500"
                            data-rule-id="{{ $ruleId }}"
                            aria-label="Lihat detail aturan {{ $ruleId }}"
                        >
                            <span>Lihat Detail</span>
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                            </svg>
                        </button>
                    </div>

                    <h2 class="text-sm sm:text-base font-semibold text-slate-900 leading-snug">
                        {{ $title }}
                    </h2>

                    <p class="text-xs sm:text-sm text-slate-600 mt-1 leading-relaxed">
                        {{ $description }}
                    </p>
                </div>
            @empty
                <div class="rounded-xl border border-dashed border-slate-200 bg-white p-8 text-center text-slate-500">
                    <p class="font-medium text-slate-700">Katalog aturan belum tersedia.</p>
                </div>
            @endforelse
        </div>

        <!-- State Kosong Saat Pencarian atau Filter Tidak Menghasilkan Data -->
        <div
            id="empty-state"
            class="hidden rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500 shadow-2xs my-4"
        >
            <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-3 text-slate-400">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
            </div>
            <h3 class="text-sm sm:text-base font-semibold text-slate-800">
                Tidak ada aturan yang sesuai dengan pencarian atau filter.
            </h3>
            <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">
                Coba sesuaikan kata kunci pencarian, pilih bagian formulir yang berbeda, atau atur ulang seluruh filter.
            </p>
            <button
                type="button"
                id="btn-reset-filters-empty"
                class="mt-4 inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 rounded-lg border border-indigo-200/80 transition-colors cursor-pointer focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-indigo-500"
            >
                Atur Ulang Filter
            </button>
        </div>
    </main>

    <!-- Modal Detail Rule RBS -->
    <div
        id="modal-rule-detail"
        class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4 overflow-y-auto"
        role="dialog"
        aria-modal="true"
        aria-labelledby="modal-rule-title"
    >
        <div
            id="modal-rule-detail-content"
            class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl border border-slate-200 flex flex-col max-h-[80vh] overflow-hidden"
        >
            <!-- Header Modal -->
            <div class="p-4 sm:p-5 border-b border-slate-200 bg-white shrink-0">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <span id="modal-rule-id" class="font-mono text-xs sm:text-sm font-bold text-slate-900 bg-slate-100 px-2.5 py-1 rounded border border-slate-200">
                            -
                        </span>
                        <span id="modal-rule-severity-badge">
                            <!-- Injected badge -->
                        </span>
                    </div>
                    <button
                        type="button"
                        id="btn-close-modal-header"
                        aria-label="Tutup modal detail aturan"
                        class="p-1.5 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg cursor-pointer transition-colors focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-indigo-500"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <h3 id="modal-rule-title" class="text-base sm:text-lg font-bold text-slate-900 tracking-tight mt-2.5">
                    Nama Rule
                </h3>
            </div>

            <!-- Body Modal (Scrollable) -->
            <div id="modal-rule-body" class="p-4 sm:p-6 overflow-y-auto space-y-4 flex-1 bg-white text-xs sm:text-sm">
                <!-- Metadata Ringkas -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 p-3 rounded-xl bg-slate-50 border border-slate-200/80 text-xs">
                    <div>
                        <span class="text-slate-400 block font-medium">Bagian Formulir:</span>
                        <span id="modal-rule-section" class="font-semibold text-slate-800">-</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block font-medium">Jenis Pemeriksaan:</span>
                        <span id="modal-rule-check-type" class="font-semibold text-slate-800">-</span>
                    </div>
                </div>

                <!-- Apa yang diperiksa -->
                <div class="space-y-1">
                    <span class="font-semibold text-slate-900 block text-xs">Apa yang diperiksa</span>
                    <div class="rounded-xl border border-slate-200/80 bg-slate-50/60 p-3 text-slate-700 leading-relaxed">
                        <p id="modal-rule-description">-</p>
                    </div>
                </div>

                <!-- Kapan aturan berlaku -->
                <div class="space-y-1">
                    <span class="font-semibold text-slate-900 block text-xs">Kapan aturan berlaku</span>
                    <div class="rounded-xl border border-slate-200/80 bg-slate-50/60 p-3 text-slate-700 leading-relaxed">
                        <p id="modal-rule-applicability">-</p>
                    </div>
                </div>

                <!-- Kondisi yang menghasilkan temuan -->
                <div class="space-y-1">
                    <span class="font-semibold text-slate-900 block text-xs">Kondisi yang menghasilkan temuan</span>
                    <div class="rounded-xl border border-rose-200/70 bg-rose-50/40 p-3 text-rose-950 leading-relaxed">
                        <p id="modal-rule-violation">-</p>
                    </div>
                </div>

                <!-- Kondisi lulus -->
                <div class="space-y-1">
                    <span class="font-semibold text-slate-900 block text-xs">Kondisi lulus</span>
                    <div class="rounded-xl border border-emerald-200/70 bg-emerald-50/40 p-3 text-emerald-950 leading-relaxed">
                        <p id="modal-rule-pass">-</p>
                    </div>
                </div>

                <!-- Kapan belum dapat dievaluasi -->
                <div class="space-y-1">
                    <span class="font-semibold text-slate-900 block text-xs">Kapan belum dapat dievaluasi</span>
                    <div class="rounded-xl border border-slate-200/80 bg-slate-50/60 p-3 text-slate-700 leading-relaxed">
                        <p id="modal-rule-not-evaluable">-</p>
                    </div>
                </div>

                <!-- Pesan yang digunakan sistem -->
                <div class="space-y-1">
                    <span class="font-semibold text-slate-900 block text-xs">Pesan yang digunakan sistem</span>
                    <div class="rounded-xl border border-slate-200/80 bg-slate-50/60 p-3 text-slate-800 leading-relaxed">
                        <p id="modal-rule-ui-message" class="font-medium">-</p>
                    </div>
                </div>

                <!-- Detail Teknis (Collapsible) -->
                <details class="group rounded-xl border border-slate-200 bg-slate-50/60 p-3.5 text-xs">
                    <summary class="inline-flex items-center gap-1.5 font-semibold text-slate-700 hover:text-indigo-600 cursor-pointer select-none transition-colors focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-indigo-500 rounded">
                        <svg class="w-3.5 h-3.5 transition-transform duration-200 group-open:rotate-90 text-slate-400 group-hover:text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                        <span>Detail Teknis</span>
                    </summary>
                    <div class="mt-2.5 pt-2 border-t border-slate-200/70 space-y-1.5">
                        <span class="text-slate-500 block">Field yang diperiksa:</span>
                        <div id="modal-rule-fields" class="flex flex-wrap gap-1.5 font-mono text-[11px]">
                            <!-- Injected badges -->
                        </div>
                    </div>
                </details>
            </div>

            <!-- Footer Modal -->
            <div class="p-3.5 sm:p-4 border-t border-slate-200 bg-white flex items-center justify-end shrink-0">
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

    <!-- Footer Kompak -->
    <footer class="border-t border-slate-200 bg-white py-3.5 text-center text-xs text-slate-500 mt-6">
        <p>&copy; {{ date('Y') }} ROMANTIK Web &mdash; Halaman Awal Prototipe</p>
    </footer>

    <!-- JSON Metadata Rule Katalog untuk Interaksi Klien Cepat -->
    <script id="catalog-rules-json" type="application/json">
        {!! json_encode($rules, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}
    </script>

    <!-- Interaksi Frontend Vanilla JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // 1. Data Katalog
            var rulesDataElement = document.getElementById('catalog-rules-json');
            var rulesData = [];
            var rulesMap = {};

            try {
                if (rulesDataElement && rulesDataElement.textContent) {
                    rulesData = JSON.parse(rulesDataElement.textContent);
                    for (var i = 0; i < rulesData.length; i++) {
                        var item = rulesData[i];
                        if (item && item.rule_id) {
                            rulesMap[item.rule_id.toUpperCase().trim()] = item;
                        }
                    }
                }
            } catch (e) {
                console.error('Gagal memproses data JSON katalog aturan:', e);
            }

            // 2. Elemen Kontrol Pencarian & Filter
            var searchInput = document.getElementById('input-search-rules');
            var clearSearchBtn = document.getElementById('btn-clear-search');
            var sectionSelect = document.getElementById('select-filter-section');
            var severitySelect = document.getElementById('select-filter-severity');
            var resetBtn = document.getElementById('btn-reset-filters');
            var resetBtnEmpty = document.getElementById('btn-reset-filters-empty');
            var filterIndicator = document.getElementById('active-filter-indicator');
            var visibleCountEl = document.getElementById('visible-count');
            var emptyStateEl = document.getElementById('empty-state');
            var ruleCards = document.querySelectorAll('.rule-card');

            function applyFilters() {
                var searchQuery = (searchInput ? searchInput.value : '').toLowerCase().trim();
                var selectedSection = sectionSelect ? sectionSelect.value : '';
                var selectedSeverity = severitySelect ? severitySelect.value.toLowerCase() : '';

                var hasActiveFilter = searchQuery !== '' || selectedSection !== '' || selectedSeverity !== '';

                if (clearSearchBtn) {
                    if (searchQuery !== '') {
                        clearSearchBtn.classList.remove('hidden');
                    } else {
                        clearSearchBtn.classList.add('hidden');
                    }
                }

                if (resetBtn) {
                    if (hasActiveFilter) {
                        resetBtn.classList.remove('hidden');
                    } else {
                        resetBtn.classList.add('hidden');
                    }
                }

                if (filterIndicator) {
                    if (hasActiveFilter) {
                        filterIndicator.classList.remove('hidden');
                    } else {
                        filterIndicator.classList.add('hidden');
                    }
                }

                var visibleCount = 0;

                for (var i = 0; i < ruleCards.length; i++) {
                    var card = ruleCards[i];
                    var cardSearch = (card.getAttribute('data-search') || '').toLowerCase();
                    var cardSection = card.getAttribute('data-section') || '';
                    var cardSeverity = (card.getAttribute('data-severity') || '').toLowerCase();

                    var matchesSearch = true;
                    if (searchQuery !== '') {
                        // Mendukung pencarian multi-kata (semua token harus cocok)
                        var queryTokens = searchQuery.split(/\s+/);
                        for (var t = 0; t < queryTokens.length; t++) {
                            if (cardSearch.indexOf(queryTokens[t]) === -1) {
                                matchesSearch = false;
                                break;
                            }
                        }
                    }

                    var matchesSection = (selectedSection === '' || cardSection === selectedSection);
                    var matchesSeverity = (selectedSeverity === '' || cardSeverity === selectedSeverity);

                    if (matchesSearch && matchesSection && matchesSeverity) {
                        card.classList.remove('hidden');
                        visibleCount++;
                    } else {
                        card.classList.add('hidden');
                    }
                }

                if (visibleCountEl) {
                    visibleCountEl.textContent = visibleCount;
                }

                if (emptyStateEl) {
                    if (visibleCount === 0) {
                        emptyStateEl.classList.remove('hidden');
                    } else {
                        emptyStateEl.classList.add('hidden');
                    }
                }
            }

            function resetFilters() {
                if (searchInput) searchInput.value = '';
                if (sectionSelect) sectionSelect.value = '';
                if (severitySelect) severitySelect.value = '';
                applyFilters();
                if (searchInput) searchInput.focus();
            }

            if (searchInput) searchInput.addEventListener('input', applyFilters);
            if (clearSearchBtn) {
                clearSearchBtn.addEventListener('click', function () {
                    if (searchInput) searchInput.value = '';
                    applyFilters();
                    if (searchInput) searchInput.focus();
                });
            }
            if (sectionSelect) sectionSelect.addEventListener('change', applyFilters);
            if (severitySelect) severitySelect.addEventListener('change', applyFilters);
            if (resetBtn) resetBtn.addEventListener('click', resetFilters);
            if (resetBtnEmpty) resetBtnEmpty.addEventListener('click', resetFilters);

            // 3. Modal Detail Rule
            var modal = document.getElementById('modal-rule-detail');
            var modalContent = document.getElementById('modal-rule-detail-content');
            var btnCloseHeader = document.getElementById('btn-close-modal-header');
            var btnCloseFooter = document.getElementById('btn-close-modal-footer');
            var lastFocusedElement = null;

            function openRuleModal(ruleId) {
                if (!ruleId) return;
                var key = ruleId.toUpperCase().trim();
                var rule = rulesMap[key];
                if (!rule) {
                    console.warn('Aturan dengan ID ' + ruleId + ' tidak ditemukan di katalog.');
                    return;
                }

                lastFocusedElement = document.activeElement;

                // Isi data modal
                var elId = document.getElementById('modal-rule-id');
                var elTitle = document.getElementById('modal-rule-title');
                var elSeverity = document.getElementById('modal-rule-severity-badge');
                var elSection = document.getElementById('modal-rule-section');
                var elCheckType = document.getElementById('modal-rule-check-type');
                var elDescription = document.getElementById('modal-rule-description');
                var elApplicability = document.getElementById('modal-rule-applicability');
                var elViolation = document.getElementById('modal-rule-violation');
                var elPass = document.getElementById('modal-rule-pass');
                var elNotEvaluable = document.getElementById('modal-rule-not-evaluable');
                var elUiMessage = document.getElementById('modal-rule-ui-message');
                var elFields = document.getElementById('modal-rule-fields');

                if (elId) elId.textContent = rule.rule_id || '-';
                if (elTitle) elTitle.textContent = rule.title || '-';
                if (elSection) elSection.textContent = rule.section || '-';
                if (elCheckType) elCheckType.textContent = rule.check_type || '-';
                if (elDescription) elDescription.textContent = rule.description || '-';
                if (elApplicability) elApplicability.textContent = rule.applicability || '-';
                if (elViolation) elViolation.textContent = rule.violation_condition || '-';
                if (elPass) elPass.textContent = rule.pass_condition || '-';
                if (elNotEvaluable) elNotEvaluable.textContent = rule.not_evaluable_condition || '-';
                if (elUiMessage) elUiMessage.textContent = rule.ui_message || '-';

                // Severity badge
                if (elSeverity) {
                    var sev = (rule.severity || '').toLowerCase();
                    if (sev === 'error') {
                        elSeverity.innerHTML = '<span class="inline-flex items-center gap-1 rounded-md bg-rose-50 px-2 py-0.5 text-xs font-semibold text-rose-700 ring-1 ring-inset ring-rose-600/20"><span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>Kesalahan</span>';
                    } else if (sev === 'warning') {
                        elSeverity.innerHTML = '<span class="inline-flex items-center gap-1 rounded-md bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/20"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>Peringatan</span>';
                    } else {
                        elSeverity.innerHTML = '<span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700 ring-1 ring-inset ring-slate-600/20">' + (rule.severity || '-') + '</span>';
                    }
                }

                // Fields badges
                if (elFields) {
                    elFields.innerHTML = '';
                    var fields = rule.main_field || [];
                    if (Array.isArray(fields) && fields.length > 0) {
                        for (var f = 0; f < fields.length; f++) {
                            var span = document.createElement('span');
                            span.className = 'px-2 py-0.5 rounded bg-slate-200/80 text-slate-800 border border-slate-300/70';
                            span.textContent = fields[f];
                            elFields.appendChild(span);
                        }
                    } else {
                        elFields.innerHTML = '<span class="text-slate-400 italic">Tidak ada field spesifik</span>';
                    }
                }

                // Tampilkan modal
                if (modal) {
                    modal.classList.remove('hidden');
                    document.body.classList.add('overflow-hidden');
                    if (btnCloseHeader) {
                        btnCloseHeader.focus();
                    }
                }
            }

            function closeRuleModal() {
                if (modal) {
                    modal.classList.add('hidden');
                    document.body.classList.remove('overflow-hidden');
                    if (lastFocusedElement) {
                        try {
                            lastFocusedElement.focus();
                        } catch (e) {}
                    }
                }
            }

            // Pasang event listener tombol "Lihat Detail"
            var detailButtons = document.querySelectorAll('.btn-open-detail');
            for (var b = 0; b < detailButtons.length; b++) {
                detailButtons[b].addEventListener('click', function () {
                    var rId = this.getAttribute('data-rule-id');
                    openRuleModal(rId);
                });
            }

            if (btnCloseHeader) btnCloseHeader.addEventListener('click', closeRuleModal);
            if (btnCloseFooter) btnCloseFooter.addEventListener('click', closeRuleModal);

            if (modal) {
                modal.addEventListener('click', function (e) {
                    if (e.target === modal) {
                        closeRuleModal();
                    }
                });
            }

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' || e.key === 'Esc') {
                    if (modal && !modal.classList.contains('hidden')) {
                        closeRuleModal();
                    }
                }
            });

            // 4. Integrasi Query Parameter URL: ?rule=R-VI-05
            var urlParams = new URLSearchParams(window.location.search);
            var targetRule = urlParams.get('rule') || (window.location.hash ? window.location.hash.substring(1) : '');

            if (targetRule) {
                targetRule = targetRule.trim();
                var cardId = 'rule-card-' + targetRule;
                var targetCard = document.getElementById(cardId);

                if (targetCard) {
                    setTimeout(function () {
                        targetCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        targetCard.classList.add('ring-2', 'ring-indigo-500', 'border-indigo-300');
                        setTimeout(function () {
                            targetCard.classList.remove('ring-2', 'ring-indigo-500', 'border-indigo-300');
                        }, 3500);
                        openRuleModal(targetRule);
                    }, 150);
                } else {
                    // Coba buka modal langsung jika card belum ter-render atau ID memiliki formatting lain
                    openRuleModal(targetRule);
                }
            }
        });
    </script>
</body>
</html>
