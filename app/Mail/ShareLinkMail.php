<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ShareLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $url;
    public $expires_at;

    /**
     * Create a new message instance.
     */
    public function __construct(string $url, $expires_at)
    {
        $this->url = $url;
        $this->expires_at = $expires_at;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Lien de partage YamsDrive')
            ->view('emails.share_link');
    }
}