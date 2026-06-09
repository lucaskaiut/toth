<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MigrateVectorDatabaseCommand extends Command
{
    protected $signature = 'vector:migrate {--force : Force the operation to run when in production}';

    protected $description = 'Executa migrations do banco vetorial (PostgreSQL + pgvector)';

    public function handle(): int
    {
        $this->call('migrate', [
            '--database' => 'vector',
            '--path' => 'database/migrations/vector',
            '--force' => $this->option('force'),
        ]);

        return self::SUCCESS;
    }
}
