<?php

namespace App\Mail;

use App\Models\Syarikat;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Peringatan pembaharuan kepada pemilik: 30, 7 dan 1 hari sebelum tamat, dan pada hari tamat. */
class PeringatanLanggananMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Syarikat $syarikat, public int $baki) {}

    public function envelope(): Envelope
    {
        $bila = $this->baki > 0 ? "tamat dalam {$this->baki} hari" : 'telah tamat';

        return new Envelope(subject: "Langganan Dyno Project OS {$this->syarikat->namaPenuh()} {$bila}");
    }

    public function content(): Content
    {
        return new Content(view: 'mail.peringatan-langganan', text: 'mail.peringatan-langganan-teks');
    }
}
