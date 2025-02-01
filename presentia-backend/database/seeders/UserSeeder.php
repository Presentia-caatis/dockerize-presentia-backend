<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run()
    {
        for($i = 1; $i <= 6; $i++){
            User::create([
                'school_id' => 1,
                'email' => "presentia{$i}@gmail.com",
                'fullname' => "Presentia {$i} Official Account",
                'username' => "presentia{$i}",
                'password' => bcrypt('12345678')
            ]);
        }
        
    }
}
