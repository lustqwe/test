<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\StoreProfileRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\ProfileResource;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(private readonly ActivityLogger $activityLogger)
    {
    }

    public function show(Request $request): JsonResponse
    {
        $profile = $request->user()->profile()->first();

        return response()->json([
            'data' => $profile ? new ProfileResource($profile) : null,
        ]);
    }

    public function store(StoreProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user->profile()->exists()) {
            return response()->json(['message' => 'Profile already exists.'], 409);
        }

        $profile = $user->profile()->create($request->validated());

        $this->activityLogger->log(
            user: $user,
            action: 'profile.create',
            description: 'User created profile.',
            subject: $profile,
            request: $request,
        );

        return response()->json([
            'message' => 'Profile created successfully.',
            'data' => new ProfileResource($profile),
        ], 201);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $profile = $user->profile()->first();

        if (! $profile) {
            return response()->json(['message' => 'Profile not found.'], 404);
        }

        $profile->update($request->validated());

        $this->activityLogger->log(
            user: $user,
            action: 'profile.update',
            description: 'User updated profile.',
            subject: $profile,
            request: $request,
        );

        return response()->json([
            'message' => 'Profile updated successfully.',
            'data' => new ProfileResource($profile->fresh()),
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = $user->profile()->first();

        if (! $profile) {
            return response()->json(['message' => 'Profile not found.'], 404);
        }

        $this->activityLogger->log(
            user: $user,
            action: 'profile.delete',
            description: 'User deleted profile.',
            subject: $profile,
            request: $request,
        );

        $profile->delete();

        return response()->json([
            'message' => 'Profile deleted successfully.',
        ]);
    }
}
