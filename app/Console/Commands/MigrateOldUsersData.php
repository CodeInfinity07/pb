<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MigrateOldUsersData extends Command
{
    protected $signature = 'migrate:old-users {--connection=old_db} {--dry-run}';
    protected $description = 'Migrate users from old database to new structure';

    private $userMapping = [];
    private $errors = [];
    private $stats = [
        'total' => 0,
        'migrated' => 0,
        'errors' => 0,
        'skipped' => 0,
    ];

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('╔════════════════════════════════════════╗');
            $this->warn('║      DRY RUN MODE - NO DATA SAVED     ║');
            $this->warn('╚════════════════════════════════════════╝');
            $this->newLine();
        }

        $this->info('Starting user migration...');

        try {
            // Test connection first
            $this->info('Testing database connection...');
            DB::connection($this->option('connection'))
                ->select('SELECT COUNT(*) as count FROM users LIMIT 1');
            $this->info('✓ Connection successful!');
            $this->newLine();

            // Get old users
            $oldUsers = DB::connection($this->option('connection'))
                ->table('users')
                ->orderBy('id')
                ->get();

            $this->stats['total'] = $oldUsers->count();
            $this->info("Found {$oldUsers->count()} users to migrate");
            $this->newLine();

            if ($oldUsers->isEmpty()) {
                $this->warn('No users found to migrate!');
                return 0;
            }

            // Show sample data
            $this->showSampleData($oldUsers->first());

            if (!$this->confirm('Do you want to proceed with the migration?', true)) {
                $this->info('Migration cancelled by user.');
                return 0;
            }

            $bar = $this->output->createProgressBar($oldUsers->count());
            $bar->start();

            DB::beginTransaction();

            foreach ($oldUsers as $oldUser) {
                try {
                    $this->migrateUser($oldUser, $dryRun);
                    $this->stats['migrated']++;
                } catch (\Exception $e) {
                    $this->stats['errors']++;
                    $this->errors[] = [
                        'old_id' => $oldUser->id,
                        'email' => $oldUser->email,
                        'username' => $oldUser->username,
                        'error' => $e->getMessage()
                    ];
                    $this->newLine();
                    $this->error("Error migrating user {$oldUser->email}: " . $e->getMessage());
                }
                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            // Second pass: Update sponsor relationships
            if (!empty($this->userMapping)) {
                $this->info('Updating sponsor relationships...');
                $this->updateSponsorRelationships($dryRun);
            }

            if ($dryRun) {
                DB::rollBack();
                $this->newLine();
                $this->warn('╔════════════════════════════════════════╗');
                $this->warn('║   DRY RUN - All changes rolled back    ║');
                $this->warn('╚════════════════════════════════════════╝');
            } else {
                DB::commit();
                $this->newLine();
                $this->info('╔════════════════════════════════════════╗');
                $this->info('║     Migration completed successfully   ║');
                $this->info('╚════════════════════════════════════════╝');
            }

            $this->displayResults($dryRun);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->newLine();
            $this->error('╔════════════════════════════════════════╗');
            $this->error('║         Migration failed!              ║');
            $this->error('╚════════════════════════════════════════╝');
            $this->error('Error: ' . $e->getMessage());
            $this->error('File: ' . $e->getFile() . ':' . $e->getLine());
            return 1;
        }

        return 0;
    }

    private function showSampleData($sample)
    {
        $this->info('Sample user data:');
        $this->table(
            ['Field', 'Value'],
            [
                ['ID', $sample->id],
                ['Full Name', $sample->fullname],
                ['Email', $sample->email],
                ['Username', $sample->username],
                ['Phone', $sample->phone],
                ['Status', $sample->status],
                ['Sponsor ID', $sample->sponsor],
                ['Level', $sample->level],
                ['Created At', $sample->created_at],
                ['Updated At', $sample->updated_at],
            ]
        );
        $this->newLine();
    }

    private function migrateUser($oldUser, $dryRun = false)
    {
        // Check if user already exists
        $existingUser = User::where('email', $oldUser->email)->first();
        if ($existingUser) {
            $this->stats['skipped']++;
            $this->userMapping[$oldUser->id] = $existingUser->id;
            return;
        }

        // Parse timestamps - CRITICAL: Parse before using
        $createdAt = $this->parseTimestamp($oldUser->created_at);
        $updatedAt = $this->parseTimestamp($oldUser->updated_at);

        // Split fullname
        $nameParts = $this->splitFullName($oldUser->fullname);

        // Map status
        $status = $this->mapStatus($oldUser->status);

        // Determine role
        $role = $oldUser->is_admin ? 'admin' : 'user';

        // Determine email_verified_at based on status
        $emailVerifiedAt = ($status === 'active') ? $createdAt : null;

        // Create new user - USE PARSED TIMESTAMPS
        $userData = [
            'first_name' => $nameParts['first_name'],
            'last_name' => $nameParts['last_name'],
            'email' => $oldUser->email,
            'username' => $oldUser->username,
            'password' => $oldUser->password,
            'phone' => null,
            'referral_code' => $oldUser->referralCode ?: $this->generateReferralCode(),
            'sponsor_id' => null,
            'status' => $status,
            'role' => $role,
            'user_level' => $this->mapLevel($oldUser->level),
            'total_invested' => 0,
            'total_earned' => $this->parseDecimal($oldUser->earnings),
            'email_verified_at' => $emailVerifiedAt,  // Use parsed timestamp
            'created_at' => $createdAt,               // Use parsed timestamp
            'updated_at' => $updatedAt,               // Use parsed timestamp
        ];

        if ($dryRun) {
            $this->line("\n[DRY RUN] Would create user: {$oldUser->email}");
            $this->line("  Created: {$createdAt->toDateTimeString()}");
            $this->line("  Updated: {$updatedAt->toDateTimeString()}");
            $this->line("  Email Verified: " . ($emailVerifiedAt ? $emailVerifiedAt->toDateTimeString() : 'NULL'));
            $this->userMapping[$oldUser->id] = 999999 + $oldUser->id;
            return;
        }

        // Create user WITHOUT timestamps (we'll set them manually)
        $newUser = new User($userData);
        $newUser->exists = false;
        
        // Manually set timestamps to bypass automatic timestamp handling
        $newUser->created_at = $createdAt;
        $newUser->updated_at = $updatedAt;
        
        // Save without updating timestamps
        $newUser->save(['timestamps' => false]);

        // Store mapping
        $this->userMapping[$oldUser->id] = $newUser->id;

        // Create user profile
        $this->createUserProfile($newUser, $oldUser, $createdAt, $updatedAt);
    }

    private function createUserProfile($newUser, $oldUser, $createdAt, $updatedAt)
    {
        // Parse wallets JSON
        $wallets = $this->parseWallets($oldUser->wallets);

        // Build referral link
        $domain = config('app.url');
        $referralLink = $oldUser->referralCode 
            ? rtrim($domain, '/') . '/register?ref=' . $oldUser->referralCode 
            : null;

        // Determine verification timestamps based on old data
        $kycVerifiedAt = ($oldUser->kyc == 1) ? $createdAt : null;
        $phoneVerifiedAt = ($oldUser->phone_verification == 1) ? $createdAt : null;
        $gameLinkedAt = !empty($oldUser->uname) ? $createdAt : null;

        $profileData = [
            'user_id' => $newUser->id,
            'country' => 'PK',
            'city' => $oldUser->city ?: null,
            'address' => $oldUser->address ?: null,
            'postal_code' => $oldUser->zip ?: null,
            'state_province' => $oldUser->state ?: null,
            'level' => $this->mapLevel($oldUser->level),
            'total_investments' => 0,
            'total_deposit' => 0,
            'total_withdraw' => 0,
            'last_deposit' => 0,
            'last_withdraw' => 0,
            'kyc_status' => $this->mapKycStatus($oldUser->kyc),
            'kyc_verified_at' => $kycVerifiedAt,      // Use parsed timestamp
            'referrallink' => $referralLink,
            'treferrallink' => null,
            'uname' => $oldUser->uname,
            'upwd' => $oldUser->upwd,
            'umoney' => $oldUser->umoney ?: 0,
            'game_linked_at' => $gameLinkedAt,        // Use parsed timestamp
            'email_notifications' => true,
            'sms_notifications' => false,
            'phone_verified' => $oldUser->phone_verification == 1,
            'phone_verified_at' => $phoneVerifiedAt,  // Use parsed timestamp
            'two_factor_enabled' => $oldUser->two_fa_enabled == 1,
            'timezone' => $oldUser->timezone ?: 'Asia/Karachi',
            'metadata' => [
                'old_user_id' => $oldUser->id,
                'old_referral_code' => $oldUser->referralCode,
                'old_sponsor_id' => $oldUser->sponsor,
                'old_country' => $oldUser->country,
                'migration_date' => now()->toDateTimeString(),
                'wallets' => $wallets,
            ],
        ];

        // Create profile with manual timestamps
        $profile = new UserProfile($profileData);
        $profile->created_at = $createdAt;           // Use parsed timestamp
        $profile->updated_at = $updatedAt;           // Use parsed timestamp
        $profile->save(['timestamps' => false]);
    }

    private function updateSponsorRelationships($dryRun = false)
    {
        $updated = 0;

        foreach ($this->userMapping as $oldId => $newId) {
            $oldUser = DB::connection($this->option('connection'))
                ->table('users')
                ->where('id', $oldId)
                ->first();

            if ($oldUser->sponsor && isset($this->userMapping[$oldUser->sponsor])) {
                $newSponsorId = $this->userMapping[$oldUser->sponsor];

                if ($dryRun) {
                    $this->line("[DRY RUN] Would update sponsor for user {$newId}: sponsor_id = {$newSponsorId}");
                } else {
                    // Update without changing updated_at
                    DB::table('users')
                        ->where('id', $newId)
                        ->update(['sponsor_id' => $newSponsorId]);
                }

                $updated++;
            }
        }

        $this->info("Updated {$updated} sponsor relationships");
    }

    private function parseTimestamp($timestamp)
    {
        if (empty($timestamp)) {
            return now();
        }

        try {
            // If it's already a Carbon instance, return it
            if ($timestamp instanceof Carbon) {
                return $timestamp;
            }

            // Parse string timestamp
            $parsed = Carbon::parse($timestamp);
            
            // Validate it's a reasonable date (not in future, not before 2020)
            if ($parsed->isFuture() || $parsed->year < 2020) {
                \Log::warning('Suspicious timestamp detected', [
                    'original' => $timestamp,
                    'parsed' => $parsed->toDateTimeString()
                ]);
            }

            return $parsed;
        } catch (\Exception $e) {
            \Log::error('Failed to parse timestamp', [
                'timestamp' => $timestamp,
                'error' => $e->getMessage()
            ]);
            return now();
        }
    }

    private function splitFullName($fullname)
    {
        if (empty($fullname)) {
            return ['first_name' => 'User', 'last_name' => ''];
        }

        $parts = explode(' ', trim($fullname), 2);

        return [
            'first_name' => $parts[0] ?? 'User',
            'last_name' => $parts[1] ?? '',
        ];
    }

    private function mapStatus($oldStatus)
    {
        return match((int)$oldStatus) {
            1 => 'active',
            2 => 'inactive',
            default => 'pending_verification',
        };
    }

    private function mapKycStatus($kycValue)
    {
        return ($kycValue == 1) ? 'verified' : 'pending';
    }

    private function mapLevel($level)
    {
        if (empty($level)) return 0;
        if (is_numeric($level)) return (int)$level;
        return (int)preg_replace('/[^0-9]/', '', $level);
    }

    private function parseDecimal($value)
    {
        if (empty($value)) return 0;
        return (float)$value;
    }

    private function parseWallets($walletsJson)
    {
        if (empty($walletsJson) || $walletsJson === 'null' || $walletsJson === '[]') {
            return [];
        }

        try {
            $wallets = json_decode($walletsJson, true);
            return is_array($wallets) ? $wallets : [];
        } catch (\Exception $e) {
            return [];
        }
    }

    private function generateReferralCode()
    {
        return 'MLM' . strtoupper(Str::random(6));
    }

    private function displayResults($dryRun = false)
    {
        $this->newLine(2);
        $this->info('═══════════════════════════════════════════');
        $this->info('           MIGRATION SUMMARY');
        $this->info('═══════════════════════════════════════════');

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Users Found', $this->stats['total']],
                ['Successfully Migrated', $this->stats['migrated']],
                ['Skipped (Already Exist)', $this->stats['skipped']],
                ['Errors', $this->stats['errors']],
            ]
        );

        if (!empty($this->errors)) {
            $this->newLine();
            $this->error('ERRORS ENCOUNTERED:');
            $this->table(
                ['Old ID', 'Email', 'Username', 'Error'],
                array_map(function ($error) {
                    return [
                        $error['old_id'],
                        $error['email'],
                        $error['username'],
                        Str::limit($error['error'], 50)
                    ];
                }, $this->errors)
            );
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn('This was a DRY RUN. No data was migrated.');
            $this->info('Run without --dry-run to perform actual migration:');
            $this->line('php artisan migrate:old-users --connection=old_db');
        } else {
            $this->newLine();
            $this->info('✓ Migration completed successfully!');
        }
    }
}