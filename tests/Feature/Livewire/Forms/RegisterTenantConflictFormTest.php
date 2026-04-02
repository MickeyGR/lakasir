<?php

use App\Livewire\Forms\Auth\RegisterTenantForm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\get;
use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['tenancy.central_domains' => ['localhost.com']]);
    DB::statement('DROP DATABASE IF EXISTS lakasir_orphanstoreweb');
    DB::statement('CREATE DATABASE lakasir_orphanstoreweb');
});

afterEach(function () {
    DB::statement('DROP DATABASE IF EXISTS lakasir_orphanstoreweb');
});

it('shows a form error instead of crashing when the tenant database already exists', function () {
    get('/auth/register')
        ->assertSeeLivewire(RegisterTenantForm::class);

    livewire(RegisterTenantForm::class)
        ->fillForm([
            'full_name' => 'Owner Test',
            'email' => 'owner@orphanstoreweb.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'shop_name' => 'Orphan Store Web',
            'business_type' => 'fnb',
            'domain' => 'orphanstoreweb',
        ])
        ->call('create')
        ->assertHasFormErrors(['domain']);
});
