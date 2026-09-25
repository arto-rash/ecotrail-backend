<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

#[Signature('admin:create {--email=} {--name=}')]
#[Description('Create (or promote) an admin user for the /admin panel')]
class CreateAdmin extends Command
{
    public function handle(): int
    {
        $email = $this->option('email') ?: text('Email', required: true, validate: fn ($v) => filter_var($v, FILTER_VALIDATE_EMAIL) ? null : 'Enter a valid email.');
        $user = User::firstWhere('email', $email);

        if ($user) {
            $user->update(['is_admin' => true]);
            $this->info("{$email} is now an admin.");

            return self::SUCCESS;
        }

        $name = $this->option('name') ?: text('Name', required: true);
        $pass = password('Password (min 12 characters)', required: true, validate: fn ($v) => strlen($v) >= 12 ? null : 'Use at least 12 characters.');

        User::create(['name' => $name, 'email' => $email, 'password' => $pass, 'is_admin' => true]);
        $this->info("Admin {$email} created. Sign in at ".rtrim(config('app.url'), '/').'/admin');

        return self::SUCCESS;
    }
}
