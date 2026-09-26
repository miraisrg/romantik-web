<!-- Header Aplikasi (Logo, Judul, dan Navigasi Header) -->
<header class="border-b border-slate-200 bg-white sticky top-0 z-30 shadow-2xs">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 py-2.5 sm:py-3 flex flex-wrap items-center justify-between gap-y-2">
        <div class="flex items-center gap-3 sm:gap-6">
            <a
                href="{{ route('review.index') }}"
                class="group flex items-center gap-2.5 rounded-lg focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-orange-500 p-1 -m-1 transition-opacity hover:opacity-95"
                title="Kembali ke Beranda ROMANTIK Web"
            >
                <div class="w-8 h-8 rounded-lg bg-orange-500 group-hover:bg-orange-600 flex items-center justify-center text-white font-bold text-base shadow-xs transition-colors">
                    R
                </div>
                <div>
                    <span class="text-sm sm:text-base font-semibold text-slate-900 group-hover:text-orange-600 transition-colors leading-tight block">
                        ROMANTIK Web
                    </span>
                    <p class="text-[11px] text-slate-500 leading-none mt-0.5">Rekomendasi Kegiatan Statistik</p>
                </div>
            </a>

            <!-- Navigasi Utama -->
            <nav class="flex items-center gap-1 sm:gap-1.5" aria-label="Navigasi Utama">
                <a
                    href="{{ route('review.index') }}"
                    class="px-2.5 sm:px-3 py-1.5 text-xs sm:text-sm font-medium rounded-lg transition-colors focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-orange-500 {{ request()->routeIs('review.*') ? 'bg-orange-50 text-orange-700 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}"
                    {{ request()->routeIs('review.*') ? 'aria-current="page"' : '' }}
                >
                    Pemeriksaan
                </a>
                <a
                    href="{{ route('rules.index') }}"
                    class="px-2.5 sm:px-3 py-1.5 text-xs sm:text-sm font-medium rounded-lg transition-colors focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-orange-500 {{ request()->routeIs('rules.*') ? 'bg-orange-50 text-orange-700 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}"
                    {{ request()->routeIs('rules.*') ? 'aria-current="page"' : '' }}
                >
                    Katalog Aturan RBS
                </a>
            </nav>
        </div>

        <div class="flex items-center gap-2">
            <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600 ring-1 ring-inset ring-slate-200">
                Tahap Prototipe
            </span>
        </div>
    </div>
</header>
