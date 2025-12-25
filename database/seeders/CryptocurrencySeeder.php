<?php

namespace Database\Seeders;

use App\Models\Cryptocurrency;
use Illuminate\Database\Seeder;

class CryptocurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cryptocurrencies = [
            [
                'name' => 'Tether USD (BEP20)',
                'symbol' => 'USDT-BEP20',
                'network' => 'BSC',
                'contract_address' => '0x55d398326f99059ff775485246999027b3197955',
                'decimal_places' => 18,
                'min_withdrawal' => 1,
                'max_withdrawal' => 50000,
                'withdrawal_fee' => 1,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Tether USD (TRC20)',
                'symbol' => 'USDT-TRC20',
                'network' => 'TRON',
                'contract_address' => 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t',
                'decimal_places' => 6,
                'min_withdrawal' => 1,
                'max_withdrawal' => 50000,
                'withdrawal_fee' => 1,
                'is_active' => true,
                'sort_order' => 2,
            ],
        ];

        foreach ($cryptocurrencies as $crypto) {
            Cryptocurrency::updateOrCreate(
                ['symbol' => $crypto['symbol']],
                $crypto
            );
        }
    }
}