<?php

namespace App\Modules\Shared\Console\Commands;

use App\Modules\Shared\Models\TechProfile;
use App\Modules\Shared\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateSuperUserCommand extends Command
{
    protected $signature = 'app:create-superuser';

    protected $description = 'Create a SuperUser account (CLI only, SPEC.md §6.9)';

    public function handle(): int
    {
        $fullName = $this->ask('Full name');
        $email    = $this->ask('Email');
        $password = $this->secret('Password');

        if (! $fullName || ! $email || ! $password) {
            $this->error('All fields are required.');
            return self::FAILURE;
        }

        $existing = User::withTrashed()->where('email', $email)->first();

        if ($existing) {
            if (! $this->confirm("A user with email [{$email}] already exists. Overwrite?")) {
                $this->info('Aborted.');
                return self::SUCCESS;
            }

            DB::transaction(function () use ($existing, $fullName, $password) {
                $existing->forceFill([
                    'full_name'    => $fullName,
                    'password'     => $password,
                    'is_super_user' => true,
                    'is_tech'      => true,
                    'deleted_at'   => null,
                ])->save();

                if (! $existing->techProfile()->exists()) {
                    TechProfile::create([
                        'user_id'     => $existing->id,
                        'promoted_by' => $existing->id,
                        'promoted_at' => now(),
                    ]);
                }
            });

            $this->info("SuperUser updated: {$email}");
            return self::SUCCESS;
        }

        DB::transaction(function () use ($fullName, $email, $password) {
            $user = User::create([
                'full_name'    => $fullName,
                'email'        => $email,
                'password'     => $password,
                'is_super_user' => true,
                'is_tech'      => true,
                'locale'       => 'ar',
            ]);

            TechProfile::create([
                'user_id'     => $user->id,
                'promoted_by' => $user->id,
                'promoted_at' => now(),
            ]);
        });

        $this->info("SuperUser created: {$email}");
        return self::SUCCESS;
    }
}
