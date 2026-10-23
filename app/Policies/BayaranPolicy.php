<?php

namespace App\Policies;

use App\Models\Bayaran;
use App\Models\User;

class BayaranPolicy
{
    /** Menunggu → Diterima. Hanya pemilik/pengurus; baki hanya berubah selepas ini. */
    public function sahkan(User $user, Bayaran $bayaran): bool
    {
        return $bayaran->menunggu() && $this->urus($user, $bayaran);
    }

    public function update(User $user, Bayaran $bayaran): bool
    {
        return $this->urus($user, $bayaran);
    }

    public function delete(User $user, Bayaran $bayaran): bool
    {
        return $this->urus($user, $bayaran);
    }

    private function urus(User $user, Bayaran $bayaran): bool
    {
        return $user->can('update', $bayaran->projek);
    }
}
