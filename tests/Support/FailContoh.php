<?php

namespace Tests\Support;

use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Fail Excel REKAAN untuk ujian — meniru struktur PROJECT FLOW sebenar (baris
 * berbilang, amaun pendua, teks tarikh M/D/YY, sel tarikh sebenar, invois berulang,
 * baris TOTAL) tanpa sebarang data pelanggan.
 *
 * Jangkaan: 4 projek · 5 bayaran · RM29,000 nilai · RM10,000 diterima.
 */
class FailContoh
{
    public const TAJUK = ['No.', 'Nama Klien', 'Alamat', 'Date Invoice', 'Inv No', 'Deposit', 'Drawing', 'Picture Before',
        'Date Installation', 'Balance Payment', 'Picture After', 'Feedback', 'Total Payment By Client(RM)', 'Socmed',
        'Total Project(RM)', 'Remarks'];

    public static function flow(): string
    {
        $baris = [
            ['PROJECT FLOW 2026 (CONTOH)'],
            [],
            self::TAJUK,
            // 1 — dua bayaran, satu amaun pendua (inv & deposit sama), selesai → Siap
            ['1', 'EN CONTOH SATU', 'TAMAN CONTOH, SHAH ALAM', '1/15/26', 'No: INV001/2026', 'DONE', 'DONE', 'DONE', '3/2/26', '3/20/26', 'DONE', 'DONE', '', 'DONE', 'RM5,000.00', 'Kabinet dapur'],
            ['', '', '012-555 0101', '', 'RM2,000.00', 'RM2,000.00', '', '', '', 'RM3,000.00', '', '', 'RM5,000.00', '', '', ''],
            ['', '', '', '', '', '1/16/26', '', '', '', '', '', '', '', '', '', ''],
            [],
            // 2 — kurang dari jumlah lajur Total Payment → "Bayaran Lain"; tarikh invois sel tarikh sebenar
            ['2', 'PUAN CONTOH DUA', 'BANGI', 'TARIKH', 'INV002/2026', 'DONE', '', '', '', '', '', '', '', '', 'RM10,000.00', ''],
            ['', '', '013-555 0202', '', '', 'RM3,000.00', '', '', '', '', '', '', 'RM4,000.00', '', '', ''],
            [],
            // 3 — nama sementara, tiada bayaran, invois berulang
            ['3', 'XXX', 'KAJANG', '', 'INV002/2026', '', '', '', '', '', '', '', '', '', 'RM8,000.00', ''],
            [],
            // 4 — satu amaun melebihi jumlah → diganti "Bayaran Diterima"; tarikh masa depan (M/D/YY)
            ['4', 'SYARIKAT CONTOH EMPAT', 'SEPANG', '', 'INV004/2026', 'DONE', '', '', '', '', '', '', '', '', 'RM6,000.00', ''],
            ['', '', '019-555 0404', '', '', 'RM1,005.00', '', '', '', '', '', '', 'RM1,000.00', '', '', ''],
            ['', '', '', '', '', '12/08/26', '', '', '', '', '', '', '', '', '', ''],
            [],
            ['', '', '', '', '', '', '', '', '', '', '', 'TOTAL', 'RM10,000.00', '', 'RM29,000.00', ''],
            [],
            ['', 'SALES JANUARY =>', '', '', '', '', '', '', '', '', '', '', '', '', 'RM5,000.00', ''],
        ];

        $buku = new Spreadsheet;
        $ws = $buku->getActiveSheet()->setTitle('PROJECT FLOW 2026');
        $ws->fromArray($baris, null, 'A1', true);
        // Sel tarikh sebenar (bukan teks) — mesti dibaca tepat sebagai 2026-02-10.
        $ws->setCellValue('D8', Date::PHPToExcel(new \DateTime('2026-02-10')));
        $ws->getStyle('D8')->getNumberFormat()->setFormatCode('m/d/yyyy');
        $buku->createSheet()->setTitle('Nota')->setCellValue('A1', 'helaian lain');

        return self::tulis($buku);
    }

    public static function generik(): string
    {
        $buku = new Spreadsheet;
        $buku->getActiveSheet()->setTitle('Senarai')->fromArray([
            ['Kod', 'Nama Projek', 'Klien', 'Nilai Kontrak (RM)', 'Deposit', 'Status', 'Tarikh Mula'],
            ['ABC-1', 'Rumah teres contoh', 'EN CONTOH', '120,000', '30,000', 'Dalam progres', '15/03/2026'],
            ['', 'Pejabat contoh', 'SYARIKAT CONTOH', '80000', '', 'Baru', '2026-04-01'],
            ['', '', '', '', '', '', ''],
        ], null, 'A1', true);

        return self::tulis($buku);
    }

    private static function tulis(Spreadsheet $buku): string
    {
        $laluan = tempnam(sys_get_temp_dir(), 'dyno').'.xlsx';
        (new Xlsx($buku))->save($laluan);

        return $laluan;
    }
}
