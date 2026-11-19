<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * @var array<int, array{name: string, type: 'income'|'expense', color: string}>
     */
    private const DEFAULT_CATEGORIES = [
        ['name' => 'Salário', 'type' => 'income', 'color' => 'emerald'],
        ['name' => 'Serviços', 'type' => 'income', 'color' => 'sky'],
        ['name' => 'Vendas', 'type' => 'income', 'color' => 'violet'],
        ['name' => 'Outros', 'type' => 'income', 'color' => 'teal'],
        ['name' => 'Moradia', 'type' => 'expense', 'color' => 'amber'],
        ['name' => 'Alimentação', 'type' => 'expense', 'color' => 'orange'],
        ['name' => 'Educação', 'type' => 'expense', 'color' => 'indigo'],
        ['name' => 'Impostos', 'type' => 'expense', 'color' => 'slate'],
        ['name' => 'Transporte', 'type' => 'expense', 'color' => 'blue'],
        ['name' => 'Saúde', 'type' => 'expense', 'color' => 'rose'],
        ['name' => 'Compras', 'type' => 'expense', 'color' => 'pink'],
        ['name' => 'Lazer', 'type' => 'expense', 'color' => 'purple'],
        ['name' => 'Assinaturas', 'type' => 'expense', 'color' => 'slate'],
        ['name' => 'Outros', 'type' => 'expense', 'color' => 'zinc'],
    ];

    public function run(): void
    {
        User::query()->each(static fn (User $user) => self::seedFor($user));
    }

    public static function seedFor(User $user): void
    {
        foreach (self::DEFAULT_CATEGORIES as $category) {
            $user->categories()->firstOrCreate(
                ['name' => $category['name'], 'type' => $category['type']],
                ['color' => $category['color']],
            );
        }
    }
}
