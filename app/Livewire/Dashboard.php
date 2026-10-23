<?php

namespace App\Livewire;

use App\Models\Bayaran;
use App\Models\Projek;
use App\Support\Kiraan;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::app')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    public function sahkan(int $id): void
    {
        $bayaran = Bayaran::findOrFail($id);
        $this->authorize('sahkan', $bayaran);
        $bayaran->sahkan(auth()->user());
    }

    public function render()
    {
        $user = auth()->user();
        $projek = Projek::query()->bolehDilihat($user);

        return view('livewire.dashboard', [
            'r' => Kiraan::ringkasan($projek),
            'menunggu' => Bayaran::query()->with('projek')->where('status', Bayaran::MENUNGGU)
                ->whereIn('projek_id', (clone $projek)->select('id'))
                ->latest()->limit(10)->get(),
            'bolehSahkan' => (bool) $user->peranan()?->pengurusan(),
        ]);
    }
}
