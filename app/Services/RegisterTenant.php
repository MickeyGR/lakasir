<?php

namespace App\Services;

use App\Constants\Role;
use App\Models\Tenants\About;
use App\Models\Tenants\Role as TenantRole;
use App\Models\Tenants\User;
use App\Notifications\DomainCreated;
use App\Tenant;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Spatie\Permission\PermissionRegistrar;
use Stancl\Tenancy\Exceptions\DomainOccupiedByOtherTenantException;
use Stancl\Tenancy\Exceptions\TenantDatabaseAlreadyExistsException;
use Stancl\Tenancy\Exceptions\TenantDatabaseUserAlreadyExistsException;

class RegisterTenant
{
    public function create(array $data): Tenant
    {
        $name = $data['name'] ?? null;
        $domain = $data['domain'] ?? null;

        $this->guardAgainstExistingTenantResources($name, $domain);

        try {
            /** @var Tenant */
            $tenant = Tenant::create([
                'id' => $name,
                'tenancy_db_name' => 'lakasir_'.$name,
                'tenancy_email' => $data['email'],
            ]);
        } catch (TenantDatabaseAlreadyExistsException|TenantDatabaseUserAlreadyExistsException|DomainOccupiedByOtherTenantException $e) {
            throw ValidationException::withMessages([
                'domain' => [$this->tenantConflictMessage($name)],
            ]);
        }

        $tenant->domains()->create([
            'domain' => $data['domain'],
        ]);

        $tenant->run(function () use ($data) {
            $user = User::create([
                'email' => $data['email'],
                'password' => bcrypt($data['password']),
                'is_owner' => true,
            ]);

            About::create([
                'shop_name' => $data['full_name'] ?? null,
                'business_type' => $data['business_type'],
                'other_business_type' => $data['other_business_type'] ?? null,
            ]);

            $user->notify(new DomainCreated());

            $this->seedTenantData('PermissionSeeder');
            $this->seedTenantData('PaymentMethodSeeder');
            $this->seedTenantData('CategorySeeder');

            app(PermissionRegistrar::class)->forgetCachedPermissions();
            $adminRole = TenantRole::firstOrCreate([
                'name' => Role::admin,
                'guard_name' => 'web',
            ]);
            if (! $user->hasRole($adminRole)) {
                $user->assignRole($adminRole);
            }
        });

        return $tenant;
    }

    private function guardAgainstExistingTenantResources(?string $name, ?string $domain): void
    {
        if (blank($name) || blank($domain)) {
            return;
        }

        if (Tenant::query()->whereKey($name)->exists()) {
            throw ValidationException::withMessages([
                'domain' => ['This domain is already registered.'],
            ]);
        }

        if (DB::connection('mysql')->table('domains')->where('domain', $domain)->exists()) {
            throw ValidationException::withMessages([
                'domain' => ['This domain is already registered.'],
            ]);
        }

        if ($this->tenantDatabaseExists('lakasir_'.$name)) {
            throw ValidationException::withMessages([
                'domain' => [$this->tenantConflictMessage($name)],
            ]);
        }
    }

    private function tenantDatabaseExists(string $databaseName): bool
    {
        return (bool) DB::connection('mysql')->select(
            'SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?',
            [$databaseName]
        );
    }

    private function tenantConflictMessage(?string $name): string
    {
        $identifier = $name ?: 'this tenant';

        return "A tenant database for '{$identifier}' already exists. If this is your store, reconnect it from the central tenant registry instead of registering it again.";
    }

    private function seedTenantData(string $seederClass): void
    {
        $exitCode = Artisan::call('db:seed', [
            '--class' => $seederClass,
            '--force' => true,
        ]);

        if ($exitCode !== 0) {
            throw new RuntimeException("Failed seeding tenant with {$seederClass}: ".trim(Artisan::output()));
        }
    }
}
