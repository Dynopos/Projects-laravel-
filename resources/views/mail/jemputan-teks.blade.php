Anda dijemput menyertai {{ $syarikat->namaPenuh() }} di Dyno Project OS sebagai {{ $jemputan->peranan->label() }}.

Terima jemputan dan tetapkan kata laluan:
{{ $pautan }}

Pautan ini sah selama {{ \App\Models\Jemputan::TEMPOH_HARI }} hari dan hanya untuk {{ $jemputan->email }}.
Jika anda tidak menjangka e-mel ini, abaikan sahaja.
