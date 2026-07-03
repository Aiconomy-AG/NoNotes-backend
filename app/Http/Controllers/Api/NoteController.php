<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Validation\Validator;

class NoteController extends Controller
{
    public function index(Request $request)
    {
        return $request->user()
            ->notes()
            ->orderBy('sort_order')
            ->latest()
            ->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'blocks' => ['nullable', 'array'],
            'folder_id' => ['nullable', 'integer'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
        $this->validateFolderOwnership($request, $data);

        $data['title'] = filled($data['title'] ?? null) ? trim($data['title']) : 'Untitled';

        $note = $request->user()->notes()->create($data);

        return response()->json($note, 201);
    }

    public function update(Request $request, Note $note)
    {
        abort_unless($note->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'blocks' => ['sometimes', 'nullable', 'array'],
            'folder_id' => ['sometimes', 'nullable', 'integer'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);
        $this->validateFolderOwnership($request, $data);

        if (array_key_exists('title', $data)) {
            $data['title'] = filled($data['title']) ? trim($data['title']) : 'Untitled';
        }

        $note->update($data);

        return response()->json($note);
    }

    public function destroy(Request $request, Note $note)
    {
        abort_unless($note->user_id === $request->user()->id, 403);

        $note->delete();

        return response()->noContent();
    }

    private function validateFolderOwnership(Request $request, array $data): void
    {
        validator($data, [])->after(function (Validator $validator) use ($request, $data) {
            if (! array_key_exists('folder_id', $data) || $data['folder_id'] === null) {
                return;
            }

            $exists = $request->user()->folders()->whereKey($data['folder_id'])->exists();
            if (! $exists) {
                $validator->errors()->add('folder_id', 'The selected folder does not exist.');
            }
        })->validate();
    }
}
