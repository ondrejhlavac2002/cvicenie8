<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Note;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CommentController extends Controller
{
    public function indexForNote(Note $note)
    {
        $this->authorize('view', $note);

        $comments = $note->comments()->with('user')->get();

        return response()->json(['comments' => $comments], Response::HTTP_OK);
    }

    public function storeForNote(Request $request, Note $note)
    {
        $this->authorize('view', $note);
        $this->authorize('create', Comment::class);

        $validated = $request->validate([
            'body' => ['required', 'string'],
        ]);

        $comment = $note->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
        ]);

        return response()->json([
            'message' => 'Komentár bol úspešne vytvorený.',
            'comment' => $comment->load('user'),
        ], Response::HTTP_CREATED);
    }

    public function indexForTask(Request $request, $note, $task)
    {
        $note = Note::findOrFail($note);
        $task = Task::findOrFail($task);

        $this->authorize('view', $task->note);

        $comments = $task->comments()->with('user')->get();

        return response()->json(['comments' => $comments], Response::HTTP_OK);
    }

    public function storeForTask(Request $request, $note, $task)
    {
        $note = Note::findOrFail($note);
        $task = Task::findOrFail($task);

        $this->authorize('view', $task->note);
        $this->authorize('create', Comment::class);

        $validated = $request->validate([
            'body' => ['required', 'string'],
        ]);

        $comment = $task->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
        ]);

        return response()->json([
            'message' => 'Komentár bol úspešne vytvorený.',
            'comment' => $comment->load('user'),
        ], Response::HTTP_CREATED);
    }

    public function update(Request $request, Comment $comment)
    {
        $this->authorize('update', $comment);

        $validated = $request->validate([
            'body' => ['required', 'string'],
        ]);

        $comment->update($validated);

        return response()->json([
            'message' => 'Komentár bol úspešne aktualizovaný.',
            'comment' => $comment->fresh()->load('user'),
        ], Response::HTTP_OK);
    }

    public function destroy(Comment $comment)
    {
        $this->authorize('delete', $comment);

        $comment->delete();

        return response()->json([
            'message' => 'Komentár bol odstránený.',
        ], Response::HTTP_OK);
    }
}
