<?php

namespace Tests\Traits;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar; // Import Spatie PermissionRegistrar

trait UsesRefreshDatabase
{
    use RefreshDatabase;

    /**
     * Perform any traits setup.
     * This method is called by Laravel's TestCase.
     * It's a good place to clear Spatie Permission cache.
     */
    protected function setUpTraits()
    {
        parent::setUpTraits();
        
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}