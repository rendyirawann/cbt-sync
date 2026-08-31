<?php

namespace App\Traits;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Generate template Excel & baca file impor untuk manajemen data master.
 *
 * Spesifikasi ($spec):
 *  - title    : judul (mis. "DATA SEKOLAH")
 *  - file     : nama file unduhan (mis. "Template_Data_Sekolah.xlsx")
 *  - guide    : array baris petunjuk tambahan (opsional)
 *  - columns  : list kolom, tiap kolom:
 *       ['key'=>'name','label'=>'Nama Sekolah','required'=>true,'width'=>40,'options'=>['L','P'],'hint'=>'...']
 *  - examples : list baris contoh, tiap baris assoc [key => nilai]
 */
trait ExcelMasterTemplate
{
    protected function downloadExcelTemplate(array $spec): StreamedResponse
    {
        $ss = new Spreadsheet();
        $this->buildGuideSheet($ss->getActiveSheet(), $spec);
        $this->buildDataSheet($ss->createSheet(), 'Contoh', $spec, $spec['examples'] ?? [], false);
        $this->buildDataSheet($ss->createSheet(), 'Data', $spec, [], true);
        $ss->setActiveSheetIndexByName('Data');

        $writer = new Xlsx($ss);
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $spec['file'], [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /** Baca lembar "Data" → daftar baris assoc [key => nilai]. Baris kosong dilewati. */
    protected function readExcelRows(UploadedFile $file, array $columns): array
    {
        $ss = IOFactory::load($file->getRealPath());
        $sheet = $ss->getSheetByName('Data') ?: $ss->getActiveSheet();
        $grid = $sheet->toArray(null, true, false, false);

        // Cari baris header: baris yang selnya PERSIS sama dengan label kolom pertama
        // (pakai kecocokan penuh agar tidak tertukar dengan baris judul yang memuat kata sama).
        $norm = fn ($s) => trim(preg_replace('/\s+/', ' ', str_replace('*', '', strtolower((string) $s))));
        $firstLabel = $norm($columns[0]['label']);
        $headerIdx = null;
        foreach ($grid as $i => $row) {
            foreach ($row as $cell) {
                if (is_string($cell) && $norm($cell) === $firstLabel) {
                    $headerIdx = $i;
                    break 2;
                }
            }
        }
        if ($headerIdx === null) {
            throw new \RuntimeException('Judul kolom tidak ditemukan pada lembar "Data".');
        }

        // Peta: key kolom → indeks kolom di file (cocokkan berdasarkan label yang ternormalisasi).
        $header = $grid[$headerIdx];
        $map = [];
        foreach ($columns as $col) {
            $label = $norm($col['label']);
            foreach ($header as $j => $h) {
                if (is_string($h) && str_contains($norm($h), $label)) {
                    $map[$col['key']] = $j;
                    break;
                }
            }
        }

        $rows = [];
        for ($i = $headerIdx + 1; $i < count($grid); $i++) {
            $assoc = [];
            $hasValue = false;
            foreach ($columns as $col) {
                $j = $map[$col['key']] ?? null;
                $val = $j !== null ? trim((string) ($grid[$i][$j] ?? '')) : '';
                $assoc[$col['key']] = $val;
                if ($val !== '') $hasValue = true;
            }
            if ($hasValue) {
                $assoc['_row'] = $i + 1; // nomor baris Excel (1-based) untuk pesan error
                $rows[] = $assoc;
            }
        }
        return $rows;
    }

    protected function importSummary(int $imported, int $skipped, array $errors): \Illuminate\Http\RedirectResponse
    {
        if ($imported === 0 && $skipped === 0 && empty($errors)) {
            return back()->with('error', 'Tidak ada data pada lembar "Data" yang bisa diimpor.');
        }
        $msg = "$imported data berhasil diimpor.";
        if ($skipped) $msg .= " $skipped dilewati (sudah ada).";
        if ($errors) $msg .= ' Gagal: ' . implode('; ', array_slice($errors, 0, 5)) . (count($errors) > 5 ? ' …' : '');
        return back()->with($errors ? 'error' : 'success', $msg);
    }

    /* ----------------------------- builder ----------------------------- */

    private function buildGuideSheet($s, array $spec): void
    {
        $s->setTitle('Petunjuk');
        $s->getColumnDimension('A')->setWidth(4);
        $s->getColumnDimension('B')->setWidth(100);
        $s->mergeCells('A1:B1');
        $s->setCellValue('A1', 'CARA MENGISI — ' . $spec['title']);
        $s->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setARGB('FFFFFFFF');
        $s->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF4F46E5');
        $s->getStyle('A1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $s->getRowDimension(1)->setRowHeight(26);

        $lines = [['', '']];
        $lines[] = ['1', 'Isi data pada lembar "Data". Lihat lembar "Contoh" sebagai panduan.'];
        $lines[] = ['2', 'Yang diimpor HANYA lembar "Data". Baris kosong dilewati.'];
        $n = 3;
        foreach ($spec['columns'] as $col) {
            $req = !empty($col['required']) ? ' (WAJIB)' : ' (opsional)';
            $opt = !empty($col['options']) ? ' — pilihan: ' . implode('/', $col['options']) : '';
            $hint = !empty($col['hint']) ? ' — ' . $col['hint'] : '';
            $lines[] = [(string) $n++, $col['label'] . $req . $opt . $hint];
        }
        foreach (($spec['guide'] ?? []) as $g) {
            $lines[] = ['•', $g];
        }

        $r = 3;
        foreach ($lines as $ln) {
            $s->setCellValue("A$r", $ln[0]);
            $s->setCellValue("B$r", $ln[1]);
            $s->getStyle("A$r")->getFont()->setBold(true)->getColor()->setARGB('FF4F46E5');
            $s->getStyle("A$r:B$r")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
            $r++;
        }
    }

    private function buildDataSheet($s, string $title, array $spec, array $rows, bool $isData): void
    {
        $s->setTitle($title);
        $cols = $spec['columns'];
        $lastCol = Coordinate::stringFromColumnIndex(count($cols));

        $s->mergeCells("A1:{$lastCol}1");
        $s->setCellValue('A1', ($isData ? '' : 'CONTOH — ') . $spec['title']);
        $s->getStyle('A1')->getFont()->setBold(true)->setSize(13)->getColor()->setARGB('FFFFFFFF');
        $s->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($isData ? 'FF4F46E5' : 'FF64748B');
        $s->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $s->getRowDimension(1)->setRowHeight(24);

        foreach ($cols as $i => $col) {
            $letter = Coordinate::stringFromColumnIndex($i + 1);
            $label = $col['label'] . (!empty($col['required']) ? ' *' : '');
            $s->setCellValue("{$letter}2", $label);
            $s->getColumnDimension($letter)->setWidth($col['width'] ?? 20);
        }
        $s->getStyle("A2:{$lastCol}2")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $s->getStyle("A2:{$lastCol}2")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1E293B');
        $s->getStyle("A2:{$lastCol}2")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER)->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $s->getRowDimension(2)->setRowHeight(28);

        $r = 3;
        foreach ($rows as $row) {
            foreach ($cols as $i => $col) {
                $letter = Coordinate::stringFromColumnIndex($i + 1);
                $s->setCellValueExplicit("{$letter}{$r}", (string) ($row[$col['key']] ?? ''), DataType::TYPE_STRING);
            }
            $s->getStyle("A{$r}:{$lastCol}{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF8FAFC');
            $s->getStyle("A{$r}:{$lastCol}{$r}")->getFont()->getColor()->setARGB('FF64748B');
            $r++;
        }

        $lastRow = $isData ? $r + 80 : max($r - 1, 2);
        $s->getStyle("A2:{$lastCol}{$lastRow}")->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFCBD5E1');
        $s->getStyle("A2:{$lastCol}{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);

        if ($isData) {
            foreach ($cols as $i => $col) {
                if (!empty($col['options'])) {
                    $letter = Coordinate::stringFromColumnIndex($i + 1);
                    $this->applyDropdown($s, $letter, $lastRow, '"' . implode(',', $col['options']) . '"');
                }
            }
            $s->setSelectedCell('A3');
        }
        $s->freezePane('A3');
    }

    private function applyDropdown($s, string $col, int $lastRow, string $formula): void
    {
        for ($row = 3; $row <= $lastRow; $row++) {
            $dv = $s->getCell("{$col}{$row}")->getDataValidation();
            $dv->setType(DataValidation::TYPE_LIST);
            $dv->setErrorStyle(DataValidation::STYLE_INFORMATION);
            $dv->setAllowBlank(true);
            $dv->setShowDropDown(true);
            $dv->setShowInputMessage(true);
            $dv->setShowErrorMessage(true);
            $dv->setFormula1($formula);
        }
    }
}
