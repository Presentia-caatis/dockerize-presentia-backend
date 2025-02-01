<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \App\Models\AdmsCredential;

class AdmsCredentialController extends Controller
{
    public function index()
    {
        //
    }

    public function store(Request $request)
    {
        $validatedData = $validatedData = $request->validate([
            'school_id' => 'required|exists:schools,id',
            'username' => 'required|string',
            'password' => 'required|string'
        ]);

        $data = AdmsCredential::create($validatedData);

        
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
