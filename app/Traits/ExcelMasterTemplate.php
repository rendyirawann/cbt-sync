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

        // Peta: key kolom → indeks kolom di file.
        //
        // DUA TAHAP, dan urutannya penting. Dulu hanya str_contains, sehingga
        // label pendek bisa menyambar kolom lain: "Nama" cocok dengan "Nama Ortu",
        // "Email" cocok dengan "Email Ortu". Selama urutan kolomnya persis seperti
        // template hal itu tidak terasa, tapi begitu pengguna memindah/menambah
        // kolom, isi kolom bisa masuk ke field yang salah — dan itu muncul sebagai
        // "data kosong" atau tertukar tanpa pesan galat apa pun.
        $header = $grid[$headerIdx];
        $map = [];
        $terpakai = [];

        // Tahap 1: kecocokan PENUH.
        foreach ($columns as $col) {
            $label = $norm($col['label']);
            foreach ($header as $j => $h) {
                if (isset($terpakai[$j]) || !is_string($h)) {
                    continue;
                }
                if ($norm($h) === $label) {
                    $map[$col['key']] = $j;
                    $terpakai[$j] = true;
                    break;
                }
            }
        }

        // Tahap 2: sisanya baru dicocokkan sebagian, dan hanya ke kolom yang
        // belum terpakai — supaya tidak merebut kolom milik label lain.
        foreach ($columns as $col) {
            if (isset($map[$col['key']])) {
                continue;
            }
            $label = $norm($col['label']);
            foreach ($header as $j => $h) {
                if (isset($terpakai[$j]) || !is_string($h) || $norm($h) === '') {
                    continue;
                }
                if (str_contains($norm($h), $label)) {
                    $map[$col['key']] = $j;
                    $terpakai[$j] = true;
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

    /**
     * $catatan: peringatan LUNAK — barisnya tetap masuk, tetapi ada kolom penting
     * yang dibiarkan kosong. Ini yang membuat data terasa "kosong" setelah impor
     * padahal tidak ada galat; sebelumnya sama sekali tidak dilaporkan.
     */
    protected function importSummary(int $imported, int $skipped, array $errors, array $catatan = []): \Illuminate\Http\RedirectResponse
    {
        if ($imported === 0 && $skipped === 0 && empty($errors)) {
            return back()->with('error', 'Tidak ada data pada lembar "Data" yang bisa diimpor.');
        }
        $msg = "$imported data berhasil diimpor.";
        if ($skipped) $msg .= " $skipped dilewati (sudah ada).";
        if ($errors) $msg .= ' Gagal: ' . implode('; ', array_slice($errors, 0, 5)) . (count($errors) > 5 ? ' …' : '');

        $r = back()->with($errors ? 'error' : 'success', $msg);

        if ($catatan) {
            $r = $r->with('warning', 'Perlu dilengkapi: ' . implode('; ', array_slice($catatan, 0, 6))
                . (count($catatan) > 6 ? ' … (' . count($catatan) . ' baris)' : ''));
        }

        return $r;
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

        // Petunjuk dipisah menjadi bagian-bagian. Bentuk lama berupa satu daftar
        // bernomor panjang: kolom wajib dan opsional bercampur, sehingga orang
        // tidak tahu mana yang benar-benar harus diisi dan mana yang boleh
        // dilewati — itulah yang berujung pada data masuk tapi banyak kosong.
        $wajib = array_values(array_filter($spec['columns'], fn ($c) => !empty($c['required'])));
        $opsional = array_values(array_filter($spec['columns'], fn ($c) => empty($c['required'])));

        $baris = function (array $col): string {
            $opt = !empty($col['options']) ? '  [pilihan: ' . implode(' / ', $col['options']) . ']' : '';
            $hint = !empty($col['hint']) ? '  — ' . $col['hint'] : '';

            return $col['label'] . $opt . $hint;
        };

        $lines = [['', '']];
        $lines[] = ['1', 'Isi data pada lembar "Data" saja. Lembar "Contoh" hanya panduan dan TIDAK diimpor.'];
        $lines[] = ['2', 'Jangan mengubah/menghapus/memindahkan baris judul kolom. Menambah kolom sendiri tidak apa-apa, kolom itu diabaikan.'];
        $lines[] = ['3', 'Baris yang seluruh selnya kosong dilewati. Isi mulai baris ke-3.'];
        $lines[] = ['4', 'Sel bertanda * dan berwarna ungu = WAJIB. Baris yang kolom wajibnya kosong akan GAGAL dan dilaporkan nomor barisnya.'];

        $lines[] = ['', ''];
        $lines[] = ['A.', 'KOLOM WAJIB — baris ditolak bila ini kosong'];
        foreach ($wajib as $col) {
            $lines[] = ['*', $baris($col)];
        }

        $lines[] = ['', ''];
        $lines[] = ['B.', 'KOLOM OPSIONAL — baris tetap masuk, tetapi datanya jadi kosong di aplikasi'];
        foreach ($opsional as $col) {
            $lines[] = ['-', $baris($col)];
        }

        if (!empty($spec['guide'])) {
            $lines[] = ['', ''];
            $lines[] = ['C.', 'CATATAN PENTING'];
            foreach ($spec['guide'] as $gitem) {
                $lines[] = ['•', $gitem];
            }
        }

        $lines[] = ['', ''];
        $lines[] = ['D.', 'YANG SERING BIKIN DATA KOSONG / GAGAL'];
        foreach ([
            'Angka berawalan 0 (NISN, No. HP) — kolomnya sudah diatur bertipe TEKS. Jangan ubah formatnya, kalau tidak angka 0 di depan hilang.',
            'Tanggal — tulis persis dd/mm/yyyy, mis. 17/08/2010. Bila Excel mengubahnya jadi tanggal berformat lain, kolomnya jadi tidak terbaca dan berakhir kosong.',
            'Kolom berdropdown — pilih dari daftar, jangan diketik manual. Nama yang tidak sama persis akan diabaikan tanpa peringatan.',
            'Menyalin-tempel dari Word/web sering menyertakan spasi tersembunyi. Tempel dengan Paste Special > Values.',
            'Satu baris = satu data. Jangan menggabungkan sel (merge) di lembar "Data".',
        ] as $tips) {
            $lines[] = ['!', $tips];
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

            // Kolom WAJIB diberi warna berbeda supaya terlihat sekali lihat,
            // tidak hanya bergantung pada tanda bintang.
            if (!empty($col['required'])) {
                $s->getStyle("{$letter}2")->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FF7E22CE');
            }
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

        // Kolom yang ditandai 'format' => 'text' dipaksa bertipe TEKS.
        //
        // Tanpa ini Excel "membetulkan" isinya sendiri: NISN 0012345678 kehilangan
        // angka 0 di depan, nomor HP 081... jadi 8,1E+10, dan tanggal 17/08/2010
        // diubah ke format tanggal lokal komputer (mis. 08/17/2010) sehingga
        // terbaca sebagai bulan ke-17 lalu ditolak dan berakhir KOSONG.
        // BATAS JAGA, dipisah dari $lastRow.
        //
        // $lastRow hanya sejauh baris contoh + 80, dan itu dipakai untuk hal
        // kosmetik (garis, perataan). Tapi penjaga isi TIDAK boleh berhenti di
        // situ: begitu petugas menempel 365 baris, baris ke-81 dan seterusnya
        // kehilangan format Teks — dan NISN 0091910544 kembali kehilangan nol
        // depannya tanpa peringatan apa pun. Itu tepat yang terjadi di lapangan.
        $batasJaga = $isData ? max($lastRow, 1000) : $lastRow;

        foreach ($cols as $i => $col) {
            if (($col['format'] ?? null) !== 'text') {
                continue;
            }
            $letter = Coordinate::stringFromColumnIndex($i + 1);
            $s->getStyle("{$letter}3:{$letter}{$batasJaga}")
                ->getNumberFormat()->setFormatCode('@');
        }

        // Penjaga nilai KEMBAR untuk kolom yang ditandai 'unik'. Dua lapis,
        // karena satu lapis saja tidak cukup:
        //   - Data Validation menolak saat DIKETIK, tapi TIDAK berjalan saat
        //     isian di-TEMPEL — dan menempel dari daftar lain justru cara
        //     paling umum mengisi template ini.
        //   - Conditional formatting mewarnai merah, dan itu tetap bekerja
        //     pada isian yang ditempel.
        // Kolom yang ditandai unik mengikuti indeks UNIQUE yang benar-benar ada
        // di database: users_email_unique, users_username_unique,
        // students_nisn_unique.
        if ($isData) {
            foreach ($cols as $i => $col) {
                if (empty($col['unik'])) {
                    continue;
                }
                $letter = Coordinate::stringFromColumnIndex($i + 1);
                $rentang = "\${$letter}\$3:\${$letter}\${$batasJaga}";
                $label = str_replace('*', '', $col['label']);

                $dv = new DataValidation();
                $dv->setType(DataValidation::TYPE_CUSTOM);
                $dv->setErrorStyle(DataValidation::STYLE_STOP);
                $dv->setAllowBlank(true);
                $dv->setShowErrorMessage(true);
                $dv->setErrorTitle("$label kembar");
                $dv->setError("$label ini sudah dipakai di baris lain. Setiap siswa harus punya $label sendiri — kalau kembar, barisnya TIDAK akan masuk saat diimpor.");
                // COUNTIF menghitung kemunculan nilai sel ini di seluruh kolom;
                // lebih dari satu berarti kembar. Dipasang sekali untuk seluruh
                // rentang, bukan per sel, supaya berkasnya tetap ringan.
                $dv->setFormula1("COUNTIF({$rentang},{$letter}3)<2");
                $s->setDataValidation("{$letter}3:{$letter}{$batasJaga}", $dv);

                $kondisi = new \PhpOffice\PhpSpreadsheet\Style\Conditional();
                $kondisi->setConditionType(\PhpOffice\PhpSpreadsheet\Style\Conditional::CONDITION_EXPRESSION);
                $kondisi->setOperatorType(\PhpOffice\PhpSpreadsheet\Style\Conditional::OPERATOR_NONE);
                // Dirangkai dengan kutip TUNGGAL: rumusnya memuat "" (sel kosong),
                // dan menuliskannya di dalam string berkutip ganda butuh escaping
                // berlapis yang mudah salah.
                $kondisi->addCondition('AND(' . $letter . '3<>"",COUNTIF(' . $rentang . ',' . $letter . '3)>1)');
                $kondisi->getStyle()->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFFECACA');
                $kondisi->getStyle()->getFont()->setBold(true)->getColor()->setARGB('FF991B1B');

                $gaya = $s->getStyle("{$letter}3:{$letter}{$batasJaga}");
                $daftar = $gaya->getConditionalStyles();
                $daftar[] = $kondisi;
                $gaya->setConditionalStyles($daftar);
            }

            foreach ($cols as $i => $col) {
                if (!empty($col['options'])) {
                    $letter = Coordinate::stringFromColumnIndex($i + 1);
                    // Daftar pilihan panjang (mis. nama gelombang/kelas) tidak
                    // dimasukkan sebagai rumus literal karena Excel membatasi
                    // panjangnya; di atas 240 karakter dropdown-nya dilewati saja
                    // agar berkasnya tidak rusak.
                    $isiDaftar = implode(',', $col['options']);
                    if (mb_strlen($isiDaftar) <= 240) {
                        $this->applyDropdown($s, $letter, $batasJaga, '"' . $isiDaftar . '"');
                    }
                }
            }
            $s->setSelectedCell('A3');
        }
        $s->freezePane('A3');
    }

    private function applyDropdown($s, string $col, int $lastRow, string $formula): void
    {
        // Dipasang sekali untuk seluruh rentang, bukan per sel: dengan batas
        // jaga 1000 baris, versi per-sel menghasilkan ribuan entri validasi dan
        // membengkakkan berkasnya tanpa guna.
        $dv = new DataValidation();
        $dv->setType(DataValidation::TYPE_LIST);
        $dv->setErrorStyle(DataValidation::STYLE_INFORMATION);
        $dv->setAllowBlank(true);
        $dv->setShowDropDown(true);
        $dv->setShowInputMessage(true);
        $dv->setShowErrorMessage(true);
        $dv->setFormula1($formula);
        $s->setDataValidation("{$col}3:{$col}{$lastRow}", $dv);
    }
}
