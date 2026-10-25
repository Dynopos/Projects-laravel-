@php($sel = fn ($v, $j) => \App\Http\Controllers\EksportController::selPdf($v, $j))
<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="utf-8">
<title>{{ $l['tajuk'] }} · {{ $syarikat->namaPenuh() }}</title>
<style>
  /* DomPDF: CSS ringkas sahaja. Cetakan putih — tiada latar gelap. */
  @page { margin: 26px 28px 40px; }
  body { font-family: "DejaVu Sans", sans-serif; font-size: 9px; color: #14130f; }
  .kepala { border-bottom: 2px solid #9a7213; padding-bottom: 8px; margin-bottom: 10px; }
  .syarikat { font-family: "DejaVu Serif", serif; font-size: 15px; font-weight: bold; letter-spacing: .04em; }
  .syarikat span { color: #9a7213; }
  .jabatan { font-size: 8px; letter-spacing: .14em; color: #5c584c; text-transform: uppercase; }
  h1 { font-family: "DejaVu Serif", serif; font-size: 17px; margin: 10px 0 2px; }
  .meta { color: #5c584c; font-size: 8.5px; }
  table { width: 100%; border-collapse: collapse; margin-top: 10px; }
  th { background: #f6f0dd; color: #7d5c0e; text-align: left; font-size: 8px; text-transform: uppercase; letter-spacing: .06em; padding: 6px 5px; border-bottom: 1px solid #c39a2c; }
  td { padding: 5px; border-bottom: 1px solid #e9e6de; vertical-align: top; }
  tr:nth-child(even) td { background: #faf8f3; }
  .num { text-align: right; white-space: nowrap; }
  tfoot td { font-weight: bold; background: #f6f0dd !important; border-top: 1px solid #c39a2c; }
  .kaki { position: fixed; bottom: -26px; left: 0; right: 0; font-size: 7.5px; color: #8d8878; }
  .kosong { padding: 30px; text-align: center; color: #8d8878; }
</style>
</head>
<body>
  <div class="kaki">Dyno Project OS · Dijana {{ $masa->format('d/m/Y H:i') }} oleh {{ $oleh->name }} · Sulit — untuk kegunaan dalaman {{ $syarikat->namaPenuh() }}</div>

  <div class="kepala">
    <div class="syarikat">{{ $syarikat->nama }}<span>{{ $syarikat->sufiks }}</span></div>
    <div class="jabatan">{{ $syarikat->jabatan }}</div>
  </div>

  <h1>{{ $l['tajuk'] }}</h1>
  <div class="meta">{{ $l['tapisan'] }} · {{ count($l['baris']) }} rekod · {{ $masa->format('d/m/Y H:i') }}</div>

  @if($l['baris'])
    <table>
      <thead><tr>@foreach($l['lajur'] as $c)<th @class(['num' => in_array($c['jenis'], ['wang', 'peratus'])])>{{ $c['label'] }}</th>@endforeach</tr></thead>
      <tbody>
        @foreach($l['baris'] as $r)
          <tr>@foreach($r as $i => $v)<td @class(['num' => in_array($l['lajur'][$i]['jenis'], ['wang', 'peratus'])])>{{ $sel($v, $l['lajur'][$i]['jenis']) }}</td>@endforeach</tr>
        @endforeach
      </tbody>
      @if($l['jumlah'])
        <tfoot><tr>@foreach($l['jumlah'] as $i => $v)<td @class(['num' => in_array($l['lajur'][$i]['jenis'], ['wang', 'peratus'])])>{{ $sel($v, $l['lajur'][$i]['jenis']) }}</td>@endforeach</tr></tfoot>
      @endif
    </table>
  @else
    <div class="kosong">Tiada rekod untuk tapisan ini.</div>
  @endif
</body>
</html>
