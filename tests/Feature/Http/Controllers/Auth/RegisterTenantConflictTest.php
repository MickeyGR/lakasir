<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['tenancy.central_domains' => ['localhost.com']]);
    DB::statement('DROP DATABASE IF EXISTS lakasir_orphanstore');
    DB::statement('CREATE DATABASE lakasir_orphanstore');
});

afterEach(function () {
    DB::statement('DROP DATABASE IF EXISTS lakasir_orphanstore');
});

it('returns a validation error when a tenant database already exists without a central tenant record', function () {
    $response = postJson('/api/domain/register', [
        'domain' => 'orphanstore.localhost.com',
        'email' => 'owner@orphanstore.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'business_type' => 'fnb',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['domain']);
});
