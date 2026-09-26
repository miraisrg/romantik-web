<?php

use App\Services\ReviewHtmlFormatter;

test('mengembalikan pesan fallback jika html bernilai null, kosong, atau hanya whitespace', function () {
    expect(ReviewHtmlFormatter::format(null))->toContain('Tidak ada catatan pemeriksaan Hybrid AI untuk formulir ini.')
        ->and(ReviewHtmlFormatter::format(''))->toContain('Tidak ada catatan pemeriksaan Hybrid AI untuk formulir ini.')
        ->and(ReviewHtmlFormatter::format('   '))->toContain('Tidak ada catatan pemeriksaan Hybrid AI untuk formulir ini.')
        ->and(ReviewHtmlFormatter::format("\n\t  \n"))->toContain('Tidak ada catatan pemeriksaan Hybrid AI untuk formulir ini.');
});

test('mengembalikan pesan fallback jika html hanya berisi tag kosong tanpa narasi', function () {
    expect(ReviewHtmlFormatter::format('<p></p>'))->toContain('Tidak ada catatan pemeriksaan Hybrid AI untuk formulir ini.')
        ->and(ReviewHtmlFormatter::format('<p><br></p>'))->toContain('Tidak ada catatan pemeriksaan Hybrid AI untuk formulir ini.')
        ->and(ReviewHtmlFormatter::format('<p>   <br/>  </p>'))->toContain('Tidak ada catatan pemeriksaan Hybrid AI untuk formulir ini.');
});

test('mengizinkan tag p dan br murni tanpa atribut serta mempertahankan narasi', function () {
    $input = '<p>Paragraf pertama.<br>Baris kedua.<br/>Baris ketiga.<br />Baris keempat.</p><p>Paragraf kedua.</p>';
    $output = ReviewHtmlFormatter::format($input);

    expect($output)->toBe('<p>Paragraf pertama.<br>Baris kedua.<br>Baris ketiga.<br>Baris keempat.</p><p>Paragraf kedua.</p>');
});

test('meng-escape tag html berbahaya dan tag dengan atribut untuk mencegah XSS', function () {
    $input = '<p onclick="alert(1)">Teks dengan event</p><script>alert("xss")</script><img src=x onerror=alert(2)><a href="javascript:void(0)">Link</a>';
    $output = ReviewHtmlFormatter::format($input);

    expect($output)->not->toContain('<script>')
        ->and($output)->not->toContain('</script>')
        ->and($output)->not->toContain('<img')
        ->and($output)->not->toContain('<a')
        ->and($output)->not->toContain('<p onclick')
        ->and($output)->toContain('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;')
        ->and($output)->toContain('&lt;img src=x onerror=alert(2)&gt;')
        ->and($output)->toContain('&lt;p onclick=&quot;alert(1)&quot;&gt;');
});
