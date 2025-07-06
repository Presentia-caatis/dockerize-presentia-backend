<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TimeController extends Controller
{
    public function getCurrentTime(Request $request){
        return now()->setTimezone($request->query('timezone') ?? 'Asia/Jakarta')->toDateTimeString();;
    }
}
