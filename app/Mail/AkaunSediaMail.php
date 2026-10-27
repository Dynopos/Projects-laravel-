<?php

namespace App\Mail;

use App\Models\Syarikat;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Selepas bayaran daftar: pautan ke /daftar/selesai, yang menerbitkan jemputan pemilik. */
class AkaunSediaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Syarikat $syarikat, public string $nama, public string $pautan) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Ruang '.$this->syarikat->namaPenuh().' di Dyno Project OS sudah sedia');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.akaun-sedia', text: 'mail.akaun-sedia-teks');
    }
}
