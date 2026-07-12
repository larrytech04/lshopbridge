<?php

namespace App\Enums;

enum AppType: string
{
    case Alipay = 'alipay';
    case WeChat = 'wechat';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Alipay => 'Alipay',
            self::WeChat => 'WeChat Pay',
            self::Other => 'Other China wallet',
        };
    }
}
