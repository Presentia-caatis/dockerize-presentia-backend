<?php

namespace Tests;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

trait TestCaseHelpers
{
    use RefreshDatabase;

    protected $token;
    protected $authUser;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $school = School::factory()->create();

        $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'school_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'school_staff', 'guard_name' => 'web']);

        Permission::firstOrCreate(['name' => 'manage_school_users', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'basic_school', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage_students', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage_schools', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage_attendance', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'assign_roles', 'guard_name' => 'web']);

        $superAdminRole = Role::findByName('super_admin', 'web');

        $superAdminRole->givePermissionTo([
            'manage_school_users',
            'basic_school',
            'manage_students',
            'manage_schools',
            'manage_attendance',
            'assign_roles', 
        ]);

        $schoolAdminRole = Role::findByName('school_admin', 'web');
        $schoolAdminRole->givePermissionTo('basic_school', 'manage_schools'); 

        $this->authUser = User::factory()->create([
            'password' => bcrypt('password123'),
            'school_id' => $school->id,
            'email_verified_at' => now(),
        ]);
        $this->authUser->assignRole('super_admin');

        $response = $this->postJson('api/login', [
            'email_or_username' => $this->authUser->email,
            'password' => 'password123',  
        ]);

        $this->token = $response->json('token');

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ]);
        
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}