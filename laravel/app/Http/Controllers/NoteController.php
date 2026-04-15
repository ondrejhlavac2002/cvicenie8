<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class NoteController extends Controller
{

    public function index(Request $request)
    {
        $this->authorize('viewAny', Note::class);

        $notes = Note::query()
            ->with(['user', 'categories'])
            ->where(function ($query) use ($request) {
                $query->where('status', '!=', Note::STATUS_DRAFT)
                    ->orWhere('user_id', $request->user()->id);
            })
            ->recent()
            ->paginate(5);

        return response()->json($notes, Response::HTTP_OK);
    }

    public function myNotes(Request $request)
    {
        $notes = $request->user()
            ->notes()
            ->with(['categories'])
            ->recent()
            ->paginate(5);

        return response()->json($notes, Response::HTTP_OK);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Note::class);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:128'],
            'body' => ['nullable', 'string'],
            'status' => ['nullable', 'string', Rule::in(Note::STATUSES)],
            'is_pinned' => ['nullable', 'boolean'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
        ]);

        $user = $request->user();

        $note = $user->notes()->create([
            'title' => $validated['title'],
            'body' => $validated['body'] ?? null,
            'status' => $validated['status'] ?? Note::STATUS_DRAFT,
            'is_pinned' => $validated['is_pinned'] ?? false,
        ]);

        if (isset($validated['category_ids'])) {
            $note->categories()->sync($validated['category_ids']);
        }

        return response()->json([
            'message' => 'Poznámka bola úspešne vytvorená.',
            'note' => $this->loadNoteRelations($note),
        ], Response::HTTP_CREATED);
    }

    public function show(Note $note)
    {
        $this->authorize('view', $note);

        return response()->json([
            'note' => $note->load(['user', 'categories', 'tasks.comments', 'comments']),
        ], Response::HTTP_OK);
    }

    public function update(Request $request, Note $note)
    {
        $this->authorize('update', $note);

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:128'],
            'body' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'string', Rule::in(Note::STATUSES)],
            'is_pinned' => ['sometimes', 'boolean'],
            'category_ids' => ['sometimes', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
        ]);

        $note->update(Arr::except($validated, ['category_ids']));

        if (array_key_exists('category_ids', $validated)) {
            $note->categories()->sync($validated['category_ids']);
        }

        return response()->json([
            'message' => 'Poznámka bola úspešne aktualizovaná.',
            'note' => $this->loadNoteRelations($note->fresh()),
        ], Response::HTTP_OK);
    }

    public function destroy(Note $note)
    {
        $this->authorize('delete', $note);

        $note->delete();

        return response()->json([
            'message' => 'Poznámka odstránená.',
        ], Response::HTTP_OK);
    }

    public function statsByStatus()
    {
        $stats = Note::statusBreakdown();

        return response()->json(['stats' => $stats], Response::HTTP_OK);
    }

    public function archiveOldDrafts()
    {
        $affected = Note::archiveOldDrafts();

        return response()->json([
            'message' => 'Staré koncepty boli archivované.',
            'affected_rows' => $affected,
        ], Response::HTTP_OK);
    }

    public function userNotesWithCategories(string $userId)
    {
        $user = User::query()
            ->with([
                'notes' => fn ($query) => $query->with('categories')->recent(),
            ])
            ->findOrFail($userId);

        $notes = $user->notes->map(function (Note $note): array {
            return [
                'id' => $note->id,
                'title' => $note->title,
                'status' => $note->status,
                'is_pinned' => $note->is_pinned,
                'categories' => $note->categories->pluck('name')->all(),
            ];
        });

        return response()->json(['notes' => $notes], Response::HTTP_OK);
    }

    public function search(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $notes = Note::query()
            ->with(['user', 'categories'])
            ->published()
            ->search($q)
            ->recent()
            ->limit(20)
            ->get();

        return response()->json([
            'query' => $q,
            'notes' => $notes,
        ], Response::HTTP_OK);
    }

    public function pin(Note $note)
    {
        $this->authorize('update', $note);

        $note->pin();

        return response()->json([
            'message' => 'Poznámka bola pripnutá.',
            'note' => $this->loadNoteRelations($note->fresh()),
        ], Response::HTTP_OK);
    }

    public function unpin(Note $note)
    {
        $this->authorize('update', $note);

        $note->unpin();

        return response()->json([
            'message' => 'Poznámka bola odopnutá.',
            'note' => $this->loadNoteRelations($note->fresh()),
        ], Response::HTTP_OK);
    }

    public function publish(Note $note)
    {
        $this->authorize('update', $note);

        $note->publish();

        return response()->json([
            'message' => 'Poznámka bola publikovaná.',
            'note' => $this->loadNoteRelations($note->fresh()),
        ], Response::HTTP_OK);
    }

    public function archive(Note $note)
    {
        $this->authorize('update', $note);

        $note->archive();

        return response()->json([
            'message' => 'Poznámka bola archivovaná.',
            'note' => $this->loadNoteRelations($note->fresh()),
        ], Response::HTTP_OK);
    }

    private function loadNoteRelations(Note $note): Note
    {
        return $note->load(['user', 'categories']);
    }
}
