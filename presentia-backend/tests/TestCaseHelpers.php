<?php

namespace Tests;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

trait TestCaseHelpers
{
    use RefreshDatabase;

    protected $token;
    protected $authUser;

    protected function setUp(): void
    {
        parent::setUp();

        $school = School::factory()->create();

        $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage_school_users', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'basic_school', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage_students', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage_schools', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage_attendance', 'guard_name' => 'web']);

        $role->givePermissionTo('manage_school_users');
        $role->givePermissionTo('basic_school');
        $role->givePermissionTo('manage_students');
        $role->givePermissionTo('manage_schools');
        $role->givePermissionTo('manage_attendance');

        $this->authUser = User::factory()->create([
            'password' => bcrypt('123'),
            'school_id' => $school->id
        ]);
        $this->authUser->assignRole(Role::findByName('super_admin', 'web'));

        $response = $this->postJson('api/login', [
            'email_or_username' => $this->authUser->email,
            'password' => '123',  
        ]);

        $this->token = $response->json('token');

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ]);
    }
}