<?php

namespace App\Console\Commands;

use App\Services\Tenants\WipeMediaReferencesService;
use App\Tenant;
use Illuminate\Console\Command;

class WipeTenantMediaReferences extends Command
{
    protected $signature = 'app:wipe-tenant-media-refs
        {tenant_ids?* : Tenant IDs to clean}
        {--all : Clean all tenants}';

    protected $description = 'Remove all tenant database references to uploaded files and images';

    public function handle(WipeMediaReferencesService $service): int
    {
        $tenants = $this->resolveTenants();

        if ($tenants->isEmpty()) {
            $this->error('No tenant was selected.');

            return self::FAILURE;
        }

        foreach ($tenants as $tenant) {
            $this->line("Cleaning media references for tenant [{$tenant->getTenantKey()}]...");

            $results = $tenant->run(fn () => $service->handle());

            foreach ($results as $label => $count) {
                $this->line("  - {$label}: {$count}");
            }
        }

        $this->info('Tenant media references wiped.');

        return self::SUCCESS;
    }

    private function resolveTenants()
    {
        if ($this->option('all')) {
            return Tenant::query()->get();
        }

        $tenantIds = collect($this->argument('tenant_ids'))
            ->filter()
            ->values();

        if ($tenantIds->isEmpty()) {
            return collect();
        }

        return Tenant::query()
            ->whereIn('id', $tenantIds)
            ->get();
    }
}
