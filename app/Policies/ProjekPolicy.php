<?php

namespace App\Policies;

use App\Models\Projek;
use App\Models\User;
use App\Support\SyarikatSemasa;

class ProjekPolicy
{
    public function view(User $user, Projek $projek): bool
    {
        if (! $this->syarikatSama($projek) || ! $p = $user->peranan()) {
            return false;
        }

        return $p->nampakSemua() || $projek->staf()->whereKey($user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $this->bolehTulis() && (bool) $user->peranan()?->pengurusan();
    }

    public function update(User $user, Projek $projek): bool
    {
        return $this->syarikatSama($projek) && $this->create($user);
    }

    public function delete(User $user, Projek $projek): bool
    {
        return $this->update($user, $projek);
    }

    /** Staf rekod bayaran & belanja untuk projek yang dia nampak. Akauntan baca sahaja. */
    public function rekod(User $user, Projek $projek): bool
    {
        return $this->bolehTulis() && $this->view($user, $projek) && $user->peranan()?->value !== 'akauntan';
    }

    private function syarikatSama(Projek $projek): bool
    {
        return (int) $projek->syarikat_id === app(SyarikatSemasa::class)->id();
    }

    private function bolehTulis(): bool
    {
        return ! app(SyarikatSemasa::class)->get()?->bacaSahaja();
    }
}
