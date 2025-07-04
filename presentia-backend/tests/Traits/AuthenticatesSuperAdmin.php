<?php

namespace Tests\Traits;

use App\Http\Controllers\SubscriptionPlanController;
use App\Models\School;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

trait AuthenticatesSuperAdmin
{
    use UsesRefreshDatabase;

    protected $superAdminToken;
    protected $superAdminUser;
    protected $superAdminSchool;
    protected $defaultSubscriptionPlan;

    protected function setUp(): void
    {
        parent::setUp(); 

        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'school_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'school_staff', 'guard_name' => 'web']);
        
        Permission::firstOrCreate(['name' => 'manage_school_users', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'basic_school', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage_students', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage_schools', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage_attendance', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'assign_roles', 'guard_name' => 'web']);
        
        $superAdminRole = Role::findByName('super_admin', 'web');
        $allPermissions = Permission::pluck('name')->toArray();
        $superAdminRole->givePermissionTo($allPermissions);

        $this->superAdminSchool = School::factory()->create();
        $this->defaultSubscriptionPlan = SubscriptionPlan::factory()->create([
            'billing_cycle_month' => 6,
            'price' => 84,
            'subscription_name' => 'Default Plan for SuperAdmin Tests'
        ]);

        $this->superAdminSchool->update([
            'subscription_plan_id' => $this->defaultSubscriptionPlan->id, 
            'latest_subscription' => Carbon::now()->subMonths(1)->format('Y-m-d H:i:s'),
            'timezone' => 'Asia/Jakarta' 
        ]);

        $this->superAdminUser = User::factory()->create([
            'password' => bcrypt('password123'),
            'school_id' => $this->superAdminSchool->id, 
            'email_verified_at' => now(),
        ]);
        $this->superAdminUser->assignRole('super_admin');

        $response = $this->postJson('api/login', [
            'email_or_username' => $this->superAdminUser->email,
            'password' => 'password123',
        ]);
        $this->superAdminToken = $response->json('token');

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->superAdminToken,
        ]);
    }

    /**
     * Helper to set the School-Id header for the super_admin's requests.
     * Call this before making a request that needs a school context.
     *
     * @param int|null $schoolId
     * @return void
     */
    protected function actingAsSuperAdminWithSchool(int $schoolId = null): void
    {
        $headers = ['Authorization' => 'Bearer ' . $this->superAdminToken];
        if ($schoolId !== null) {
            $headers['School-Id'] = $schoolId; 
        }
        $this->withHeaders($headers);
    }
}