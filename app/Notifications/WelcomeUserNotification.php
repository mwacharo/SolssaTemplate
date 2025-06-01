<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;



namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class WelcomeUserNotification extends Notification
{
    protected $plainPassword;

    public function __construct($plainPassword)
    {
        $this->plainPassword = $plainPassword;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('🎉 Welcome to Our Platform')
            ->greeting('Hello ' . $notifiable->name)
            ->line('Your account has been created successfully.')
            ->line('**Email**: ' . $notifiable->email)
            ->line('**Password**: ' . $this->plainPassword)
            ->line('Please change your password after logging in.')
            ->action('Login Now', url('/login'))
            ->line('We’re glad to have you on board!');
    }
}
