<?php

namespace App\Mail;

use App\Enums\Enums\MailType;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class Mailed extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public User $user, private MailType $mailType, public array $data)
    {
        //
    }


    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: app()->environment(['production', 'development']) ? $this->mailType->view() : null, 
            text: app()->environment('local') ? $this->mailType->text() : null,
            markdown: app()->environment(['production', 'test', 'development']) ? $this->mailType->markdown() : null,
            with: $this->data,
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
