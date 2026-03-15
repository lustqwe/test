<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\File\UploadUserFileRequest;
use App\Http\Resources\UserFileResource;
use App\Models\UserFile;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public function __construct(private readonly ActivityLogger $activityLogger)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = min(100, max(1, (int) $request->query('per_page', 15)));

        $files = $request->user()
            ->files()
            ->latest()
            ->paginate($perPage);

        return UserFileResource::collection($files);
    }

    public function store(UploadUserFileRequest $request): JsonResponse
    {
        $user = $request->user();
        $file = $request->file('file');
        $disk = $request->validated('disk', 'public');

        $path = $file->store("uploads/{$user->id}", $disk);

        $userFile = $user->files()->create([
            'original_name' => $file->getClientOriginalName(),
            'disk' => $disk,
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        $this->activityLogger->log(
            user: $user,
            action: 'file.upload',
            description: 'User uploaded a file.',
            metadata: [
                'file_id' => $userFile->id,
                'original_name' => $userFile->original_name,
                'size' => $userFile->size,
            ],
            subject: $userFile,
            request: $request,
        );

        return response()->json([
            'message' => 'File uploaded successfully.',
            'data' => new UserFileResource($userFile),
        ], 201);
    }

    public function destroy(Request $request, UserFile $userFile): JsonResponse
    {
        $user = $request->user();

        $userFile = $user->files()->whereKey($userFile->getKey())->firstOrFail();

        Storage::disk($userFile->disk)->delete($userFile->path);

        $this->activityLogger->log(
            user: $user,
            action: 'file.delete',
            description: 'User deleted a file.',
            metadata: [
                'file_id' => $userFile->id,
                'original_name' => $userFile->original_name,
            ],
            subject: $userFile,
            request: $request,
        );

        $userFile->delete();

        return response()->json([
            'message' => 'File deleted successfully.',
        ]);
    }
}
