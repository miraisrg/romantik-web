<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Hasil Pemeriksaan Formulir ROMANTIK - {{ $identity['id_trans'] ?? 'Dokumen' }}</title>
    <style>
        @page {
            margin: 20mm 15mm 20mm 15mm;
            size: a4 portrait;
        }

        body {
            font-family: 'DejaVu Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 8.5pt;
            line-height: 1.45;
            color: #1e293b;
            margin: 0;
            padding: 0;
        }

        /* Fixed Footer on all pages */
        .footer {
            position: fixed;
            bottom: -15mm;
            left: 0;
            right: 0;
            height: 12mm;
            font-size: 7.5pt;
            color: #64748b;
            border-top: 1px solid #cbd5e1;
            padding-top: 3px;
        }

        /* Header & Titles */
        .doc-header {
            width: 100%;
            border-bottom: 2px solid #1e293b;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .app-title {
            font-size: 15pt;
            font-weight: bold;
            color: #0f172a;
            line-height: 1.2;
        }
        .doc-subtitle {
            font-size: 10pt;
            color: #475569;
            margin-top: 2px;
        }
        .badge-prototype {
            display: inline-block;
            padding: 3px 8px;
            font-size: 8pt;
            font-weight: bold;
            color: #4338ca;
            background-color: #e0e7ff;
            border: 1px solid #c7d2fe;
            border-radius: 4px;
        }

        /* Section Headings */
        .section-title {
            font-size: 9.5pt;
            font-weight: bold;
            color: #0f172a;
            border-bottom: 1px solid #94a3b8;
            padding-bottom: 3px;
            margin-top: 14px;
            margin-bottom: 7px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Tables */
        table.meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        table.meta-table td {
            padding: 4px 6px;
            font-size: 8.5pt;
            vertical-align: top;
            border: 1px solid #e2e8f0;
        }
        table.meta-table td.label-col {
            width: 25%;
            font-weight: bold;
            background-color: #f8fafc;
            color: #475569;
        }
        table.meta-table td.value-col {
            color: #0f172a;
        }

        /* Summary Grid Table */
        table.summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        table.summary-table th,
        table.summary-table td {
            border: 1px solid #cbd5e1;
            padding: 5px 8px;
            text-align: left;
            font-size: 8.5pt;
        }
        table.summary-table th {
            background-color: #f1f5f9;
            color: #334155;
            font-weight: bold;
            width: 25%;
        }
        .metric-num {
            font-size: 11pt;
            font-weight: bold;
            color: #0f172a;
        }

        /* Cards */
        .card {
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 7px 9px;
            margin-bottom: 7px;
            background-color: #ffffff;
            page-break-inside: avoid;
        }
        .card-header {
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 4px;
            margin-bottom: 5px;
        }

        /* Badges */
        .badge-error {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #f87171;
            font-weight: bold;
            font-size: 7.5pt;
            padding: 1px 5px;
            border-radius: 3px;
            display: inline-block;
        }
        .badge-warning {
            background-color: #fef3c7;
            color: #92400e;
            border: 1px solid #fbbf24;
            font-weight: bold;
            font-size: 7.5pt;
            padding: 1px 5px;
            border-radius: 3px;
            display: inline-block;
        }
        .badge-info {
            background-color: #f1f5f9;
            color: #334155;
            border: 1px solid #cbd5e1;
            font-weight: bold;
            font-size: 7.5pt;
            padding: 1px 5px;
            border-radius: 3px;
            display: inline-block;
        }
        .badge-rule {
            font-family: "Courier New", Courier, monospace;
            font-weight: bold;
            color: #1e1b4b;
            background-color: #e0e7ff;
            border: 1px solid #c7d2fe;
            padding: 1px 5px;
            border-radius: 3px;
            font-size: 8pt;
        }

        /* Item details */
        .item-row {
            margin-bottom: 3px;
        }
        .item-label {
            font-weight: bold;
            color: #475569;
            font-size: 8pt;
        }
        .item-val {
            color: #0f172a;
            font-size: 8.5pt;
        }

        /* Evidence pre block */
        .evidence-box {
            margin-top: 4px;
            border-top: 1px dashed #e2e8f0;
            padding-top: 3px;
        }
        .evidence-title {
            font-size: 7.5pt;
            font-weight: bold;
            color: #475569;
            margin-bottom: 1px;
        }
        pre.evidence-pre {
            font-family: "Courier New", Courier, monospace;
            font-size: 7pt;
            line-height: 1.25;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 3px;
            padding: 4px 6px;
            margin: 2px 0 0 0;
            white-space: pre-wrap;
            word-wrap: break-word;
            color: #0f172a;
        }

        /* Hybrid AI section */
        .hybrid-content {
            background-color: #fafafa;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 7px 10px;
            font-size: 8.5pt;
            line-height: 1.5;
            color: #1e293b;
        }
        .hybrid-content p {
            margin: 0 0 5px 0;
        }
        .hybrid-content p:last-child {
            margin-bottom: 0;
        }

        /* Explanatory note */
        .note-box {
            background-color: #f8fafc;
            border-left: 3px solid #64748b;
            padding: 4px 7px;
            font-size: 8pt;
            color: #334155;
            margin-bottom: 6px;
        }
        .empty-note {
            padding: 8px;
            text-align: center;
            font-style: italic;
            color: #64748b;
            border: 1px dashed #cbd5e1;
            border-radius: 4px;
            background-color: #f8fafc;
        }
    </style>
</head>
<body>
    <!-- Footer Halaman (Otomatis berulang di setiap halaman A4) -->
    <div class="footer">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="text-align: left; vertical-align: top; border: none; padding: 0;">
                    ROMANTIK Web &mdash; Prototype Pemeriksaan Hybrid AI
                    <br>
                    <span style="font-size: 6.5pt; color: #94a3b8;">Dokumen dihasilkan dari hasil pemeriksaan sistem. Bukan keputusan resmi BPS.</span>
                </td>
                <td style="text-align: right; vertical-align: top; border: none; padding: 0;">
                    Waktu unduh: {{ $downloadedAt }}
                </td>
            </tr>
        </table>
    </div>

    <!-- Header Dokumen -->
    <table class="doc-header" style="border-collapse: collapse;">
        <tr>
            <td style="vertical-align: top; border: none; padding: 0;">
                <div class="app-title">ROMANTIK Web</div>
                <div class="doc-subtitle">Hasil Pemeriksaan Formulir ROMANTIK</div>
            </td>
            <td style="text-align: right; vertical-align: top; border: none; padding: 0;">
                <span class="badge-prototype">Prototype Hybrid AI</span>
            </td>
        </tr>
    </table>

    <!-- A. IDENTITAS FORMULIR -->
    <div class="section-title">A. IDENTITAS FORMULIR</div>
    <table class="meta-table">
        <tr>
            <td class="label-col">ID Transaksi</td>
            <td class="value-col" style="font-family: 'Courier New', Courier, monospace; font-weight: bold;">{{ $identity['id_trans'] }}</td>
        </tr>
        <tr>
            <td class="label-col">Nama Kegiatan</td>
            <td class="value-col"><strong>{{ $identity['nama_kegiatan'] }}</strong></td>
        </tr>
        <tr>
            <td class="label-col">Tahun Kegiatan</td>
            <td class="value-col">{{ $identity['tahun_kegiatan'] }}</td>
        </tr>
        <tr>
            <td class="label-col">Instansi Penyelenggara</td>
            <td class="value-col">{{ $identity['instansi'] }}</td>
        </tr>
        @if (!empty($identity['file_name']))
            <tr>
                <td class="label-col">Nama Berkas Sumber</td>
                <td class="value-col" style="font-family: 'Courier New', Courier, monospace; font-size: 8pt;">{{ $identity['file_name'] }}</td>
            </tr>
        @endif
    </table>

    <!-- B. RINGKASAN PEMERIKSAAN -->
    <div class="section-title">B. RINGKASAN PEMERIKSAAN</div>
    <table class="summary-table">
        <tr>
            <th>Temuan RBS</th>
            <th>Belum Dapat Dievaluasi</th>
            <th>Waktu Proses</th>
            <th>Status Pemeriksaan</th>
        </tr>
        <tr>
            <td>
                <span class="metric-num">{{ $summary['n_findings'] }}</span>
                <span style="font-size: 7.5pt; color: #64748b;"> temuan</span>
            </td>
            <td>
                <span class="metric-num">{{ $summary['n_not_evaluable'] }}</span>
                <span style="font-size: 7.5pt; color: #64748b;"> aturan</span>
            </td>
            <td>
                <strong>{{ $summary['processing_time'] }}</strong>
            </td>
            <td>
                <strong style="color: #047857;">{{ $summary['status'] }}</strong>
            </td>
        </tr>
    </table>

    <!-- C. TEMUAN RULE-BASED SYSTEM -->
    <div class="section-title">C. TEMUAN RULE-BASED SYSTEM</div>
    @if (!empty($findings))
        @foreach ($findings as $finding)
            <div class="card">
                <div class="card-header">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="border: none; padding: 0; vertical-align: middle;">
                                <strong style="color: #475569; font-size: 8pt;">Temuan #{{ $finding['index'] }}</strong>
                                &nbsp;
                                <span class="badge-rule">{{ $finding['rule_id'] }}</span>
                                &nbsp;
                                <strong style="color: #0f172a; font-size: 8.5pt;">{{ $finding['title'] }}</strong>
                            </td>
                            <td style="border: none; padding: 0; text-align: right; vertical-align: middle;">
                                @if ($finding['severity_type'] === 'error')
                                    <span class="badge-error">{{ $finding['severity_label'] }}</span>
                                @elseif ($finding['severity_type'] === 'warning')
                                    <span class="badge-warning">{{ $finding['severity_label'] }}</span>
                                @else
                                    <span class="badge-info">{{ $finding['severity_label'] }}</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="item-row">
                    <span class="item-label">Pesan:</span>
                    <span class="item-val">{{ $finding['message'] }}</span>
                </div>

                <div class="item-row">
                    <span class="item-label">Apa yang diperiksa:</span>
                    <span class="item-val">{{ $finding['description'] }}</span>
                </div>

                <div class="item-row">
                    <span class="item-label">Kondisi yang menghasilkan temuan:</span>
                    <span class="item-val">{{ $finding['violation_condition'] }}</span>
                </div>

                @if (!empty($finding['evidence_json']))
                    <div class="evidence-box">
                        <div class="evidence-title">Bukti Pemeriksaan:</div>
                        <pre class="evidence-pre"><code>{{ $finding['evidence_json'] }}</code></pre>
                    </div>
                @endif
            </div>
        @endforeach
    @else
        <div class="empty-note">
            Tidak ditemukan temuan deterministik oleh Rule-Based System.
        </div>
    @endif

    <!-- D. ATURAN BELUM DAPAT DIEVALUASI -->
    <div class="section-title">D. ATURAN BELUM DAPAT DIEVALUASI</div>
    <div class="note-box">
        Status ini menunjukkan aturan belum dapat menyimpulkan lulus atau temuan karena informasi yang diperlukan belum tersedia atau kondisi evaluasinya belum dapat dipastikan.
    </div>

    @if (!empty($notEvaluable))
        @foreach ($notEvaluable as $item)
            <div class="card">
                <div class="card-header">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="border: none; padding: 0; vertical-align: middle;">
                                <strong style="color: #475569; font-size: 8pt;">#{{ $item['index'] }}</strong>
                                &nbsp;
                                <span class="badge-rule">{{ $item['rule_id'] }}</span>
                                &nbsp;
                                <strong style="color: #0f172a; font-size: 8.5pt;">{{ $item['title'] }}</strong>
                            </td>
                            <td style="border: none; padding: 0; text-align: right; vertical-align: middle;">
                                <span class="badge-info">Belum Dapat Dievaluasi</span>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="item-row">
                    <span class="item-label">Apa yang diperiksa:</span>
                    <span class="item-val">{{ $item['description'] }}</span>
                </div>

                <div class="item-row">
                    <span class="item-label">Alasan:</span>
                    <span class="item-val">{{ $item['reason'] }}</span>
                </div>

                <div class="item-row">
                    <span class="item-label">Kapan rule ini dapat dievaluasi:</span>
                    <span class="item-val">{{ $item['applicability'] }}</span>
                </div>

                @if (!empty($item['evidence_json']))
                    <div class="evidence-box">
                        <div class="evidence-title">Bukti / Konteks Evaluasi:</div>
                        <pre class="evidence-pre"><code>{{ $item['evidence_json'] }}</code></pre>
                    </div>
                @endif
            </div>
        @endforeach
    @else
        <div class="empty-note">
            Semua aturan yang relevan dapat dievaluasi.
        </div>
    @endif

    <!-- E. CATATAN PEMERIKSAAN HYBRID AI -->
    <div class="section-title">E. CATATAN PEMERIKSAAN HYBRID AI</div>
    <div class="hybrid-content">
        @if (!empty($formattedHybridReview))
            {!! $formattedHybridReview !!}
        @else
            <p style="color: #64748b; font-style: italic;">Tidak ada catatan pemeriksaan Hybrid AI untuk formulir ini.</p>
        @endif
    </div>
</body>
</html>
