<?php

namespace App\Services\Tenants;

use App\Models\Tenants\About;
use App\Models\Tenants\UploadedFile;
use App\Models\Tenants\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AboutService
{
    public function createOrUpdate(array $data): void
    {
        $about = About::query()
            ->updateOrCreate([
                'id' => About::first()?->getKey() ?? null,
            ], Arr::only($data, [
                'shop_name',
                'slogan',
                'shop_location',
                'business_type',
                'other_business_type',
                'phone',
                'facebook',
                'messenger',
                'website',
                'linkedin',
            ]));

        $owner = User::owner()->first();
        if ($owner && isset($data['owner_name'])) {
            $owner->name = $data['owner_name'];
            $owner->save();
        }

        if (! array_key_exists('photo_url', $data)) {
            return;
        }

        if (blank($data['photo_url'])) {
            $this->deletePhoto($about->photo);

            if ($about->photo !== null) {
                $about->update([
                    'photo' => null,
                ]);
            }

            return;
        }

        if ($data['photo_url'] !== $about->photo) {
            /** @var \App\Models\Tenants\UploadedFile $tmpFile */
            $tmpFile = UploadedFile::where('url', $data['photo_url'])->first();
            $url = $data['photo_url'];
            if ($tmpFile?->disk === 'tmp') {
                $url = $tmpFile->moveToPuplic('profile', $about->photo ? Str::of($about->photo)->after('profile/') : null);
            } else {
                $this->deletePhoto($about->photo);
            }
            $about->update([
                'photo' => $url,
            ]);
        }
    }

    private function deletePhoto(?string $photoUrl): void
    {
        if (blank($photoUrl)) {
            return;
        }

        /** @var UploadedFile|null $tmpFile */
        $tmpFile = UploadedFile::where('url', $photoUrl)->first();

        if ($tmpFile) {
            $tmpFile->deleteFromPublic('profile');

            return;
        }

        $path = ltrim((string) Str::of(parse_url($photoUrl, PHP_URL_PATH) ?? '')->after('/storage/'), '/');

        if ($path !== '' && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
