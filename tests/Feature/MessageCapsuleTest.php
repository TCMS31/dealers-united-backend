<?php

namespace Tests\Feature;

use App\Models\MessageCapsule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\Fluent\AssertableJson;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class MessageCapsuleTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    private function path(?int $capsuleId = null, ?User $owner = null): string
    {
        $owner ??= $this->user;
        $base = "/api/v1/users/{$owner->id}/message-capsules";

        return $capsuleId === null ? $base : "{$base}/{$capsuleId}";
    }

    // ---------------------------------------------------------------- index

    #[Test]
    public function it_lists_the_owners_capsules_ordered_by_opening_time(): void
    {
        $late = MessageCapsule::factory()->recycle($this->user)->openingAt(now()->addDays(9))->create();
        $early = MessageCapsule::factory()->recycle($this->user)->openingAt(now()->addDays(2))->create();

        $response = $this->actingAs($this->user)->getJson($this->path());

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $early->id)
            ->assertJsonPath('data.1.id', $late->id);
    }

    #[Test]
    public function the_listing_returns_the_full_collection_by_default(): void
    {
        MessageCapsule::factory()->count(20)->recycle($this->user)->create();

        $this->actingAs($this->user)->getJson($this->path())
            ->assertOk()
            ->assertJsonCount(20, 'data')
            ->assertJsonMissingPath('meta');
    }

    #[Test]
    public function pagination_is_opt_in(): void
    {
        MessageCapsule::factory()->count(20)->recycle($this->user)->create();

        $this->actingAs($this->user)->getJson($this->path().'?per_page=5')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.total', 20)
            ->assertJsonPath('meta.per_page', 5);
    }

    #[Test]
    public function a_page_size_beyond_the_cap_is_rejected(): void
    {
        $this->actingAs($this->user)->getJson($this->path().'?per_page=5000')
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors('per_page');
    }

    // --------------------------------------------------------------- create

    #[Test]
    public function it_seals_a_new_capsule(): void
    {
        $payload = [
            'note' => 'Tomorrow you will be proud of this.',
            'scheduled_opening_time' => now()->addWeek()->toJSON(),
        ];

        $response = $this->actingAs($this->user)->postJson($this->path(), $payload);

        $response->assertCreated()
            ->assertJsonPath('data.is_opened', false)
            ->assertJsonPath('data.can_be_opened', false);

        $this->assertDatabaseHas('message_capsules', [
            'user_id' => $this->user->id,
            'note' => $payload['note'],
            'is_opened' => false,
        ]);
    }

    /**
     * The create response used to be the raw Eloquent model: the full note in
     * clear, plus user_id and the timestamps. The client had to re-mask it.
     */
    #[Test]
    public function the_create_response_is_masked_and_carries_no_internal_columns(): void
    {
        $response = $this->actingAs($this->user)->postJson($this->path(), [
            'note' => 'Tomorrow you will be proud of this.',
            'scheduled_opening_time' => now()->addWeek()->toJSON(),
        ]);

        $response->assertCreated()
            ->assertJson(fn (AssertableJson $json) => $json->has('data', fn (AssertableJson $capsule) => $capsule
                ->where('note', 'Tomo****')
                ->hasAll(['id', 'scheduled_opening_time', 'is_opened', 'can_be_opened'])
                ->missingAll(['user_id', 'created_at', 'updated_at'])
            ));
    }

    /**
     * The exact payload the Vue client sends: an ISO-8601 instant in UTC.
     * Before the `datetime` cast this string went into the datetime column
     * verbatim ("2027-03-01T11:00:00.000Z"), which MySQL rejects in strict
     * mode and which no consumer could read back reliably.
     */
    #[Test]
    public function it_stores_an_iso_8601_instant_as_a_real_timestamp(): void
    {
        $response = $this->actingAs($this->user)->postJson($this->path(), [
            'note' => 'An absolute instant, please.',
            'scheduled_opening_time' => '2099-03-01T11:00:00.000Z',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.scheduled_opening_time', '2099-03-01T11:00:00.000000Z');

        $this->assertDatabaseHas('message_capsules', [
            'scheduled_opening_time' => '2099-03-01 11:00:00',
        ]);
    }

    /**
     * An offset other than Z must land on the same absolute instant.
     */
    #[Test]
    public function an_offset_timestamp_is_normalised_to_utc(): void
    {
        $this->actingAs($this->user)->postJson($this->path(), [
            'note' => 'Berlin noon is eleven in UTC.',
            'scheduled_opening_time' => '2099-03-01T12:00:00+01:00',
        ])->assertCreated()
            ->assertJsonPath('data.scheduled_opening_time', '2099-03-01T11:00:00.000000Z');
    }

    #[Test]
    public function it_rejects_an_empty_note(): void
    {
        $this->actingAs($this->user)->postJson($this->path(), [
            'note' => '',
            'scheduled_opening_time' => now()->addWeek()->toJSON(),
        ])->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors('note');
    }

    #[Test]
    public function it_rejects_an_opening_time_in_the_past(): void
    {
        $this->actingAs($this->user)->postJson($this->path(), [
            'note' => 'Too late.',
            'scheduled_opening_time' => now()->subMinute()->toJSON(),
        ])->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors('scheduled_opening_time');
    }

    #[Test]
    public function a_capsule_cannot_be_created_already_open(): void
    {
        $this->actingAs($this->user)->postJson($this->path(), [
            'note' => 'Nice try.',
            'scheduled_opening_time' => now()->addWeek()->toJSON(),
            'is_opened' => true,
        ])->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors('is_opened');
    }

    #[Test]
    public function a_note_longer_than_the_configured_maximum_is_rejected(): void
    {
        config(['capsules.max_note_length' => 50]);

        $this->actingAs($this->user)->postJson($this->path(), [
            'note' => str_repeat('a', 51),
            'scheduled_opening_time' => now()->addWeek()->toJSON(),
        ])->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors('note');
    }

    // -------------------------------------------------------------- masking

    #[Test]
    public function a_sealed_note_is_masked_by_the_server(): void
    {
        MessageCapsule::factory()->recycle($this->user)->create([
            'note' => 'Tomorrow you will be proud of this.',
        ]);

        $this->actingAs($this->user)->getJson($this->path())
            ->assertOk()
            ->assertJsonPath('data.0.note', 'Tomo****')
            ->assertJsonMissing(['note' => 'Tomorrow you will be proud of this.']);
    }

    /**
     * Masking must not depend on the clock alone: a capsule whose time has
     * passed but which has not been opened is still masked in the listing.
     */
    #[Test]
    public function an_unlocked_but_unopened_note_is_still_masked_in_the_listing(): void
    {
        MessageCapsule::factory()->recycle($this->user)->unlocked()->create([
            'note' => 'Tomorrow you will be proud of this.',
        ]);

        $this->actingAs($this->user)->getJson($this->path())
            ->assertOk()
            ->assertJsonPath('data.0.note', 'Tomo****')
            ->assertJsonPath('data.0.can_be_opened', true);
    }

    #[Test]
    public function an_opened_note_is_returned_in_full(): void
    {
        MessageCapsule::factory()->recycle($this->user)->opened()->create([
            'note' => 'Tomorrow you will be proud of this.',
        ]);

        $this->actingAs($this->user)->getJson($this->path())
            ->assertOk()
            ->assertJsonPath('data.0.note', 'Tomorrow you will be proud of this.');
    }

    // ----------------------------------------------------------------- show

    #[Test]
    public function it_returns_a_single_capsule_once_its_time_has_passed(): void
    {
        $capsule = MessageCapsule::factory()->recycle($this->user)->opened()->create();

        $this->actingAs($this->user)->getJson($this->path($capsule->id))
            ->assertOk()
            ->assertJsonPath('data.id', $capsule->id);
    }

    #[Test]
    public function reading_a_still_sealed_capsule_is_forbidden_not_unauthenticated(): void
    {
        $capsule = MessageCapsule::factory()->recycle($this->user)->create();

        // 403, not 401: the caller is logged in, they are simply too early.
        // A 401 tells an HTTP client the session is gone and logs the user out.
        $this->actingAs($this->user)->getJson($this->path($capsule->id))
            ->assertForbidden()
            ->assertJsonStructure(['message'])
            ->assertJsonMissingPath('trace')
            ->assertJsonMissingPath('exception');
    }

    #[Test]
    public function an_unknown_capsule_is_a_404(): void
    {
        $this->actingAs($this->user)->getJson($this->path(999999))
            ->assertNotFound()
            ->assertJsonStructure(['message']);
    }

    // ----------------------------------------------------------------- open

    #[Test]
    public function it_opens_a_capsule_whose_time_has_passed_and_reveals_the_note(): void
    {
        $capsule = MessageCapsule::factory()->recycle($this->user)->unlocked()->create([
            'note' => 'Tomorrow you will be proud of this.',
        ]);

        $this->actingAs($this->user)->putJson($this->path($capsule->id).'/open')
            ->assertOk()
            ->assertJsonPath('data.is_opened', true)
            ->assertJsonPath('data.note', 'Tomorrow you will be proud of this.');

        $this->assertTrue($capsule->fresh()->is_opened);
    }

    #[Test]
    public function opening_is_idempotent(): void
    {
        $capsule = MessageCapsule::factory()->recycle($this->user)->unlocked()->create();

        $this->actingAs($this->user)->putJson($this->path($capsule->id).'/open')->assertOk();
        $this->actingAs($this->user)->putJson($this->path($capsule->id).'/open')
            ->assertOk()
            ->assertJsonPath('data.is_opened', true);
    }

    #[Test]
    public function opening_early_is_forbidden_and_leaves_the_capsule_sealed(): void
    {
        $capsule = MessageCapsule::factory()->recycle($this->user)->create();

        $this->actingAs($this->user)->putJson($this->path($capsule->id).'/open')
            ->assertForbidden();

        $this->assertFalse($capsule->fresh()->is_opened);
    }

    /**
     * One second before the scheduled instant it is still sealed; at the
     * instant itself it opens.
     */
    #[Test]
    public function the_unlock_boundary_is_inclusive(): void
    {
        Carbon::setTestNow('2024-03-01T12:00:00Z');

        $capsule = MessageCapsule::factory()->recycle($this->user)
            ->openingAt('2024-03-01T12:00:01Z')->create();

        $this->actingAs($this->user)->putJson($this->path($capsule->id).'/open')->assertForbidden();

        Carbon::setTestNow('2024-03-01T12:00:01Z');

        $this->actingAs($this->user)->putJson($this->path($capsule->id).'/open')->assertOk();

        Carbon::setTestNow();
    }

    // -------------------------------------------------------- authorisation

    #[Test]
    public function an_anonymous_request_is_rejected_with_401(): void
    {
        $this->getJson($this->path())
            ->assertUnauthorized()
            ->assertJsonStructure(['message']);
    }

    /**
     * Regression: an unauthenticated request that did not ask for JSON used to
     * return 500 "Route [login] not defined" because Fortify registers no
     * named login route when views are disabled.
     */
    #[Test]
    public function an_anonymous_non_json_request_is_a_401_not_a_500(): void
    {
        $this->get($this->path())->assertUnauthorized();
    }

    #[Test]
    public function a_user_cannot_read_another_users_collection(): void
    {
        $other = User::factory()->create();
        MessageCapsule::factory()->recycle($other)->opened()->create();

        $this->actingAs($this->user)->getJson($this->path(null, $other))
            ->assertForbidden();
    }

    #[Test]
    public function a_user_cannot_create_a_capsule_for_another_user(): void
    {
        $other = User::factory()->create();

        $this->actingAs($this->user)->postJson($this->path(null, $other), [
            'note' => 'Not mine to write.',
            'scheduled_opening_time' => now()->addWeek()->toJSON(),
        ])->assertForbidden();

        $this->assertDatabaseCount('message_capsules', 0);
    }

    #[Test]
    public function a_user_cannot_open_another_users_capsule(): void
    {
        $other = User::factory()->create();
        $capsule = MessageCapsule::factory()->recycle($other)->unlocked()->create();

        $this->actingAs($this->user)->putJson($this->path($capsule->id, $other).'/open')
            ->assertForbidden();

        $this->assertFalse($capsule->fresh()->is_opened);
    }

    /**
     * Regression: `$request->user` resolved the request *body* before the
     * route parameter, so a payload carrying a `user` key crashed the
     * ownership middleware with "Call to a member function getKey() on
     * string" — a 500 reachable with one extra JSON field.
     */
    #[Test]
    public function a_user_key_in_the_payload_does_not_crash_the_ownership_check(): void
    {
        $this->actingAs($this->user)->postJson($this->path(), [
            'user' => 'anything',
            'note' => 'Payloads are not route parameters.',
            'scheduled_opening_time' => now()->addWeek()->toJSON(),
        ])->assertCreated();
    }
}
