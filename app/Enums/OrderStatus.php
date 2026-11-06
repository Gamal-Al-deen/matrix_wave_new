<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Preparing = 'preparing';
    case OutForDelivery = 'out_for_delivery';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function isFinal(): bool
    {
        return $this === self::Completed || $this === self::Cancelled;
    }

    /**
     * Allowed operational transitions.
     *
     * Orders move forward through fulfillment, and any non-final order may be
     * cancelled by an authorized administrator. Completed and cancelled
     * orders are terminal historical records.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Confirmed, self::Cancelled],
            self::Confirmed => [self::Preparing, self::Cancelled],
            self::Preparing => [self::OutForDelivery, self::Cancelled],
            self::OutForDelivery => [self::Completed, self::Cancelled],
            self::Completed, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'قيد الانتظار',
            self::Confirmed => 'مؤكد',
            self::Preparing => 'قيد التجهيز',
            self::OutForDelivery => 'قيد التوصيل',
            self::Completed => 'مكتمل',
            self::Cancelled => 'ملغي',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pending => 'bg-warning/10 text-warning',
            self::Confirmed => 'bg-info/10 text-info',
            self::Preparing => 'bg-brand-teal/15 text-brand-ink',
            self::OutForDelivery => 'bg-brand-orange/15 text-brand-ink',
            self::Completed => 'bg-success/10 text-success',
            self::Cancelled => 'bg-danger/10 text-danger',
        };
    }
}
