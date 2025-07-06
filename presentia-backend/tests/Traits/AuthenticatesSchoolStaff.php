<?php

namespace Tests\Traits;

use App\Models\School;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

trait AuthenticatesSchoolStaff
{
    use UsesRefreshDatabase; 
    
    protected $schoolStaffToken; 
    protected $schoolStaffUser;  
    protected $schoolForStaff;   
    
    protected function setUp(): void
    {
        parent::setUp(); 
        
        $schoolStaffRole = Role::firstOrCreate(['name' => 'school_staff', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']); // Juga perlu ada
        Role::firstOrCreate(['name' => 'school_admin', 'guard_name' => 'web']); // Juga perlu ada
        
        Permission::firstOrCreate(['name' => 'basic_school', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage_students', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage_schools', 'guard_name' => 'web']); 
        Permission::firstOrCreate(['name' => 'manage_attendance', 'guard_name' => 'web']);

        $schoolStaffRole->givePermissionTo([
            'basic_school', 'manage_students', 'manage_schools', 'manage_attendance',
        ]);

        $this->schoolForStaff = School::factory()->create();

        $this->defaultSubscriptionPlan = SubscriptionPlan::factory()->create([
            'billing_cycle_month' => 6,
            'price' => 84,
            'subscription_name' => 'Default Plan for Staff Tests'
        ]);

        $this->schoolForStaff->update([
            'subscription_plan_id' => $this->defaultSubscriptionPlan->id, 
            'latest_subscription' => Carbon::now()->subMonths(1)->format('Y-m-d H:i:s'),
            'timezone' => 'Asia/Jakarta'
        ]);

        $this->schoolStaffUser = User::factory()->create([
            'password' => bcrypt('password123'),
            'school_id' => $this->schoolForStaff->id,
            'email_verified_at' => now(),
        ]);
        $this->schoolStaffUser->assignRole('school_staff');

        $response = $this->postJson('api/login', [
            'email_or_username' => $this->schoolStaffUser->email,
            'password' => 'password123',
        ]);
        $this->schoolStaffToken = $response->json('token');

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->schoolStaffToken,
            'School-Id' => $this->schoolForStaff->id,
        ]);
    }
}