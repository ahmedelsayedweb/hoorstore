<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Artisan;

class MigrationController extends Controller
{
    public function run()
    {
        try {
            $exitCode = Artisan::call('migrate', ['--force' => true]);
            $output = Artisan::output();

            return response()->json([
                'success' => $exitCode === 0,
                'output' => trim($output),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'output' => $e->getMessage(),
            ], 500);
        }
    }

    public function status()
    {
        try {
            Artisan::call('migrate:status');
            $output = Artisan::output();

            // Parse the output into structured data
            $lines = explode("\n", trim($output));
            $migrations = [];

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || str_contains($line, '----') || str_contains($line, 'Migration name') || str_contains($line, 'Migration')) {
                    // Check if it's a header line
                    if (str_contains($line, 'Migration name') || (str_contains($line, 'Migration') && str_contains($line, 'Batch'))) {
                        continue;
                    }
                    if (empty($line) || str_contains($line, '----')) {
                        continue;
                    }
                }

                // Try to parse "Ran? | Migration | Batch" format
                if (str_contains($line, '|')) {
                    $parts = array_map('trim', explode('|', $line));
                    $parts = array_values(array_filter($parts, fn($p) => $p !== ''));
                    if (count($parts) >= 2) {
                        $migrations[] = [
                            'ran' => str_contains(strtolower($parts[0]), 'yes') || str_contains($parts[0], '✓'),
                            'migration' => $parts[1] ?? '',
                            'batch' => $parts[2] ?? null,
                        ];
                    }
                } else {
                    // Lumen might output in a simpler format
                    // Try matching patterns like "[YES] migration_name" or "migration_name ... Ran"
                    if (preg_match('/\[?(Yes|No|Ran|Pending)\]?\s+(.+)/i', $line, $matches)) {
                        $migrations[] = [
                            'ran' => strtolower($matches[1]) === 'yes' || strtolower($matches[1]) === 'ran',
                            'migration' => trim($matches[2]),
                            'batch' => null,
                        ];
                    } elseif (preg_match('/(.+?)\s+\.{2,}\s+(Ran|Pending)/i', $line, $matches)) {
                        $migrations[] = [
                            'ran' => strtolower($matches[2]) === 'ran',
                            'migration' => trim($matches[1]),
                            'batch' => null,
                        ];
                    }
                }
            }

            return response()->json([
                'migrations' => $migrations,
                'raw' => trim($output),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
