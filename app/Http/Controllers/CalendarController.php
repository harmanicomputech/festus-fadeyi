<?php

namespace App\Http\Controllers;

use App\Support\EventInfo;

class CalendarController extends Controller
{
    public function ics()
    {
        return response(EventInfo::ics(), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="dr-festus-fadeyi-at-85.ics"',
        ]);
    }
}
