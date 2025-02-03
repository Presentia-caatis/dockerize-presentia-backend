<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ClearAndCacheAll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'call:clear-and-cache-all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear cache, clear config, and cache config in one command';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Run clear:cache
        $this->info('Clearing application cache...');
        $this->call('cache:clear');

        // Run clear:config
        $this->info('Clearing configuration cache...');
        $this->call('config:clear');

        // Run config:cache
        $this->info('Caching configuration...');
        $this->call('config:cache');

        // // Run optimize:clear (optional, clears all cached files)
        // $this->info('Clearing all cached files...');
        // $this->call('optimize:clear');

        // // Run optimize (optional, caches everything)
        // $this->info('Caching everything...');
        // $this->call('optimize');

        $this->info('All cache and config operations completed successfully!');
    }
}
