<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    /**
     * El token de restablecimiento de contraseña.
     *
     * @var string
     */
    public $token;

    /**
     * Crear una nueva instancia de la notificación.
     */
    public function __construct(string $token)
    {
        $this->token = $token;
    }

    /**
     * Obtener los canales de entrega de la notificación.
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Obtener la representación de correo de la notificación.
     */
    public function toMail($notifiable): MailMessage
    {
        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], true);

        return (new MailMessage)
            ->subject('Restablecer Contraseña - Transacciones Facturación')
            ->view('emails.reset-password', [
                'url' => $url,
                'name' => $notifiable->name,
            ]);
    }
}
