<?php

namespace App\Console\Commands;

use App\Services\Campaigns;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('ff85:send-scheduled {--campaign= : Run one campaign now, ignoring its schedule}')]
#[Description('Send any scheduled event emails that are due (RSVP reminder, event reminder, day-of note, thank-you).')]
class SendScheduledEmails extends Command
{
    public function handle(Campaigns $campaigns): int
    {
        $only = $this->option('campaign');
        if ($only && ! array_key_exists($only, Campaigns::CAMPAIGNS)) {
            $this->error('Unknown campaign. Use one of: '.implode(', ', array_keys(Campaigns::CAMPAIGNS)));

            return self::FAILURE;
        }

        foreach (array_keys(Campaigns::CAMPAIGNS) as $campaign) {
            if ($only ? $campaign !== $only : ! $campaigns->isDue($campaign)) {
                continue;
            }
            $r = $campaigns->run($campaign);
            $line = sprintf('%s: %d sent, %d already sent, %d failed', $campaign, $r['sent'], $r['skipped'], $r['failed']);
            $this->info($line);
            if ($r['sent'] || $r['failed']) {
                Log::info('Scheduled email campaign run', ['campaign' => $campaign] + $r);
            }
        }

        return self::SUCCESS;
    }
}
