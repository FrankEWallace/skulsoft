<?php

namespace App\Mail;

use App\Models\Config\Template;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomMail extends Mailable
{
    use Queueable, SerializesModels;

    public $mailTemplate;

    public $mailAttachments;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Template $mailTemplate, array $mailAttachments = [])
    {
        $this->mailTemplate = $mailTemplate;
        $this->mailAttachments = $mailAttachments;
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            subject: $this->mailTemplate->subject,
        );
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {
        return new Content(
            view: 'email.index',
            with: [
                'body' => $this->mailTemplate->content,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        if (empty($this->mailAttachments)) {
            return [];
        }

        $attachments = [];
        foreach ($this->mailAttachments as $index => $attachment) {
            // $attachments[] = Attachment::fromPath(storage_path($attachment))->withMime('application/pdf');
            $name = is_int($index) ? $attachment : $index;
            $attachments[] = Attachment::fromStorage($attachment)->as($name)->withMime('application/pdf');
        }

        return $attachments;
    }
}
