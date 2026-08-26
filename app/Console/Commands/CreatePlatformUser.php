<?php

namespace App\Console\Commands;

use App\Models\PlatformUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Creates a back-office account for a member of our staff.
 *
 * The only way to make one. There is no registration route, because an
 * account here can reach every client on the platform — that is not something
 * a web form should be able to mint.
 */
class CreatePlatformUser extends Command
{
    protected $signature = 'platform:user
        {email : The email this person signs in with}
        {--name= : Their full name}
        {--password= : Their password. Generated and shown once if omitted}';

    protected $description = 'Create a back-office account for platform staff';

    public function handle(): int
    {
        $email = Str::lower(trim((string) $this->argument('email')));
        $name = (string) ($this->option('name') ?: Str::headline(Str::before($email, '@')));

        $validator = Validator::make(
            ['email' => $email],
            ['email' => ['required', 'email', 'unique:'.$this->qualifiedTable().',email']]
        );

        if ($validator->fails()) {
            $this->components->error((string) $validator->errors()->first('email'));

            return self::FAILURE;
        }

        $password = (string) ($this->option('password') ?: Str::password(16));
        $generated = ! $this->option('password');

        PlatformUser::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'is_active' => true,
        ]);

        $this->components->info("Back-office account created for {$name}.");
        $this->table(['', ''], array_filter([
            ['Sign in at', rtrim((string) config('app.url'), '/').'/admin/login'],
            ['Email', $email],
            $generated ? ['Password', $password] : null,
        ]));

        if ($generated) {
            $this->components->warn('This password is shown once and is not stored anywhere.');
        }

        return self::SUCCESS;
    }

    /**
     * The unique rule needs the table on the CENTRAL connection, which is
     * where PlatformUser lives regardless of the caller's context.
     *
     * Not named table(): Illuminate\Console\Command already has a public
     * table() for rendering one, and shadowing it is a fatal error.
     */
    protected function qualifiedTable(): string
    {
        $connection = config('tenancy.database.central_connection');

        return $connection.'.'.(new PlatformUser)->getTable();
    }
}
