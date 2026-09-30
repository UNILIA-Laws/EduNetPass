<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeAdmin extends Command
{
    protected $signature = 'eduroam:make-admin {email} {--name=ICT Admin}';
    protected $description = 'Create (or reset the password of) a portal login';

    public function handle(): int
    {
        $password = $this->secret('Password (min 10 characters)');
        if (strlen((string) $password) < 10) {
            $this->error('Password too short.');
            return self::FAILURE;
        }

        User::updateOrCreate(
            ['email' => $this->argument('email')],
            ['name' => $this->option('name'), 'password' => $password]
        );

        $this->info('Done. You can now log in as ' . $this->argument('email'));
        return self::SUCCESS;
    }
}
