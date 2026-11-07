<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class BootstrapProductionAdmin extends Command
{
    protected $signature = 'bgs:bootstrap-admin {email} {name}';
    protected $description = 'Create the first administrator only on an empty users table, with a hidden interactive password';

    public function handle(): int
    {
        if ($this->option('no-interaction') || !$this->input->isInteractive()) {
            $this->error('Interactive terminal required; passwords cannot be supplied as arguments.');
            return 1;
        }
        $data = ['email' => $this->argument('email'), 'name' => $this->argument('name')];
        if (Validator::make($data, ['email' => 'required|email|max:255', 'name' => 'required|string|max:255'])->fails()
            || DB::connection()->getDriverName() !== 'mysql' || User::query()->exists()) {
            $this->error('Bootstrap refused: valid identity, MySQL and an empty users table are required.');
            return 1;
        }
        $password = $this->secret('Password (minimum 8 characters)', false);
        $confirmation = $this->secret('Confirm password', false);
        if (!is_string($password) || mb_strlen($password) < 8 || $password !== $confirmation) {
            $this->error('Password must match and contain at least 8 characters.');
            return 1;
        }
        $lock = 'bgs-bootstrap-'.hash('sha256', DB::connection()->getDatabaseName());
        // MySQL lock names are limited to 64 characters.
        $lock = substr($lock, 0, 64);
        if ((int) DB::selectOne('SELECT GET_LOCK(?, 0) AS acquired', [$lock])->acquired !== 1) {
            $this->error('Another bootstrap is in progress.');
            return 1;
        }
        try {
            DB::transaction(function () use ($data, $password) {
                if (User::query()->exists()) {
                    throw new RuntimeException('Bootstrap refused: users already exist.');
                }
                $user = new User();
                $user->forceFill($data + ['password' => Hash::make($password), 'role' => 'admin'])->save();
                if ($user->hasRestrictedFullAccess()) {
                    throw new RuntimeException('Bootstrap refused: reserved legacy identity; review the ID policy.');
                }
            });
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());
            return 1;
        } finally {
            DB::selectOne('SELECT RELEASE_LOCK(?) AS released', [$lock]);
        }
        $this->info('First administrator created. No existing account was modified.');
        return 0;
    }
}
