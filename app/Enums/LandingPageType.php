<?php

namespace App\Enums;

enum LandingPageType: string
{
    case ServiceLocation = 'service_location';
    case ServiceIndustry = 'service_industry';
    case ServiceLocationIndustry = 'service_location_industry';

    public function label(): string
    {
        return match ($this) {
            self::ServiceLocation => 'Service + Location',
            self::ServiceIndustry => 'Service + Industry',
            self::ServiceLocationIndustry => 'Service + Location + Industry',
        };
    }

    public function needsLocation(): bool
    {
        return $this !== self::ServiceIndustry;
    }

    public function needsIndustry(): bool
    {
        return $this !== self::ServiceLocation;
    }

    public static function options(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn (self $c) => $c->label(), self::cases())
        );
    }
}
