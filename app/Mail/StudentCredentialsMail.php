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
            ->view('emails.student_credentials')
            ->attach(public_path('unilia.ac.mw.pem'), [
                'as'   => 'unilia.ac.mw.pem',
                'mime' => 'application/x-pem-file',
            ]);
    }
}