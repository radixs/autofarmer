<?php

namespace App\Console\Commands;

use App\Support\TelemetryTables;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class RestoreTelemetryCommand extends Command
{
    protected $signature = 'measurements:restore
                            {path : Path, file name, or relative backup reference}
                            {--merge=true : Merge rows instead of truncating before import}';

    protected $description = 'Restore measurement + cache tables from a SQL dump.';

    public function handle(): int
    {
        $pathArgument = $this->argument('path');
        $path = $this->resolveBackupPath($pathArgument);

        if (! $path || ! File::exists($path)) {
            $this->error("Backup file [{$pathArgument}] could not be found.");

            return Command::FAILURE;
        }

        $shouldMerge = $this->parseBoolOption($this->option('merge'));

        if (! $shouldMerge) {
            $this->truncateTables();
        }

        $statements = $this->statementsFromSql(File::get($path));

        if (empty($statements)) {
            $this->warn('No SQL statements were found in the provided backup.');

            return Command::SUCCESS;
        }

        Schema::disableForeignKeyConstraints();

        DB::transaction(function () use ($statements): void {
            foreach ($statements as $statement) {
                DB::unprepared($statement);
            }
        });

        Schema::enableForeignKeyConstraints();

        $this->info(sprintf(
            'Restored %d statements from %s using %s mode.',
            count($statements),
            $path,
            $shouldMerge ? 'merge' : 'truncate'
        ));

        return Command::SUCCESS;
    }

    private function truncateTables(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach (TelemetryTables::names() as $table) {
            DB::table($table)->truncate();
        }

        Schema::enableForeignKeyConstraints();
    }

    private function parseBoolOption(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        $bool = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return $bool ?? true;
    }

    private function resolveBackupPath(string $argument): ?string
    {
        $candidates = array_unique(array_filter([
            $argument,
            base_path($argument),
            storage_path($argument),
            storage_path('db_backups'.DIRECTORY_SEPARATOR.$argument),
        ]));

        foreach ($candidates as $candidate) {
            if (File::exists($candidate)) {
                return realpath($candidate) ?: $candidate;
            }
        }

        return null;
    }

    private function statementsFromSql(string $sql): array
    {
        $clean = trim(str_replace(["\r\n", "\r"], "\n", $sql));

        if ($clean === '') {
            return [];
        }

        $statements = preg_split('/;\s*(?:\n|$)/', $clean);

        return array_values(array_filter(array_map('trim', $statements)));
    }
}
