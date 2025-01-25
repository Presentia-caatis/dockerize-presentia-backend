<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    private $school_id;

    public function __construct($school_id)
    {
        $this->school_id = $school_id;
    }

    public function run()
    {
        User::create([
            'school_id' => $this->school_id,
            'email' => "presentia{$this->school_id}@gmail.com",
            'fullname' => "Presentia {$this->school_id} Official Account",
            'username' => "presentia{$this->school_id}",
            'password' => bcrypt('12345678')
        ]);
    }
}
