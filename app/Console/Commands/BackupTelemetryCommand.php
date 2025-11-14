<?php

namespace App\Console\Commands;

use App\Support\TelemetryTables;
use DateTimeInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class BackupTelemetryCommand extends Command
{
    protected $signature = 'measurements:backup';

    protected $description = 'Create a SQL dump for the measurement and cache tables.';

    private const CHUNK_SIZE = 500;

    public function handle(): int
    {
        $directory = storage_path('db_backups');
        File::ensureDirectoryExists($directory);

        $timestamp = now('UTC')->format('Y-m-d_-H-i-s');
        $fileName = "db_{$timestamp}.sql";
        $path = $directory.DIRECTORY_SEPARATOR.$fileName;

        $handle = fopen($path, 'w');

        if ($handle === false) {
            $this->error('Unable to open backup file for writing.');

            return Command::FAILURE;
        }

        try {
            foreach (TelemetryTables::names() as $table) {
                $this->dumpTable($table, $handle);
            }
        } finally {
            fclose($handle);
        }

        $this->info("Backup stored at {$path}.");

        return Command::SUCCESS;
    }

    private function dumpTable(string $table, $handle): void
    {
        $columns = Schema::getColumnListing($table);

        if (empty($columns)) {
            return;
        }

        $columnList = '`'.implode('`, `', $columns).'`';

        DB::table($table)
            ->orderBy('id')
            ->chunkById(self::CHUNK_SIZE, function ($rows) use ($table, $columns, $columnList, $handle) {
                foreach ($rows as $row) {
                    $values = array_map(function (string $column) use ($row) {
                        return $this->quoteValue($row->{$column} ?? null);
                    }, $columns);

                    $statement = sprintf(
                        'REPLACE INTO `%s` (%s) VALUES (%s);',
                        $table,
                        $columnList,
                        implode(', ', $values)
                    );

                    fwrite($handle, $statement.PHP_EOL);
                }
            }, 'id');
    }

    private function quoteValue(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if ($value instanceof DateTimeInterface) {
            $value = $value->format('Y-m-d H:i:s');
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        return DB::getPdo()->quote((string) $value);
    }
}
