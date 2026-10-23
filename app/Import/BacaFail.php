<?php

namespace App\Import;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

/**
 * Baca .xls / .xlsx / .csv kepada helaian → baris → sel (rentetan).
 *
 * Sel teks & nombor dipulangkan seperti yang dipapar dalam Excel (formatData), sama
 * dengan SheetJS raw:false dalam versi HTML. Sel TARIKH sebenar dipulangkan sebagai
 * Y-m-d — tepat, tanpa perlu meneka M/D/Y atau D/M/Y.
 */
class BacaFail
{
    /** @return array<string, list<list<string>>> nama helaian => baris */
    public static function helaian(string $laluan): array
    {
        $pembaca = IOFactory::createReaderForFile($laluan);
        $pembaca->setReadDataOnly(false);   // perlu format nombor untuk kenal sel tarikh
        $buku = $pembaca->load($laluan);

        $hasil = [];
        foreach ($buku->getWorksheetIterator() as $ws) {
            $baris = [];
            foreach ($ws->getRowIterator() as $row) {
                $sel = [];
                $iter = $row->getCellIterator();
                $iter->setIterateOnlyExistingCells(false);
                foreach ($iter as $c) {
                    $sel[] = self::teks($c);
                }
                $baris[] = $sel;
            }
            // Buang baris kosong di hujung
            while ($baris && trim(implode('', end($baris))) === '') {
                array_pop($baris);
            }
            $hasil[$ws->getTitle()] = $baris;
        }

        return $hasil;
    }

    private static function teks(?Cell $c): string
    {
        if (! $c) {
            return '';
        }
        $v = $c->getValue();
        if ($v === null) {
            return '';
        }
        if (is_numeric($v) && Date::isDateTime($c)) {
            return Date::excelToDateTimeObject((float) $v)->format('Y-m-d');
        }

        return trim((string) $c->getFormattedValue());
    }
}
