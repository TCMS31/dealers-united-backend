<?php

namespace Tests\Unit;

use App\Models\MessageCapsule;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The unlock rule, exercised without a database.
 */
class MessageCapsuleTest extends TestCase
{
    #[Test]
    public function a_sealed_capsule_cannot_be_opened_before_its_time(): void
    {
        Carbon::setTestNow('2024-03-01T11:59:59Z');

        $capsule = new MessageCapsule([
            'scheduled_opening_time' => '2024-03-01T12:00:00Z',
            'is_opened' => false,
        ]);

        $this->assertFalse($capsule->openingTimePassed());
        $this->assertFalse($capsule->canBeOpened());
    }

    #[Test]
    public function the_boundary_instant_itself_unlocks_the_capsule(): void
    {
        Carbon::setTestNow('2024-03-01T12:00:00Z');

        $capsule = new MessageCapsule([
            'scheduled_opening_time' => '2024-03-01T12:00:00Z',
            'is_opened' => false,
        ]);

        $this->assertTrue($capsule->openingTimePassed());
        $this->assertTrue($capsule->canBeOpened());
    }

    #[Test]
    public function an_already_opened_capsule_cannot_be_opened_again(): void
    {
        Carbon::setTestNow('2024-03-02T00:00:00Z');

        $capsule = new MessageCapsule([
            'scheduled_opening_time' => '2024-03-01T12:00:00Z',
            'is_opened' => true,
        ]);

        $this->assertTrue($capsule->openingTimePassed());
        $this->assertFalse($capsule->canBeOpened());
    }

    /**
     * The regression that motivated the `datetime` cast and APP_TIMEZONE=UTC.
     *
     * The client sends an absolute instant with an offset. Whatever the
     * server's own timezone, that instant must be the one the capsule unlocks
     * at — not the same wall-clock reading in a different zone.
     */
    #[Test]
    public function an_offset_carrying_timestamp_is_understood_as_an_absolute_instant(): void
    {
        Carbon::setTestNow('2024-03-01T11:30:00Z');

        // 12:00 in Berlin is 11:00 UTC, i.e. already past.
        $berlin = new MessageCapsule([
            'scheduled_opening_time' => '2024-03-01T12:00:00+01:00',
            'is_opened' => false,
        ]);

        // 12:00 UTC is still half an hour away.
        $utc = new MessageCapsule([
            'scheduled_opening_time' => '2024-03-01T12:00:00Z',
            'is_opened' => false,
        ]);

        $this->assertTrue($berlin->canBeOpened());
        $this->assertFalse($utc->canBeOpened());
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}
