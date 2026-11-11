<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReminderResource;
use App\Models\Reminder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Reminders belong to the authenticated user (who set the reminder), not to
 * a role/module — every action is scoped to `auth()->id()`. No Policy: a
 * reminder is only ever visible to the person who created it.
 */
class ReminderController extends Controller
{
    /** GET /api/reminders — the current user's own pending reminders, newest due date first. */
    public function index(Request $request): JsonResponse
    {
        $reminders = Reminder::query()
            ->where('user_id', $request->user()->id)
            ->pending()
            ->orderBy('due_at')
            ->get();

        return $this->success(ReminderResource::collection($reminders));
    }

    public function update(Request $request, Reminder $reminder): JsonResponse
    {
        abort_if($reminder->user_id !== $request->user()->id, 404, 'Resource not found.');

        $data = $request->validate([
            'completed' => ['sometimes', 'boolean'],
            'title' => ['sometimes', 'string', 'max:255'],
            'due_at' => ['sometimes', 'date'],
        ]);

        if (array_key_exists('completed', $data)) {
            $reminder->completed_at = $data['completed'] ? now() : null;
            unset($data['completed']);
        }
        $reminder->fill($data);
        $reminder->save();

        return $this->success(new ReminderResource($reminder), 'Reminder updated successfully.');
    }

    public function destroy(Request $request, Reminder $reminder): JsonResponse
    {
        abort_if($reminder->user_id !== $request->user()->id, 404, 'Resource not found.');

        $reminder->delete();

        return $this->success(null, 'Reminder deleted successfully.');
    }
}
