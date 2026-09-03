<?php

namespace App\Http\Controllers\Backend\Master;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Template Excel untuk pembuatan soal (unduh) + impor soal dari file terisi.
 * Tiga jenis: Pilihan Ganda (pg), PG + Essay (mixed), Essay saja (essay).
 * Struktur file: lembar "Petunjuk" + "Contoh" (terisi) + "Soal" (kosong, untuk diisi & diimpor).
 */
class ExamTemplateController extends Controller
{
    private string $indigo = 'FF4F46E5';
    private string $dark = 'FF1E293B';
    private string $example = 'FFF8FAFC';

    /* ============================ UNDUH TEMPLATE ============================ */

    public function download(string $type): StreamedResponse
    {
        $type = in_array($type, ['pg', 'mixed', 'essay']) ? $type : 'pg';
        $config = $this->config($type);

        $ss = new Spreadsheet();
        $this->buildGuideSheet($ss, $type); // memakai sheet default (index 0) → "Petunjuk"
        $this->buildTableSheet($ss->createSheet(), 'Contoh', $config, $config['examples']); // contoh terisi
        $this->buildTableSheet($ss->createSheet(), 'Soal', $config, []);                    // kosong untuk diisi
        $ss->setActiveSheetIndexByName('Soal'); // buka langsung ke lembar "Soal"

        $writer = new Xlsx($ss);
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $config['file'], [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    private function config(string $type): array
    {
        return [
            'pg' => [
                'title' => 'SOAL PILIHAN GANDA',
                'file' => 'Template_Soal_Pilihan_Ganda.xlsx',
                'columns' => ['No', 'Pertanyaan', 'Poin (benar)', 'Pengurang (salah)', 'Opsi A', 'Opsi B', 'Opsi C', 'Opsi D', 'Opsi E', 'Kunci (A-E)'],
                'widths' => [5, 50, 12, 14, 20, 20, 20, 20, 20, 12],
                'examples' => [
                    ['1', 'Ibu kota Indonesia adalah ...', '1', '0', 'Jakarta', 'Bandung', 'Surabaya', 'Medan', '', 'A'],
                    ['2', 'Hasil dari 7 x 8 adalah ...', '1', '0', '54', '56', '48', '64', '', 'B'],
                    ['3', 'Nilai dari $\\sqrt{144}$ adalah ...', '2', '0.5', '$12$', '$14$', '$16$', '$11$', '', 'A'],
                    ['4', 'Luas lingkaran dinyatakan dengan rumus ...', '2', '0', '$\\pi r^2$', '$2 \\pi r$', '$\\pi d$', '$\\frac{1}{2} r^2$', '', 'A'],
                ],
            ],
            'essay' => [
                'title' => 'SOAL ESSAY',
                'file' => 'Template_Soal_Essay.xlsx',
                'columns' => ['No', 'Pertanyaan', 'Skor Maksimal'],
                'widths' => [5, 80, 16],
                'examples' => [
                    ['1', 'Jelaskan proses terjadinya hujan!', '10'],
                    ['2', 'Sebutkan 3 dampak positif teknologi bagi pendidikan!', '15'],
                    ['3', 'Tentukan hasil dari $\\int_0^1 x^2\\,dx$ beserta langkahnya.', '20'],
                ],
            ],
            'mixed' => [
                'title' => 'SOAL PILIHAN GANDA + ESSAY',
                'file' => 'Template_Soal_PG_dan_Essay.xlsx',
                'columns' => ['No', 'Tipe (PG/Essay)', 'Pertanyaan', 'Poin / Skor', 'Pengurang (salah)', 'Opsi A', 'Opsi B', 'Opsi C', 'Opsi D', 'Opsi E', 'Kunci (A-E)'],
                'widths' => [5, 15, 44, 12, 14, 18, 18, 18, 18, 18, 12],
                'examples' => [
                    ['1', 'PG', 'Planet terdekat dengan matahari adalah ...', '1', '0', 'Merkurius', 'Venus', 'Bumi', 'Mars', '', 'A'],
                    ['2', 'PG', 'Nilai $2^5$ adalah ...', '1', '0', '$32$', '$25$', '$16$', '$64$', '', 'A'],
                    ['3', 'Essay', 'Jelaskan perbedaan hewan herbivora dan karnivora!', '15', '', '', '', '', '', '', ''],
                ],
            ],
        ][$type];
    }

    private function buildGuideSheet(Spreadsheet $ss, string $type): void
    {
        $s = $ss->getActiveSheet();
        $s->setTitle('Petunjuk');
        $s->getColumnDimension('A')->setWidth(4);
        $s->getColumnDimension('B')->setWidth(102);

        $s->mergeCells('A1:B1');
        $s->setCellValue('A1', 'CARA MENGISI TEMPLATE SOAL');
        $s->getStyle('A1')->getFont()->setBold(true)->setSize(15)->getColor()->setARGB('FFFFFFFF');
        $s->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($this->indigo);
        $s->getStyle('A1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $s->getRowDimension(1)->setRowHeight(28);

        $lines = array_merge([
            ['', ''],
            ['1', 'Lihat lembar "Contoh" untuk melihat format pengisian, lalu isi soal Anda di lembar "Soal".'],
            ['2', 'Isi mulai baris kosong di bawah judul kolom pada lembar "Soal". Kolom "No" hanya penomoran urut.'],
            ['3', 'Yang diimpor ke sistem HANYA lembar "Soal". Lembar "Contoh" diabaikan saat impor.'],
        ], $this->guideForType($type), [
            ['', ''],
            ['✎', 'RUMUS / EQUATION: tulis di antara tanda $ … $.'],
            ['', '   Contoh: $\\frac{a}{b}$ , $x^2$ , $\\sqrt{9}$ , $\\pi r^2$ , $\\int_0^1 x\\,dx$ — akan tampil rapi di sistem.'],
            ['', '   Rumus juga boleh dipakai di dalam pilihan jawaban (Opsi A–E).'],
            ['', ''],
            ['🖼', 'GAMBAR (soal atau pilihan jawaban): TIDAK bisa lewat Excel.'],
            ['', '   Impor teksnya dulu, lalu buka soal tersebut di sistem → klik Edit → unggah gambar soal/opsi di sana.'],
            ['', ''],
            ['⚠', 'Simpan tetap format Excel (.xlsx). Jangan mengubah nama/urutan judul kolom, dan jangan ganti nama lembar "Soal".'],
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
                ['4', '"Poin (benar)": nilai bila benar (mis. 1). "Pengurang (salah)": nilai dikurangi bila salah (isi 0 bila tidak dipakai).'],
                ['5', 'Isi Opsi A–D (Opsi E opsional). "Kunci (A-E)": huruf opsi yang benar (pilih dari dropdown).'],
            ],
            'essay' => [
                ['4', '"Skor Maksimal": nilai maksimal bila jawaban sempurna (mis. 10). Essay dinilai manual oleh guru.'],
            ],
            'mixed' => [
                ['4', '"Tipe": pilih PG atau Essay (dropdown).'],
                ['5', 'Baris PG: isi "Poin / Skor", "Pengurang (salah)", Opsi A–E, dan "Kunci". Baris Essay: cukup "Poin / Skor" (skor maksimal), opsi & kunci dikosongkan.'],
            ],
            default => [],
        };
    }

    /** Bangun satu lembar tabel (dipakai untuk "Contoh" berisi & "Soal" kosong). */
    private function buildTableSheet($s, string $sheetTitle, array $config, array $rows): void
    {
        $s->setTitle($sheetTitle);
        $cols = $config['columns'];
        $lastCol = Coordinate::stringFromColumnIndex(count($cols));
        $isContoh = $sheetTitle === 'Contoh';

        // Judul.
        $s->mergeCells("A1:{$lastCol}1");
        $s->setCellValue('A1', ($isContoh ? 'CONTOH — ' : '') . $config['title']);
        $s->getStyle('A1')->getFont()->setBold(true)->setSize(13)->getColor()->setARGB('FFFFFFFF');
        $s->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($isContoh ? 'FF64748B' : $this->indigo);
        $s->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $s->getRowDimension(1)->setRowHeight(26);

        // Header kolom (baris 2).
        foreach ($cols as $i => $label) {
            $col = Coordinate::stringFromColumnIndex($i + 1);
            $s->setCellValue("{$col}2", $label);
            $s->getColumnDimension($col)->setWidth($config['widths'][$i]);
        }
        $s->getStyle("A2:{$lastCol}2")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $s->getStyle("A2:{$lastCol}2")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($this->dark);
        $s->getStyle("A2:{$lastCol}2")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER)->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $s->getRowDimension(2)->setRowHeight(30);

        // Isi (contoh) — semua sebagai teks agar rumus $..$ tidak diubah Excel.
        $r = 3;
        foreach ($rows as $row) {
            foreach ($row as $i => $val) {
                $col = Coordinate::stringFromColumnIndex($i + 1);
                $s->setCellValueExplicit("{$col}{$r}", (string) $val, DataType::TYPE_STRING);
            }
            $s->getStyle("A{$r}:{$lastCol}{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($this->example);
            $r++;
        }

        $lastRow = $isContoh ? max($r - 1, 2) : $r + 80; // "Soal" diberi banyak baris kosong
        if ($lastRow < 2) {
            $lastRow = 2;
        }
        $s->getStyle("A2:{$lastCol}{$lastRow}")->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFCBD5E1');
        $s->getStyle("A2:{$lastCol}{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);

        // Dropdown validasi (hanya pada lembar "Soal" yang diisi).
        if (!$isContoh) {
            $this->applyDropdowns($s, $cols, $lastRow);
            $s->setSelectedCell('B3');
        }
        $s->freezePane('A3');
    }

    private function applyDropdowns($s, array $cols, int $lastRow): void
    {
        $tipeIdx = array_search('Tipe (PG/Essay)', $cols, true);
        $kunciIdx = array_search('Kunci (A-E)', $cols, true);
        if ($tipeIdx !== false) {
            $this->dropdownRange($s, Coordinate::stringFromColumnIndex($tipeIdx + 1), $lastRow, '"PG,Essay"', 'Pilih PG atau Essay.');
        }
        if ($kunciIdx !== false) {
            $this->dropdownRange($s, Coordinate::stringFromColumnIndex($kunciIdx + 1), $lastRow, '"A,B,C,D,E"', 'Pilih huruf opsi yang benar (A–E).');
        }
    }

    private function dropdownRange($s, string $col, int $lastRow, string $formula, string $prompt): void
    {
        for ($row = 3; $row <= $lastRow; $row++) {
            $dv = $s->getCell("{$col}{$row}")->getDataValidation();
            $dv->setType(DataValidation::TYPE_LIST);
            $dv->setErrorStyle(DataValidation::STYLE_INFORMATION);
            $dv->setAllowBlank(true);
            $dv->setShowInputMessage(true);
            $dv->setShowErrorMessage(true);
            $dv->setShowDropDown(true);
            $dv->setPrompt($prompt);
            $dv->setFormula1($formula);
        }
    }

    /* ============================ IMPOR SOAL ============================ */

    /** Terima Excel (.xlsx/.xls) maupun Word (.docx). Word mendukung gambar tertempel. */
    public function import(Request $request, $examId)
    {
        $exam = Exam::findOrFail($examId);
        $this->authorizeExam($exam);

        if ($exam->hasStartedAttempts()) {
            return back()->with('error', 'Soal tidak bisa diimpor karena sudah ada siswa yang memulai ujian.');
        }

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,docx|max:8192',
        ], [
            'file.mimes' => 'Berkas harus Excel (.xlsx/.xls) atau Word (.docx).',
            'file.max' => 'Ukuran berkas maksimal 8 MB.',
        ]);

        $ext = strtolower($request->file('file')->getClientOriginalExtension());
        $path = $request->file('file')->getRealPath();

        try {
            $rows = $ext === 'docx' ? $this->parseWord($path) : $this->parseExcel($path);
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal membaca berkas: ' . $e->getMessage());
        }

        $allowMc = $exam->hasMc();
        $allowEssay = $exam->hasEssay();
        $imported = 0;
        $skipped = 0;
        $order = (int) ($exam->questions()->max('order') ?? 0);

        DB::transaction(function () use ($rows, $exam, $allowMc, $allowEssay, &$imported, &$skipped, &$order) {
            foreach ($rows as $row) {
                if (($row['type'] === 'mc' && !$allowMc) || ($row['type'] === 'essay' && !$allowEssay)) {
                    $skipped++;
                    continue;
                }
                $this->persistQuestion($exam, $row, $order);
                $order++;
                $imported++;
            }
        });

        if ($imported === 0) {
            return back()->with('error', 'Tidak ada soal yang diimpor. Pastikan soal diisi pada tabel/lembar "Soal".'
                . ($skipped ? " ($skipped baris tidak sesuai kategori ujian dilewati.)" : ''));
        }

        return back()->with('success', "$imported soal berhasil diimpor."
            . ($skipped ? " $skipped baris dilewati (tidak sesuai kategori ujian)." : ''));
    }

    /**
     * Baca Excel → daftar soal ternormalisasi.
     * Bentuk tiap item: [type, question, points, penalty, q_image=null, options=[[text,is_correct,image=null]]]
     */
    private function parseExcel(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getSheetByName('Soal') ?: $spreadsheet->getActiveSheet();
        $grid = $sheet->toArray(null, true, false, false);

        $headerIdx = $this->findHeaderRow($grid);
        if ($headerIdx === null) {
            throw new \RuntimeException('Judul kolom "Pertanyaan" tidak ditemukan pada lembar "Soal".');
        }
        $map = $this->mapColumns($grid[$headerIdx]);

        $out = [];
        for ($i = $headerIdx + 1; $i < count($grid); $i++) {
            $cells = array_map(fn ($v) => ['text' => trim((string) $v), 'image' => null], $grid[$i]);
            $item = $this->normalizeRow($cells, $map);
            if ($item) {
                $out[] = $item;
            }
        }
        return $out;
    }

    /**
     * Baca Word (.docx) → daftar soal ternormalisasi (mendukung gambar tertempel di sel).
     * Membaca TABEL TERAKHIR pada dokumen (tabel "SOAL"); tabel "CONTOH" diabaikan.
     */
    private function parseWord(string $path): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('Berkas Word tidak bisa dibuka.');
        }
        $docXml = $zip->getFromName('word/document.xml');
        $relsXml = $zip->getFromName('word/_rels/document.xml.rels');
        if ($docXml === false) {
            $zip->close();
            throw new \RuntimeException('Struktur .docx tidak dikenali.');
        }

        // Peta relasi rId → target media.
        $rels = [];
        if ($relsXml !== false && ($rx = @simplexml_load_string($relsXml)) !== false) {
            foreach ($rx->Relationship as $rel) {
                $rels[(string) $rel['Id']] = (string) $rel['Target'];
            }
        }

        $wns = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
        $ans = 'http://schemas.openxmlformats.org/drawingml/2006/main';
        $rns = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
        $vns = 'urn:schemas-microsoft-com:vml';

        $dom = new \DOMDocument();
        @$dom->loadXML($docXml);
        $xp = new \DOMXPath($dom);
        $xp->registerNamespace('w', $wns);
        $xp->registerNamespace('a', $ans);
        $xp->registerNamespace('r', $rns);
        $xp->registerNamespace('v', $vns);

        $tables = $xp->query('//w:tbl');
        if (!$tables || $tables->length === 0) {
            $zip->close();
            throw new \RuntimeException('Tabel soal tidak ditemukan pada dokumen Word.');
        }
        $table = $tables->item($tables->length - 1); // tabel terakhir = SOAL

        $matrix = [];
        foreach ($xp->query('./w:tr', $table) as $tr) {
            $rowCells = [];
            foreach ($xp->query('./w:tc', $tr) as $tc) {
                // Teks: gabung tiap paragraf dengan baris baru.
                $lines = [];
                foreach ($xp->query('./w:p', $tc) as $p) {
                    $s = '';
                    foreach ($xp->query('.//w:t', $p) as $t) {
                        $s .= $t->nodeValue;
                    }
                    $lines[] = $s;
                }
                // Gambar: ambil pertama. Dukung DrawingML (a:blip@r:embed) & VML (v:imagedata@r:id).
                $image = null;
                $rids = [];
                foreach ($xp->query('.//a:blip', $tc) as $blip) {
                    $rid = $blip->getAttributeNS($rns, 'embed');
                    if ($rid) $rids[] = $rid;
                }
                foreach ($xp->query('.//v:imagedata', $tc) as $vi) {
                    $rid = $vi->getAttributeNS($rns, 'id');
                    if ($rid) $rids[] = $rid;
                }
                foreach ($rids as $rid) {
                    if (!isset($rels[$rid])) continue;
                    $target = 'word/' . ltrim(str_replace('\\', '/', $rels[$rid]), '/');
                    $bytes = $zip->getFromName($target);
                    if ($bytes !== false) {
                        $image = ['bytes' => $bytes, 'ext' => strtolower(pathinfo($target, PATHINFO_EXTENSION)) ?: 'png'];
                        break;
                    }
                }
                $rowCells[] = ['text' => trim(implode("\n", $lines)), 'image' => $image];
            }
            $matrix[] = $rowCells;
        }
        $zip->close();

        if (count($matrix) < 1) {
            throw new \RuntimeException('Tabel soal kosong.');
        }
        $header = array_map(fn ($c) => $c['text'], $matrix[0]);
        $map = $this->mapColumns($header);
        if ($map['question'] === null) {
            throw new \RuntimeException('Judul kolom "Pertanyaan" tidak ditemukan pada tabel SOAL.');
        }

        $out = [];
        for ($i = 1; $i < count($matrix); $i++) {
            $item = $this->normalizeRow($matrix[$i], $map);
            if ($item) {
                $out[] = $item;
            }
        }
        return $out;
    }

    /** Ubah satu baris sel (each: [text,image]) menjadi item soal ternormalisasi, atau null bila kosong. */
    private function normalizeRow(array $cells, array $map): ?array
    {
        $qCell = $map['question'] !== null ? ($cells[$map['question']] ?? null) : null;
        $qText = trim((string) ($qCell['text'] ?? ''));
        $qImage = $qCell['image'] ?? null;
        if ($qText === '' && !$qImage) {
            return null; // baris kosong
        }

        // Tentukan tipe.
        $type = 'mc';
        if ($map['type'] !== null) {
            $t = strtolower(trim((string) ($cells[$map['type']]['text'] ?? '')));
            $type = ($t !== '' && str_starts_with($t, 'es')) ? 'essay' : ($t !== '' ? 'mc' : 'mc');
        } else {
            $type = ($map['A'] !== null || $map['B'] !== null) ? 'mc' : 'essay';
        }

        $points = $this->num($map['points'] !== null ? ($cells[$map['points']]['text'] ?? null) : null, $type === 'essay' ? 10 : 1);
        $penalty = $this->num($map['penalty'] !== null ? ($cells[$map['penalty']]['text'] ?? null) : null, 0);

        $options = [];
        if ($type === 'mc') {
            $key = strtoupper(trim((string) ($map['key'] !== null ? ($cells[$map['key']]['text'] ?? '') : '')));
            $key = $key !== '' ? $key[0] : '';
            foreach (['A', 'B', 'C', 'D', 'E'] as $letter) {
                if ($map[$letter] === null) continue;
                $cell = $cells[$map[$letter]] ?? null;
                $text = trim((string) ($cell['text'] ?? ''));
                $img = $cell['image'] ?? null;
                if ($text === '' && !$img) continue;
                $options[] = ['text' => $text, 'is_correct' => ($letter === $key), 'image' => $img];
            }
        }

        return ['type' => $type, 'question' => $qText, 'points' => $points, 'penalty' => $penalty, 'q_image' => $qImage, 'options' => $options];
    }

    /** Simpan satu soal ternormalisasi ke DB (beserta opsi & gambar). */
    private function persistQuestion(Exam $exam, array $row, int $prevOrder): void
    {
        $data = [
            'exam_id' => $exam->id,
            'type' => $row['type'],
            'question_text' => $row['question'],
            'points' => $row['points'],
            'penalty' => $row['type'] === 'mc' ? $row['penalty'] : 0,
            'order' => $prevOrder + 1,
        ];
        if (!empty($row['q_image'])) {
            $data['image_path'] = $this->storeImage($row['q_image']);
        }
        $question = Question::create($data);

        if ($row['type'] === 'mc') {
            $i = 0;
            $hasCorrect = false;
            foreach ($row['options'] as $opt) {
                $question->options()->create([
                    'label' => chr(65 + $i),
                    'option_text' => $opt['text'],
                    'image_path' => !empty($opt['image']) ? $this->storeImage($opt['image']) : null,
                    'is_correct' => !empty($opt['is_correct']),
                    'order' => $i,
                ]);
                if (!empty($opt['is_correct'])) $hasCorrect = true;
                $i++;
            }
            if (!$hasCorrect && ($first = $question->options()->orderBy('order')->first())) {
                $first->update(['is_correct' => true]);
            }
        }

        // Soal hasil impor juga masuk Bank Soal Bersama.
        \App\Support\BankSoal::cerminkan($question->fresh('options'), $exam);
    }

    private function storeImage(array $image): string
    {
        $ext = in_array($image['ext'], ['jpg', 'jpeg', 'png', 'gif']) ? $image['ext'] : 'png';
        $path = 'exam-questions/' . Str::uuid() . '.' . $ext;
        Storage::disk('public')->put($path, $image['bytes']);
        return $path;
    }

    private function findHeaderRow(array $grid): ?int
    {
        foreach ($grid as $i => $row) {
            foreach ($row as $cell) {
                if (is_string($cell) && stripos($cell, 'pertanyaan') !== false) {
                    return $i;
                }
            }
        }
        return null;
    }

    /** Petakan indeks kolom (0-based) berdasarkan judul header. */
    private function mapColumns(array $header): array
    {
        $map = ['type' => null, 'question' => null, 'points' => null, 'penalty' => null, 'key' => null,
            'A' => null, 'B' => null, 'C' => null, 'D' => null, 'E' => null];
        foreach ($header as $idx => $h) {
            $h = strtolower(trim((string) $h));
            if ($h === '') continue;
            if (str_contains($h, 'tipe')) $map['type'] = $idx;
            elseif (str_contains($h, 'pertanyaan')) $map['question'] = $idx;
            elseif (str_contains($h, 'pengurang')) $map['penalty'] = $idx;
            elseif (str_contains($h, 'poin') || str_contains($h, 'skor')) $map['points'] = $idx;
            elseif (str_contains($h, 'kunci')) $map['key'] = $idx;
            elseif (str_contains($h, 'opsi a')) $map['A'] = $idx;
            elseif (str_contains($h, 'opsi b')) $map['B'] = $idx;
            elseif (str_contains($h, 'opsi c')) $map['C'] = $idx;
            elseif (str_contains($h, 'opsi d')) $map['D'] = $idx;
            elseif (str_contains($h, 'opsi e')) $map['E'] = $idx;
        }
        return $map;
    }

    private function num($val, float $default): float
    {
        if ($val === null || trim((string) $val) === '') return $default;
        $val = str_replace(',', '.', trim((string) $val));
        return is_numeric($val) ? (float) $val : $default;
    }

    private function authorizeExam(Exam $exam): void
    {
        $user = auth()->user();
        if ($user->hasRole('Guru')) {
            $teacherId = $user->teacher?->id;
            if (!$teacherId || $exam->teachingAssignment?->teacher_id !== $teacherId) {
                abort(403, 'Anda hanya dapat mengelola ujian milik Anda.');
            }
        }
    }
}
