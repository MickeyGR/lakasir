<?php

namespace App\Filament\Tenant\Resources;

// Resource Pages
use App\Filament\Tenant\Resources\ProformaResource\Pages;

// Models
use App\Models\Tenants\Proforma;
use App\Models\Tenants\Member;
use App\Models\Tenants\Product;
use App\Models\Tenants\Setting;

// Filament Core
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables\Table;

// Form Components
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;

// Table Components
use Filament\Tables\Actions;
use Filament\Tables\Columns\TextColumn;

// Infolist Components
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;

class ProformaResource extends Resource
{
    protected static ?string $model = Proforma::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Proformas';
    protected static ?string $pluralLabel = 'Proformas';
    protected static ?string $label = 'Proforma';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Información General')
                    ->schema([
                        Select::make('member_id')
                            ->label('Cliente')
                            ->options(Member::all()->pluck('name', 'id'))
                            ->searchable(),
                        TextInput::make('number')
                            ->label('Número de Proforma')
                            ->default('PRO-' . random_int(1000, 9999))
                            ->required(),
                        Select::make('status')
                            ->options([
                                'pending' => 'Pendiente',
                                'converted' => 'Convertida',
                                'cancelled' => 'Cancelada',
                            ])
                            ->default('pending')
                            ->required(),
                    ])->columns(3),

                Section::make('Productos')
                    ->schema([
                        Repeater::make('details')
                            ->relationship()
                            ->label('Productos')
                            ->schema([
                                Select::make('product_id')
                                    ->label('Producto')
                                    ->options(Product::all()->pluck('name', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(fn($state, callable $set) => $set('price', Product::find($state)?->selling_price ?? 0)),
                                TextInput::make('qty')
                                    ->label('Cantidad')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->reactive(),
                                TextInput::make('price')
                                    ->label('Precio')
                                    ->numeric()
                                    ->required()
                                    ->reactive(),
                                TextInput::make('discount_price')
                                    ->label('Descuento')
                                    ->numeric()
                                    ->default(0)
                                    ->reactive(),
                            ])->columns(4)->columnSpan('full'),
                    ]),

                Section::make('Descuentos e Impuestos')
                    ->schema([
                        TextInput::make('discount_price')
                            ->label('Descuento Global')
                            ->numeric()
                            ->default(0)
                            ->prefix(Setting::get('currency', 'NIO')),
                        TextInput::make('tax')
                            ->label('Impuesto (%)')
                            ->numeric()
                            ->default(Setting::get('default_tax', 0))
                            ->suffix('%'),
                        TextInput::make('notes')
                            ->label('Notas')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')->searchable()->sortable(),
                TextColumn::make('member.name')->label('Cliente')->searchable(),
                TextColumn::make('grand_total_price')
                    ->label('Total Final')
                    ->money(Setting::get('currency', 'NIO'))
                    ->sortable(),
                TextColumn::make('total_price')
                    ->label('Subtotal')
                    ->money(Setting::get('currency', 'NIO'))
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'converted' => 'success',
                        'cancelled' => 'danger',
                    }),
                TextColumn::make('created_at')->label('Fecha')->dateTime()->sortable(),
            ])
            ->actions([
                Actions\ViewAction::make()->url(fn($record) => self::getUrl('view', ['record' => $record])),
                Actions\EditAction::make(),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfoSection::make('Información de la Proforma')
                    ->schema([
                        TextEntry::make('member.name')->label('Cliente'),
                        TextEntry::make('number')->label('Número'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                'pending' => 'warning',
                                'converted' => 'success',
                                'cancelled' => 'danger',
                            }),
                        TextEntry::make('created_at')->label('Fecha de Creación')->dateTime(),
                    ])->columns(2),

                InfoSection::make('Productos')
                    ->schema([
                        RepeatableEntry::make('details')
                            ->hiddenLabel()
                            ->schema([
                                TextEntry::make('product.name')->label('Producto')->weight('bold'),
                                TextEntry::make('qty')->label('Cantidad'),
                                TextEntry::make('price')->label('Precio Unitario')->money(Setting::get('currency', 'NIO')),
                                TextEntry::make('discount_price')->label('Descuento')->money(Setting::get('currency', 'NIO')),
                                TextEntry::make('total_price')->label('Total')->money(Setting::get('currency', 'NIO')),
                            ])->columns(5),
                    ]),

                InfoSection::make('Totales')
                    ->schema([
                        TextEntry::make('total_price')
                            ->label('Subtotal')
                            ->money(Setting::get('currency', 'NIO')),
                        TextEntry::make('tax_price')
                            ->label('Impuesto')
                            ->money(Setting::get('currency', 'NIO')),
                        TextEntry::make('discount_price')
                            ->label('Descuento Global')
                            ->money(Setting::get('currency', 'NIO')),
                        TextEntry::make('total_discount_per_item')
                            ->label('Descuento por Ítem')
                            ->money(Setting::get('currency', 'NIO')),
                        TextEntry::make('grand_total_price')
                            ->label('Total Final')
                            ->money(Setting::get('currency', 'NIO'))
                            ->weight('bold')
                            ->size('lg'),
                    ])->columns(2),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProformas::route('/'),
            'create' => Pages\CreateProforma::route('/create'),
            'view' => Pages\ViewProforma::route('/{record}'),
            'edit' => Pages\EditProforma::route('/{record}/edit'),
        ];
    }
}
