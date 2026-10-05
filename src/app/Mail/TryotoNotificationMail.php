<?php

namespace Siberfx\LaravelTryoto\app\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Siberfx\LaravelTryoto\app\Notifications\TryotoMessage;

/**
 * Email rendering of a TryotoMessage, used by the mail notification channel.
 */
class TryotoNotificationMail extends Mailable
{
    public function __construct(
        public TryotoMessage $notification,
        public string $subjectPrefix = '',
        public ?string $fromAddress = null,
        public ?string $fromName = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: $this->fromAddress ? new Address($this->fromAddress, $this->fromName) : null,
            subject: trim($this->subjectPrefix . ' ' . $this->notification->title),
        );
    }

    public function content(): Content
    {
        return new Content(htmlString: $this->renderHtml());
    }

    public function renderHtml(): string
    {
        $rows = '';
        foreach ($this->notification->fields as $label => $value) {
            $rows .= '<tr>'
                . '<td style="padding:6px 12px 6px 0;color:#555;font-weight:600;vertical-align:top;white-space:nowrap">' . e($label) . '</td>'
                . '<td style="padding:6px 0;color:#111">' . e($value) . '</td>'
                . '</tr>';
        }

        $link = $this->notification->url === null ? '' : '<p style="margin:20px 0 0">'
            . '<a href="' . e($this->notification->url) . '" style="display:inline-block;padding:10px 16px;background:#ef5b25;color:#fff;text-decoration:none;border-radius:4px">'
            . e($this->notification->urlLabel) . '</a></p>';

        return '<!doctype html><html lang="' . e($this->notification->locale ?? 'en') . '"><body style="margin:0;padding:24px;background:#f5f5f5;font-family:Arial,Helvetica,sans-serif;font-size:14px">'
            . '<div style="max-width:560px;margin:0 auto;background:#fff;border-radius:6px;padding:24px">'
            . '<h2 style="margin:0 0 16px;font-size:18px;color:#111">' . e($this->notification->title) . '</h2>'
            . ($rows === '' ? '' : '<table style="border-collapse:collapse">' . $rows . '</table>')
            . $link
            . '</div></body></html>';
    }
}
