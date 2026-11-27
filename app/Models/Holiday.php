<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Support\HolidayCalendar;
use Database\Factories\HolidayFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A public holiday. Leave, payroll and attendance skip it like a weekend.
 */
#[Fillable(['date', 'name'])]
class Holiday extends Model
{
    /** @use HasFactory<HolidayFactory> */
    use Auditable, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => app(HolidayCalendar::class)->flush());
        static::deleted(fn () => app(HolidayCalendar::class)->flush());
    }

    public function auditLabel(): string
    {
        return $this->name . ' · ' . $this->date?->format('j M Y');
    }
}
