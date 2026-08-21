<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class BackupController extends Controller
{
    /**
     * Pastikan hanya user arief@gmail.com yang bisa mengakses.
     */
    private function authorizeBackupAccess()
    {
        if (!auth()->check() || auth()->user()->email !== 'arief@gmail.com') {
            abort(403, 'Anda tidak memiliki izin untuk mengakses fitur ini. Hanya untuk user arief@gmail.com.');
        }
    }

    public function index()
    {
        $files = Storage::disk('local')->files('backups');
        $backupFiles = [];

        foreach ($files as $file) {
            if (Storage::disk('local')->exists($file)) {
                $backupFiles[] = [
                    'name' => basename($file),
                    'path' => $file,
                    'last_modified' => Storage::disk('local')->lastModified($file),
                ];
            }
        }

        // Urutkan berdasarkan tanggal terbaru
        usort($backupFiles, function ($a, $b) {
            return $b['last_modified'] - $a['last_modified'];
        });

        return view('backup.index', compact('backupFiles'));
    }

    public function backup()
    {
        $this->authorizeBackupAccess();
        
        $backupDir = storage_path('app/backups');
        if (!file_exists($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $tables = DB::select('SHOW TABLES');
        $databaseName = env('DB_DATABASE');
        $tableKey = "Tables_in_{$databaseName}";

        $sql = "-- Backup generated on " . date('Y-m-d H:i:s') . "\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            $tableName = $table->$tableKey;
            $sql .= $this->getTableStructure($tableName);
            $sql .= $this->getTableData($tableName);
            $sql .= "\n";
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

        $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        $path = $backupDir . DIRECTORY_SEPARATOR . $filename;

        file_put_contents($path, $sql);

        return redirect()->route('backup.index')
            ->with('success', 'Backup berhasil dibuat!')
            ->with('backup_file', $filename);
    }

    private function getTableStructure($tableName)
    {
        $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`");
        $sql = "DROP TABLE IF EXISTS `{$tableName}`;\n";
        $sql .= $createTable[0]->{'Create Table'} . ";\n\n";
        return $sql;
    }

    private function getTableData($tableName)
    {
        $rows = DB::table($tableName)->get();
        if ($rows->isEmpty()) return '';

        $columns = array_keys((array)$rows->first());
        $columns = array_map(function($col) { return "`{$col}`"; }, $columns);
        $columnsList = implode(', ', $columns);

        $sql = "INSERT INTO `{$tableName}` ({$columnsList}) VALUES\n";
        $values = [];

        foreach ($rows as $row) {
            $rowArray = (array)$row;
            $escapedValues = array_map(function($value) {
                if (is_null($value)) return 'NULL';
                return "'" . addslashes($value) . "'";
            }, $rowArray);
            $values[] = "(" . implode(', ', $escapedValues) . ")";
        }

        $sql .= implode(",\n", $values) . ";\n\n";
        return $sql;
    }

    public function download($filename)
    {
        $this->authorizeBackupAccess();
        $path = storage_path('app/backups/' . $filename);
        if (!File::exists($path)) {
            return back()->with('error', 'File tidak ditemukan.');
        }
        return response()->download($path);
    }

    public function restoreForm()
    {
        $this->authorizeBackupAccess();
        return view('backup.restore');
    }

    public function restore(Request $request)
    {
        $this->authorizeBackupAccess();
        
        $request->validate([
            'backup_file' => 'required|file|mimes:sql,txt|max:102400'
        ]);

        $file = $request->file('backup_file');
        $content = file_get_contents($file->getRealPath());

        // Hapus komentar
        $lines = explode("\n", $content);
        $sql = '';
        foreach ($lines as $line) {
            if (strpos(trim($line), '--') !== 0 && !empty(trim($line))) {
                $sql .= $line . "\n";
            }
        }

        $queries = array_filter(array_map('trim', explode(";\n", $sql)));

        DB::beginTransaction();
        try {
            foreach ($queries as $query) {
                if (!empty($query)) {
                    DB::statement($query);
                }
            }
            DB::commit();
            return redirect()->route('backup.index')
                ->with('success', 'Database berhasil direstore!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Restore gagal: ' . $e->getMessage());
        }
    }
}