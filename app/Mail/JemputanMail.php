<?php

namespace App\Mail;

use App\Models\Jemputan;
use App\Models\Syarikat;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class JemputanMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Jemputan $jemputan, public Syarikat $syarikat, public string $pautan) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Jemputan menyertai '.$this->syarikat->namaPenuh().' di Dyno Project OS');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.jemputan', text: 'mail.jemputan-teks');
    }
}
