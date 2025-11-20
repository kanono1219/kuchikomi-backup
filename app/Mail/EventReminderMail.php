<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Event;
use App\Models\User;

class EventReminderMail extends Mailable
{
    use Queueable, SerializesModels;
    
    public $event;
    public $user;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Event $event, User $user)
    {
        $subject = sprintf(
            "【開催%d日前】%s（%s）",
            $this->user->notification_days_before,
            $this->event->name,
            $this->event->category->name
        );

        return $this->view('emails.event-reminder')
                    ->subject($subject);
    }
    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function build()
    {
        return $this->view('emails.event-reminder')
                    ->subject("【開催{$this->user->notification_days_before}日前】{$this->event->name}のお知らせ");
    } 
    
    public function envelope()
    {
        return new Envelope(
            subject: 'Event Reminder Mail',
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
            view: 'view.name',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [];
    }
}
