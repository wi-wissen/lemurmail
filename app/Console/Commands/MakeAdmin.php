<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class MakeAdmin extends Command
{
    protected $signature = 'lemurmail:make-admin {email} {--password= : Password (asked for when omitted)}';

    protected $description = 'Create a verified, active super admin';

    public function handle(): int
    {
        $email = strtolower(trim($this->argument('email')));

        if (User::where('email', $email)->exists()) {
            $this->error(__('auth.cli_admin_exists', ['email' => $email]));

            return self::FAILURE;
        }

        $password = $this->option('password');

        if ($password === null) {
            if (! $this->input->isInteractive()) {
                $this->error(__('auth.cli_password_required'));

                return self::FAILURE;
            }

            $password = $this->secret(__('auth.cli_password_prompt'));
        }

        $validator = Validator::make(
            ['email' => $email, 'password' => $password],
            ['email' => ['required', 'email'], 'password' => ['required', Password::defaults()]],
        );

        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }

        User::create([
            'name' => strstr($email, '@', true),
            'email' => $email,
            'password' => $password,
            'role' => User::ROLE_SUPER_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ])->markEmailAsVerified();

        $this->info(__('auth.cli_admin_created', ['email' => $email]));

        return self::SUCCESS;
    }
}
