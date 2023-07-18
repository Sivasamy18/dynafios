<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class GenerateInvoice extends Mailable
{
    use Queueable, SerializesModels;

    //public $afterCommit = true;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    protected $data;

    public function __construct($details)
    {
        $this->data = $details;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // return $this->view('emails/hospitals/emailInvoice')
        //     ->subject('Test Queued Email')
        //     ->with($this->data)
        //     ->attach($this->data["file"], [
        //         'as' => 'invoice.pdf',
        //         'mime' => 'application/pdf',
        //     ]);

        return $this->subject('DYNAFIOS: Invoice report with payment for ' . $this->data['month'] . ' ' . $this->data['year'])
            ->attach($this->data["file"], [
                'as' => 'invoice.pdf',
                'mime' => 'application/pdf',
            ])
            ->view('emails.hospitals.emailInvoice', $this->data);
    }
}
