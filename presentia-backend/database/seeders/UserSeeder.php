<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run()
    {
        $dummyAccounts = [];

        for ($i = 0; $i < 10; $i++) {
            $password = Str::random(8); 

            $dummyAccounts[] = [
                'username' => "presentia_dummy_$i",
                'email' => "presentia_dummy_$i@gmail.com",
                'fullname' => "Presentia Dummy Account $i",
                'password' => Hash::make($password),
                'google_id' => "presentia_dummy",
                'created_at' => now(),
                'updated_at' => now(),
            ];
            echo "presentia_dummy_$i: $password\n";
        }
        User::insert($dummyAccounts);
        
    }
}
