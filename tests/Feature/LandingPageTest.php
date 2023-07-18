<?php

namespace Tests\Feature;

use Tests\TestCase;

class LandingPageTest extends TestCase
{

    /**
     * A basic functional test example.
     *
     * @return void
     */
    public function testUnauthenticatedUsersAreRedirectedToLoginForm()
    {
        $response = $this->call('GET', '/');

        $this->assertEquals(302, $response->getStatusCode());
    }

}
