<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class TvVendMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public $tvTransaction;
    public function __construct(\App\TVTransaction $tvTransaction)
    {
        $this->tvTransaction = $tvTransaction;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('info@cardcom.ng', env('APP_NAME'))->subject('TV Subscription')->view('mails.tv_notification');
    }
}
