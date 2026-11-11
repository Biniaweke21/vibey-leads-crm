<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CloudinaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * POST /api/uploads — uploads a single file to Cloudinary and returns
 * metadata only (no DB row). The module-specific attachment endpoints
 * (leads/{lead}/attachments, etc.) take that metadata and create the real
 * Attachment record — same two-step shape the original API design planned.
 */
class UploadController extends Controller
{
    public function __construct(private readonly CloudinaryService $cloudinary) {}

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:20480'], // 20 MB
        ]);

        $result = $this->cloudinary->upload($request->file('file'));

        return $this->success([
            'name' => $result['original_filename'],
            'mime_type' => $request->file('file')->getClientMimeType(),
            'size' => $result['bytes'],
            'url' => $result['url'],
            'secure_url' => $result['secure_url'],
            'cloudinary_public_id' => $result['public_id'],
        ], 'File uploaded successfully.', 201);
    }
}
