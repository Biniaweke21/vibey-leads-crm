<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Project\AssignMemberRequest;
use App\Http\Requests\Project\IndexProjectRequest;
use App\Http\Requests\Project\ShowProjectRequest;
use App\Http\Requests\Project\StoreAttachmentRequest;
use App\Http\Requests\Project\StoreDesignSubmissionRequest;
use App\Http\Requests\Project\StoreMilestoneRequest;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\StoreTaskRequest;
use App\Http\Requests\Project\UpdateDesignSubmissionRequest;
use App\Http\Requests\Project\UpdateMilestoneRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Http\Requests\Project\UpdateTaskRequest;
use App\Http\Resources\AttachmentResource;
use App\Http\Resources\DesignSubmissionResource;
use App\Http\Resources\MilestoneResource;
use App\Http\Resources\ProjectCollection;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\TaskResource;
use App\Models\Attachment;
use App\Models\DesignSubmission;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;

class ProjectController extends Controller
{
    public function __construct(private readonly ProjectService $projects) {}

    /**
     * GET /api/projects — search, filter, sort, paginate.
     * Authorization: ProjectPolicy::viewAny (checked in IndexProjectRequest).
     */
    public function index(IndexProjectRequest $request): JsonResponse
    {
        $paginator = $this->projects->list($request->validated());

        $data = (new ProjectCollection($paginator->getCollection()))->toArray($request);

        return $this->paginated($paginator, '', $data);
    }

    /**
     * GET /api/projects/{project}
     * Authorization: ProjectPolicy::view (checked in ShowProjectRequest).
     */
    public function show(ShowProjectRequest $request, Project $project): JsonResponse
    {
        $project = $this->projects->show($project, $request->validated('with'));

        return $this->success(new ProjectResource($project));
    }

    /**
     * POST /api/projects
     * Authorization: ProjectPolicy::create (checked in StoreProjectRequest).
     */
    public function store(StoreProjectRequest $request): JsonResponse
    {
        $project = $this->projects->create($request->validated());

        return $this->success(new ProjectResource($project), 'Project created successfully.', 201);
    }

    /**
     * PUT/PATCH /api/projects/{project}
     * Authorization: ProjectPolicy::update (checked in UpdateProjectRequest).
     */
    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        $project = $this->projects->update($project, $request->validated());

        return $this->success(new ProjectResource($project), 'Project updated successfully.');
    }

    /**
     * DELETE /api/projects/{project} — Admin only.
     * Authorization: ProjectPolicy::delete.
     */
    public function destroy(Project $project): JsonResponse
    {
        $this->authorize('delete', $project);

        $this->projects->delete($project);

        return $this->success(null, 'Project deleted successfully.');
    }

    // ---------- Tasks ----------

    public function storeTask(StoreTaskRequest $request, Project $project): JsonResponse
    {
        $task = $this->projects->addTask($project, $request->validated());

        return $this->success(new TaskResource($task), 'Task added successfully.', 201);
    }

    public function updateTask(UpdateTaskRequest $request, Project $project, Task $task): JsonResponse
    {
        abort_if($task->project_id !== $project->id, 404, 'Resource not found.');

        $task = $this->projects->updateTask($project, $task, $request->validated());

        return $this->success(new TaskResource($task), 'Task updated successfully.');
    }

    public function destroyTask(Project $project, Task $task): JsonResponse
    {
        $this->authorize('update', $project);
        abort_if($task->project_id !== $project->id, 404, 'Resource not found.');

        $this->projects->deleteTask($project, $task);

        return $this->success(null, 'Task deleted successfully.');
    }

    // ---------- Milestones ----------

    public function storeMilestone(StoreMilestoneRequest $request, Project $project): JsonResponse
    {
        $milestone = $this->projects->addMilestone($project, $request->validated());

        return $this->success(new MilestoneResource($milestone), 'Milestone added successfully.', 201);
    }

    public function updateMilestone(UpdateMilestoneRequest $request, Project $project, Milestone $milestone): JsonResponse
    {
        abort_if($milestone->project_id !== $project->id, 404, 'Resource not found.');

        $milestone = $this->projects->updateMilestone($project, $milestone, $request->validated());

        return $this->success(new MilestoneResource($milestone), 'Milestone updated successfully.');
    }

    public function destroyMilestone(Project $project, Milestone $milestone): JsonResponse
    {
        $this->authorize('update', $project);
        abort_if($milestone->project_id !== $project->id, 404, 'Resource not found.');

        $this->projects->deleteMilestone($project, $milestone);

        return $this->success(null, 'Milestone deleted successfully.');
    }

    // ---------- Design submissions ----------

    public function storeDesignSubmission(StoreDesignSubmissionRequest $request, Project $project): JsonResponse
    {
        $design = $this->projects->addDesignSubmission($project, $request->validated());

        return $this->success(new DesignSubmissionResource($design), 'Design submitted successfully.', 201);
    }

    public function updateDesignSubmission(UpdateDesignSubmissionRequest $request, Project $project, DesignSubmission $designSubmission): JsonResponse
    {
        abort_if($designSubmission->project_id !== $project->id, 404, 'Resource not found.');

        $designSubmission = $this->projects->updateDesignSubmission($project, $designSubmission, $request->validated());

        return $this->success(new DesignSubmissionResource($designSubmission), 'Design submission updated successfully.');
    }

    public function destroyDesignSubmission(Project $project, DesignSubmission $designSubmission): JsonResponse
    {
        $this->authorize('update', $project);
        abort_if($designSubmission->project_id !== $project->id, 404, 'Resource not found.');

        $this->projects->deleteDesignSubmission($project, $designSubmission);

        return $this->success(null, 'Design submission deleted successfully.');
    }

    // ---------- Team members ----------

    public function storeMember(AssignMemberRequest $request, Project $project): JsonResponse
    {
        $this->projects->addMember($project, (int) $request->validated('user_id'));

        return $this->success(null, 'Team member added successfully.', 201);
    }

    public function destroyMember(Project $project, User $user): JsonResponse
    {
        $this->authorize('update', $project);

        $this->projects->removeMember($project, $user->id);

        return $this->success(null, 'Team member removed successfully.');
    }

    // ---------- Attachments ----------

    public function storeAttachment(StoreAttachmentRequest $request, Project $project): JsonResponse
    {
        $attachment = $this->projects->addAttachment($project, $request->validated());

        return $this->success(new AttachmentResource($attachment), 'File attached successfully.', 201);
    }

    public function destroyAttachment(Project $project, Attachment $attachment): JsonResponse
    {
        $this->authorize('update', $project);
        abort_if($attachment->attachable_id !== $project->id || $attachment->attachable_type !== Project::class, 404, 'Resource not found.');

        $this->projects->deleteAttachment($project, $attachment);

        return $this->success(null, 'File removed successfully.');
    }
}
