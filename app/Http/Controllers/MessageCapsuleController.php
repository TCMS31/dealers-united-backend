<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexMessageCapsuleRequest;
use App\Http\Requests\StoreMessageCapsuleRequest;
use App\Http\Resources\MessageCapsuleResource;
use App\Models\MessageCapsule;
use App\Models\User;
use App\Services\MessageCapsuleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * HTTP entry point for capsules. Authorisation lives in
 * {@see \App\Policies\MessageCapsulePolicy}, validation in the form requests,
 * and every rule about capsules themselves in
 * {@see MessageCapsuleService}.
 */
class MessageCapsuleController extends Controller
{
    public function __construct(private readonly MessageCapsuleService $capsules)
    {
    }

    /**
     * Returns the whole collection by default. Passing `?per_page=` (or
     * `?page=`) opts into a paginated response with `links` and `meta`.
     */
    public function index(IndexMessageCapsuleRequest $request, User $user): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [MessageCapsule::class, $user]);

        return MessageCapsuleResource::collection(
            $this->capsules->listFor($user, $request->perPage())
        );
    }

    public function show(User $user, MessageCapsule $messageCapsule): MessageCapsuleResource
    {
        $this->authorize('view', $messageCapsule);

        return new MessageCapsuleResource($messageCapsule);
    }

    public function store(StoreMessageCapsuleRequest $request, User $user): JsonResponse
    {
        $this->authorize('create', [MessageCapsule::class, $user]);

        $capsule = $this->capsules->create($user, $request->validated());

        return (new MessageCapsuleResource($capsule))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function open(User $user, MessageCapsule $messageCapsule): MessageCapsuleResource
    {
        $this->authorize('open', $messageCapsule);

        return new MessageCapsuleResource(
            $this->capsules->open($messageCapsule)
        );
    }
}
