<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TimeController extends Controller
{
    public function getCurrentTime(){
        return now()->setTimezone('Asia/Jakarta')->toDateTimeString();;
    }
}
