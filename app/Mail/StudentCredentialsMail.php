<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class StudentCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public $student;

    public function __construct($student)
    {
        $this->student = $student;
    }

    public function build()
    {
        return $this->subject('Eduroam Login Credentials')
        ->attach(storage_path('app/public/Eduroam user guide-Laws Campus.pdf'))
            ->view('emails.student_credentials');
    }
}
