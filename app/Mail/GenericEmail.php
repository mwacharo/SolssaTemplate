<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;

class GenericEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $subjectText;
    public $bodyText;

    public function __construct($subjectText, $bodyText)
    {
        $this->subjectText = $subjectText;
        $this->bodyText = $bodyText;
    }


    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Generic Email',
        );
    }

    /**
     * Get the message content definition.
     */
    // public function content(): Content
    // {
    //     return new Content(
    //         view: 'view.name',
    //     );
    // }


    public function build()
    {
        return $this->subject($this->subjectText)
                    ->view('emails.generic')
                    ->with([
                        'bodyText' => $this->bodyText,
                    ]);
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


    public function sendEmail(Request $request)
{
    $to = $request->input('to');
    $subject = $request->input('subject');
    $body = $request->input('body');

    Mail::to($to)->send(new GenericEmail($subject, $body));

    return response()->json(['message' => 'Email sent successfully']);
}
}
