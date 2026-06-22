<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ErrorReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function build(): self
    {
        return $this->subject('[Restaurante] Reporte de problema: ' . $this->payload['subject'])
            ->view('emails.error_report');
    }
}
