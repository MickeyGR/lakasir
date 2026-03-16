<?php

namespace App\Filament\Tenant\Pages;

use App\Filament\Tenant\Resources\Traits\RefreshThePage;
use App\Models\Tenants\About;
use App\Models\Tenants\Profile;
use App\Models\Tenants\Setting;
use App\Models\Tenants\UploadedFile;
use App\Models\Tenants\User;
use App\Services\Tenants\AboutService;
use App\Traits\HasTranslatableResource;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\Page;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Laravel\Pennant\Feature;

class GeneralSetting extends Page implements HasActions, HasForms
{
    use HasTranslatableResource,
        InteractsWithFormActions,
        InteractsWithForms,
        RefreshThePage;

    public static ?string $label = 'General Setting';

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $view = 'filament.tenant.pages.general-setting';

    public $about = [
        'shop_location' => '',
        'photo' => '',
    ];

    public $setting = [];

    public $feature = [];

    public $profile = [];

    public function mount(): void
    {
        $about = About::first()?->toArray() ?? $this->about;
        if ($about) {
            $about['preview_image'] = $about['photo'];
            if ($about['photo']) {
                $about['photo'] = [$about['photo']];
            }
            foreach (config('setting.key') as $key) {
                $this->setting[$key] = Setting::get($key);
            }
            $this->about = $about;
        }

        $this->feature = [
            'supplier' => Feature::active('supplier'),
            'purchasing' => Feature::active('purchasing'),
            'receivable' => Feature::active('receivable'),
            'stock-opname' => Feature::active('stock-opname'),
            'voucher' => Feature::active('voucher'),
            'pos-v2' => Feature::active('pos-v2'),
            'product-import' => Feature::active('product-import')
        ];

        /** @var User $user */
        $user = auth()->user();
        $profile = $user->profile;

        $this->profile = [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $profile->phone,
            'address' => $profile->address,
            'locale' => $profile->locale,
            'timezone' => $profile->timezone,
            'photo' => $profile->photo ? [$profile->photo] : null,
        ];
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Tabs::make('Tabs')
                ->tabs([
                    Tabs\Tab::make('About')
                        ->statePath('about')
                        ->translateLabel()
                        ->schema(About::form()),
                    Tabs\Tab::make('App')
                        ->statePath('setting')
                        ->translateLabel()
                        ->schema([
                            Select::make('currency')
                                ->options([
                                    'IDR' => 'IDR',
                                    'MXN' => 'MXN',
                                    'USD' => 'USD',
                                    'NIO' => 'NIO',
                                ])
                                ->translateLabel(),
                            Select::make('minimum_stock_nofication')
                                ->options([
                                    0 => 0,
                                    5 => 5,
                                    10 => 10,
                                    20 => 20,
                                    50 => 50,
                                ])
                                ->translateLabel(),
                            TextInput::make('default_tax')
                                ->numeric()
                                ->suffix('%')
                                ->translateLabel(),
                            Actions::make([
                                Action::make('Save')
                                    ->translateLabel()
                                    ->requiresConfirmation()
                                    ->action('saveApp'),
                            ]),
                        ]),
                    Tabs\Tab::make('Feature')
                        ->statePath('feature')
                        ->visible(can('access feature flag'))
                        ->translateLabel()
                        ->schema([
                            Section::make([
                                Checkbox::make('supplier')->inline()->translateLabel(),
                                Checkbox::make('purchasing')->inline()->translateLabel(),
                                Checkbox::make('receivable')->inline()->translateLabel(),
                                Checkbox::make('stock-opname')->label('Stock opname')->inline()->translateLabel(),
                                Checkbox::make('voucher')->inline()->translateLabel(),
                                Checkbox::make('pos-v2')->label('POS V2')->inline()->translateLabel(),
                                Checkbox::make('product-import')->label('Product import')->inline()->translateLabel(),
                            ]),
                            Actions::make([
                                Action::make('Save')
                                    ->translateLabel()
                                    ->requiresConfirmation()
                                    ->action('saveFeature'),
                            ]),
                        ]),
                    Tabs\Tab::make('Profile')
                        ->statePath('profile')
                        ->translateLabel()
                        ->schema(Profile::form()),
                ]),
        ]);
    }

    public function saveApp(): void
    {
        foreach ($this->setting as $key => $value) {
            Setting::set($key, $value);
        }

        Notification::make()
            ->title(__('Success'))
            ->success()
            ->send();

        $this->mount();
    }

    public function saveAbout(AboutService $aboutService): void
    {
        $this->validate([
            'about.shop_name' => 'required',
            'about.business_type' => 'required',
            'about.shop_location' => 'required',
            // 'about.currency' => 'required',
            // 'data.photo' => 'required',
        ]);

        $this->about['photo_url'] = $this->resolvePhotoUrl($this->about['photo'] ?? null);
        unset($this->about['photo']);

        $aboutService->createOrUpdate($this->about);

        Notification::make()
            ->title(__('Success'))
            ->success()
            ->send();

        $this->mount();
    }

    public function saveFeature(): void
    {
        if (can('access feature flag')) {
            foreach ($this->feature as $name => $value) {
                if ($value) {
                    Feature::activate($name);
                } else {
                    Feature::deactivate($name);
                }
            }

            Notification::make()
                ->title(__('Success'))
                ->success()
                ->send();

            $this->mount();
        }
    }

    public function saveProfile(): void
    {
        $this->validate([
            'profile.email' => 'required|email',
            'profile.timezone' => 'required',
            'profile.locale' => 'required',
            'profile.password' => 'nullable|confirmed',
            // 'data.photo' => 'required',
        ]);

        /** @var User $user */
        $user = auth()->user();
        $profile = $user->profile;
        $photoUrl = feature('edit-profile')
            ? $this->resolvePhotoUrl($this->profile['photo'] ?? null)
            : $profile->photo;

        $user->update(Arr::except(Arr::only($this->profile, [
            'name',
            'email',
            'password',
        ]), [
            'password_confirmation',
        ]));

        $profile->update(Arr::only($this->profile, [
            'phone',
            'address',
            'locale',
            'timezone',
        ]));

        if (feature('edit-profile')) {
            $this->syncPhoto($profile, $photoUrl);
        }

        Notification::make()
            ->title(__('Success'))
            ->success()
            ->send();

        $this->mount();
    }

    private function resolvePhotoUrl(array|string|null $state): ?string
    {
        if (blank($state)) {
            return null;
        }

        $files = is_array($state) ? array_values(array_filter($state)) : [$state];

        foreach (array_reverse($files) as $file) {
            if ($file instanceof TemporaryUploadedFile) {
                $path = $file->store('profile', 'public');

                return Storage::disk('public')->url($path);
            }

            if (! is_string($file) || $file === '') {
                continue;
            }

            if (Str::startsWith($file, ['http://', 'https://'])) {
                return $file;
            }

            $path = ltrim((string) Str::of($file)->after('/storage/'), '/');

            if ($path !== '') {
                return Storage::disk('public')->url($path);
            }
        }

        return null;
    }

    private function syncPhoto(About|Profile $record, ?string $photoUrl): void
    {
        if ($photoUrl === $record->photo) {
            return;
        }

        if (blank($photoUrl)) {
            $this->deletePhoto($record->photo);

            if ($record->photo !== null) {
                $record->update([
                    'photo' => null,
                ]);
            }

            return;
        }

        /** @var UploadedFile|null $tmpFile */
        $tmpFile = UploadedFile::where('url', $photoUrl)->first();

        if ($tmpFile) {
            $photoUrl = $tmpFile->moveToPuplic('profile', $record->photo ? Str::of($record->photo)->after('profile/') : null);
        } else {
            $this->deletePhoto($record->photo);
        }

        $record->update([
            'photo' => $photoUrl,
        ]);
    }

    private function deletePhoto(?string $photoUrl): void
    {
        if (blank($photoUrl)) {
            return;
        }

        /** @var UploadedFile|null $uploadedFile */
        $uploadedFile = UploadedFile::where('url', $photoUrl)->first();

        if ($uploadedFile) {
            $uploadedFile->deleteFromPublic('profile');

            return;
        }

        $path = ltrim((string) Str::of(parse_url($photoUrl, PHP_URL_PATH) ?? '')->after('/storage/'), '/');

        if ($path !== '' && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
