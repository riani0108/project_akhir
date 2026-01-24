<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pelanggan;
use Illuminate\Support\Facades\Hash;

class PelangganSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        Pelanggan::create([
            'nama_pelanggan' => 'Administrator',
            'email' => 'admin@tower.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'is_active' => true,
            'permissions' => ['*'] // Semua permission
        ]);

        // Operator
        Pelanggan::create([
            'nama_pelanggan' => 'Operator 1',
            'email' => 'operator@tower.com',
            'password' => Hash::make('operator123'),
            'role' => 'operator',
            'is_active' => true,
            'permissions' => ['view_towers', 'add_towers', 'edit_towers']
        ]);

        // Pelanggan biasa
        Pelanggan::create([
            'nama_pelanggan' => 'Pelanggan Demo',
            'email' => 'pelanggan@tower.com',
            'password' => Hash::make('pelanggan123'),
            'role' => 'pelanggan',
            'is_active' => true,
            'permissions' => ['view_towers']
        ]);

        // Tambahkan halaman default untuk setiap role
        $this->createDefaultPages();
    }

    private function createDefaultPages()
    {
        // Halaman untuk admin
        $admin = Pelanggan::where('email', 'admin@tower.com')->first();
        $admin->pages()->create([
            'page_name' => 'Dashboard Admin',
            'page_url' => '/admin/dashboard',
            'is_default' => true,
            'order' => 1,
            'page_settings' => ['theme' => 'dark', 'show_sidebar' => true]
        ]);

        $admin->pages()->create([
            'page_name' => 'Kelola User',
            'page_url' => '/admin/users',
            'order' => 2
        ]);

        // Halaman untuk operator
        $operator = Pelanggan::where('email', 'operator@tower.com')->first();
        $operator->pages()->create([
            'page_name' => 'Dashboard Operator',
            'page_url' => '/operator/dashboard',
            'is_default' => true,
            'order' => 1,
            'page_settings' => ['theme' => 'light']
        ]);

        // Halaman untuk pelanggan
        $pelanggan = Pelanggan::where('email', 'pelanggan@tower.com')->first();
        $pelanggan->pages()->create([
            'page_name' => 'Dashboard Saya',
            'page_url' => '/pelanggan/dashboard',
            'is_default' => true,
            'order' => 1
        ]);
    }
}