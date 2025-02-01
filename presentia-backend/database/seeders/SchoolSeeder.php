<?php

namespace Database\Seeders;

use App\Models\School;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Str;

class SchoolSeeder extends Seeder
{
    public function run()
    {
        School::create([
            'subscription_plan_id' => 1,
            'logo_image_path' => 'png/Logo-SMK-10-Bandung.png',
            'name' => 'SMKN 10 Bandung',
            'address' => 'JL. CIJAWURA HILIR NO. 339, Cijaura, Kec. Buahbatu, Kota Bandung, Jawa Barat',
            'latest_subscription' => now(),
            'end_subscription' => now()->addMonth(),
            'timezone' => 'Asia/Jakarta',
            'school_token' => Str::uuid(),
        ]);

        School::create([
            'subscription_plan_id' => 1,
            'logo_image_path' => '',
            'name' => 'SMKN Telkom',
            'address' => 'Jl. Radio Palasari Road, Citeureup, Kec. Dayeuhkolot, Kabupaten Bandung, Jawa Barat',
            'latest_subscription' => now(),
            'end_subscription' => now()->addMonth(),
            'timezone' => 'Asia/Jakarta',
            'school_token' => Str::uuid(),
        ]);
    }
}
