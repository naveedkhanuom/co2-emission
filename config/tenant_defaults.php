<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Developer account
    |--------------------------------------------------------------------------
    |
    | A standing account created inside every client workspace, so the people
    | who build and support the platform can sign in to one directly.
    |
    | Read this before enabling it in production. The account exists in EVERY
    | client's database with the SAME credentials, which means:
    |
    |   - Handing a client their database — the thing this whole architecture
    |     is for — hands them the password hash of an account that opens every
    |     other client's workspace too.
    |   - One leaked or brute-forced password is not one breach, it is all of
    |     them at once.
    |   - It appears in each client's own user list, because hiding it would
    |     be worse: a support account nobody can see is one nobody can audit.
    |
    | The password is an environment value rather than a literal here, so it
    | is not committed and can differ per environment. Set a strong, unique
    | one anywhere real, or turn the account off entirely and reach client
    | workspaces through audited impersonation from the back-office instead.
    |
    */

    'developer_account' => [
        /*
         * Defaults to FALSE, and must stay that way.
         *
         * This was `true`, which meant a deployment that never set the key got
         * a standing account owner in every client database. A security control
         * whose default is "off" fails by leaving someone locked out; one whose
         * default is "on" fails by silently granting access to every client on
         * the platform, and nothing about the deployment would look wrong.
         *
         * Turning it on is a deliberate act that has to be written down in an
         * .env. Forgetting to is now the safe outcome.
         */
        'enabled' => env('TENANT_DEV_ACCOUNT_ENABLED', false),

        'name' => env('TENANT_DEV_ACCOUNT_NAME', 'Developer'),

        'email' => env('TENANT_DEV_ACCOUNT_EMAIL'),

        'password' => env('TENANT_DEV_ACCOUNT_PASSWORD'),

        /*
         * Account owner, so the developer sees every company inside the
         * client's account rather than being stuck in whichever one they
         * happen to be attached to.
         */
        'role' => env('TENANT_DEV_ACCOUNT_ROLE', 'Super Admin'),
    ],

];
