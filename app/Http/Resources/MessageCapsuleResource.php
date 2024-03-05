<?php

namespace App\Http\Resources;

use App\Models\MessageCapsule;
use App\Support\Masking\NoteMasker;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The single representation of a capsule.
 *
 * Masking happens here, on the server, for every endpoint that returns a
 * capsule — including `store`, which previously returned the raw model and
 * leaked the full note alongside internal columns.
 *
 * @mixin \App\Models\MessageCapsule
 */
class MessageCapsuleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var MessageCapsule $capsule */
        $capsule = $this->resource;

        return [
            'id' => $capsule->id,
            'note' => $capsule->is_opened
                ? $capsule->note
                : app(NoteMasker::class)->mask($capsule->note),
            'scheduled_opening_time' => $capsule->scheduled_opening_time?->toJSON(),
            'is_opened' => $capsule->is_opened,
            'can_be_opened' => $capsule->canBeOpened(),
        ];
    }
}
