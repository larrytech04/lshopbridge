<?php

namespace App\Enums;

enum AgentType: string
{
    case Shipping = 'shipping_agent';
    case Sourcing = 'sourcing_agent';
    case Purchasing = 'purchasing_agent';
    case Warehouse = 'warehouse_agent';
    case Delivery = 'delivery_agent';
    case Customs = 'customs_agent';

    public function label(): string
    {
        return match ($this) {
            self::Shipping => 'Shipping Agent',
            self::Sourcing => 'Sourcing Agent',
            self::Purchasing => 'Purchasing Agent',
            self::Warehouse => 'Warehouse Agent',
            self::Delivery => 'Delivery Agent',
            self::Customs => 'Customs Agent',
        };
    }
}
