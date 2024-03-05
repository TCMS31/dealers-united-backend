<?php

namespace App\Casts;

use DateTimeInterface;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Store a timestamp as UTC, read it back as UTC.
 *
 * Eloquent's built-in `datetime` cast is not enough here. On the way in it
 * tries `Carbon::createFromFormat('Y-m-d H:i:s', $value)` first, and PHP's
 * `createFromFormat` treats the trailing offset of an ISO-8601 string as
 * *trailing data*: "2099-03-01T12:00:00+01:00" parses to 12:00 with the
 * "+01:00" silently discarded, so a Berlin client's capsule unlocks an hour
 * late. `Carbon::parse` honours the offset, which is what this cast uses.
 *
 * Reading back, the stored value is naive, so it is explicitly interpreted as
 * UTC rather than as whatever APP_TIMEZONE happens to be.
 *
 * @implements CastsAttributes<Carbon|null, mixed>
 */
class UtcDateTime implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value)->utc();
        }

        return Carbon::parse($value, 'UTC')->utc();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $date = $value instanceof DateTimeInterface
            ? Carbon::instance($value)
            : Carbon::parse($value);

        return $date->utc()->format('Y-m-d H:i:s');
    }
}
