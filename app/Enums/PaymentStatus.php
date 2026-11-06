<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Unpaid = 'unpaid';
    case AwaitingVerification = 'awaiting_verification';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Refunded = 'refunded';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'غير مدفوع',
            self::AwaitingVerification => 'بانتظار التحقق',
            self::PartiallyPaid => 'مدفوع جزئيًا',
            self::Paid => 'مدفوع',
            self::Refunded => 'مسترد',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Unpaid => 'bg-warning/10 text-warning',
            self::AwaitingVerification => 'bg-info/10 text-info',
            self::PartiallyPaid => 'bg-brand-orange/15 text-brand-ink',
            self::Paid => 'bg-success/10 text-success',
            self::Refunded => 'bg-slate-100 text-slate-600',
        };
    }
}
