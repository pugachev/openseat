<?php

namespace App\Mail;

use App\Models\Pin;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PinNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Pin $pin,
        public readonly string $pinUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '📍 ピンが投稿されました',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.pin_notification',
        );
    }
}
