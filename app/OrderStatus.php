<?php

namespace App;

enum OrderStatus: string
{
    case NewOrder = 'new';
    case Paid = 'paid';
    case Cancelled = 'cancelled';

    public function canTransitionTo(self $nextStatus): bool
    {
        return match (true) {
            ($this === self::NewOrder) && ($nextStatus === self::Paid) => true,
            ($this === self::NewOrder) && ($nextStatus === self::Cancelled) => true,
            default => false,
        };
    }
}
