<?php

namespace App\Services;

class ReviewHtmlFormatter
{
    /**
     * Memformat string hybrid_review agar aman dari XSS dengan meng-escape seluruh teks
     * terlebih dahulu, kemudian hanya mengizinkan tag <p>, </p>, dan <br> tanpa atribut.
     * Jika catatan kosong, mengembalikan pesan pemberitahuan yang sesuai.
     */
    public static function format(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '<p class="text-slate-500 italic text-sm">Tidak ada catatan pemeriksaan Hybrid AI untuk formulir ini.</p>';
        }

        // 1. Escape seluruh teks biasa dan tag HTML/entitas
        $escaped = htmlspecialchars($html, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        // 2. Hanya izinkan kembali tag <p>, </p>, dan <br> tanpa atribut
        $patterns = [
            '/&lt;p&gt;/i',
            '/&lt;\/p&gt;/i',
            '/&lt;br\s*\/?&gt;/i',
        ];

        $replacements = [
            '<p>',
            '</p>',
            '<br>',
        ];

        $formatted = preg_replace($patterns, $replacements, $escaped);

        if (trim(strip_tags((string) $formatted)) === '') {
            return '<p class="text-slate-500 italic text-sm">Tidak ada catatan pemeriksaan Hybrid AI untuk formulir ini.</p>';
        }

        return (string) $formatted;
    }
}
