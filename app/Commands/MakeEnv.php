<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Storage;
use LaravelZero\Framework\Commands\Command;

class MakeEnv extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'make:env';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Generate new .env file.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if(Storage::exists('.env')) {
            $this->info(".env file already exists.");
            return;
        }
        Storage::put('.env', "TOKEN=");
    }
}
