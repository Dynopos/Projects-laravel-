<?php

namespace App\Enums;

enum Peranan: string
{
    case Pemilik = 'pemilik';
    case Pengurus = 'pengurus';
    case Staf = 'staf';
    case Akauntan = 'akauntan';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /** Boleh cipta/ubah projek, sahkan bayaran, padam rekod, import. */
    public function pengurusan(): bool
    {
        return in_array($this, [self::Pemilik, self::Pengurus], true);
    }

    /** Nampak semua projek syarikat (staf hanya projek yang ditugaskan). */
    public function nampakSemua(): bool
    {
        return $this !== self::Staf;
    }

    public function bolehEksport(): bool
    {
        return $this !== self::Staf;
    }

    /**
     * Peranan yang boleh dijemput / diurus oleh peranan ini.
     * Pemilik: semua. Pengurus: staf sahaja. Lain-lain: tiada.
     *
     * @return list<self>
     */
    public function bolehUrus(): array
    {
        return match ($this) {
            self::Pemilik => self::cases(),
            self::Pengurus => [self::Staf],
            default => [],
        };
    }

    public function bolehUrusPeranan(self $sasaran): bool
    {
        return in_array($sasaran, $this->bolehUrus(), true);
    }
}
