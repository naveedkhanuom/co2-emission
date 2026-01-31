<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Company;

class AssignCompanyToUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:assign-company {user_id} {company_ids* : Company IDs to assign (space-separated)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign one or more companies to a user (adds to company_access array)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('user_id');
        $companyIds = $this->argument('company_ids');

        // Get user
        $user = User::find($userId);
        if (!$user) {
            $this->error("User with ID {$userId} not found!");
            return 1;
        }

        // Get companies
        $companies = Company::whereIn('id', $companyIds)->get();
        if ($companies->count() !== count($companyIds)) {
            $this->warn("Some company IDs were not found!");
        }

        // Get current access
        $currentAccess = $user->company_access ?? [];
        
        // Add new company IDs (avoid duplicates)
        $newAccess = array_unique(array_merge($currentAccess, $companyIds));
        
        // Update user
        $user->company_access = array_values($newAccess); // Re-index array
        $user->save();

        // Display results
        $this->info("✓ User: {$user->name} (ID: {$user->id})");
        $this->info("✓ Primary Company ID: " . ($user->company_id ?? 'Not set'));
        $this->info("✓ Company Access: " . json_encode($user->company_access));
        
        $accessibleCount = $user->accessibleCompanies()->count();
        $this->info("✓ Total Accessible Companies: {$accessibleCount}");

        // Show list of accessible companies
        $accessibleCompanies = $user->accessibleCompanies()->get(['id', 'name', 'is_active']);
        $this->table(
            ['ID', 'Name', 'Active'],
            $accessibleCompanies->map(function($company) {
                return [
                    $company->id,
                    $company->name,
                    $company->is_active ? 'Yes' : 'No',
                ];
            })->toArray()
        );

        return 0;
    }
}
