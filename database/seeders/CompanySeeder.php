<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $companies = [
            [
                'name' => 'Acme Corp',
                'email' => 'contact@acme.com',
                'phone' => '+1-202-555-0123',
                'address' => '123 Acme Street, Springfield, USA',
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'name' => 'Globex Inc',
                'email' => 'info@globex.com',
                'phone' => '+1-202-555-0456',
                'address' => '456 Globex Avenue, Metropolis, USA',
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'name' => 'Initech',
                'email' => 'hello@initech.com',
                'phone' => '+1-202-555-0789',
                'address' => '789 Initech Blvd, Silicon Valley, USA',
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'name' => 'Umbrella Corp',
                'email' => 'support@umbrella.com',
                'phone' => '+1-202-555-1011',
                'address' => '101 Umbrella Road, Raccoon City, USA',
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'name' => 'Wayne Enterprises',
                'email' => 'contact@wayneenterprises.com',
                'phone' => '+1-202-555-1213',
                'address' => '1007 Mountain Drive, Gotham City, USA',
                'created_at' => $now,
                'updated_at' => $now
            ],
        ];

        DB::table('companies')->insert($companies);
    }
}
