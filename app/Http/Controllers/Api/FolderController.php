<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Folder;
use Illuminate\Http\Request;
use Illuminate\Validation\Validator;

class FolderController extends Controller
{
    public function index(Request $request)
    {
        return $request->user()
            ->folders()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function store(Request $request)
    {
        $data = $this->validatedFolderData($request);
        $data['name'] = filled($data['name'] ?? null) ? trim($data['name']) : 'Untitled folder';

        $folder = $request->user()->folders()->create($data);

        return response()->json($folder, 201);
    }

    public function update(Request $request, Folder $folder)
    {
        $this->authorizeFolder($request, $folder);

        $data = $this->validatedFolderData($request, $folder);
        if (array_key_exists('name', $data)) {
            $data['name'] = filled($data['name']) ? trim($data['name']) : 'Untitled folder';
        }

        $folder->update($data);

        return response()->json($folder);
    }

    public function destroy(Request $request, Folder $folder)
    {
        $this->authorizeFolder($request, $folder);

        $folder->delete();

        return response()->noContent();
    }

    private function validatedFolderData(Request $request, ?Folder $folder = null): array
    {
        $data = $request->validate([
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'parent_id' => ['sometimes', 'nullable', 'integer'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        validator($data, [])->after(function (Validator $validator) use ($request, $data, $folder) {
            if (! array_key_exists('parent_id', $data) || $data['parent_id'] === null) {
                return;
            }

            $parent = $request->user()->folders()->find($data['parent_id']);
            if (! $parent) {
                $validator->errors()->add('parent_id', 'The selected folder does not exist.');
                return;
            }

            if ($folder && $this->isDescendantOrSelf($folder, $parent)) {
                $validator->errors()->add('parent_id', 'A folder cannot be moved inside itself.');
            }
        })->validate();

        return $data;
    }

    private function authorizeFolder(Request $request, Folder $folder): void
    {
        abort_unless($folder->user_id === $request->user()->id, 403);
    }

    private function isDescendantOrSelf(Folder $folder, Folder $candidateParent): bool
    {
        if ($folder->id === $candidateParent->id) {
            return true;
        }

        $parentId = $candidateParent->parent_id;
        while ($parentId !== null) {
            if ($parentId === $folder->id) {
                return true;
            }
            $parentId = Folder::query()->whereKey($parentId)->value('parent_id');
        }

        return false;
    }
}
