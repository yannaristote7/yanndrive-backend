<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class ShareLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $url;
    public Carbon $expiresAt;

    public function __construct(string $url, Carbon $expiresAt)
    {
        $this->url       = $url;
        $this->expiresAt = $expiresAt;
    }

    public function build()
    {
        return $this->subject('Un document a été partagé avec vous')
                    ->view('emails.share_link')
                    ->with([
                        'url'       => $this->url,
                        'expiresAt' => $this->expiresAt,
                    ]);
    }
}