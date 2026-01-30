<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class SendLogMail extends Mailable
{
    use Queueable, SerializesModels;

    public $logFile;

    public function __construct($logFile)
    {
        $this->logFile = $logFile;
    }

   public function build()
{
    
    $fullPath = \Illuminate\Support\Facades\Storage::disk('local')->path($this->logFile);

    return $this->subject('Eduroam Bulk Upload Log')
                ->view('emails.log_email')
                ->attach($fullPath);
}

}
