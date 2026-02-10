<?php

namespace App\Services;

use App\Constants\Role;
use App\Models\Tenants\About;
use App\Models\Tenants\Role as TenantRole;
use App\Models\Tenants\User;
use App\Notifications\DomainCreated;
use App\Tenant;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\PermissionRegistrar;

class RegisterTenant
{
    public function create(array $data): Tenant
    {
        $name = $data['name'] ?? null;
        /** @var Tenant */
        $tenant = Tenant::create([
            'id' => $name,
            'tenancy_db_name' => 'lakasir_'.$name,
            'tenancy_email' => $data['email'],
        ]);

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

            Artisan::call('db:seed', [
                '--class' => 'PermissionSeeder',
            ]);
            Artisan::call('db:seed', [
                '--class' => 'PaymentMethodSeeder',
            ]);
            Artisan::call('db:seed', [
                '--class' => 'CategorySeeder',
            ]);

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
}
