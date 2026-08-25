<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * The root route redirects to login. This is a multi-tenant B2B app with
     * self-registration disabled, so there is no public landing page — a guest
     * hitting `/` is sent to sign in.
     */
    public function test_the_root_route_sends_guests_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    /** A signed-in user is not bounced back to the login screen. */
    public function test_login_page_is_reachable(): void
    {
        $this->get(route('login'))->assertOk();
    }
}
