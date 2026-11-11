<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

/**
 * Reusable Cloudinary upload service built on Laravel's HTTP client and
 * Cloudinary's signed REST API — no extra SDK package required.
 *
 * ponytail: signed REST upload is ~40 lines vs. pulling in the full SDK; swap
 * for cloudinary/cloudinary_php only if we need transformations/eager pipelines.
 *
 * Not wired into any module yet (per Phase 1 scope).
 */
class CloudinaryService
{
    private string $cloudName;

    private string $apiKey;

    private string $apiSecret;

    private string $folder;

    public function __construct()
    {
        $this->cloudName = (string) config('services.cloudinary.cloud_name');
        $this->apiKey = (string) config('services.cloudinary.api_key');
        $this->apiSecret = (string) config('services.cloudinary.api_secret');
        $this->folder = (string) config('services.cloudinary.upload_folder', 'vibey-leads');
    }

    /**
     * Upload a file and return normalized metadata.
     *
     * @return array{public_id:string,url:string,secure_url:string,bytes:int,format:string,resource_type:string,original_filename:string}
     *
     * @throws RequestException
     */
    public function upload(UploadedFile $file, ?string $folder = null): array
    {
        $params = [
            'folder' => $folder ?? $this->folder,
            'timestamp' => time(),
        ];
        $params['signature'] = $this->sign($params);
        $params['api_key'] = $this->apiKey;

        $response = Http::attach(
            'file',
            file_get_contents($file->getRealPath()),
            $file->getClientOriginalName(),
        )->post("https://api.cloudinary.com/v1_1/{$this->cloudName}/auto/upload", $params)
            ->throw()
            ->json();

        return [
            'public_id' => $response['public_id'],
            'url' => $response['url'],
            'secure_url' => $response['secure_url'],
            'bytes' => (int) $response['bytes'],
            'format' => $response['format'] ?? '',
            'resource_type' => $response['resource_type'],
            'original_filename' => $response['original_filename'] ?? $file->getClientOriginalName(),
        ];
    }

    /**
     * Delete an asset by its public id.
     *
     * @throws RequestException
     */
    public function destroy(string $publicId, string $resourceType = 'image'): bool
    {
        $params = [
            'public_id' => $publicId,
            'timestamp' => time(),
        ];
        $params['signature'] = $this->sign($params);
        $params['api_key'] = $this->apiKey;

        $response = Http::asForm()
            ->post("https://api.cloudinary.com/v1_1/{$this->cloudName}/{$resourceType}/destroy", $params)
            ->throw()
            ->json();

        return ($response['result'] ?? null) === 'ok';
    }

    /**
     * Cloudinary signature: SHA-1 of alphabetically-sorted params + api_secret.
     */
    private function sign(array $params): string
    {
        ksort($params);

        $toSign = collect($params)
            ->map(fn ($value, $key) => "{$key}={$value}")
            ->implode('&');

        return sha1($toSign.$this->apiSecret);
    }
}
