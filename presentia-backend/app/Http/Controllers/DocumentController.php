<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Document;

class DocumentController extends Controller
{
    public function index()
    {

        $data = Document::all();
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
            'school_id' => $request->user()->school_id,
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
        $document = Document::find($id);
        return response()->json([
            'status' => 'success',
            'message' => 'Document retrieved successfully',
            'data' =>  $document
        ]);
    }

    public function update(Request $request, $id)
    {
        $document = Document::find($id);
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
        $document = Document::find($id);
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
