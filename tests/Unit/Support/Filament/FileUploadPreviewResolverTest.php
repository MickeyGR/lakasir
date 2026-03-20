<?php

use App\Support\Filament\FileUploadPreviewResolver;
use Filament\Forms\Components\FileUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

it('resolves persisted public file previews', function () {
    Storage::fake('public');

    $path = 'profile/store-logo.png';
    Storage::disk('public')->put($path, 'logo');

    $preview = FileUploadPreviewResolver::resolve(
        FileUpload::make('photo')->disk('public'),
        $path,
        null,
    );

    expect($preview)->not->toBeNull();
    expect($preview['name'])->toBe($path);
    expect($preview['url'])->toBe('/storage/'.$path);
});

it('resolves livewire temporary upload previews', function () {
    $uploadedFile = UploadedFile::fake()->image('store-logo.jpg');
    $temporaryFileName = TemporaryUploadedFile::generateHashNameWithOriginalNameEmbedded($uploadedFile);

    FileUploadConfiguration::storage()->put(
        FileUploadConfiguration::path($temporaryFileName),
        file_get_contents($uploadedFile->getRealPath()),
    );

    $preview = FileUploadPreviewResolver::resolve(
        FileUpload::make('photo')->disk('public'),
        $temporaryFileName,
        null,
    );

    expect($preview)->not->toBeNull();
    expect($preview['name'])->toBe('store-logo.jpg');
    expect($preview['type'])->toStartWith('image/');
    expect($preview['url'])->toContain('livewire/preview-file');
});
