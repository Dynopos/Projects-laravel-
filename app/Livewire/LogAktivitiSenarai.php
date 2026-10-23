<?php

namespace App\Livewire;

use App\Enums\Peranan;
use App\Models\LogAktiviti;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::app')]
#[Title('Log Aktiviti')]
class LogAktivitiSenarai extends Component
{
    use WithPagination;

    public function mount(): void
    {
        abort_if(auth()->user()->peranan() === Peranan::Staf, 403);
    }

    public function render()
    {
        return view('livewire.log-aktiviti', [
            'log' => LogAktiviti::with('user')->latest('id')->paginate(50),
        ]);
    }
}
