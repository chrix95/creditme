<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class DataVendMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public $dataTransaction;
    public function __construct(\App\DataTransaction $dataTransaction)
    {
        $this->dataTransaction = $dataTransaction;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('info@cardcom.ng', env('APP_NAME'))->subject('Data Purchase')->view('mails.data_notification');
    }
}
