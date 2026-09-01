<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderNotification extends Notification
{
    use Queueable;

    protected $table = null;
    protected $user = null;
    protected $message = '';

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($table, $message, $user)
    {
        $this->table = $table;
        $this->message = $message;
        $this->user = $user;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->line('The introduction to the notification.')
                    ->action('Notification Action', url('/'))
                    ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'user' => $this->user,
            'table' => $this->table,
            'message' => $this->message
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\BroadcastMessage
     */
    public function toBroadcast($notifiable)
    {
        \Log::info('toBroadcast called for OrderNotification', [
            'notifiable_id' => $notifiable->id,
            'notifiable_email' => $notifiable->email
        ]);
        
        return new \Illuminate\Notifications\Messages\BroadcastMessage([
            'user' => $this->user,
            'table' => $this->table,
            'message' => $this->message,
            'created_at' => now()->toDateTimeString()
        ]);
    }
}
