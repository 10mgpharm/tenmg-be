<?php

namespace App\Mail;

use App\Models\Jobs\JobApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class JobApplicationSubmitted extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public JobApplication $application,
        public ?string $resumeData = null,
        public ?string $resumeFileName = null,
        public ?string $resumeMimeType = null
    ) {}

    public function build(): self
    {
        $mail = $this->subject('New Job Application: '.$this->application->first_name.' '.$this->application->last_name)
            ->view('emails.jobs.application-submitted')
            ->with([
                'application' => $this->application,
            ]);

        // Attach resume as raw data directly from upload, no storage involved
        if ($this->resumeData && $this->resumeFileName) {
            $mail->attachData(
                data: $this->resumeData,
                name: $this->resumeFileName,
                options: [
                    'mime' => $this->resumeMimeType ?? 'application/octet-stream',
                ]
            );
        }

        return $mail;
    }
}
