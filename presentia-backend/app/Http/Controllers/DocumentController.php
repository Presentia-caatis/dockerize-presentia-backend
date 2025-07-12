<?php

namespace App\Http\Controllers;

use App\Filterable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Document;
use function App\Helpers\current_school_id;

class DocumentController extends Controller
{
    use Filterable;
    public function getAll(Request $request)
    {
        $validatedData = $request->validate([
            'perPage' => 'sometimes|integer|min:1' 
        ]);

        $perPage = $validatedData['perPage'] ?? 10;

        $query = $this->applyFilters(Document::query(),  $request->input('filter', []), ['school_id']);

        $data = $query->paginate($perPage);

        $data->getCollection()->transform(function ($document) {
            $document->path = asset('storage/' . $document->path); // ✅ Full URL
            return $document;
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Documents retrieved successfully',
            'data' => $data
        ]);

    }

    public function store(Request $request)
    {
        $request->validate([
            'document_name' => 'required|string',
            'file' => 'required|file|mimes:jpg,jpeg,png,html,doc,docx,pdf',
        ]);

        $path = $request->file('file')->store($request->file('file')->extension(),'public');

        $data = Document::create([
            'school_id' => current_school_id(),
            'document_name' => $request->document_name,
            'path' => $path
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Document created successfully',
            'data' => $data
        ],201);

    }

    public function getById($id)
    {
        $document = Document::findOrFail($id);
        $document->path = asset('storage/' . $document->path);
        return response()->json([
            'status' => 'success',
            'message' => 'Document retrieved successfully',
            'data' =>  $document
        ]);
    }

    public function update(Request $request, $id)
    {
        $document = Document::findOrFail($id);
        $request->validate([
            'document_name' => 'sometimes|required|string',
            'file' => 'sometimes|file|mimes:jpg,jpeg,png,html,doc,docx,pdf',
        ]);

        if ($request->has('document_name')) {
            $document->document_name = $request->document_name;
        }


        if ($request->hasFile('file')) {
            if ($document->path) {
                Storage::disk('public')->delete($document->path);
            }

            $document->path = $request->file('file')->store($request->file('file')->extension(),'public');
        }

        $document->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Document updated successfully',
            'data' => $document
        ]);

    }

    public function destroy($id)
    {
        $document = Document::findOrFail($id);
        if ($document->path) {
            Storage::disk('public')->delete($document->path);
        }

        $document->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Document deleted successfully'
        ]);

    }
}
