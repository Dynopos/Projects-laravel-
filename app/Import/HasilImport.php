<?php

namespace App\Import;

/** Projek & bayaran yang difahami dari fail, sebelum masuk pangkalan data. */
class HasilImport
{
    /**
     * @param  list<array<string, mixed>>  $projek  kod, nama, klien, lokasi, telefon, inv, status, nilai, mula, nota, aliran
     * @param  list<array<string, mixed>>  $bayaran  kod, jenis, amaun, tarikh, ruj
     * @param  array<string, string>  $peta  medan => tajuk lajur (generik sahaja)
     */
    public function __construct(
        public string $format,
        public string $helaian,
        public array $projek,
        public array $bayaran,
        public array $peta = [],
    ) {}

    public function jumlahNilai(): float
    {
        return round(array_sum(array_column($this->projek, 'nilai')), 2);
    }

    public function jumlahDiterima(): float
    {
        return round(array_sum(array_column($this->bayaran, 'amaun')), 2);
    }

    public function diterimaUntuk(string $kod): float
    {
        return round(array_sum(array_map(fn ($b) => $b['kod'] === $kod ? $b['amaun'] : 0, $this->bayaran)), 2);
    }

    /** @return list<string> No. invois yang dipakai lebih dari satu projek */
    public function invoisBerulang(): array
    {
        $n = array_count_values(array_filter(array_column($this->projek, 'inv')));

        return array_keys(array_filter($n, fn ($c) => $c > 1));
    }

    /** @return list<array<string, mixed>> */
    public function bayaranMasaDepan(string $hariIni): array
    {
        return array_values(array_filter($this->bayaran, fn ($b) => $b['tarikh'] && $b['tarikh'] > $hariIni));
    }
}
