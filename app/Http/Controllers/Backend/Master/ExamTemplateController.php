<?php

namespace App\Http\Controllers\Backend\Master;

use App\Http\Controllers\Controller;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Template Excel untuk pembuatan soal — rapi & mudah diisi orang awam.
 * Tiga jenis: Pilihan Ganda saja (pg), PG + Essay (mixed), Essay saja (essay).
 */
class ExamTemplateController extends Controller
{
    private string $indigo = 'FF4F46E5';
    private string $dark = 'FF1E293B';
    private string $head = 'FFEEF2FF';   // biru sangat muda utk sub-judul
    private string $example = 'FFF8FAFC'; // abu sangat muda utk baris contoh

    public function download(string $type): StreamedResponse
    {
        $type = in_array($type, ['pg', 'mixed', 'essay']) ? $type : 'pg';

        $config = [
            'pg' => [
                'title' => 'TEMPLATE SOAL — PILIHAN GANDA',
                'file' => 'Template_Soal_Pilihan_Ganda.xlsx',
                'columns' => ['No', 'Pertanyaan', 'Poin (benar)', 'Pengurang (salah)', 'Opsi A', 'Opsi B', 'Opsi C', 'Opsi D', 'Opsi E', 'Kunci (A-E)'],
                'widths' => [5, 52, 12, 14, 22, 22, 22, 22, 22, 12],
                'examples' => [
                    [1, 'Ibu kota Indonesia adalah ...', 1, 0, 'Jakarta', 'Bandung', 'Surabaya', 'Medan', '', 'A'],
                    [2, 'Hasil dari 7 x 8 adalah ...', 1, 0, '54', '56', '48', '64', '', 'B'],
                    [3, 'Rumus luas lingkaran adalah ... (boleh pakai $\\pi r^2$)', 2, 0.5, '$\\pi r^2$', '$2 \\pi r$', '$\\pi d$', '$r^2$', '', 'A'],
                ],
            ],
            'essay' => [
                'title' => 'TEMPLATE SOAL — ESSAY',
                'file' => 'Template_Soal_Essay.xlsx',
                'columns' => ['No', 'Pertanyaan', 'Skor Maksimal'],
                'widths' => [5, 80, 16],
                'examples' => [
                    [1, 'Jelaskan proses terjadinya hujan!', 10],
                    [2, 'Sebutkan 3 dampak positif teknologi bagi pendidikan!', 15],
                    [3, 'Buktikan bahwa jumlah sudut segitiga adalah 180 derajat.', 20],
                ],
            ],
            'mixed' => [
                'title' => 'TEMPLATE SOAL — PILIHAN GANDA + ESSAY',
                'file' => 'Template_Soal_PG_dan_Essay.xlsx',
                'columns' => ['No', 'Tipe (PG/Essay)', 'Pertanyaan', 'Poin / Skor', 'Pengurang (salah)', 'Opsi A', 'Opsi B', 'Opsi C', 'Opsi D', 'Opsi E', 'Kunci (A-E)'],
                'widths' => [5, 15, 46, 12, 14, 20, 20, 20, 20, 20, 12],
                'examples' => [
                    [1, 'PG', 'Planet terdekat dengan matahari adalah ...', 1, 0, 'Merkurius', 'Venus', 'Bumi', 'Mars', '', 'A'],
                    [2, 'PG', 'Hasil 12 : 4 adalah ...', 1, 0, '2', '3', '4', '6', '', 'B'],
                    [3, 'Essay', 'Jelaskan perbedaan hewan herbivora dan karnivora!', 15, '', '', '', '', '', '', ''],
                ],
            ],
        ][$type];

        $ss = new Spreadsheet();
        $this->buildGuideSheet($ss, $type, $config);
        $this->buildQuestionSheet($ss, $type, $config);
        $ss->setActiveSheetIndex(1); // buka langsung ke lembar "Soal"

        $writer = new Xlsx($ss);
        $file = $config['file'];

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $file, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    private function buildGuideSheet(Spreadsheet $ss, string $type, array $config): void
    {
        $s = $ss->getActiveSheet();
        $s->setTitle('Petunjuk');
        $s->getColumnDimension('A')->setWidth(4);
        $s->getColumnDimension('B')->setWidth(100);

        $s->mergeCells('A1:B1');
        $s->setCellValue('A1', 'CARA MENGISI TEMPLATE SOAL');
        $s->getStyle('A1')->getFont()->setBold(true)->setSize(15)->getColor()->setARGB('FFFFFFFF');
        $s->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($this->indigo);
        $s->getStyle('A1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $s->getRowDimension(1)->setRowHeight(28);

        $lines = array_merge([
            ['', ''],
            ['1', 'Buka lembar "Soal" (tab di bawah). Isi mulai baris di bawah judul kolom.'],
            ['2', 'Baris contoh berwarna abu-abu boleh dihapus atau ditimpa dengan soal Anda.'],
            ['3', 'Kolom "No" hanya penomoran urut (1, 2, 3, ...).'],
        ], $this->guideForType($type), [
            ['', ''],
            ['✎', 'Menulis rumus matematika: tulis di antara tanda $ ... $.'],
            ['', '   Contoh: $\\frac{a}{b}$ , $x^2$ , $\\sqrt{9}$ , $\\pi r^2$ — akan tampil rapi di sistem.'],
            ['', ''],
            ['⚠', 'Simpan tetap dalam format Excel (.xlsx). Jangan mengubah nama/urutan kolom judul.'],
        ]);

        $r = 3;
        foreach ($lines as $ln) {
            $s->setCellValue("A$r", $ln[0]);
            $s->setCellValue("B$r", $ln[1]);
            $s->getStyle("A$r")->getFont()->setBold(true)->getColor()->setARGB($this->indigo);
            $s->getStyle("A$r:B$r")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
            $r++;
        }
    }

    private function guideForType(string $type): array
    {
        return match ($type) {
            'pg' => [
                ['4', 'Kolom "Poin (benar)": nilai bila jawaban benar (mis. 1). "Pengurang (salah)": nilai dikurangi bila salah (isi 0 bila tidak dipakai).'],
                ['5', 'Isi Opsi A–D (Opsi E opsional). Boleh dikosongkan bila tidak dipakai.'],
                ['6', 'Kolom "Kunci (A-E)": pilih huruf opsi yang benar dari dropdown (A/B/C/D/E).'],
            ],
            'essay' => [
                ['4', 'Kolom "Skor Maksimal": nilai maksimal bila jawaban sempurna (mis. 10). Essay dinilai manual oleh guru.'],
            ],
            'mixed' => [
                ['4', 'Kolom "Tipe": pilih PG atau Essay dari dropdown.'],
                ['5', 'Untuk PG: isi "Poin / Skor" (nilai bila benar), "Pengurang (salah)", Opsi A–E, dan "Kunci".'],
                ['6', 'Untuk Essay: cukup isi "Poin / Skor" (skor maksimal). Kolom opsi & kunci biarkan kosong.'],
            ],
            default => [],
        };
    }

    private function buildQuestionSheet(Spreadsheet $ss, string $type, array $config): void
    {
        $s = $ss->createSheet();
        $s->setTitle('Soal');
        $cols = $config['columns'];
        $lastColIdx = count($cols);
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastColIdx);

        // Judul.
        $s->mergeCells("A1:{$lastCol}1");
        $s->setCellValue('A1', $config['title']);
        $s->getStyle('A1')->getFont()->setBold(true)->setSize(13)->getColor()->setARGB('FFFFFFFF');
        $s->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($this->indigo);
        $s->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $s->getRowDimension(1)->setRowHeight(26);

        // Header kolom (baris 2).
        foreach ($cols as $i => $label) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $s->setCellValue("{$col}2", $label);
            $s->getColumnDimension($col)->setWidth($config['widths'][$i]);
        }
        $s->getStyle("A2:{$lastCol}2")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $s->getStyle("A2:{$lastCol}2")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($this->dark);
        $s->getStyle("A2:{$lastCol}2")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER)->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $s->getRowDimension(2)->setRowHeight(30);

        // Baris contoh.
        $r = 3;
        foreach ($config['examples'] as $ex) {
            foreach ($ex as $i => $val) {
                $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
                $s->setCellValueExplicit("{$col}{$r}", (string) $val, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
            $s->getStyle("A{$r}:{$lastCol}{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($this->example);
            $s->getStyle("A{$r}:{$lastCol}{$r}")->getFont()->getColor()->setARGB('FF64748B');
            $r++;
        }

        // Baris kosong siap diisi + border seluruh area.
        $lastRow = $r + 60; // sediakan banyak baris kosong
        $s->getStyle("A2:{$lastCol}{$lastRow}")->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFCBD5E1');
        $s->getStyle("A2:{$lastCol}{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);

        // Dropdown validasi.
        $this->applyDropdowns($s, $type, $cols, $lastRow);

        $s->freezePane('A3');
        $s->setSelectedCell('B3');
    }

    /** Pasang dropdown untuk kolom Tipe (PG/Essay) & Kunci (A-E). */
    private function applyDropdowns(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $s, string $type, array $cols, int $lastRow): void
    {
        $letter = fn (int $idx) => \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($idx + 1);

        $tipeIdx = array_search('Tipe (PG/Essay)', $cols, true);
        $kunciIdx = array_search('Kunci (A-E)', $cols, true);

        if ($tipeIdx !== false) {
            $this->dropdownRange($s, $letter($tipeIdx), $lastRow, '"PG,Essay"', 'Pilih tipe soal', 'Pilih PG atau Essay.');
        }
        if ($kunciIdx !== false) {
            $this->dropdownRange($s, $letter($kunciIdx), $lastRow, '"A,B,C,D,E"', 'Pilih kunci', 'Pilih huruf opsi yang benar (A–E).');
        }
    }

    private function dropdownRange($s, string $col, int $lastRow, string $formula, string $promptTitle, string $prompt): void
    {
        for ($row = 3; $row <= $lastRow; $row++) {
            $dv = $s->getCell("{$col}{$row}")->getDataValidation();
            $dv->setType(DataValidation::TYPE_LIST);
            $dv->setErrorStyle(DataValidation::STYLE_INFORMATION);
            $dv->setAllowBlank(true);
            $dv->setShowInputMessage(true);
            $dv->setShowErrorMessage(true);
            $dv->setShowDropDown(true);
            $dv->setPromptTitle($promptTitle);
            $dv->setPrompt($prompt);
            $dv->setFormula1($formula);
        }
    }
}
