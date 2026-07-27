<?php

namespace App\Enums;

enum WalletIdentifierType: string
{
    case Email = 'email';
    case Phone = 'phone';
    case WalletId = 'wallet_id';
    case QrCode = 'qr_code';
    case CardNumber = 'card_number';
    case Username = 'username';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Email => 'Email',
            self::Phone => 'Phone number',
            self::WalletId => 'Wallet ID',
            self::QrCode => 'QR code',
            self::CardNumber => 'Card number',
            self::Username => 'Username',
            self::Custom => 'Custom',
        };
    }
}
