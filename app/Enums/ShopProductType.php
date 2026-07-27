<?php

namespace App\Enums;

enum ShopProductType: string
{
    case Physical = 'physical';
    case Digital = 'digital';
    case Esim = 'esim';
    case Data = 'data';
    case BillPayment = 'bill_payment';
    case GiftCard = 'giftcard';
    case Subscription = 'subscription';
    case Vpn = 'vpn';
    case Service = 'service';
    case Custom = 'custom';
    case Gaming = 'gaming';
    case Streaming = 'streaming';
    case Software = 'software';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Physical => 'Physical product',
            self::Digital => 'Digital product',
            self::Esim => 'eSIM',
            self::Data => 'Airtime or data bundle',
            self::BillPayment => 'Bill payment',
            self::GiftCard => 'Gift card',
            self::Subscription => 'Subscription',
            self::Vpn => 'VPN or software licence',
            self::Service => 'Service',
            self::Custom => 'Custom product',
            self::Gaming => 'Gaming',
            self::Streaming => 'Streaming',
            self::Software => 'Software',
            self::Other => 'Other',
        };
    }

    /** The 10 options shown behind the "Add Product" split-button, per the spec. */
    public static function addProductOptions(): array
    {
        return [self::Physical, self::Digital, self::Esim, self::Data, self::BillPayment, self::GiftCard, self::Subscription, self::Vpn, self::Service, self::Custom];
    }

    public function isDigitallyDelivered(): bool
    {
        return $this !== self::Physical;
    }
}
