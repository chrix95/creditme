<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class AirtimeVendMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public $airtimeTransaction;
    public function __construct(\App\AirtimeTransaction $airtimeTransaction)
    {
        $this->airtimeTransaction = $airtimeTransaction;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('info@cardcom.ng', env('APP_NAME'))->subject('Airtime Purchase')->view('mails.airtime_notification');
    }
}
