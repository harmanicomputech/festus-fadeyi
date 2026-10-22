<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

#[Signature('ff85:create-admin {email} {--name=} {--role=admin : admin (full dashboard) or checkin (welcome desk only)}')]
#[Description('Create or update a dashboard user. The password is prompted for, never passed on the command line.')]
class CreateAdmin extends Command
{
    public function handle(): int
    {
        $email = strtolower($this->argument('email'));
        $role = $this->option('role');
        $password = $this->secret('Password (min 12 characters, mixed case and a number)');

        $v = Validator::make(compact('email', 'role', 'password'), [
            'email' => ['required', 'email'],
            'role' => ['required', 'in:admin,checkin'],
            'password' => ['required', Password::defaults()],
        ]);
        if ($v->fails()) {
            foreach ($v->errors()->all() as $e) {
                $this->error($e);
            }

            return self::FAILURE;
        }

        $user = User::query()->updateOrCreate(['email' => $email], [
            'name' => $this->option('name') ?: $email,
            'password' => $password,
            'role' => $role,
        ]);

        $this->info("{$user->email} saved with role '{$user->role}'.");

        return self::SUCCESS;
    }
}
