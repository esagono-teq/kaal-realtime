<?php

namespace Kaal\Realtime\Examples;

use Kaal\Realtime\Contracts\RealtimeHandler;
// use App\Models\Task; // Assumed to exist in host app
// use Illuminate\Support\Facades\View;

class TaskListHandler implements RealtimeHandler
{
    public function render(): string
    {
        // Example logic
        // return View::make('partials.tasks', ['tasks' => Task::latest()->get()])->render();
        
        return '<ul><li>Example Task 1</li><li>Example Task 2</li></ul>';
    }
}
