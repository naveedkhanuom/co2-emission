<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DefaultUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Creating Super Admin User
        $superAdmin = User::create([
            'name' => 'Naveed khan', 
            'email' => 'engr.naveedkhan3@gmail.com',
            'password' => Hash::make('secret@123')
        ]);
        $superAdmin->assignRole('Super Admin');

        // Creating Admin User
        $admin = User::create([
            'name' => 'fawad', 
            'email' => 'fawad@gmail.com',
            'password' => Hash::make('secret@123')
        ]);
        $admin->assignRole('Admin');

        // Creating Product Manager User
        $productManager = User::create([
            'name' => 'zaheer', 
            'email' => 'zaheer@gmail.com',
            'password' => Hash::make('secret@123')
        ]);
        $productManager->assignRole('Product Manager');

        // Creating Application User
        $user = User::create([
            'name' => 'waqer', 
            'email' => 'waqer@gmail.com',
            'password' => Hash::make('secret@123')
        ]);
        $user->assignRole('User');
    }
}