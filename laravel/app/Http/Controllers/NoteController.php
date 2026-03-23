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

    public function index()
    {
        $notes = Note::query()
            ->with(['user', 'categories'])
            ->recent()
            ->get();

        return response()->json(['notes' => $notes], Response::HTTP_OK);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'title' => ['required', 'string', 'max:128'],
            'body' => ['nullable', 'string'],
            'status' => ['nullable', 'string', Rule::in(Note::STATUSES)],
            'is_pinned' => ['nullable', 'boolean'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
        ]);

        $note = Note::create([
            'user_id' => $validated['user_id'],
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
        return response()->json([
            'note' => $note->load(['user', 'categories', 'tasks.comments', 'comments']),
        ], Response::HTTP_OK);
    }

    public function update(Request $request, Note $note)
    {
        $validated = $request->validate([
            'user_id' => ['sometimes', 'integer', 'exists:users,id'],
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
        $note->pin();

        return response()->json([
            'message' => 'Poznámka bola pripnutá.',
            'note' => $this->loadNoteRelations($note->fresh()),
        ], Response::HTTP_OK);
    }

    public function unpin(Note $note)
    {
        $note->unpin();

        return response()->json([
            'message' => 'Poznámka bola odopnutá.',
            'note' => $this->loadNoteRelations($note->fresh()),
        ], Response::HTTP_OK);
    }

    public function publish(Note $note)
    {
        $note->publish();

        return response()->json([
            'message' => 'Poznámka bola publikovaná.',
            'note' => $this->loadNoteRelations($note->fresh()),
        ], Response::HTTP_OK);
    }

    public function archive(Note $note)
    {
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
