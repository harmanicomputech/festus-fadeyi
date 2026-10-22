<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

/**
 * Scheduled emails for hosting where `php artisan schedule:run` cannot be used.
 * Call /cron/{CRON_TOKEN} every 5 minutes (DirectAdmin cron job with wget/curl,
 * or an external service such as cron-job.org).
 */
class CronController extends Controller
{
    public function run(string $token)
    {
        $expected = (string) config('app.cron_token');
        abort_unless(strlen($expected) >= 16 && hash_equals($expected, $token), 404);

        // Never let two overlapping calls send the same campaign at once.
        $output = Cache::lock('ff85:cron', 600)->get(function () {
            Artisan::call('ff85:send-scheduled');

            return trim(Artisan::output());
        });

        return response(($output === false ? 'Already running.' : ($output ?: 'Nothing due.'))."\n", 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
            'Cache-Control' => 'no-store',
        ]);
    }
}
