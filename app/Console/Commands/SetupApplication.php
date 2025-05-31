<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SetupApplication extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'setup:app';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('## Start setup.');

        // Wipe the database
        $this->info('Wiping database....');
        $this->call('db:wipe', ['--force' => true]);
        $this->info('Database wiped.');

        // Run migrations
        $this->info('Running migration....');
        $this->call('migrate', ['--force' => true]);
        $this->info('Table migrated.');

        // Run seeders
        $this->info('Seeding database....');
        $this->call('db:seed', ['--force' => true]);
        $this->info('Data seeded.');

        // Output a success message
        $this->call('optimize:clear');
        $this->info('## Setup done.');

        return Command::SUCCESS;
    }
}
