<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\MediaUploadingTrait;
use App\Http\Requests\MassDestroyTaskRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Task;
use App\Models\TaskStatus;
use App\Models\TaskTag;
use App\Models\User;
use Carbon\Carbon;
use Gate;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response;

class TaskController extends Controller
{
    use MediaUploadingTrait;

    const HOURS_IN_DAY = 24;

    public function index()
    {
        abort_if(Gate::denies('task_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $user = auth()->user();
        if ($user->getIsAdminAttribute()) {
            $tasks = Task::with(['status', 'tags', 'assigned_to', 'media'])->get();
        } else {
            $tasks = Task::with(['status', 'tags', 'assigned_to', 'media'])->where('assigned_to_id', $user->id)->get();
        }

        return view('admin.tasks.index', compact('tasks'));
    }

    public function create()
    {
        abort_if(Gate::denies('task_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $statuses = TaskStatus::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $tags = TaskTag::pluck('name', 'id');

        $assigned_tos = User::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        return view('admin.tasks.create', compact('assigned_tos', 'statuses', 'tags'));
    }

    public function store(StoreTaskRequest $request)
    {
        $user = auth()->user();

        $tasks = Task::where('assigned_by_id', $user->id)->count();

        $hours_since_last_task_added = Carbon::parse($user->last_task_add_date)->diffInHours();

        if (!is_null($user->last_task_add_date) && $hours_since_last_task_added < $this::HOURS_IN_DAY && $tasks > 0 && !($tasks % 4)) {
            abort_if(true, Response::HTTP_FORBIDDEN, 'Within 24 Hour constraint');
        } else {
            $user->last_task_add_date = now();
            $user->save();
        }

        // if ($tasks > 0 && !($tasks % 4)) {
        //     abort_if(true, Response::HTTP_FORBIDDEN, 'Exceeded limit of 4, Try again after 24 Hours');
        // }

        $request->merge(['assigned_by_id' => $user->id]);
        if (!is_null($request->assigned_to_id)) {
            // assign to just the indicated user
            $this->globalTask($request);
        } else {
            // assign task to admin to represent the global task
            $this->globalTask($request);
            // then to every other user except the admins
            $this->allUsersExceptAdmin($request);
        }

        return redirect()->route('admin.tasks.index');
    }

    public function globalTask(StoreTaskRequest $request)
    {
        $task = Task::create($request->all());
        $task->tags()->sync($request->input('tags', []));
        if ($request->input('image', false)) {
            $task->addMedia(storage_path('tmp/uploads/' . basename($request->input('image'))))->toMediaCollection('image');
        }

        if ($media = $request->input('ck-media', false)) {
            Media::whereIn('id', $media)->update(['model_id' => $task->id]);
        }
    }

    public function allUsersExceptAdmin(StoreTaskRequest $request)
    {
        $users = User::all();
        foreach ($users as $user) {
            if (!$user->getIsAdminAttribute()) {
                $request->merge(['assigned_to_id' => $user->id]);
                $this->globalTask($request);
            }
        }
    }

    public function edit(Task $task)
    {
        abort_if(Gate::denies('task_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $statuses = TaskStatus::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $tags = TaskTag::pluck('name', 'id');

        $assigned_tos = User::pluck('name', 'id')->prepend(trans('global.pleaseSelect'), '');

        $task->load('status', 'tags', 'assigned_to');

        return view('admin.tasks.edit', compact('assigned_tos', 'statuses', 'tags', 'task'));
    }

    public function update(UpdateTaskRequest $request, Task $task)
    {
        $user = auth()->user();

        $request->merge(['assigned_by_id' => $user->id]);

        $task->update($request->all());
        $task->tags()->sync($request->input('tags', []));
        if ($request->input('image', false)) {
            if (!$task->image || $request->input('image') !== $task->image->file_name) {
                if ($task->image) {
                    $task->image->delete();
                }
                $task->addMedia(storage_path('tmp/uploads/' . basename($request->input('image'))))->toMediaCollection('image');
            }
        } elseif ($task->image) {
            $task->image->delete();
        }

        return redirect()->route('admin.tasks.index');
    }

    public function show(Task $task)
    {
        abort_if(Gate::denies('task_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $task->load('status', 'tags', 'assigned_to');

        return view('admin.tasks.show', compact('task'));
    }

    public function destroy(Task $task)
    {
        abort_if(Gate::denies('task_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $task->delete();

        return back();
    }

    public function massDestroy(MassDestroyTaskRequest $request)
    {
        $tasks = Task::find(request('ids'));

        foreach ($tasks as $task) {
            $task->delete();
        }

        return response(null, Response::HTTP_NO_CONTENT);
    }

    public function storeCKEditorImages(Request $request)
    {
        abort_if(Gate::denies('task_create') && Gate::denies('task_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $model         = new Task();
        $model->id     = $request->input('crud_id', 0);
        $model->exists = true;
        $media         = $model->addMediaFromRequest('upload')->toMediaCollection('ck-media');

        return response()->json(['id' => $media->id, 'url' => $media->getUrl()], Response::HTTP_CREATED);
    }
}
