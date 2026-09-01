<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ShopNotification extends Notification
{
    use Queueable;

    protected $table = null;
    protected $user = null;
    protected $message = '';

    protected $logsArr = [];

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($table, $message, $user,$logsArr = null)
    {
        $this->table = $table;
        $this->message = $message;
        $this->user = $user;
        $this->logsArr = $logsArr;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        //return ['database', 'broadcast'];
        return ['database'];
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
            'logsArr' => ($this->logsArr),
            'message' => $this->message,
        ];
    }
}
