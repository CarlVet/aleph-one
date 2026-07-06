<?php

namespace App\Enums;

enum ParasiteStatus: string
{
    case Intact = 'intact';
    case Dissected = 'dissected';
    case Dry = 'dry';
    case Degraded = 'degraded';

    public function label(): string
    {
        return match ($this) {
            self::Intact => 'Intact',
            self::Dissected => 'Dissected',
            self::Dry => 'Dry',
            self::Degraded => 'Degraded',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }

    public static function tryFromLabel(?string $value): ?self
    {
        $value = strtolower(trim((string) $value));

        return match ($value) {
            'intact' => self::Intact,
            'dissected' => self::Dissected,
            'dry' => self::Dry,
            'degraded' => self::Degraded,
            default => null,
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Intact => 'bg-green-100 text-green-800',
            self::Dissected => 'bg-blue-100 text-blue-800',
            self::Dry => 'bg-amber-100 text-amber-800',
            self::Degraded => 'bg-red-100 text-red-800',
        };
    }
}
