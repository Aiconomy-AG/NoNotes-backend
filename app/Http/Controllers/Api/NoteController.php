<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Note;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    public function index(Request $request)
    {
        return $request->user()->notes()->latest()->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'blocks' => ['nullable', 'array'],
        ]);

        $data['title'] = filled($data['title'] ?? null) ? trim($data['title']) : 'Untitled';

        $note = $request->user()->notes()->create($data);

        return response()->json($note, 201);
    }

    public function update(Request $request, Note $note)
    {
        abort_unless($note->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'blocks' => ['nullable', 'array'],
        ]);

        $data['title'] = filled($data['title'] ?? null) ? trim($data['title']) : 'Untitled';

        $note->update($data);

        return response()->json($note);
    }

    public function destroy(Request $request, Note $note)
    {
        abort_unless($note->user_id === $request->user()->id, 403);

        $note->delete();

        return response()->noContent();
    }
}
