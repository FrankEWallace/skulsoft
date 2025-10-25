<?php

namespace App\Mail\Communication;

use App\Models\Communication\Communication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class Email extends Mailable
{
    use Queueable, SerializesModels;

    public $communication;

    public function __construct(Communication $communication)
    {
        $this->communication = $communication;
    }

    public function build()
    {
        return $this->subject($this->communication->subject)
            ->view('email.communication')
            ->with([
                'content' => $this->communication->content,
            ]);
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        if ($this->communication->media->count() === 0) {
            return [];
        }

        $attachments = [];
        foreach ($this->communication->media as $media) {
            if (! \Storage::exists($media->name)) {
                continue;
            }

            $attachments[] = Attachment::fromStorage($media->name)->as($media->name)->as($media->file_name)->withMime($media->getMeta('mime'));
        }

        return $attachments;
    }
}
