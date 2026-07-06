<?php

namespace App\Enums;

enum ExperimentPurpose: string
{
    case Screening = 'screening';
    case Confirmation = 'confirmation';

    public function label(): string
    {
        return match ($this) {
            self::Screening => 'Screening',
            self::Confirmation => 'Confirmation',
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
            'screening' => self::Screening,
            'confirmation' => self::Confirmation,
            default => null,
        };
    }
}
