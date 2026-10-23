<div>
  <div class="head"><div><h1>Log Aktiviti</h1><p>Siapa, bila, apa. Tidak boleh diubah atau dipadam — oleh sesiapa pun.</p></div></div>
  <div class="tbl"><table>
    <thead><tr><th>Masa</th><th>Pengguna</th><th>Tindakan</th><th>Butiran</th></tr></thead>
    <tbody>
    @forelse($log as $l)
      <tr wire:key="l-{{ $l->id }}">
        <td style="white-space:nowrap">{{ $l->created_at?->timezone(app(\App\Support\SyarikatSemasa::class)->wajib()->zon_waktu)->format('d/m/Y H:i') }}</td>
        <td>{{ $l->user?->name ?? 'Sistem' }}</td>
        <td><b>{{ $l->tindakan }}</b></td>
        <td>{{ $l->butiran }}</td>
      </tr>
    @empty
      <tr><td colspan="4" class="empty">Belum ada aktiviti.</td></tr>
    @endforelse
    </tbody>
  </table></div>
  <div style="margin-top:14px">{{ $log->links() }}</div>
</div>
