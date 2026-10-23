<?php

namespace App\Livewire;

use App\Enums\Peranan;
use App\Mail\JemputanMail;
use App\Models\Jemputan;
use App\Models\Keahlian;
use App\Models\LogAktiviti;
use App\Support\SyarikatSemasa;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Pemilik urus semua ahli; pengurus jemput & urus staf sahaja (Peranan::bolehUrus()).
 * Tiada sesiapa boleh mengubah keahlian sendiri — elak pemilik terkunci di luar.
 */
#[Layout('layouts::app')]
#[Title('Ahli & Jemputan')]
class UrusAhli extends Component
{
    public string $email = '';

    public string $peranan = 'staf';

    public ?string $mesej = null;

    public function mount(): void
    {
        abort_unless($this->saya()->bolehUrus(), 403);
        abort_if($this->semasa()->wajib()->bacaSahaja(), 403);
    }

    public function jemput(): void
    {
        $saya = $this->saya();
        $syarikat = $this->semasa()->wajib();
        abort_if($syarikat->bacaSahaja(), 403);

        $data = $this->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
            'peranan' => ['required', Rule::in(array_map(fn ($p) => $p->value, $saya->bolehUrus()))],
        ], ['peranan.in' => 'Anda tidak boleh menjemput peranan ini.']);

        $email = Str::lower(trim($data['email']));
        $sudahAhli = Keahlian::where('syarikat_id', $syarikat->id)
            ->whereHas('user', fn ($q) => $q->whereRaw('lower(email) = ?', [$email]))->exists();
        if ($sudahAhli) {
            $this->addError('email', 'E-mel ini sudah ahli syarikat.');

            return;
        }

        // Elak e-mel spam dari satu akaun yang dicuri.
        $kunci = 'jemput:'.$syarikat->id.':'.auth()->id();
        if (RateLimiter::tooManyAttempts($kunci, 20)) {
            $this->addError('email', 'Terlalu banyak jemputan. Cuba lagi dalam sejam.');

            return;
        }
        RateLimiter::hit($kunci, 3600);

        $this->hantar($email, Peranan::from($data['peranan']));
        $this->reset('email');
        $this->mesej = "Jemputan dihantar ke {$email}.";
    }

    public function hantarSemula(int $id): void
    {
        $j = Jemputan::belumDiterima()->findOrFail($id);
        abort_unless($this->saya()->bolehUrusPeranan($j->peranan), 403);
        $this->hantar($j->email, $j->peranan);
        $this->mesej = "Jemputan baharu dihantar ke {$j->email}. Pautan lama tidak lagi sah.";
    }

    public function batal(int $id): void
    {
        $j = Jemputan::belumDiterima()->findOrFail($id);
        abort_unless($this->saya()->bolehUrusPeranan($j->peranan), 403);
        $j->delete();
        LogAktiviti::catat('Batal jemputan', $j->email);
    }

    public function togolAktif(int $id): void
    {
        $k = $this->ahliBolehDiurus($id);
        $k->update(['aktif' => ! $k->aktif]);
        LogAktiviti::catat($k->aktif ? 'Aktifkan ahli' : 'Nyahaktif ahli', $k->user->email);
    }

    public function tukarPeranan(int $id, string $baharu): void
    {
        $k = $this->ahliBolehDiurus($id);
        $p = Peranan::tryFrom($baharu);
        abort_unless($p && $this->saya()->bolehUrusPeranan($p), 403);
        $k->update(['peranan' => $p]);
        LogAktiviti::catat('Tukar peranan', "{$k->user->email} → {$p->label()}");
    }

    public function render()
    {
        $syarikat = $this->semasa()->wajib();

        return view('livewire.urus-ahli', [
            'ahli' => Keahlian::with('user')->where('syarikat_id', $syarikat->id)->get()
                ->sortBy(fn ($k) => [array_search($k->peranan, Peranan::cases(), true), $k->user->name])->values(),
            'jemputan' => Jemputan::belumDiterima()->with('penjemput')->latest()->get(),
            'pilihan' => $this->saya()->bolehUrus(),
            'saya' => $this->saya(),
        ]);
    }

    private function hantar(string $email, Peranan $peranan): void
    {
        $syarikat = $this->semasa()->wajib();
        [$j, $token] = Jemputan::terbitkan($email, $peranan, auth()->user());
        Mail::to($email)->send(new JemputanMail($j, $syarikat, $syarikat->url('/jemputan/'.$token)));
        LogAktiviti::catat('Hantar jemputan', "{$email} · {$peranan->label()}");
    }

    private function ahliBolehDiurus(int $id): Keahlian
    {
        $k = Keahlian::with('user')->where('syarikat_id', $this->semasa()->id())->findOrFail($id);
        abort_if($k->user_id === auth()->id(), 403, 'Anda tidak boleh mengubah keahlian sendiri.');
        abort_unless($this->saya()->bolehUrusPeranan($k->peranan), 403);

        return $k;
    }

    private function saya(): Peranan
    {
        return auth()->user()->peranan() ?? abort(403);
    }

    private function semasa(): SyarikatSemasa
    {
        return app(SyarikatSemasa::class);
    }
}
