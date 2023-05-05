<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Task;

class TasksCalendarController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        if ($user->getIsAdminAttribute()) {
            $events = Task::whereNotNull('due_date')->get();
        } else {
            $events = Task::whereNotNull('due_date')->where('assigned_to_id', $user->id)->get();
        }

        return view('admin.tasksCalendars.index', compact('events'));
    }
}
