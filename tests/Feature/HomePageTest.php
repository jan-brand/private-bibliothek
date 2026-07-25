<?php

namespace Tests\Feature;

use Tests\TestCase;

final class HomePageTest extends TestCase
{
    public function test_home_page_is_available(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('MiniBib ist bereit.')
            ->assertSeeLivewire('system-status');
    }
}
