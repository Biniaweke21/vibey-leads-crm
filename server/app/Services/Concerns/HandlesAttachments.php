<?php

namespace App\Services\Concerns;

use App\Models\Attachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Shared by every Service whose model uses HasCrmRelations — the
 * add/delete logic is identical everywhere (unlike notes, where the
 * activity-log wording differs per module), so a trait avoids four
 * byte-identical copies.
 */
trait HandlesAttachments
{
    /**
     * @param  Model&HasAttributes  $model  Must use HasCrmRelations.
     * @param  array{name:string,mime_type:?string,size:?int,url:?string,secure_url:?string,cloudinary_public_id:?string}  $data
     */
    public function addAttachment(Model $model, array $data): Attachment
    {
        return DB::transaction(function () use ($model, $data) {
            /** @var User $user */
            $user = auth()->user();

            $attachment = $model->attachments()->create([
                'uploaded_by' => $user->id,
                'uploaded_by_role' => $user->role->isTeam() ? 'team' : 'client',
                'name' => $data['name'],
                'mime_type' => $data['mime_type'] ?? null,
                'size' => $data['size'] ?? null,
                'url' => $data['url'] ?? null,
                'secure_url' => $data['secure_url'] ?? null,
                'cloudinary_public_id' => $data['cloudinary_public_id'] ?? null,
            ]);

            $model->activities()->create([
                'user_id' => $user->id,
                'description' => "File uploaded: {$data['name']}",
            ]);

            return $attachment;
        });
    }

    public function deleteAttachment(Model $model, Attachment $attachment): void
    {
        DB::transaction(function () use ($model, $attachment) {
            $name = $attachment->name;
            $attachment->delete();

            $model->activities()->create([
                'user_id' => auth()->id(),
                'description' => "File removed: {$name}",
            ]);
        });
    }
}
