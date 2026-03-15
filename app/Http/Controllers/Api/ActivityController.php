<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserActivityResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = min(100, max(1, (int) $request->query('per_page', 15)));

        $activities = $request->user()
            ->activities()
            ->latest()
            ->paginate($perPage);

        return UserActivityResource::collection($activities);
    }
}
