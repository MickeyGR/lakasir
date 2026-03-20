<?php

use App\Support\Storage\StoredFileUrl;
use Illuminate\Support\Facades\Storage;

it('ignores livewire temporary upload paths restored from the database', function () {
    expect(StoredFileUrl::extractPublicPath('https://nica-computers-pos.atokatl.dev/storage/PSbPaxaYhrtjSGFJeD9ayl8G4oHHxA-metabG9nby1uaWNhLWNvbXB1dGVycy5qcGc=-.jpg'))
        ->toBeNull();
});

it('normalizes old absolute storage urls to the current public url when the file exists', function () {
    Storage::fake('public');
    Storage::disk('public')->put('profile/store-logo.png', 'logo');

    expect(StoredFileUrl::toCurrentPublicUrl('https://nica-computers-pos.atokatl.dev/storage/profile/store-logo.png'))
        ->toBe('/storage/profile/store-logo.png');
});
