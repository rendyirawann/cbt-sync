<?php

namespace App\Http\Controllers\Backend\Master;

use App\Http\Controllers\Controller;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Template Word (.docx) untuk pembuatan soal — mendukung TEMPEL GAMBAR langsung
 * ke dalam sel tabel (kolom Pertanyaan maupun Opsi). Impor-nya ditangani oleh
 * ExamTemplateController@import (parser .docx).
 */
class ExamWordController extends Controller
{
    public function download(string $type): BinaryFileResponse
    {
        $type = in_array($type, ['pg', 'mixed', 'essay']) ? $type : 'pg';
        $config = $this->config($type);

        $word = new PhpWord();
        $section = $word->addSection([
            'orientation' => $type === 'essay' ? 'portrait' : 'landscape',
            'marginTop' => 720, 'marginBottom' => 720, 'marginLeft' => 720, 'marginRight' => 720,
        ]);

        $section->addText('TEMPLATE SOAL — ' . $config['title'], ['bold' => true, 'size' => 15, 'color' => '4F46E5']);
        $section->addTextBreak(1);

        $section->addText('Petunjuk pengisian:', ['bold' => true, 'size' => 11]);
        foreach ($this->guideLines($type) as $line) {
            $section->addText('•  ' . $line, ['size' => 9]);
        }
        $section->addTextBreak(1);

        // Tabel CONTOH (diabaikan saat impor).
        $section->addText('CONTOH (panduan saja — diabaikan saat impor):', ['bold' => true, 'size' => 10, 'color' => '64748B']);
        $this->buildTable($word, $section, $config, $config['examples'], 0);
        $section->addTextBreak(1);

        // Tabel SOAL (yang diisi & diimpor).
        $section->addText('SOAL — isi di tabel ini (boleh tambah baris):', ['bold' => true, 'size' => 11, 'color' => '059669']);
        $this->buildTable($word, $section, $config, [], 12);

        // PhpWord (ZipArchive) butuh file nyata — tidak bisa menulis ke php://output.
        // Simpan ke file sementara lalu kirim & hapus setelah terunduh.
        $tmp = tempnam(sys_get_temp_dir(), 'wordtpl_') . '.docx';
        IOFactory::createWriter($word, 'Word2007')->save($tmp);

        return response()->download($tmp, $config['file'], [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Cache-Control' => 'max-age=0',
        ])->deleteFileAfterSend(true);
    }

    private function buildTable(PhpWord $word, $section, array $config, array $rows, int $emptyRows): void
    {
        $styleName = 'tpl_' . uniqid();
        $word->addTableStyle($styleName, [
            'borderSize' => 6, 'borderColor' => 'CBD5E1', 'cellMargin' => 60,
        ]);
        $table = $section->addTable($styleName);

        // Header.
        $table->addRow(420, ['tblHeader' => true]);
        foreach ($config['columns'] as $i => $label) {
            $cell = $table->addCell($config['widths'][$i], ['bgColor' => '1E293B', 'valign' => 'center']);
            $cell->addText($label, ['bold' => true, 'color' => 'FFFFFF', 'size' => 9], ['alignment' => Jc::CENTER]);
        }

        // Baris contoh.
        foreach ($rows as $row) {
            $table->addRow();
            foreach ($config['columns'] as $i => $col) {
                $val = (string) ($row[$i] ?? '');
                $table->addCell($config['widths'][$i], ['bgColor' => 'F8FAFC', 'valign' => 'center'])
                    ->addText($val, ['size' => 9, 'color' => '64748B']);
            }
        }

        // Baris kosong untuk diisi.
        for ($r = 0; $r < $emptyRows; $r++) {
            $table->addRow(600);
            foreach ($config['columns'] as $i => $col) {
                $table->addCell($config['widths'][$i], ['valign' => 'center'])->addText('');
            }
        }
    }

    private function guideLines(string $type): array
    {
        $common = [
            'Isi soal pada tabel "SOAL". Tabel "CONTOH" hanya panduan dan diabaikan saat impor.',
            'RUMUS/EQUATION: tulis di antara tanda $ … $  (mis. $\\frac{a}{b}$, $x^2$, $\\sqrt{9}$). Equation editor Word TIDAK terbaca.',
            'GAMBAR: klik di dalam sel lalu Insert > Picture / tempel (paste). Bisa di kolom Pertanyaan maupun Opsi.',
            'Jangan mengubah judul kolom. Boleh menambah baris pada tabel SOAL (klik baris terakhir lalu Tab).',
        ];
        $extra = match ($type) {
            'pg' => ['Kolom "Kunci (A-E)": tulis huruf opsi yang benar (A/B/C/D/E).'],
            'essay' => ['Kolom "Skor Maksimal": nilai bila jawaban sempurna. Essay dinilai manual oleh guru.'],
            'mixed' => ['Kolom "Tipe": tulis PG atau Essay. Baris Essay: kosongkan Opsi dan Kunci.'],
            default => [],
        };
        return array_merge($common, $extra);
    }

    /** Kolom & contoh sama dengan template Excel agar parser header konsisten. */
    private function config(string $type): array
    {
        return [
            'pg' => [
                'title' => 'PILIHAN GANDA',
                'file' => 'Template_Soal_Pilihan_Ganda.docx',
                'columns' => ['No', 'Pertanyaan', 'Poin (benar)', 'Pengurang (salah)', 'Opsi A', 'Opsi B', 'Opsi C', 'Opsi D', 'Opsi E', 'Kunci (A-E)'],
                'widths' => [500, 3200, 900, 1100, 1300, 1300, 1300, 1300, 1300, 900],
                'examples' => [
                    ['1', 'Ibu kota Indonesia adalah ...', '1', '0', 'Jakarta', 'Bandung', 'Surabaya', 'Medan', '', 'A'],
                    ['2', 'Nilai dari $\\sqrt{144}$ adalah ...', '2', '0', '$12$', '$14$', '$16$', '$11$', '', 'A'],
                    ['3', '(Contoh soal bergambar: tempel gambar di sel ini)', '2', '0', 'Opsi teks', '(boleh gambar)', 'Opsi teks', 'Opsi teks', '', 'B'],
                ],
            ],
            'essay' => [
                'title' => 'ESSAY',
                'file' => 'Template_Soal_Essay.docx',
                'columns' => ['No', 'Pertanyaan', 'Skor Maksimal'],
                'widths' => [700, 7200, 1500],
                'examples' => [
                    ['1', 'Jelaskan proses terjadinya hujan!', '10'],
                    ['2', 'Tentukan hasil dari $\\int_0^1 x^2\\,dx$ beserta langkahnya.', '20'],
                    ['3', '(Contoh soal bergambar: tempel gambar di sel Pertanyaan ini)', '15'],
                ],
            ],
            'mixed' => [
                'title' => 'PILIHAN GANDA + ESSAY',
                'file' => 'Template_Soal_PG_dan_Essay.docx',
                'columns' => ['No', 'Tipe (PG/Essay)', 'Pertanyaan', 'Poin / Skor', 'Pengurang (salah)', 'Opsi A', 'Opsi B', 'Opsi C', 'Opsi D', 'Opsi E', 'Kunci (A-E)'],
                'widths' => [450, 1000, 2800, 900, 1000, 1200, 1200, 1200, 1200, 1200, 800],
                'examples' => [
                    ['1', 'PG', 'Planet terdekat dengan matahari adalah ...', '1', '0', 'Merkurius', 'Venus', 'Bumi', 'Mars', '', 'A'],
                    ['2', 'PG', 'Nilai $2^5$ adalah ...', '1', '0', '$32$', '$25$', '$16$', '$64$', '', 'A'],
                    ['3', 'Essay', 'Jelaskan perbedaan herbivora dan karnivora!', '15', '', '', '', '', '', '', ''],
                ],
            ],
        ][$type];
    }
}
