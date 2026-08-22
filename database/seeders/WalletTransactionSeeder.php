<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class WalletTransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        $parties = DB::table('parties')->pluck('id')->toArray();
        $users = DB::table('users')->pluck('id')->toArray();

        if (empty($parties)) {
            return;
        }

        $transactions = [];

        foreach (array_slice($parties, 0, 10) as $partyId) {
            $numTxns = rand(1, 5);
            $balance = 0;

            for ($i = 0; $i < $numTxns; $i++) {
                $type = $faker->randomElement(['credit', 'debit']);
                $amount = rand(100, 1000);

                // Prevent negative balance
                if ($type === 'debit' && $balance < $amount) {
                    $type = 'credit';
                }

                if ($type === 'credit') {
                    $balance += $amount;
                    $desc = 'Wallet funded';
                } else {
                    $balance -= $amount;
                    $desc = 'Payment for order';
                }

                $transactions[] = [
                    'party_id' => $partyId,
                    'amount' => $amount,
                    'type' => $type,
                    'reference_type' => $type === 'debit' ? 'order' : 'deposit',
                    'reference_id' => $faker->optional()->numberBetween(1, 100),
                    'description' => $desc,
                    'created_by' => !empty($users) ? $users[0] : null,
                    'created_at' => now()->subDays(rand(1, 30)),
                    'updated_at' => now(),
                ];
            }
            
            // Note: In real app, we would also update the party's wallet_balance if that column exists
            // But since we just want test data in wallet_transactions table, this is fine
        }

        DB::table('wallet_transactions')->insert($transactions);
    }
}
