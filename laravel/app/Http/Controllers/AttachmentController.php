<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentController extends Controller
{
    public function store(Request $request, Note $note)
    {
        $this->authorize('update', $note);

        $request->validate([
            'files' => ['required', 'array', 'min:1', 'max:10'],
            'files.*' => ['file', 'mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,txt,zip', 'max:10240'],
        ]);

        $storedPaths = [];

        DB::beginTransaction();

        try {
            $attachments = [];

            foreach ($request->file('files') as $file) {
                $folder = "notes/{$note->id}";
                $storedName = Str::ulid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs($folder, $storedName, 'local');
                $storedPaths[] = $path;

                $attachment = $note->attachments()->create([
                    'public_id' => (string) Str::ulid(),
                    'collection' => 'note-attachment',
                    'visibility' => 'private',
                    'disk' => 'local',
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'stored_name' => $storedName,
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ]);

                $attachments[] = $attachment;
            }

            DB::commit();

            return response()->json([
                'message' => 'Prílohy boli úspešne nahrané.',
                'attachments' => $attachments,
            ], Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            DB::rollBack();

            foreach ($storedPaths as $storedPath) {
                Storage::disk('local')->delete($storedPath);
            }

            return response()->json([
                'message' => 'Nahrávanie príloh zlyhalo.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy(Attachment $attachment)
    {
        $this->authorize('delete', $attachment);

        Storage::disk($attachment->disk)->delete($attachment->path);
        $attachment->delete();

        return response()->json([
            'message' => 'Príloha bola odstránená.',
        ], Response::HTTP_OK);
    }

    public function link(Attachment $attachment)
    {
        $this->authorize('view', $attachment);

        if ($attachment->disk !== 'local') {
            return response()->json([
                'message' => 'Tento súbor nie je súkromný.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $path = Storage::disk('local')->path($attachment->path);

        return response()->download($path, $attachment->original_name, [
            'Content-Type' => $attachment->mime_type,
        ]);
    }
}
