<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoEnvironmentSeeder extends Seeder
{
    public const ADMIN_EMAIL = 'admin@novacms.test';

    public const EDITOR_EMAIL = 'editor@novacms.test';

    public const OPERATOR_EMAIL = 'operator@novacms.test';

    public const PASSWORD = 'password';

    /**
     * Seed a ready-to-click demo environment.
     */
    public function run(): void
    {
        $this->call([
            DemoContentSeeder::class,
        ]);

        $accounts = [
            [
                'name' => 'NovaCMS Demo Admin',
                'email' => self::ADMIN_EMAIL,
                'role' => UserRole::ADMIN,
            ],
            [
                'name' => 'NovaCMS Demo Editor',
                'email' => self::EDITOR_EMAIL,
                'role' => UserRole::EDITOR,
            ],
            [
                'name' => 'NovaCMS Demo Operator',
                'email' => self::OPERATOR_EMAIL,
                'role' => UserRole::OPERATOR,
            ],
        ];

        foreach ($accounts as $account) {
            User::query()->updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => self::PASSWORD,
                    'role' => $account['role'],
                    'email_verified_at' => now(),
                ],
            );
        }
    }
}
