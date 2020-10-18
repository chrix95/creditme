<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class PowerVendMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public $powerTransaction;
    public function __construct(\App\PowerTransaction $powerTransaction)
    {
        $this->powerTransaction = $powerTransaction;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('info@cardcom.ng', env('APP_NAME'))->subject('Power Purchase')->view('mails.power_notification');
    }
}
