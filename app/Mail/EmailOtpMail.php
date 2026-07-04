<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public string $otp,
        public User $user
    ) {}

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Kode OTP Login - ProTrack')
                    ->view('emails.otp');
    }
}
