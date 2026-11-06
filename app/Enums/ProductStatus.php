<?php

namespace App\Enums;

enum ProductStatus: string
{
    case Available = 'available';
    case OutOfStock = 'out_of_stock';
    case Inactive = 'inactive';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function isOrderable(): bool
    {
        return $this === self::Available;
    }

    public function label(): string
    {
        return match ($this) {
            self::Available => 'متاح',
            self::OutOfStock => 'غير متوفر',
            self::Inactive => 'غير مفعّل',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Available => 'bg-success/10 text-success',
            self::OutOfStock => 'bg-warning/10 text-warning',
            self::Inactive => 'bg-slate-100 text-slate-600',
        };
    }
}
