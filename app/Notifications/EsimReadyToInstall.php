<?php

namespace App\Notifications;

use App\Models\EsimProvisioning;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The eSIM is genuinely ready. Deliberately never embeds the QR image,
 * LPA string, activation code, or SM-DP+ address in the email body — only a
 * link into the owner-gated install page (EsimDeliveryController), per the
 * eSIM spec's QR-security rules ("do not expose... sensitive fulfilment
 * data" and "no permanent public storage URLs").
 */
class EsimReadyToInstall extends Notification
{
    public function __construct(public EsimProvisioning $provisioning) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $item = $this->provisioning->orderItem;

        return (new MailMessage)
            ->subject('Your eSIM is ready to install')
            ->greeting("Hi {$notifiable->name},")
            ->line('Your eSIM for **'.$item->name.'** is ready.')
            ->line('For your security, the QR code and activation details are only available when you\'re signed in.')
            ->action('Install my eSIM', route('esim.mine.show', $this->provisioning))
            ->line('Activation policy: '.($this->provisioning->activation_policy ? __(esim_activation_policy_label($this->provisioning->activation_policy)) : 'See your installation page for details.'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'esim.ready',
            'title' => 'Your eSIM is ready',
            'message' => 'Install it now to get connected.',
            'url' => route('esim.mine.show', $this->provisioning),
            'icon' => 'sim',
        ];
    }
}
