<?php

// =============================================================================
// File: app/Mail/TemplateEmail.php
// =============================================================================

namespace App\Mail;

use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class TemplateEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $templateSlug;
    public $templateData;
    public $template;

    /**
     * Create a new message instance.
     */
    public function __construct(string $templateSlug, array $templateData = [])
    {
        $this->templateSlug = $templateSlug;
        $this->templateData = $templateData;
        $this->template = EmailTemplate::getBySlug($templateSlug);
    }

    /**
     * Build the message.
     */
    public function build()
    {
        if (!$this->template) {
            throw new \Exception("Email template '{$this->templateSlug}' not found or is inactive");
        }

        $rendered = $this->template->render($this->templateData);

        return $this->subject($rendered['subject'])
            ->text('emails.template-plain')
            ->view('emails.template')
            ->with([
                'subject' => $rendered['subject'],
                'body' => $rendered['body'],
                'data' => $this->templateData
            ]);
    }
}