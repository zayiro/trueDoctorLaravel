<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Availability\AvailabilityCalculator;
use App\Services\Availability\SlotGenerator;
use Illuminate\Support\ServiceProvider;

class AvailabilityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // El calculador recibe sus valores por defecto desde config/booking.php.
        $this->app->singleton(AvailabilityCalculator::class, fn () => new AvailabilityCalculator(
            new SlotGenerator(),
            [
                'min_notice_hours' => (int) config('booking.default_min_notice_hours', 2),
                'max_advance_days' => (int) config('booking.default_max_advance_days', 30),
                'buffer_minutes' => (int) config('booking.default_buffer_minutes', 0),
                'fallback_duration' => (int) config('booking.fallback_duration_minutes', 20),
            ],
        ));
    }
}