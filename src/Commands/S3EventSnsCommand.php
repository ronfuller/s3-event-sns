<?php

namespace Psi\S3EventSns\Commands;

use Illuminate\Console\Command;

class S3EventSnsCommand extends Command
{
    public $signature = 's3-event-sns';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
