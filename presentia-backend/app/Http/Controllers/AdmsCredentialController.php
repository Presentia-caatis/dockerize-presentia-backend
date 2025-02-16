<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Models\AdmsCredential;
use Str;

class AdmsCredentialController extends Controller
{
    public function index(Request $request)
    {
        $validatedData = $request->validate([
            'perPage' => 'sometimes|integer|min:1' 
        ]);

        $perPage = $validatedData['perPage'] ?? 10;
        
        $data = AdmsCredential::paginate($perPage);

        return response()->json([
            'status' => 'success',
            'message' => 'ADMS Credential retrieved successfully',
            'data' => $data
        ]);     
    }

    public function store(Request $request)
    {
        $validatedData = $validatedData = $request->validate([
            'school_id' => 'required|exists:schools,id',
            'username' => 'required|string',
            'password' => 'required|string'
        ]);

        $validatedData['id']  = Str::uuid();

        $admsc = AdmsCredential::create($validatedData);

        return response()->json([
            'status' => 'success',
            'message' => 'ADMS Credential created successfully',
            'data' => $admsc
        ]);        
    }

    public function show(string $id)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        //
    }

    public function destroy(string $id)
    {
        //
    }
}
