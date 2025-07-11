<?php

namespace Tests\Traits;

use App\Models\School;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

trait AuthenticatesSchoolAdmin
{
    use UsesRefreshDatabase; 

    protected $schoolAdminToken; 
    protected $schoolAdminUser;  
    protected $schoolForAdmin;  

    protected function setUp(): void
    {
        parent::setUp();

        $schoolAdminRole = Role::firstOrCreate(['name' => 'school_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']); 
       
        Permission::firstOrCreate(['name' => 'basic_school', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage_students', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage_schools', 'guard_name' => 'web']); 
        Permission::firstOrCreate(['name' => 'manage_attendance', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage_school_users', 'guard_name' => 'web']);

        $schoolAdminRole->givePermissionTo([
            'basic_school', 'manage_students', 'manage_schools', 'manage_attendance', 'manage_school_users'
        ]);

        $this->schoolForAdmin = School::factory()->create();

        $this->defaultSubscriptionPlan = SubscriptionPlan::factory()->create([
            'billing_cycle_month' => 6,
            'price' => 84,
            'subscription_name' => 'Default Plan for SchoolAdmin Tests'
        ]);
        $this->schoolForAdmin->update([
            'subscription_plan_id' => $this->defaultSubscriptionPlan->id, 
            'latest_subscription' => Carbon::now()->subMonths(1)->format('Y-m-d H:i:s'),
            'timezone' => 'Asia/Jakarta'
        ]);

        $this->schoolAdminUser = User::factory()->create([
            'password' => bcrypt('password123'),
            'school_id' => $this->schoolForAdmin->id,
            'email_verified_at' => now(),
        ]);
        $this->schoolAdminUser->assignRole('school_admin');

        config(['school.id' => $this->schoolForAdmin->id]); 
        config(['app.timezone' => 'Asia/Jakarta']);

        $response = $this->postJson('api/login', [
            'email_or_username' => $this->schoolAdminUser->email,
            'password' => 'password123',
        ]);
        $this->schoolAdminToken = $response->json('token');

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->schoolAdminToken,
            'School-Id' => $this->schoolForAdmin->id,
        ]);
    }
}