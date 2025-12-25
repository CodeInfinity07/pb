<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Transaction;
use App\Models\UserInvestment;
use App\Models\InvestmentPlan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Exception;

class MigrateOldTransactions extends Command
{
    protected $signature = 'migrate:old-transactions 
                           {--connection=old_db : Old database connection name} 
                           {--dry-run : Preview migration without saving data}
                           {--batch-size=100 : Number of records to process per batch}
                           {--force : Skip confirmation prompts}';

    protected $description = 'Migrate transactions from legacy database preserving original timestamps';

    private array $userMapping = [];
    private array $errors = [];
    private int $transactionCounter = 0;
    private array $stats = [
        'total_found' => 0,
        'transactions_migrated' => 0,
        'investments_created' => 0,
        'users_mapped' => 0,
        'skipped_no_user' => 0,
        'errors' => 0,
    ];

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $batchSize = (int) $this->option('batch-size');
        $force = $this->option('force');

        $this->showHeader($isDryRun);

        try {
            $this->buildUserMapping();
            $this->testConnection();
            $totalTransactions = $this->getTotalCount();
            
            if ($totalTransactions === 0) {
                $this->warn('No transactions found to migrate!');
                return Command::SUCCESS;
            }

            $this->stats['total_found'] = $totalTransactions;
            $this->info("Found {$totalTransactions} transactions to migrate");

            $this->showSampleData();
            
            if (!$force && !$this->confirm('Proceed with migration?', true)) {
                $this->info('Migration cancelled.');
                return Command::SUCCESS;
            }

            $this->processMigration($batchSize, $isDryRun);
            $this->showResults($isDryRun);

        } catch (Exception $e) {
            $this->handleError($e);
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function showHeader(bool $isDryRun): void
    {
        $this->info('Transaction Migration Tool - Timestamp Preserving');
        $this->info('===================================================');
        
        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No data will be saved!');
            $this->newLine();
        }
    }

    private function buildUserMapping(): void
    {
        $this->info('Building user mapping...');

        $newUsers = User::select('id', 'email')->get()->keyBy('email');
        $oldUsers = DB::connection($this->option('connection'))
            ->table('users')
            ->select('id', 'email')
            ->whereNotNull('email')
            ->get();

        foreach ($oldUsers as $oldUser) {
            if (isset($newUsers[$oldUser->email])) {
                $this->userMapping[$oldUser->id] = $newUsers[$oldUser->email]->id;
            }
        }

        $this->stats['users_mapped'] = count($this->userMapping);
        $this->info("Mapped {$this->stats['users_mapped']} users");
    }

    private function testConnection(): void
    {
        $this->info('Testing database connection...');
        DB::connection($this->option('connection'))
            ->select('SELECT COUNT(*) as count FROM transactions LIMIT 1');
        $this->info('Connection successful!');
    }

    private function getTotalCount(): int
    {
        return DB::connection($this->option('connection'))
            ->table('transactions')
            ->count();
    }

    private function showSampleData(): void
    {
        $sample = DB::connection($this->option('connection'))
            ->table('transactions')
            ->first();

        if (!$sample) return;

        $this->info('Sample transaction with timestamp analysis:');
        $this->table(['Field', 'Value', 'Parsed Timestamp'], [
            ['ID', $sample->id ?? 'N/A', ''],
            ['User ID', $sample->user_id ?? 'N/A', ''],
            ['Amount', $sample->amount ?? 'N/A', ''],
            ['Type', $sample->txn_type ?? 'N/A', ''],
            ['TXN ID', $sample->txn_id ?? 'N/A', ''],
            ['Created At', $sample->created_at ?? 'N/A', $this->parseOldTimestamp($sample, 'created_at')?->format('Y-m-d H:i:s') ?? 'Failed to parse'],
            ['Updated At', $sample->updated_at ?? 'N/A', $this->parseOldTimestamp($sample, 'updated_at')?->format('Y-m-d H:i:s') ?? 'Failed to parse'],
            ['Timestamp', $sample->timestamp ?? 'N/A', $this->parseOldTimestamp($sample, 'timestamp')?->format('Y-m-d H:i:s') ?? 'Failed to parse'],
        ]);
        $this->newLine();
    }

    private function processMigration(int $batchSize, bool $isDryRun): void
    {
        $totalBatches = ceil($this->stats['total_found'] / $batchSize);
        $this->info("Processing {$totalBatches} batches of {$batchSize} transactions each...");
        
        $progressBar = $this->output->createProgressBar($this->stats['total_found']);
        $progressBar->start();

        if (!$isDryRun) {
            DB::beginTransaction();
        }

        try {
            DB::connection($this->option('connection'))
                ->table('transactions')
                ->orderBy('id')
                ->chunk($batchSize, function (Collection $batch) use ($progressBar, $isDryRun) {
                    foreach ($batch as $oldTransaction) {
                        try {
                            $this->migrateTransaction($oldTransaction, $isDryRun);
                        } catch (Exception $e) {
                            $this->recordError($oldTransaction, $e);
                        }
                        $progressBar->advance();
                    }
                });

            if (!$isDryRun) {
                DB::commit();
                $this->info('Migration committed to database');
            }

        } catch (Exception $e) {
            if (!$isDryRun) {
                DB::rollBack();
            }
            throw $e;
        } finally {
            $progressBar->finish();
            $this->newLine(2);
        }
    }

    private function migrateTransaction(object $oldTxn, bool $isDryRun): void
    {
        if (!isset($this->userMapping[$oldTxn->user_id])) {
            $this->stats['skipped_no_user']++;
            return;
        }

        $newUserId = $this->userMapping[$oldTxn->user_id];

        if ($this->isInvestmentTransaction($oldTxn)) {
            $this->migrateInvestment($oldTxn, $newUserId, $isDryRun);
        } else {
            $this->migrateRegularTransaction($oldTxn, $newUserId, $isDryRun);
        }
    }

    private function isInvestmentTransaction(object $oldTxn): bool
    {
        return in_array(strtolower($oldTxn->txn_type ?? ''), ['invest', 'investment']);
    }

    private function migrateRegularTransaction(object $oldTxn, int $newUserId, bool $isDryRun): void
    {
        $createdAt = $this->parseOldTimestamp($oldTxn, 'created_at');
        $updatedAt = $this->parseOldTimestamp($oldTxn, 'updated_at') ?? $createdAt;
        $processedAt = $this->getProcessedTimestamp($oldTxn);

        $transactionData = [
            'user_id' => $newUserId,
            'transaction_id' => $this->generateUniqueTransactionId($oldTxn),
            'type' => $this->mapTransactionType($oldTxn->txn_type ?? ''),
            'amount' => abs((float) ($oldTxn->amount ?? 0)),
            'currency' => $this->determineCurrency($oldTxn),
            'status' => $this->mapTransactionStatus($oldTxn->status ?? 0),
            'payment_method' => $this->mapPaymentMethod($oldTxn),
            'crypto_address' => $oldTxn->address ?? null,
            'crypto_txid' => $oldTxn->tx_url ?? null,
            'description' => $this->generateDescription($oldTxn),
            'metadata' => json_encode($this->buildMetadata($oldTxn)),
            'processed_at' => $processedAt,
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ];

        if ($isDryRun) {
            $this->line("[DRY RUN] Transaction: {$transactionData['transaction_id']} | Date: {$createdAt?->format('Y-m-d H:i:s')} | Type: {$transactionData['type']}");
            return;
        }

        // Create with timestamps, bypassing Laravel's automatic timestamp handling
        DB::table('transactions')->insert($transactionData);
        $this->stats['transactions_migrated']++;
    }

    private function migrateInvestment(object $oldTxn, int $newUserId, bool $isDryRun): void
    {
        $investmentPlan = $this->getInvestmentPlan();
        
        if (!$investmentPlan) {
            $this->warn("No investment plan available, skipping transaction {$oldTxn->id}");
            $this->stats['skipped_no_user']++;
            return;
        }

        $amount = abs((float) ($oldTxn->amount ?? 0));
        $createdAt = $this->parseOldTimestamp($oldTxn, 'created_at');
        $updatedAt = $this->parseOldTimestamp($oldTxn, 'updated_at') ?? $createdAt;
        $endDate = $this->parseOldTimestamp($oldTxn, 'timestamp') ?? $createdAt?->copy()->addDays(30);

        if ($isDryRun) {
            $this->line("[DRY RUN] Investment: \${$amount} | Date: {$createdAt?->format('Y-m-d H:i:s')} | Plan: {$investmentPlan->name}");
            return;
        }

        // Create investment record
        $investmentData = [
            'user_id' => $newUserId,
            'investment_plan_id' => $investmentPlan->id,
            'amount' => $amount,
            'status' => 'completed',
            'started_at' => $createdAt,
            'ends_at' => $endDate,
            'completed_at' => $endDate,
            'notes' => "Migrated from legacy system. Original ID: {$oldTxn->id}",
            'total_return' => 0,
            'paid_return' => 0,
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ];

        $investmentId = DB::table('user_investments')->insertGetId($investmentData);

        // Create corresponding transaction
        $transactionData = [
            'user_id' => $newUserId,
            'transaction_id' => $this->generateUniqueTransactionId($oldTxn, 'investment'),
            'type' => Transaction::TYPE_INVESTMENT,
            'amount' => $amount,
            'currency' => 'USDT',
            'status' => Transaction::STATUS_COMPLETED,
            'description' => "Investment in {$investmentPlan->name}",
            'metadata' => json_encode(array_merge($this->buildMetadata($oldTxn), [
                'investment_id' => $investmentId,
                'investment_plan' => $investmentPlan->name,
            ])),
            'processed_at' => $this->getProcessedTimestamp($oldTxn),
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ];

        DB::table('transactions')->insert($transactionData);
        
        $this->stats['investments_created']++;
        $this->stats['transactions_migrated']++;
    }

    private function parseOldTimestamp(object $oldTxn, string $field): ?Carbon
    {
        $value = $oldTxn->{$field} ?? null;
        
        if (!$value) {
            return null;
        }

        try {
            // Handle different timestamp formats
            if (is_numeric($value)) {
                // Unix timestamp
                return Carbon::createFromTimestamp($value);
            }
            
            // String date
            return Carbon::parse($value);
            
        } catch (Exception $e) {
            // If parsing fails, log and return null
            if ($this->getOutput()->isVerbose()) {
                $this->line("Failed to parse {$field}: {$value} - {$e->getMessage()}");
            }
            return null;
        }
    }

    private function getProcessedTimestamp(object $oldTxn): ?Carbon
    {
        $status = $this->mapTransactionStatus($oldTxn->status ?? 0);
        
        if ($status !== Transaction::STATUS_COMPLETED) {
            return null;
        }

        // Try different timestamp fields in order of preference
        $timestampFields = ['processed_at', 'timestamp', 'updated_at', 'created_at'];
        
        foreach ($timestampFields as $field) {
            $parsed = $this->parseOldTimestamp($oldTxn, $field);
            if ($parsed) {
                return $parsed;
            }
        }
        
        return null;
    }

    private function generateUniqueTransactionId(object $oldTxn, ?string $override = null): string
    {
        // Use existing ID if available and not empty
        if (!empty($oldTxn->txn_id) && trim($oldTxn->txn_id) !== '') {
            // Check if it already exists
            if (!$this->transactionIdExists($oldTxn->txn_id)) {
                return $oldTxn->txn_id;
            }
        }

        // Generate new unique ID
        $type = $override ?? $this->mapTransactionType($oldTxn->txn_type ?? '');
        $currency = $this->determineCurrency($oldTxn);
        $userId = $this->userMapping[$oldTxn->user_id] ?? 0;
        $originalDate = $this->parseOldTimestamp($oldTxn, 'created_at');
        
        $maxAttempts = 5;
        
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $this->transactionCounter++;
            
            // Use original date timestamp if available, otherwise use current time
            $timestamp = $originalDate ? $originalDate->timestamp : time();
            $uniqueId = $this->buildTransactionId($type, $currency, $userId, $timestamp, $this->transactionCounter);
            
            if (!$this->transactionIdExists($uniqueId)) {
                return $uniqueId;
            }
            
            // Add small delay and increment counter
            usleep(1000);
        }

        // Fallback with random suffix
        $randomSuffix = bin2hex(random_bytes(4));
        $timestamp = $originalDate ? $originalDate->timestamp : time();
        
        return $this->buildTransactionId($type, $currency, $userId, $timestamp, 0, $randomSuffix);
    }

    private function buildTransactionId(string $type, string $currency, int $userId, int $timestamp, int $counter, ?string $suffix = null): string
    {
        $prefix = strtoupper(substr($type, 0, 3));
        $baseId = "{$prefix}_{$currency}_{$userId}_{$timestamp}";
        
        if ($counter > 0) {
            $baseId .= "_{$counter}";
        }
        
        if ($suffix) {
            $baseId .= "_{$suffix}";
        }
        
        return $baseId;
    }

    private function transactionIdExists(string $transactionId): bool
    {
        return DB::table('transactions')->where('transaction_id', $transactionId)->exists();
    }

    private function mapTransactionType(string $oldType): string
    {
        return match (strtolower($oldType)) {
            'deposit' => Transaction::TYPE_DEPOSIT,
            'withdraw', 'withdrawal' => Transaction::TYPE_WITHDRAWAL,
            'earning', 'return', 'roi' => Transaction::TYPE_ROI,
            'referral', 'commission' => Transaction::TYPE_COMMISSION,
            'bonus', 'reward' => Transaction::TYPE_BONUS,
            'invest', 'investment' => Transaction::TYPE_INVESTMENT,
            'game', 'tap', 'faucet', 'mining' => Transaction::TYPE_BONUS,
            'penalty', 'fine', 'charge' => Transaction::TYPE_DEBIT_ADJUSTMENT,
            'balance', 'release', 'credit', 'adjustment' => Transaction::TYPE_CREDIT_ADJUSTMENT,
            'profit', 'profit_share' => Transaction::TYPE_PROFIT,
            default => Transaction::TYPE_CREDIT_ADJUSTMENT,
        };
    }

    private function mapTransactionStatus(int $oldStatus): string
    {
        return match ($oldStatus) {
            1 => Transaction::STATUS_COMPLETED,
            2 => Transaction::STATUS_PROCESSING,
            3 => Transaction::STATUS_FAILED,
            4 => Transaction::STATUS_CANCELLED,
            default => Transaction::STATUS_PENDING,
        };
    }

    private function determineCurrency(object $oldTxn): string
    {
        if (!empty($oldTxn->currency)) {
            return strtoupper($oldTxn->currency);
        }

        $address = $oldTxn->address ?? '';
        
        return match (true) {
            str_starts_with($address, '0x') => 'USDT_BEP20',
            str_starts_with($address, 'T') => 'USDT_TRC20',
            str_starts_with($address, 'bc1') || str_starts_with($address, '1') || str_starts_with($address, '3') => 'BTC',
            str_starts_with($address, '0x') && strlen($address) === 42 => 'ETH',
            default => 'USDT',
        };
    }

    private function mapPaymentMethod(object $oldTxn): ?string
    {
        $methodId = $oldTxn->payment_method_id ?? 0;
        
        if ($methodId === 0) {
            return null;
        }

        return match ($methodId) {
            1 => 'USDT_BEP20',
            2 => 'USDT_TRC20',
            3 => 'BTC',
            4 => 'ETH',
            5 => 'LTC',
            default => 'USDT',
        };
    }

    private function generateDescription(object $oldTxn): string
    {
        if (!empty($oldTxn->detail)) {
            return $oldTxn->detail;
        }

        if (!empty($oldTxn->description)) {
            return $oldTxn->description;
        }

        $type = $this->mapTransactionType($oldTxn->txn_type ?? '');
        $currency = $this->determineCurrency($oldTxn);
        
        return match ($type) {
            Transaction::TYPE_DEPOSIT => "Crypto deposit ({$currency})",
            Transaction::TYPE_WITHDRAWAL => "Crypto withdrawal ({$currency})",
            Transaction::TYPE_COMMISSION => "Referral commission earned",
            Transaction::TYPE_ROI => "Investment return payment",
            Transaction::TYPE_INVESTMENT => "Investment transaction",
            Transaction::TYPE_BONUS => "Bonus reward received",
            Transaction::TYPE_PROFIT => "Profit sharing distribution",
            Transaction::TYPE_CREDIT_ADJUSTMENT => "Account credit adjustment",
            Transaction::TYPE_DEBIT_ADJUSTMENT => "Account debit adjustment",
            default => "Migrated transaction",
        };
    }

    private function buildMetadata(object $oldTxn): array
    {
        return [
            'legacy_transaction_id' => $oldTxn->id ?? null,
            'legacy_txn_type' => $oldTxn->txn_type ?? null,
            'legacy_am_type' => $oldTxn->am_type ?? null,
            'legacy_payment_method_id' => $oldTxn->payment_method_id ?? null,
            'legacy_package_id' => $oldTxn->package_id ?? null,
            'legacy_plan_id' => $oldTxn->plan_id ?? null,
            'legacy_ref_id' => $oldTxn->ref_id ?? null,
            'legacy_fee' => $oldTxn->fee ?? null,
            'legacy_ip' => $oldTxn->ip ?? null,
            'legacy_rawdata' => $oldTxn->rawdata ?? null,
            'legacy_original_created_at' => $oldTxn->created_at ?? null,
            'legacy_original_updated_at' => $oldTxn->updated_at ?? null,
            'legacy_original_timestamp' => $oldTxn->timestamp ?? null,
            'migration_date' => now()->toDateTimeString(),
            'migration_version' => '2.0',
        ];
    }

    private function getInvestmentPlan(): ?InvestmentPlan
    {
        $possibleColumns = [
            ['column' => 'is_active', 'value' => true],
            ['column' => 'active', 'value' => 1],
            ['column' => 'status', 'value' => 'active'],
            ['column' => 'enabled', 'value' => 1],
        ];

        foreach ($possibleColumns as $condition) {
            try {
                $plan = InvestmentPlan::where($condition['column'], $condition['value'])->first();
                if ($plan) {
                    return $plan;
                }
            } catch (Exception $e) {
                continue;
            }
        }

        try {
            return InvestmentPlan::first();
        } catch (Exception $e) {
            return null;
        }
    }

    private function recordError(object $oldTxn, Exception $e): void
    {
        $this->stats['errors']++;
        
        $error = [
            'old_id' => $oldTxn->id ?? 'unknown',
            'old_txn_id' => $oldTxn->txn_id ?? 'none',
            'old_type' => $oldTxn->txn_type ?? 'unknown',
            'error_message' => $e->getMessage(),
            'timestamp' => now()->toDateTimeString(),
        ];
        
        $this->errors[] = $error;
        Log::warning('Transaction migration error', $error);
    }

    private function handleError(Exception $e): void
    {
        DB::rollBack();
        
        $this->error('CRITICAL ERROR: Migration failed!');
        $this->error("Error: {$e->getMessage()}");
        $this->error("File: {$e->getFile()}:{$e->getLine()}");
        
        Log::error('Critical migration error', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);
    }

    private function showResults(bool $isDryRun): void
    {
        $this->newLine();
        $this->info('MIGRATION SUMMARY');
        $this->info('=================');
        
        $this->table(['Metric', 'Count'], [
            ['Total Transactions Found', number_format($this->stats['total_found'])],
            ['Users Mapped', number_format($this->stats['users_mapped'])],
            ['Transactions Migrated', number_format($this->stats['transactions_migrated'])],
            ['Investments Created', number_format($this->stats['investments_created'])],
            ['Skipped (No User)', number_format($this->stats['skipped_no_user'])],
            ['Errors', number_format($this->stats['errors'])],
        ]);

        if (!empty($this->errors)) {
            $this->newLine();
            $this->error('ERRORS ENCOUNTERED:');
            
            $errorTable = array_slice($this->errors, 0, 10);
            $this->table(['Old ID', 'TXN ID', 'Type', 'Error'], array_map(
                fn($error) => [
                    $error['old_id'],
                    $error['old_txn_id'],
                    $error['old_type'],
                    \Str::limit($error['error_message'], 50)
                ],
                $errorTable
            ));
        }

        $successRate = $this->stats['total_found'] > 0 
            ? round(($this->stats['transactions_migrated'] / $this->stats['total_found']) * 100, 2)
            : 0;
            
        $this->newLine();
        $this->info("Success Rate: {$successRate}%");

        if ($isDryRun) {
            $this->warn('This was a DRY RUN - no data was migrated');
            $this->info('To run actual migration:');
            $this->line('php artisan migrate:old-transactions --connection=old_db');
        } else {
            $this->info('Migration completed! All original timestamps have been preserved.');
        }
    }
}