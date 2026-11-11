<?php

namespace App\Services;

use App\Enums\DesignStatus;
use App\Enums\ProjectStatus;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\DesignSubmission;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\Concerns\HandlesAttachments;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ProjectService
{
    use HandlesAttachments;

    private const ALLOWED_SORTS = ['name', 'budget', 'status', 'progress', 'created_at', 'updated_at'];

    /** Requested include name => actual (possibly nested) eager-load path. */
    private const INCLUDE_MAP = [
        'client' => 'client',
        'members' => 'members',
        'milestones' => 'milestones',
        'tasks' => 'tasks.assignee',
        'designSubmissions' => 'designSubmissions.submitter',
        'documents' => 'documents',
        'meetings' => 'meetings',
        'notes' => 'notes.user',
        'activities' => 'activities.user',
        'attachments' => 'attachments.uploader',
        'reminders' => 'reminders.user',
    ];

    public function list(array $filters): LengthAwarePaginator
    {
        $query = Project::query();

        $this->applySearch($query, $filters['search'] ?? null);
        $this->applyInList($query, 'status', $filters['status'] ?? null);
        $this->applyInList($query, 'client_id', $filters['client_id'] ?? null, 'intval');
        $this->applyMemberFilter($query, $filters['member_id'] ?? null);
        $this->applyBudgetRange($query, $filters['budget_min'] ?? null, $filters['budget_max'] ?? null);
        $this->applyDateRange($query, $filters['created_from'] ?? null, $filters['created_to'] ?? null);

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortBy = in_array($sortBy, self::ALLOWED_SORTS, true) ? $sortBy : 'created_at';
        $query->orderBy($sortBy, $filters['sort_dir'] ?? 'desc');

        if (! empty($filters['with'])) {
            $query->with($this->resolveIncludes($filters['with']));
        }

        $perPage = min((int) ($filters['per_page'] ?? 15), 100);

        return $query->paginate($perPage)->withQueryString();
    }

    public function show(Project $project, ?string $with = null): Project
    {
        if ($with) {
            $project->load($this->resolveIncludes($with));
        }

        return $project;
    }

    public function create(array $data): Project
    {
        return DB::transaction(function () use ($data) {
            $data['status'] ??= ProjectStatus::Planning->value;
            $data['progress'] = 0; // recalculated as tasks/milestones are added

            $project = Project::create($data);

            $project->activities()->create([
                'user_id' => auth()->id(),
                'description' => 'Project created',
            ]);

            return $project;
        });
    }

    public function update(Project $project, array $data): Project
    {
        return DB::transaction(function () use ($project, $data) {
            $project->fill($data);
            $statusChanged = $project->isDirty('status');
            $project->save();

            if ($statusChanged) {
                $project->activities()->create([
                    'user_id' => auth()->id(),
                    'description' => "Status changed to {$project->status->label()}",
                ]);
            }

            return $project;
        });
    }

    /**
     * Soft delete only — client/documents keep their FK intact. The DB-level
     * `cascadeOnDelete()` on those tables only fires on a real SQL DELETE,
     * which SoftDeletes never issues (it's an UPDATE).
     */
    public function delete(Project $project): void
    {
        $project->delete();
    }

    // ---------- Tasks ----------

    public function addTask(Project $project, array $data): Task
    {
        return DB::transaction(function () use ($project, $data) {
            $data['status'] ??= TaskStatus::Todo->value;
            $data['priority'] ??= TaskPriority::Medium->value;

            $task = $project->tasks()->create($data);

            $project->activities()->create([
                'user_id' => auth()->id(),
                'description' => "Task added: {$task->title}",
            ]);

            $this->recalculateProgress($project);

            return $task;
        });
    }

    public function updateTask(Project $project, Task $task, array $data): Task
    {
        return DB::transaction(function () use ($project, $task, $data) {
            $task->fill($data);
            $statusChanged = $task->isDirty('status');
            $task->save();

            if ($statusChanged) {
                $project->activities()->create([
                    'user_id' => auth()->id(),
                    'description' => "Task \"{$task->title}\" status changed to {$task->status->label()}",
                ]);
            }

            $this->recalculateProgress($project);

            return $task;
        });
    }

    public function deleteTask(Project $project, Task $task): void
    {
        DB::transaction(function () use ($project, $task) {
            $task->delete();
            $this->recalculateProgress($project);
        });
    }

    // ---------- Milestones ----------

    public function addMilestone(Project $project, array $data): Milestone
    {
        return DB::transaction(function () use ($project, $data) {
            $milestone = $project->milestones()->create($data);

            $project->activities()->create([
                'user_id' => auth()->id(),
                'description' => "Milestone added: {$milestone->title}",
            ]);

            $this->recalculateProgress($project);

            return $milestone;
        });
    }

    public function updateMilestone(Project $project, Milestone $milestone, array $data): Milestone
    {
        return DB::transaction(function () use ($project, $milestone, $data) {
            $milestone->fill($data);
            $doneChanged = $milestone->isDirty('is_done');
            $milestone->save();

            if ($doneChanged) {
                $project->activities()->create([
                    'user_id' => auth()->id(),
                    'description' => $milestone->is_done
                        ? "Milestone completed: {$milestone->title}"
                        : "Milestone reopened: {$milestone->title}",
                ]);
            }

            $this->recalculateProgress($project);

            return $milestone;
        });
    }

    public function deleteMilestone(Project $project, Milestone $milestone): void
    {
        DB::transaction(function () use ($project, $milestone) {
            $milestone->delete();
            $this->recalculateProgress($project);
        });
    }

    // ---------- Design submissions ----------

    public function addDesignSubmission(Project $project, array $data): DesignSubmission
    {
        return DB::transaction(function () use ($project, $data) {
            $data['status'] ??= DesignStatus::Pending->value;
            $data['version'] ??= 1;
            $data['submitted_by'] = auth()->id();
            $data['submitted_at'] = now();

            $design = $project->designSubmissions()->create($data);

            $project->activities()->create([
                'user_id' => auth()->id(),
                'description' => "Design submitted: {$design->name}",
            ]);

            return $design;
        });
    }

    public function updateDesignSubmission(Project $project, DesignSubmission $design, array $data): DesignSubmission
    {
        return DB::transaction(function () use ($project, $design, $data) {
            $design->fill($data);
            $statusChanged = $design->isDirty('status');

            if ($statusChanged && in_array($design->status, [DesignStatus::Approved, DesignStatus::Rejected], true)) {
                $design->decided_by = auth()->id();
                $design->decided_at = now();
            }

            $design->save();

            if ($statusChanged) {
                $project->activities()->create([
                    'user_id' => auth()->id(),
                    'description' => "Design \"{$design->name}\" {$design->status->value}",
                ]);
            }

            return $design;
        });
    }

    public function deleteDesignSubmission(Project $project, DesignSubmission $design): void
    {
        $design->delete();
    }

    // ---------- Team members ----------

    /** Uses syncWithoutDetaching — the pivot's composite PK also guarantees no duplicates at the DB level. */
    public function addMember(Project $project, int $userId): void
    {
        DB::transaction(function () use ($project, $userId) {
            $project->members()->syncWithoutDetaching([$userId]);

            $user = User::find($userId);
            $project->activities()->create([
                'user_id' => auth()->id(),
                'description' => 'Added team member: '.($user->name ?? "#{$userId}"),
            ]);
        });
    }

    public function removeMember(Project $project, int $userId): void
    {
        DB::transaction(function () use ($project, $userId) {
            $user = User::find($userId);
            $project->members()->detach($userId);

            $project->activities()->create([
                'user_id' => auth()->id(),
                'description' => 'Removed team member: '.($user->name ?? "#{$userId}"),
            ]);
        });
    }

    /** Progress = (completed tasks + completed milestones) / (total tasks + total milestones), 0-100. */
    private function recalculateProgress(Project $project): void
    {
        $totalTasks = $project->tasks()->count();
        $doneTasks = $project->tasks()->where('status', TaskStatus::Done)->count();
        $totalMilestones = $project->milestones()->count();
        $doneMilestones = $project->milestones()->where('is_done', true)->count();

        $total = $totalTasks + $totalMilestones;
        if ($total === 0) {
            return; // nothing to compute from — leave progress as-is
        }

        $project->progress = (int) round((($doneTasks + $doneMilestones) / $total) * 100);
        $project->saveQuietly();
    }

    private function applySearch(Builder $query, ?string $term): void
    {
        if (empty($term)) {
            return;
        }

        $like = '%'.$term.'%';
        $query->where(function (Builder $q) use ($like) {
            $q->where('name', 'like', $like)
                ->orWhere('description', 'like', $like)
                ->orWhereHas('client', function (Builder $cq) use ($like) {
                    $cq->where('name', 'like', $like);
                });
        });
    }

    private function applyMemberFilter(Builder $query, ?string $value): void
    {
        if (empty($value)) {
            return;
        }

        $ids = array_map('intval', $this->explode($value));
        $query->whereHas('members', function (Builder $q) use ($ids) {
            $q->whereIn('users.id', $ids);
        });
    }

    private function applyBudgetRange(Builder $query, null|string|float $min, null|string|float $max): void
    {
        if ($min !== null) {
            $query->where('budget', '>=', (float) $min);
        }
        if ($max !== null) {
            $query->where('budget', '<=', (float) $max);
        }
    }

    private function applyInList(Builder $query, string $column, ?string $value, ?string $cast = null): void
    {
        if (empty($value)) {
            return;
        }

        $values = $this->explode($value);
        if ($cast) {
            $values = array_map($cast, $values);
        }

        $query->whereIn($column, $values);
    }

    private function applyDateRange(Builder $query, ?string $from, ?string $to): void
    {
        if (! empty($from)) {
            $query->whereDate('created_at', '>=', $from);
        }
        if (! empty($to)) {
            $query->whereDate('created_at', '<=', $to);
        }
    }

    private function explode(string $value): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $value)), fn ($v) => $v !== ''));
    }

    private function resolveIncludes(string $with): array
    {
        $requested = $this->explode($with);

        return array_values(array_intersect_key(self::INCLUDE_MAP, array_flip($requested)));
    }
}
