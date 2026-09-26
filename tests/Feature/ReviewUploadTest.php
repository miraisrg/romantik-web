<?php

use Illuminate\Http\UploadedFile;

test('halaman utama menampilkan form unggah dan penanda status baru', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertSee('Penanda Status: Fitur Unggah Aktif &mdash; Pemeriksaan AI Belum Terhubung', false);
    $response->assertSee('Pemeriksaan Formulir ROMANTIK');
    $response->assertSee('Unggah Berkas Formulir');
});

test('route POST duplikat pada root tidak tersedia dan hanya tersedia pada /review/upload', function () {
    $file = UploadedFile::fake()->createWithContent('test.json', json_encode(['id_trans' => '1', 'form' => '{}']));

    // POST ke / harus ditolak (Method Not Allowed)
    $this->post('/', ['file' => $file])->assertStatus(405);

    // POST ke /review/upload harus diarahkan/berhasil
    $this->post(route('review.upload'), ['file' => $file])->assertRedirect(route('review.index'));
});

test('berhasil mengunggah berkas JSON formulir ROMANTIK valid dengan form berupa string dan menampilkan pratinjau', function () {
    $romantikData = [
        'id_trans' => 'TRX-2024-999',
        'nama_kegiatan' => 'Survei Evaluasi Layanan Publik',
        'tahun_kegiatan' => 2024,
        'instansi' => 'BPS Provinsi Jawa Barat',
        'form' => json_encode([
            'judul' => 'Survei Evaluasi Layanan Publik',
            'variabel' => ['kepuasan', 'kemudahan_akses'],
        ]),
    ];

    $rawJson = json_encode($romantikData, JSON_PRETTY_PRINT);

    $file = UploadedFile::fake()->createWithContent(
        'romantik_form.json',
        $rawJson
    );

    $response = $this->post(route('review.upload'), [
        'file' => $file,
    ]);

    $response->assertRedirect(route('review.index'));
    $response->assertSessionHas('success', 'Berkas formulir ROMANTIK berhasil diunggah.');
    $response->assertSessionHas('preview', function ($preview) {
        return $preview['id_trans'] === 'TRX-2024-999'
            && $preview['nama_kegiatan'] === 'Survei Evaluasi Layanan Publik'
            && $preview['tahun_kegiatan'] === '2024'
            && $preview['instansi'] === 'BPS Provinsi Jawa Barat';
    });

    // Memastikan teks JSON asli disimpan pada key romantik_json dan form berupa string dipertahankan apa adanya
    $response->assertSessionHas('romantik_json', function ($savedJson) use ($rawJson) {
        $decoded = json_decode($savedJson, false);

        return $savedJson === $rawJson
            && is_string($decoded->form);
    });

    // Menguji tampilan setelah redirect
    $followedResponse = $this->followingRedirects()->post(route('review.upload'), [
        'file' => $file,
    ]);

    $followedResponse->assertStatus(200);
    $followedResponse->assertSee('TRX-2024-999');
    $followedResponse->assertSee('Survei Evaluasi Layanan Publik');
    $followedResponse->assertSee('2024');
    $followedResponse->assertSee('BPS Provinsi Jawa Barat');
    $followedResponse->assertSee('Pratinjau Formulir Terunggah');
});

test('berhasil mengunggah berkas JSON dengan form berupa objek dan id_trans berupa angka', function () {
    $romantikData = [
        'id_trans' => 12345,
        'nama_kegiatan' => 'Sensus Pertanian Contoh',
        'tahun_kegiatan' => 2023,
        'instansi' => 'BPS RI',
        'form' => [
            'judul' => 'Sensus Pertanian',
            'sektor' => 'Pertanian',
        ],
    ];

    $file = UploadedFile::fake()->createWithContent(
        'romantik_objek.json',
        json_encode($romantikData)
    );

    $response = $this->post(route('review.upload'), [
        'file' => $file,
    ]);

    $response->assertRedirect(route('review.index'));
    $response->assertSessionHas('preview', function ($preview) {
        return $preview['id_trans'] === '12345'
            && $preview['nama_kegiatan'] === 'Sensus Pertanian Contoh';
    });
    $response->assertSessionHas('romantik_json', function ($savedJson) {
        $decoded = json_decode($savedJson, false);

        return is_object($decoded->form) && $decoded->form->judul === 'Sensus Pertanian';
    });
});

test('struktur objek JSON yang mengandung {} serta [] tetap dapat dibedakan setelah disimpan dan dibaca kembali dari sesi', function () {
    $jsonContent = '{"id_trans":"TRX-EMPTY-01","nama_kegiatan":"Uji Distingsi","tahun_kegiatan":2025,"instansi":"BPS","form":"{}","sub_objek":{},"sub_array":[]}';

    $file = UploadedFile::fake()->createWithContent('empty_struct.json', $jsonContent);

    $response = $this->post(route('review.upload'), [
        'file' => $file,
    ]);

    $response->assertRedirect(route('review.index'));
    $response->assertSessionHas('romantik_json', function (string $savedJson) use ($jsonContent) {
        if ($savedJson !== $jsonContent) {
            return false;
        }

        // Baca kembali teks JSON dari sesi sebagai objek
        $decoded = json_decode($savedJson, false);

        // sub_objek tetap berupa objek (stdClass), bukan array
        $isObjectPreserved = is_object($decoded->sub_objek) && ($decoded->sub_objek instanceof stdClass);

        // sub_array tetap berupa array numerik/list, bukan objek
        $isArrayPreserved = is_array($decoded->sub_array);

        return $isObjectPreserved && $isArrayPreserved;
    });
});

test('menghapus pratinjau dan data sesi lama saat pengguna mencoba mengunggah berkas baru meskipun gagal', function () {
    $initialSession = [
        'preview' => [
            'id_trans' => 'OLD-001',
            'nama_kegiatan' => 'Kegiatan Lama',
            'tahun_kegiatan' => '2020',
            'instansi' => 'Instansi Lama',
            'file_name' => 'lama.json',
        ],
        'romantik_json' => '{"id_trans":"OLD-001","form":"{}"}',
        'romantik_data' => ['id_trans' => 'OLD-001'],
    ];

    $corruptedFile = UploadedFile::fake()->createWithContent('corrupted.json', '{ invalid_json');

    $response = $this->withSession($initialSession)
        ->from('/')
        ->post(route('review.upload'), [
            'file' => $corruptedFile,
        ]);

    $response->assertRedirect('/');
    $response->assertSessionHasErrors(['file']);
    $response->assertSessionMissing('preview');
    $response->assertSessionMissing('romantik_json');
    $response->assertSessionMissing('romantik_data');
});

test('menolak berkas yang bukan berekstensi .json', function () {
    $file = UploadedFile::fake()->createWithContent('document.txt', 'Teks biasa');

    $response = $this->from('/')->post(route('review.upload'), [
        'file' => $file,
    ]);

    $response->assertRedirect('/');
    $response->assertSessionHasErrors(['file']);
});

test('menolak berkas dengan format JSON yang rusak atau tidak valid', function () {
    $file = UploadedFile::fake()->createWithContent('corrupted.json', '{ id_trans: 123, unquoted_key }');

    $response = $this->from('/')->post(route('review.upload'), [
        'file' => $file,
    ]);

    $response->assertRedirect('/');
    $response->assertSessionHasErrors(['file']);
});

test('menolak berkas JSON dengan akar berupa array / multi-form', function () {
    $multiFormData = [
        ['id_trans' => 'TRX-001', 'form' => '{}'],
        ['id_trans' => 'TRX-002', 'form' => '{}'],
    ];

    $file = UploadedFile::fake()->createWithContent('multi_form.json', json_encode($multiFormData));

    $response = $this->from('/')->post(route('review.upload'), [
        'file' => $file,
    ]);

    $response->assertRedirect('/');
    $response->assertSessionHasErrors(['file']);
});

test('menolak berkas JSON yang tidak memiliki id_trans atau id_trans bukan string/angka', function () {
    // Tanpa id_trans
    $noIdTrans = [
        'nama_kegiatan' => 'Kegiatan Tanpa ID',
        'form' => '{}',
    ];
    $response = $this->from('/')->post(route('review.upload'), [
        'file' => UploadedFile::fake()->createWithContent('no_id.json', json_encode($noIdTrans)),
    ]);
    $response->assertRedirect('/');
    $response->assertSessionHasErrors(['file']);

    // id_trans berupa array
    $arrayIdTrans = [
        'id_trans' => ['TRX-001'],
        'form' => '{}',
    ];
    $response2 = $this->from('/')->post(route('review.upload'), [
        'file' => UploadedFile::fake()->createWithContent('array_id.json', json_encode($arrayIdTrans)),
    ]);
    $response2->assertRedirect('/');
    $response2->assertSessionHasErrors(['file']);
});

test('menolak berkas JSON yang tidak memiliki field form atau form bukan string/objek', function () {
    // Tanpa field form
    $noForm = [
        'id_trans' => 'TRX-100',
        'nama_kegiatan' => 'Kegiatan',
    ];
    $response = $this->from('/')->post(route('review.upload'), [
        'file' => UploadedFile::fake()->createWithContent('no_form.json', json_encode($noForm)),
    ]);
    $response->assertRedirect('/');
    $response->assertSessionHasErrors(['file']);

    // form berupa array list (bukan string atau objek)
    $arrayForm = [
        'id_trans' => 'TRX-100',
        'form' => ['item1', 'item2'],
    ];
    $response2 = $this->from('/')->post(route('review.upload'), [
        'file' => UploadedFile::fake()->createWithContent('array_form.json', json_encode($arrayForm)),
    ]);
    $response2->assertRedirect('/');
    $response2->assertSessionHasErrors(['file']);

    // form berupa integer
    $intForm = [
        'id_trans' => 'TRX-100',
        'form' => 12345,
    ];
    $response3 = $this->from('/')->post(route('review.upload'), [
        'file' => UploadedFile::fake()->createWithContent('int_form.json', json_encode($intForm)),
    ]);
    $response3->assertRedirect('/');
    $response3->assertSessionHasErrors(['file']);
});

test('menolak berkas jika salah satu dari empat nilai metadata berupa array atau objek', function () {
    // nama_kegiatan berupa array
    $invalidNama = [
        'id_trans' => 'TRX-100',
        'nama_kegiatan' => ['Judul dalam array'],
        'form' => '{}',
    ];
    $res1 = $this->from('/')->post(route('review.upload'), [
        'file' => UploadedFile::fake()->createWithContent('invalid_nama.json', json_encode($invalidNama)),
    ]);
    $res1->assertRedirect('/');
    $res1->assertSessionHasErrors(['file']);

    // tahun_kegiatan berupa objek
    $invalidTahun = [
        'id_trans' => 'TRX-100',
        'tahun_kegiatan' => ['tahun' => 2024],
        'form' => '{}',
    ];
    $res2 = $this->from('/')->post(route('review.upload'), [
        'file' => UploadedFile::fake()->createWithContent('invalid_tahun.json', json_encode($invalidTahun)),
    ]);
    $res2->assertRedirect('/');
    $res2->assertSessionHasErrors(['file']);

    // instansi berupa array
    $invalidInstansi = [
        'id_trans' => 'TRX-100',
        'instansi' => ['Dinas Kominfo'],
        'form' => '{}',
    ];
    $res3 = $this->from('/')->post(route('review.upload'), [
        'file' => UploadedFile::fake()->createWithContent('invalid_instansi.json', json_encode($invalidInstansi)),
    ]);
    $res3->assertRedirect('/');
    $res3->assertSessionHasErrors(['file']);
});

test('menolak berkas JSON yang rusak dengan pesan yang jelas', function () {
    $file = UploadedFile::fake()->createWithContent('corrupted.json', '{ id_trans: 123, invalid_syntax: }');

    $response = $this->from('/')->post(route('review.upload'), [
        'file' => $file,
    ]);

    $response->assertRedirect('/');
    $response->assertSessionHasErrors(['file' => 'Berkas harus berupa JSON yang valid.']);
});

test('menolak berkas JSON yang ukurannya melebihi batas 10 MB', function () {
    $largeFile = UploadedFile::fake()->create('large.json', 10241);

    $response = $this->from('/')->post(route('review.upload'), [
        'file' => $largeFile,
    ]);

    $response->assertRedirect('/');
    $response->assertSessionHasErrors(['file' => 'Ukuran berkas melebihi batas 10 MB yang diizinkan.']);
});

test('menangani error upload PHP level INI_SIZE dengan pesan yang informatif', function () {
    $tempPath = tempnam(sys_get_temp_dir(), 'test_upload_');
    $file = new UploadedFile($tempPath, 'oversized.json', 'application/json', UPLOAD_ERR_INI_SIZE, true);

    $response = $this->from('/')->post(route('review.upload'), [
        'file' => $file,
    ]);

    $response->assertRedirect('/');
    $response->assertSessionHasErrors(['file' => 'Ukuran berkas melebihi batas upload PHP.']);
});

test('menangani error upload PHP level PARTIAL dengan pesan yang informatif', function () {
    $tempPath = tempnam(sys_get_temp_dir(), 'test_upload_');
    $file = new UploadedFile($tempPath, 'partial.json', 'application/json', UPLOAD_ERR_PARTIAL, true);

    $response = $this->from('/')->post(route('review.upload'), [
        'file' => $file,
    ]);

    $response->assertRedirect('/');
    $response->assertSessionHasErrors(['file' => 'Berkas hanya terunggah sebagian. Silakan coba lagi.']);
});
