<?php

namespace App\Models;

use App\Casts\UtcDateTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A note sealed by its author until a scheduled instant passes.
 *
 * @property int $id
 * @property int $user_id
 * @property string $note
 * @property Carbon $scheduled_opening_time
 * @property bool $is_opened
 */
class MessageCapsule extends Model
{
    use HasFactory;

    /** @var array<int, string> */
    protected $fillable = [
        'note',
        'scheduled_opening_time',
        'is_opened',
        'user_id',
    ];

    /**
     * `scheduled_opening_time` is cast so that it is stored in UTC and
     * serialised as an ISO-8601 instant. Without a cast the column round
     * trips as a naive string and every consumer has to guess a timezone;
     * see {@see UtcDateTime} for why the framework's `datetime` cast is not
     * sufficient for offset-carrying input.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'scheduled_opening_time' => UtcDateTime::class,
        'is_opened' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Newest opening time first, so a paginated page is deterministic.
     */
    public function scopeSealedOrder(Builder $query): Builder
    {
        return $query->orderBy('scheduled_opening_time')->orderBy('id');
    }

    /**
     * True once the capsule may be revealed: it is still sealed and its
     * scheduled instant has passed.
     */
    public function canBeOpened(?Carbon $now = null): bool
    {
        return ! $this->is_opened && $this->openingTimePassed($now);
    }

    /**
     * Boundary is inclusive: a capsule scheduled for exactly "now" is open.
     */
    public function openingTimePassed(?Carbon $now = null): bool
    {
        return ($now ?? Carbon::now())->gte($this->scheduled_opening_time);
    }
}
