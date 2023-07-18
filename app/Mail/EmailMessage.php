<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

// use App\Mail\log;
use Log;
use App\customClasses\EmailSetup;
use Lang;

class EmailMessage extends Mailable
{
    use Queueable, SerializesModels;

    protected $details = null;
    protected $email_setup = [];

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($details)
    {
        $this->details = $details;
        $this->build();

    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $email_setup_obj = new EmailSetup();
        $type = $this->details['type'];

        $this->email_setup = $email_setup_obj->getEmailTypeData($type);

        if (array_key_exists('subject_param', $this->details) && count($this->details['subject_param']) > 0) {
            $this->details['subject'] = Lang::get('emailer.' . $type, $this->details['subject_param']);
        } else if (empty($this->details['subject'])) {
            $this->details['subject'] = Lang::get('emailer.' . $type);
        }

        $this->details['view'] = $this->email_setup['view'];

        $results = $this->from(env('mail_username'), env('mail_username'))
            ->subject($this->details['subject']);

        if (array_key_exists('attachment', $this->details)) {
            if (count($this->details['attachment']) > 0) {
                foreach ($this->details['attachment'] as $attachment) {
                    $results = $results->attach($attachment['file'], ['as' => $attachment['file_name']]);
                }
            }
        }
        $results = $results->view($this->details['view'])
            ->with(
                $this->details['with']
            );

        return $results;
    }
}
