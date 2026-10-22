<?php

namespace App\Mail;

use App\Support\HtmlToText;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** One mailable for all seven branded templates (resources/views/emails). */
class BrandedMail extends Mailable
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, string>  $attachmentsData  filename => binary
     */
    public function __construct(
        public string $subjectLine,
        public string $template,
        public array $data = [],
        public array $attachmentsData = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            replyTo: [new Address(config('event.email.reply_to'), config('mail.from.name'))],
            subject: $this->subjectLine,
        );
    }

    public function content(): Content
    {
        $with = $this->data + ['subjectLine' => $this->subjectLine, 'template' => $this->template];
        $with['plainText'] = HtmlToText::convert(view('emails.'.$this->template, $with)->render());

        return new Content(html: 'emails.'.$this->template, text: 'emails.text', with: $with);
    }

    public function attachments(): array
    {
        return collect($this->attachmentsData)
            ->map(fn (string $bytes, string $name) => Attachment::fromData(fn () => $bytes, $name)->withMime('image/png'))
            ->values()->all();
    }
}
