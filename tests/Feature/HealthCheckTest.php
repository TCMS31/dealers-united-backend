<?php

namespace Tests\Feature;

use App\Providers\RouteServiceProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    #[Test]
    public function the_root_route_describes_the_service(): void
    {
        $this->getJson('/')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('api', '/'.RouteServiceProvider::API_PREFIX);
    }
}
