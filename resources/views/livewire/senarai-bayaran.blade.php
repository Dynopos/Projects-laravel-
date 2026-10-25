@php($rm = fn ($sen) => \App\Support\Kiraan::rm($sen))
<div>
  <div class="head">
    <div><h1>Pembayaran</h1><p>Semua bayaran klien merentas projek. Bayaran <b>Menunggu</b> tidak dikira dalam baki sehingga disahkan.</p></div>
    @if($projekRekod->isNotEmpty())
      <div class="acts"><button class="btn p" wire:click="bukaBorang">+ Bayaran</button></div>
    @endif
  </div>

  @if($borangBuka)
    <form class="borang" wire:submit="simpan">
      <div class="g2">
        <div class="full"><label>Projek</label>
          <select class="field" wire:model="b.projek_id">
            <option value="">— Pilih projek —</option>
            @foreach($projekRekod as $p)<option value="{{ $p->id }}">{{ $p->kod }} — {{ $p->nama }}</option>@endforeach
          </select>@error('b.projek_id')<div class="ralat">{{ $message }}</div>@enderror
        </div>
        <div><label>Jenis</label><input class="field" wire:model="b.jenis" list="jenis-bayaran">
          <datalist id="jenis-bayaran"><option>Deposit</option><option>Bayaran Progres</option><option>Bayaran Akhir</option></datalist></div>
        <div><label>Amaun (RM)</label><input class="field" wire:model="b.amaun" inputmode="decimal">@error('b.amaun')<div class="ralat">{{ $message }}</div>@enderror</div>
        <div><label>Tarikh</label><input class="field" type="date" wire:model="b.tarikh"></div>
        <div><label>Kaedah</label><input class="field" wire:model="b.kaedah" list="kaedah-bayaran">
          <datalist id="kaedah-bayaran"><option>Pindahan Bank</option><option>FPX</option><option>DuitNow</option><option>Cek</option><option>Tunai</option></datalist></div>
        <div><label>Rujukan</label><input class="field" wire:model="b.ruj" placeholder="cth no. transaksi"></div>
        @if($pengurusan)
          <div><label>Status</label><select class="field" wire:model="b.status"><option>Diterima</option><option>Menunggu</option></select></div>
        @else
          <div class="full"><div class="note" style="margin:14px 0 0">Bayaran direkod sebagai <b>Menunggu</b> dan hanya dikira dalam baki selepas pengurus mengesahkannya.</div></div>
        @endif
      </div>
      <div class="acts"><button type="button" class="btn" wire:click="$set('borangBuka', false)">Batal</button><button class="btn p">Simpan bayaran</button></div>
    </form>
  @endif

  <div class="tiles" style="grid-template-columns:repeat(3,minmax(0,1fr))">
    <button type="button" @class(['tile', 'hero' => $status === 'Diterima' && $jenis === '']) wire:click="$set('status', 'Diterima'); $set('jenis', '')">
      <div class="h"><span>Diterima</span><div class="arw"><svg viewBox="0 0 24 24"><path d="M7 17L17 7M9 7h8v8"/></svg></div></div>
      <div @class(['v', 'long' => mb_strlen($rm($diterimaSen)) > 11])>{{ $rm($diterimaSen) }}</div><div class="f">{{ $diterimaN }} bayaran</div>
    </button>
    <button type="button" @class(['tile', 'hero' => $jenis === 'deposit']) wire:click="$set('jenis', 'deposit'); $set('status', 'Diterima')">
      <div class="h"><span>Deposit</span><div class="arw"><svg viewBox="0 0 24 24"><path d="M7 17L17 7M9 7h8v8"/></svg></div></div>
      <div @class(['v', 'long' => mb_strlen($rm($depositSen)) > 11])>{{ $rm($depositSen) }}</div><div class="f">{{ $depositN }} deposit diterima</div>
    </button>
    <button type="button" @class(['tile', 'hero' => $status === 'Menunggu']) wire:click="$set('status', 'Menunggu'); $set('jenis', '')">
      <div class="h"><span>Menunggu pengesahan</span><div class="arw"><svg viewBox="0 0 24 24"><path d="M7 17L17 7M9 7h8v8"/></svg></div></div>
      <div @class(['v', 'long' => mb_strlen($rm($menungguSen)) > 11])>{{ $rm($menungguSen) }}</div><div class="f">{{ $menungguN }} bayaran · tidak dikira dalam baki</div>
    </button>
  </div>

  <div class="tabs" style="align-items:center">
    <select class="field" style="max-width:340px" wire:model.live="projek">
      <option value="">Semua projek</option>
      @foreach($projekSemua as $p)<option value="{{ $p->id }}">{{ $p->kod }} — {{ $p->nama }}</option>@endforeach
    </select>
    <select class="field" style="max-width:190px" wire:model.live="status">
      <option value="">Semua status</option><option>Diterima</option><option>Menunggu</option>
    </select>
    @if($jenis === 'deposit')<button class="tapis" wire:click="$set('jenis', '')">Deposit sahaja <b>×</b></button>@endif
    @if($projek !== '' || $status !== '' || $jenis !== '')<button class="btn sm" wire:click="kosongkan">Kosongkan tapisan</button>@endif
  </div>

  <div class="tbl"><table>
    <thead><tr><th>Tarikh</th><th>Projek</th><th>Jenis</th><th>Kaedah · Rujukan</th><th>Status</th><th class="num">Amaun</th><th></th></tr></thead>
    <tbody>
    @forelse($senarai as $b)
      <tr wire:key="by-{{ $b->id }}">
        <td style="white-space:nowrap">{{ $b->tarikh?->format('d/m/Y') ?? '—' }}</td>
        <td><a href="{{ route('projek.butiran', $b->projek_id) }}" wire:navigate style="color:inherit"><b>{{ $b->projek?->kod }}</b></a><br><small style="color:var(--muted)">{{ $b->projek?->nama }}</small></td>
        <td>{{ $b->jenis }}</td>
        <td>{{ $b->kaedah ?: '—' }}@if($b->ruj)<br><small style="color:var(--muted)">{{ $b->ruj }}</small>@endif</td>
        <td><span class="tag t-{{ \Illuminate\Support\Str::slug($b->status) }}"><i></i>{{ $b->status }}</span></td>
        <td class="num">{{ $rm(\App\Support\Kiraan::sen($b->amaun)) }}</td>
        <td><div class="rowact">
          @can('sahkan', $b)<button class="btn sm biru" wire:click="sahkan({{ $b->id }})">Sahkan</button>@endcan
          @can('delete', $b)<button class="mini" title="Padam" wire:click="padam({{ $b->id }})" wire:confirm="Padam bayaran ini?"><svg viewBox="0 0 24 24"><path d="M5 7h14M10 11v6M14 11v6M6 7l1 12a2 2 0 002 2h6a2 2 0 002-2l1-12M9 7V4h6v3"/></svg></button>@endcan
        </div></td>
      </tr>
    @empty
      <tr><td colspan="7" class="empty">Tiada bayaran{{ $projek !== '' || $status !== '' || $jenis !== '' ? ' untuk tapisan ini' : '' }}.</td></tr>
    @endforelse
    </tbody>
    @if($senarai->total())
      <tfoot><tr style="background:var(--hover);font-weight:700">
        <td colspan="5" style="padding:13px 16px">JUMLAH · {{ $senarai->total() }} bayaran</td>
        <td class="num">{{ $rm($jumlahSenarai) }}</td><td></td>
      </tr></tfoot>
    @endif
  </table></div>
  <div style="margin-top:14px">{{ $senarai->links() }}</div>
</div>
