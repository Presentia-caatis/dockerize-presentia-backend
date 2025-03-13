<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSchedule;
use App\Models\AttendanceWindow;
use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function destroy($id){
            $event = Event::findOrFail($id);
            
            AttendanceSchedule::where('event_id', $event->id)->delete();

            AttendanceWindow::where('event_id', $event->id)->delete();

            $event->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Event deleted successfully'
            ]);
    }
}
