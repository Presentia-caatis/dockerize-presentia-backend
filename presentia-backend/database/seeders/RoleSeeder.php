<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create roles
        $roles = [
            'super_admin',
            'school_admin',
            'school_coadmin',
            'school_staff',
            'guest'
        ];

        foreach ($roles as $role) {
            Role::create(["name" => $role]);
        }

        // Define permissions
        $permissions = [
            'manage_users',
            'manage_school_users',
            'manage_schools',
            'manage_students',
            'manage_attendance',
            'view_dashboard',
            'basic',
        ];

        foreach ($permissions as $perm) {
            Permission::create(["name" => $perm]);
        }

        // Assign permissions to roles
        $superAdmin = Role::findByName('super_admin');
        $schoolAdmin = Role::findByName('school_admin');
        $schoolCoadmin = Role::findByName('school_coadmin');
        $schoolStaff = Role::findByName('school_staff');
        $guest = Role::findByName('guest');

        $superAdmin->givePermissionTo(Permission::all());

        $schoolAdmin->givePermissionTo([
            'manage_school_users',
            'manage_schools',
            'manage_students',
            'manage_attendance',
            'view_dashboard',
            'basic'
        ]);

        $schoolCoadmin->givePermissionTo([
            'manage_schools',
            'manage_students',
            'manage_attendance',
            'view_dashboard',
            'basic'
        ]);

        $schoolStaff->givePermissionTo([
            'view_dashboard',
            'basic'
        ]);

        $guest->givePermissionTo([
            'basic'
        ]);
    }
}
