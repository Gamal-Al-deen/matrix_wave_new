<?php

namespace App\Enums;

enum SocialPlatform: string
{
    case Facebook = 'facebook';
    case Instagram = 'instagram';
    case X = 'x';
    case YouTube = 'youtube';
    case TikTok = 'tiktok';
    case LinkedIn = 'linkedin';
    case Telegram = 'telegram';
    case Snapchat = 'snapchat';
    case WhatsApp = 'whatsapp';

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
            self::Facebook => 'فيسبوك',
            self::Instagram => 'إنستغرام',
            self::X => 'X',
            self::YouTube => 'يوتيوب',
            self::TikTok => 'تيك توك',
            self::LinkedIn => 'لينكدإن',
            self::Telegram => 'تيليجرام',
            self::Snapchat => 'سناب شات',
            self::WhatsApp => 'واتساب',
        };
    }

    public function iconKey(): string
    {
        return $this->value;
    }
}
