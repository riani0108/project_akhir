<?php
// config/user_pages.php

return [
    'roles' => [
        'admin' => [
            [
                'name' => 'Dashboard Admin',
                'url' => '/admin/dashboard',
                'icon' => 'fas fa-tachometer-alt'
            ],
            [
                'name' => 'Kelola User',
                'url' => '/admin/users',
                'icon' => 'fas fa-users'
            ],
            [
                'name' => 'Laporan Sistem',
                'url' => '/admin/reports',
                'icon' => 'fas fa-chart-bar'
            ]
        ],
        'operator' => [
            [
                'name' => 'Dashboard Operator',
                'url' => '/operator/dashboard',
                'icon' => 'fas fa-tachometer-alt'
            ],
            [
                'name' => 'Input Tower',
                'url' => '/operator/towers',
                'icon' => 'fas fa-broadcast-tower'
            ],
            [
                'name' => 'Monitoring',
                'url' => '/operator/monitoring',
                'icon' => 'fas fa-eye'
            ]
        ],
        'pelanggan' => [
            [
                'name' => 'Dashboard Saya',
                'url' => '/pelanggan/dashboard',
                'icon' => 'fas fa-home'
            ],
            [
                'name' => 'Lihat Tower',
                'url' => '/pelanggan/towers',
                'icon' => 'fas fa-map-marker-alt'
            ],
            [
                'name' => 'Status Layanan',
                'url' => '/pelanggan/services',
                'icon' => 'fas fa-wifi'
            ]
        ]
    ]
];
