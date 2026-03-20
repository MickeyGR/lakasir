<?php

use App\Filament\Tenant\Pages\GeneralSetting;
use App\Models\Tenants\About;
use App\Models\Tenants\UploadedFile as TenantUploadedFile;
use App\Models\Tenants\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\RefreshDatabaseWithTenant;

use function Pest\Laravel\actingAs;

uses(RefreshDatabaseWithTenant::class);

it('initializes an empty about photo as a file upload array state', function () {
    About::query()->delete();

    actingAs(User::first());

    $component = Livewire::test(GeneralSetting::class);

    expect($component->get('about.photo'))
        ->toBeArray()
        ->toBeEmpty();
});

it('hydrates an existing about photo using a valid file upload state', function () {
    Storage::fake('public');

    $path = 'profile/store-logo.png';
    $url = Storage::disk('public')->url($path);

    Storage::disk('public')->put($path, 'logo');

    About::query()->create([
        'shop_name' => 'Mi tienda',
        'business_type' => 'retail',
        'shop_location' => 'Managua',
        'photo' => $url,
    ]);

    TenantUploadedFile::query()->create([
        'name' => 'store-logo.png',
        'original_name' => 'store-logo-original.png',
        'url' => $url,
        'mime_type' => 'image/png',
        'extension' => 'png',
        'size' => '4',
        'path' => Storage::disk('public')->path($path),
        'disk' => 'public',
    ]);

    actingAs(User::first());

    $component = Livewire::test(GeneralSetting::class);

    expect($component->get('about.photo'))
        ->toBeArray()
        ->toHaveCount(1);
    expect(array_values($component->get('about.photo')))->toBe([$path]);
    expect($component->get('about.photo_original_name'))->toBe('store-logo-original.png');

    $component
        ->call('saveAbout')
        ->assertHasNoErrors();

    expect(About::query()->first()->photo)->toBe($url);
});
