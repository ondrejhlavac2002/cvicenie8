<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TaskController extends Controller
{
    public function index(Note $note)
    {
        $tasks = $note->tasks()->with('comments')->get();

        return response()->json(['tasks' => $tasks], Response::HTTP_OK);
    }

    public function store(Request $request, Note $note)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:128'],
            'is_done' => ['sometimes', 'boolean'],
            'due_at' => ['nullable', 'date'],
        ]);

        $task = $note->tasks()->create($validated);

        return response()->json([
            'message' => 'Úloha bola úspešne vytvorená.',
            'task' => $task,
        ], Response::HTTP_CREATED);
    }

    public function show(Note $note, Task $task)
    {
        return response()->json([
            'task' => $task->load('comments'),
        ], Response::HTTP_OK);
    }

    public function update(Request $request, Note $note, Task $task)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:128'],
            'is_done' => ['sometimes', 'boolean'],
            'due_at' => ['nullable', 'date'],
        ]);

        $task->update($validated);

        return response()->json([
            'message' => 'Úloha bola úspešne aktualizovaná.',
            'task' => $task->fresh(),
        ], Response::HTTP_OK);
    }

    public function destroy(Note $note, Task $task)
    {
        $task->delete();

        return response()->json([
            'message' => 'Úloha bola odstránená.',
        ], Response::HTTP_OK);
    }
}
