<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CreateCustomFilamentUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:custom-filament-user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->ask('Name');
        $email = $this->ask('Email');
        $password = bcrypt($this->secret('Password'));
        $phone = $this->ask('Phone');
        $role = $this->choice('Role', ['admin', 'normal'], 0);

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'phone' => $phone,
            'role' => $role,
        ]);

        $this->info("✅ User $name created successfully!");
    }
}
