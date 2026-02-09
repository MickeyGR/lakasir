<?php

namespace App\Providers;

use App\Models\Tenants\User;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\RequestBodyObject;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Pennant\Feature;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // if ($this->app->isLocal()) {
        //     // Registrar proveedores solo para el entorno local
        //     $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
        //     // $this->app->register(\Barryvdh\Debugbar\ServiceProvider::class); // Si también usas Debugbar
        // }
        $this->app->bind(Authenticatable::class, User::class);

        if ($this->app->environment('local', 'development') && class_exists(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class)) {
            $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Global bearer auth for Scramble-generated OpenAPI (applies to all endpoints in docs).
        if (class_exists(Scramble::class)) {
            // In production, don't expose documentation routes/spec.
            if ($this->app->isProduction()) {
                Scramble::ignoreDefaultRoutes();

                return;
            }
            // Disable Scramble UI route; expose only JSON spec for Scalar.
            Scramble::ignoreDefaultRoutes();
            Scramble::registerJsonSpecificationRoute('docs/api.json');

            Scramble::routes(function (Route $route) {
                return \Illuminate\Support\Str::startsWith($route->uri, 'api/');
            });

            Scramble::afterOpenApiGenerated(function (OpenApi $openApi) {
                $openApi->secure(
                    SecurityScheme::http('bearer', 'JWT')->as('bearerAuth')
                );

                // Add example response for POST /api/auth/login.
                $loginExample = [
                    'success' => true,
                    'message' => 'Yay! success to login',
                    'data' => [
                        'id' => 1,
                        'is_owner' => 1,
                        'name' => 'Mickey',
                        'email' => 'mickeyanthonygudiel@gmail.com',
                        'email_verified_at' => null,
                        'created_at' => '2025-09-23T04:42:45.000000Z',
                        'updated_at' => '2025-09-23T04:42:45.000000Z',
                        'deleted_at' => null,
                        'dark_mode_enabled' => 0,
                        'token' => '8|ckgp3gDOPgJk7d1MV07OuLPGWUSO6oPgMztyrEDp8fc87f62',
                        'permissions' => ['...'],
                        'features' => ['...'],
                    ],
                ];

                foreach ($openApi->paths as $path) {
                    if ($path->path !== 'auth/login') {
                        continue;
                    }

                    $operation = $path->operations['post'] ?? null;
                    if (! $operation) {
                        continue;
                    }

                    $schema = \Dedoc\Scramble\Support\Generator\Schema::fromType(
                        (new ObjectType)->example($loginExample)
                    );

                    $operation->responses = [
                        Response::make(200)
                            ->setDescription('Successful login')
                            ->setContent('application/json', $schema),
                    ];
                }

                // Example for GET /api/check
                foreach ($openApi->paths as $path) {
                    if ($path->path !== 'check') {
                        continue;
                    }
                    $operation = $path->operations['get'] ?? null;
                    if (! $operation) {
                        continue;
                    }

                    $schema = Schema::fromType(
                        (new ObjectType)->example([
                            'tenant' => 'tenantdemo',
                            'tenant_email' => null,
                        ])
                    );

                    $operation->responses = [
                        Response::make(200)
                            ->setDescription('Tenant check')
                            ->setContent('application/json', $schema),
                    ];
                }

                // Example for GET /api/master/category
                foreach ($openApi->paths as $path) {
                    if ($path->path !== 'master/category') {
                        continue;
                    }
                    $operation = $path->operations['get'] ?? null;
                    if (! $operation) {
                        continue;
                    }

                    $schema = Schema::fromType(
                        (new ObjectType)->example([
                            'success' => true,
                            'data' => [
                                ['id' => 1, 'name' => 'UMUM', 'created_at' => '2026-01-24T09:02:29.000000Z', 'updated_at' => '2026-01-24T09:02:29.000000Z'],
                            ],
                        ])
                    );

                    $operation->responses = [
                        Response::make(200)
                            ->setDescription('List categories')
                            ->setContent('application/json', $schema),
                    ];
                }

                // Example for GET /api/master/product
                foreach ($openApi->paths as $path) {
                    if ($path->path !== 'master/product') {
                        continue;
                    }
                    if ($operation = $path->operations['get'] ?? null) {
                        $schema = Schema::fromType(
                            (new ObjectType)->example([
                                'success' => true,
                                'data' => [
                                    'data' => [
                                        [
                                            'id' => 2,
                                            'name' => 'Laptop Air 13',
                                            'category' => [
                                                'id' => 1,
                                                'name' => 'UMUM',
                                                'created_at' => '2026-01-24T09:02:29.000000Z',
                                                'updated_at' => '2026-01-24T09:02:29.000000Z',
                                            ],
                                            'category_id' => 1,
                                            'initial_price' => 6500000,
                                            'selling_price' => 8500000,
                                            'type' => 'product',
                                            'unit' => 'PCS',
                                            'stock' => 25,
                                            'is_non_stock' => false,
                                            'hero_images' => [],
                                            'sku' => 'LTP-002',
                                            'barcode' => '9876543210000',
                                            'show' => 1,
                                        ],
                                        [
                                            'id' => 1,
                                            'name' => 'Laptop Pro 15',
                                            'category' => [
                                                'id' => 1,
                                                'name' => 'UMUM',
                                                'created_at' => '2026-01-24T09:02:29.000000Z',
                                                'updated_at' => '2026-01-24T09:02:29.000000Z',
                                            ],
                                            'category_id' => 1,
                                            'initial_price' => 9000000,
                                            'selling_price' => 12500000,
                                            'type' => 'product',
                                            'unit' => 'PCS',
                                            'stock' => 12,
                                            'is_non_stock' => false,
                                            'hero_images' => [],
                                            'sku' => 'LTP-001',
                                            'barcode' => '1234567890123',
                                            'show' => 1,
                                        ],
                                    ],
                                    'links' => [
                                        'first' => 'http://tenantdemo.localdomain.test:8000/api/master/product?page=1',
                                        'prev' => null,
                                        'next' => null,
                                    ],
                                    'meta' => [
                                        'current_page' => 1,
                                        'from' => null,
                                        'path' => 'http://tenantdemo.localdomain.test:8000/api/master/product',
                                        'per_page' => 15,
                                        'to' => null,
                                    ],
                                ],
                            ])
                        );

                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('List products')
                                ->setContent('application/json', $schema),
                            Response::make(401)
                                ->setDescription('Unauthenticated')
                                ->setContent('application/json', Schema::fromType(
                                    (new ObjectType)->example([
                                        'message' => 'Unauthenticated.',
                                    ])
                                )),
                        ];
                    }

                    if ($operation = $path->operations['post'] ?? null) {
                        $successSchema = Schema::fromType(
                            (new ObjectType)->example([
                                'success' => true,
                                'message' => 'success creating items',
                            ])
                        );

                        $validationSchema = Schema::fromType(
                            (new ObjectType)->example([
                                'message' => 'The category field is required. (and 1 more error)',
                                'errors' => [
                                    'category' => ['The category field is required.'],
                                    'stock' => ['The stock field is required.'],
                                ],
                            ])
                        );

                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('Product created')
                                ->setContent('application/json', $successSchema),
                            Response::make(422)
                                ->setDescription('Validation error')
                                ->setContent('application/json', $validationSchema),
                        ];

                        // Request body (mirroring PUT payload shape).
                        $requestType = (new ObjectType)
                            ->addProperty('category', (new \Dedoc\Scramble\Support\Generator\Types\IntegerType)->example(1))
                            ->addProperty('initial_price', (new \Dedoc\Scramble\Support\Generator\Types\NumberType)->example(9000000))
                            ->addProperty('is_non_stock', (new \Dedoc\Scramble\Support\Generator\Types\BooleanType)->example(false))
                            ->addProperty('name', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('Laptop Pro 15'))
                            ->addProperty('selling_price', (new \Dedoc\Scramble\Support\Generator\Types\NumberType)->example(12000000))
                            ->addProperty('type', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->enum(['product', 'service'])->example('product'))
                            ->addProperty('barcode', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('1234567890123')->nullable(true))
                            ->addProperty('expired', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('2026-12-31T00:00:00Z')->nullable(true))
                            ->addProperty('hero_images_url', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('')->nullable(true))
                            ->addProperty('sku', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('LTP-001')->nullable(true))
                            ->addProperty('stock', (new \Dedoc\Scramble\Support\Generator\Types\IntegerType)->example(10))
                            ->setRequired(['category', 'initial_price', 'is_non_stock', 'name', 'selling_price', 'type']);

                        $operation->addRequestBodyObject(
                            RequestBodyObject::make()
                                ->setContent('application/json', Schema::fromType($requestType))
                                ->required()
                                ->description('Product payload')
                        );
                    }
                }

                // Example for PUT /api/master/product/{product}
                foreach ($openApi->paths as $path) {
                    if ($path->path !== 'master/product/{product}') {
                        continue;
                    }

                    // GET detail
                    if ($operation = $path->operations['get'] ?? null) {
                        $schema = Schema::fromType(
                            (new ObjectType)->example([
                                'success' => true,
                                'data' => [
                                    'id' => 1,
                                    'name' => 'Laptop Pro 15',
                                    'category' => [
                                        'id' => 1,
                                        'name' => 'UMUM',
                                        'created_at' => '2026-01-24T09:02:29.000000Z',
                                        'updated_at' => '2026-01-24T09:02:29.000000Z',
                                    ],
                                    'category_id' => 1,
                                    'initial_price' => 9000000,
                                    'selling_price' => 12000000,
                                    'type' => 'product',
                                    'unit' => 'PCS',
                                    'stock' => 7,
                                    'is_non_stock' => false,
                                    'hero_images' => [],
                                    'sku' => 'LTP-001',
                                    'barcode' => '1234567890123',
                                    'show' => 1,
                                    'stocks' => [
                                        [
                                            'id' => 1,
                                            'product_id' => 1,
                                            'purchasing_id' => null,
                                            'is_ready' => 1,
                                            'stock' => 7,
                                            'init_stock' => 10,
                                            'initial_price' => 9000000,
                                            'selling_price' => 12000000,
                                            'type' => 'in',
                                            'date' => '2026-01-24',
                                            'expired' => null,
                                            'created_at' => '2026-01-24T09:17:23.000000Z',
                                            'updated_at' => '2026-01-25T00:38:39.000000Z',
                                            'total_selling_price' => 120000000,
                                            'total_initial_price' => 90000000,
                                        ],
                                    ],
                                ],
                            ])
                        );

                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('Product detail')
                                ->setContent('application/json', $schema),
                        ];
                    }

                    // PUT update
                    if ($operation = $path->operations['put'] ?? null) {
                        $successSchema = Schema::fromType(
                            (new ObjectType)->example([
                                'success' => true,
                                'data' => [
                                    'id' => 1,
                                    'name' => 'Laptop Pro 15',
                                    'category' => [
                                        'id' => 1,
                                        'name' => 'UMUM',
                                        'created_at' => '2026-01-24T09:02:29.000000Z',
                                        'updated_at' => '2026-01-24T09:02:29.000000Z',
                                    ],
                                    'category_id' => 1,
                                    'initial_price' => 9000000,
                                    'selling_price' => 12500000,
                                    'type' => 'product',
                                    'unit' => 'PCS',
                                    'stock' => 7,
                                    'is_non_stock' => false,
                                    'hero_images' => [],
                                    'sku' => 'LTP-001',
                                    'barcode' => '1234567890123',
                                    'show' => 1,
                                ],
                                'message' => 'success updating items',
                            ])
                        );

                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('Product updated')
                                ->setContent('application/json', $successSchema),
                        ];

                        // Add request body example similar to POST.
                        $requestType = (new ObjectType)
                            ->addProperty('category', (new \Dedoc\Scramble\Support\Generator\Types\IntegerType)->example(1))
                            ->addProperty('initial_price', (new \Dedoc\Scramble\Support\Generator\Types\NumberType)->example(9000000))
                            ->addProperty('is_non_stock', (new \Dedoc\Scramble\Support\Generator\Types\BooleanType)->example(false))
                            ->addProperty('name', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('Laptop Pro 15'))
                            ->addProperty('selling_price', (new \Dedoc\Scramble\Support\Generator\Types\NumberType)->example(12500000))
                            ->addProperty('type', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->enum(['product', 'service'])->example('product'))
                            ->addProperty('barcode', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('1234567890123')->nullable(true))
                            ->addProperty('expired', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('2026-12-31T00:00:00Z')->nullable(true))
                            ->addProperty('hero_images_url', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('')->nullable(true))
                            ->addProperty('sku', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('LTP-001')->nullable(true))
                            ->addProperty('stock', (new \Dedoc\Scramble\Support\Generator\Types\IntegerType)->example(7))
                            ->setRequired(['category', 'initial_price', 'is_non_stock', 'name', 'selling_price', 'type']);

                        $operation->addRequestBodyObject(
                            RequestBodyObject::make()
                                ->setContent('application/json', Schema::fromType($requestType))
                                ->required()
                                ->description('Product payload')
                        );
                    }

                    // DELETE
                    if ($operation = $path->operations['delete'] ?? null) {
                        $successSchema = Schema::fromType(
                            (new ObjectType)->example([
                                'success' => true,
                                'message' => 'success deleting items',
                            ])
                        );

                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('Product deleted')
                                ->setContent('application/json', $successSchema),
                        ];
                    }
                }

                // Example for GET /api/about
                foreach ($openApi->paths as $path) {
                    if ($path->path !== 'about') {
                        continue;
                    }

                    if ($operation = $path->operations['get'] ?? null) {
                        $schema = Schema::fromType(
                            (new ObjectType)->example([
                                'success' => true,
                                'data' => [
                                    'shop_name' => 'NicaPC',
                                    'shop_location' => 'Managua',
                                    'owner_name' => 'MICKEY GUDIEL REYES',
                                    'business_type' => 'other',
                                    'other_business_type' => 'Computo',
                                    'currency' => 'NIO',
                                    'photo_url' => '',
                                ],
                            ])
                        );

                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('Store profile')
                                ->setContent('application/json', $schema),
                        ];
                    }

                    if ($operation = $path->operations['put'] ?? null) {
                        $schema = Schema::fromType(
                            (new ObjectType)->example([
                                'success' => true,
                                'message' => 'About updated successfully',
                            ])
                        );

                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('Store profile updated')
                                ->setContent('application/json', $schema),
                        ];
                    }
                }

                // Example for GET /api/master/member
                foreach ($openApi->paths as $path) {
                    $pathValue = ltrim($path->path, '/');
                    if ($pathValue !== 'master/member') {
                        continue;
                    }

                    // GET list
                    if ($operation = $path->operations['get'] ?? null) {
                        $schema = Schema::fromType(
                            (new ObjectType)->example([
                                'success' => true,
                                'data' => [
                                    [
                                        'id' => 1,
                                        'name' => 'Joe Mendez',
                                        'identity_type' => 'other',
                                        'identity_number' => '001-221220-1010W',
                                        'joined_date' => '2026-01-24 00:00:00',
                                        'code' => 'CUS0001',
                                        'address' => 'Bo Martin Luther King',
                                        'email' => '+50589897898',
                                        'created_at' => '2026-01-24T23:30:36.000000Z',
                                        'updated_at' => '2026-01-24T23:30:36.000000Z',
                                    ],
                                    [
                                        'id' => 2,
                                        'name' => 'Maria Lopez',
                                        'identity_type' => 'other',
                                        'identity_number' => '001-010101-0001X',
                                        'joined_date' => '2026-01-25 00:00:00',
                                        'code' => 'CUS0002',
                                        'address' => 'Residencial Las Colinas',
                                        'email' => 'maria@example.com',
                                        'created_at' => '2026-01-25T02:02:29.000000Z',
                                        'updated_at' => '2026-01-25T02:02:29.000000Z',
                                    ],
                                ],
                                'message' => '',
                            ])
                        );

                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('List members')
                                ->setContent('application/json', $schema),
                            Response::make(401)
                                ->setDescription('Unauthenticated')
                                ->setContent('application/json', Schema::fromType(
                                    (new ObjectType)->example([
                                        'message' => 'Unauthenticated.',
                                    ])
                                )),
                        ];
                    }

                    // POST create
                    if ($operation = $path->operations['post'] ?? null) {
                        $memberType = (new ObjectType)
                            ->addProperty('id', new \Dedoc\Scramble\Support\Generator\Types\IntegerType)
                            ->addProperty('name', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('Maria Lopez'))
                            ->addProperty('identity_type', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('other'))
                            ->addProperty('identity_number', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('001-010101-0001X'))
                            ->addProperty('joined_date', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('2026-01-25 00:00:00'))
                            ->addProperty('code', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('CUS0002'))
                            ->addProperty('address', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('Residencial Las Colinas'))
                            ->addProperty('email', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('maria@example.com'))
                            ->addProperty('created_at', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('2026-01-25T02:02:29.000000Z'))
                            ->addProperty('updated_at', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('2026-01-25T02:02:29.000000Z'));

                        $createdType = (new ObjectType)
                            ->addProperty('success', (new \Dedoc\Scramble\Support\Generator\Types\BooleanType)->example(true))
                            ->addProperty('data', (new \Dedoc\Scramble\Support\Generator\Types\ArrayType)
                                ->setItems($memberType)
                                ->example([
                                    [
                                        'id' => 2,
                                        'name' => 'Maria Lopez',
                                        'identity_type' => 'other',
                                        'identity_number' => '001-010101-0001X',
                                        'joined_date' => '2026-01-25 00:00:00',
                                        'code' => 'CUS0002',
                                        'address' => 'Residencial Las Colinas',
                                        'email' => 'maria@example.com',
                                        'created_at' => '2026-01-25T02:02:29.000000Z',
                                        'updated_at' => '2026-01-25T02:02:29.000000Z',
                                    ],
                                ])
                            )
                            ->addProperty('message', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('success creating items'))
                            ->setRequired(['success', 'data', 'message'])
                            ->example([
                                'success' => true,
                                'data' => [
                                    [
                                        'id' => 2,
                                        'name' => 'Maria Lopez',
                                        'identity_type' => 'other',
                                        'identity_number' => '001-010101-0001X',
                                        'joined_date' => '2026-01-25 00:00:00',
                                        'code' => 'CUS0002',
                                        'address' => 'Residencial Las Colinas',
                                        'email' => 'maria@example.com',
                                        'created_at' => '2026-01-25T02:02:29.000000Z',
                                        'updated_at' => '2026-01-25T02:02:29.000000Z',
                                    ],
                                ],
                                'message' => 'success creating items',
                            ]);

                        $createdSchema = Schema::fromType($createdType);

                        $validationSchema = Schema::fromType(
                            (new ObjectType)->example([
                                'message' => 'El campo nombre es obligatorio.',
                                'errors' => [
                                    'name' => ['El campo nombre es obligatorio.'],
                                ],
                            ])
                        );

                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('Member created example')
                                ->setContent('application/json', $createdSchema),
                            Response::make(422)
                                ->setDescription('Validation error')
                                ->setContent('application/json', $validationSchema),
                        ];
                    }
                }

                // Member detail/update/delete
                foreach ($openApi->paths as $path) {
                    $pathValue = ltrim($path->path, '/');
                    if ($pathValue !== 'master/member/{member}') {
                        continue;
                    }

                    if ($operation = $path->operations['get'] ?? null) {
                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('Member detail')
                                ->setContent('application/json', Schema::fromType(
                                    (new ObjectType)->example([
                                        'success' => true,
                                        'data' => [
                                            'id' => 2,
                                            'name' => 'Maria Lopez',
                                            'identity_type' => 'other',
                                            'identity_number' => '001-010101-0001X',
                                            'joined_date' => '2026-01-25 00:00:00',
                                            'code' => 'CUS0002',
                                            'address' => 'Residencial Las Colinas, Casa 12',
                                            'email' => 'maria.lopez@example.com',
                                            'created_at' => '2026-01-25T02:02:29.000000Z',
                                            'updated_at' => '2026-01-25T02:05:00.000000Z',
                                        ],
                                        'message' => '',
                                    ])
                                )),
                        ];
                    }

                    if ($operation = $path->operations['put'] ?? null) {
                        $memberType = (new ObjectType)
                            ->addProperty('id', new \Dedoc\Scramble\Support\Generator\Types\IntegerType)
                            ->addProperty('name', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('Maria Lopez'))
                            ->addProperty('identity_type', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('other'))
                            ->addProperty('identity_number', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('001-010101-0001X'))
                            ->addProperty('joined_date', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('2026-01-25 00:00:00'))
                            ->addProperty('code', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('CUS0002'))
                            ->addProperty('address', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('Residencial Las Colinas, Casa 12'))
                            ->addProperty('email', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('maria.lopez@example.com'))
                            ->addProperty('created_at', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('2026-01-25T02:02:29.000000Z'))
                            ->addProperty('updated_at', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('2026-01-25T02:05:00.000000Z'));

                        $detailType = (new ObjectType)
                            ->addProperty('success', (new \Dedoc\Scramble\Support\Generator\Types\BooleanType)->example(true))
                            ->addProperty('data', (new \Dedoc\Scramble\Support\Generator\Types\ArrayType)
                                ->setItems($memberType)
                                ->example([
                                    [
                                        'id' => 2,
                                        'name' => 'Maria Lopez',
                                        'identity_type' => 'other',
                                        'identity_number' => '001-010101-0001X',
                                        'joined_date' => '2026-01-25 00:00:00',
                                        'code' => 'CUS0002',
                                        'address' => 'Residencial Las Colinas, Casa 12',
                                        'email' => 'maria.lopez@example.com',
                                        'created_at' => '2026-01-25T02:02:29.000000Z',
                                        'updated_at' => '2026-01-25T02:05:00.000000Z',
                                    ],
                                ])
                            )
                            ->addProperty('message', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('success updating items'))
                            ->setRequired(['success', 'data', 'message'])
                            ->example([
                                'success' => true,
                                'data' => [
                                    [
                                        'id' => 2,
                                        'name' => 'Maria Lopez',
                                        'identity_type' => 'other',
                                        'identity_number' => '001-010101-0001X',
                                        'joined_date' => '2026-01-25 00:00:00',
                                        'code' => 'CUS0002',
                                        'address' => 'Residencial Las Colinas, Casa 12',
                                        'email' => 'maria.lopez@example.com',
                                        'created_at' => '2026-01-25T02:02:29.000000Z',
                                        'updated_at' => '2026-01-25T02:05:00.000000Z',
                                    ],
                                ],
                                'message' => 'success updating items',
                            ]);

                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('Member updated')
                                ->setContent('application/json', Schema::fromType($detailType)),
                        ];
                    }

                    if ($operation = $path->operations['delete'] ?? null) {
                        $memberType = (new ObjectType)
                            ->addProperty('id', new \Dedoc\Scramble\Support\Generator\Types\IntegerType)
                            ->addProperty('name', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('Maria Lopez'));

                        $deleteType = (new ObjectType)
                            ->addProperty('success', (new \Dedoc\Scramble\Support\Generator\Types\BooleanType)->example(true))
                            ->addProperty('data', (new \Dedoc\Scramble\Support\Generator\Types\ArrayType)
                                ->setItems($memberType)
                                ->example([
                                    ['id' => 2, 'name' => 'Maria Lopez'],
                                ])
                            )
                            ->addProperty('message', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('success deleting items'))
                            ->setRequired(['success', 'data', 'message'])
                            ->example([
                                'success' => true,
                                'data' => [
                                    ['id' => 2, 'name' => 'Maria Lopez'],
                                ],
                                'message' => 'success deleting items',
                            ]);

                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('Member deleted')
                                ->setContent('application/json', Schema::fromType($deleteType)),
                        ];
                    }
                }
                // Examples for /api/setting (POST) and /api/setting/{key} (GET)
                foreach ($openApi->paths as $path) {
                    if ($path->path === 'setting' && ($operation = $path->operations['post'] ?? null)) {
                        $schema = Schema::fromType(
                            (new ObjectType)->example([
                                'success' => true,
                                'message' => 'success update setting',
                            ])
                        );

                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('Setting updated')
                                ->setContent('application/json', $schema),
                            Response::make(401)
                                ->setDescription('Unauthenticated')
                                ->setContent('application/json', Schema::fromType(
                                    (new ObjectType)->example([
                                        'message' => 'Unauthenticated.',
                                    ])
                                )),
                        ];
                    }

                    if ($path->path === 'setting/{key}' && ($operation = $path->operations['get'] ?? null)) {
                        $schema = Schema::fromType(
                            (new ObjectType)->example([
                                'success' => true,
                                'data' => [
                                    'key' => 'currency',
                                    'value' => 'USD',
                                ],
                            ])
                        );

                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('Setting value')
                                ->setContent('application/json', $schema),
                            Response::make(401)
                                ->setDescription('Unauthenticated')
                                ->setContent('application/json', Schema::fromType(
                                    (new ObjectType)->example([
                                        'message' => 'Unauthenticated.',
                                    ])
                                )),
                        ];
                    }
                }

                // Example for GET /api/transaction/selling
                foreach ($openApi->paths as $path) {
                    if ($path->path !== 'transaction/selling') {
                        continue;
                    }

                    if ($operation = $path->operations['get'] ?? null) {
                        $schema = Schema::fromType(
                            (new ObjectType)->example([
                                'success' => true,
                                'data' => [
                                    'data' => [
                                        [
                                            'id' => 7,
                                            'member_id' => null,
                                            'user_id' => 1,
                                            'code' => 'SELL0007',
                                            'payment_method_id' => 1,
                                            'payed_money' => 21000000,
                                            'money_changes' => 90000,
                                            'total_price' => 20910000,
                                            'grand_total_price' => 20500000,
                                            'total_cost' => 15500000,
                                            'discount' => 0,
                                            'discount_price' => 20910000,
                                            'total_discount_per_item' => 0,
                                            'total_discount' => 0,
                                            'friend_price' => 0,
                                            'tax' => 2,
                                            'tax_price' => '410000.00',
                                            'total_qty' => 2,
                                            'note' => null,
                                            'created_at' => '2026-01-25T03:01:13.000000Z',
                                            'updated_at' => '2026-01-25T03:01:13.000000Z',
                                            'member' => null,
                                            'payment_method' => [
                                                'id' => 1,
                                                'name' => 'Cash',
                                                'is_cash' => true,
                                                'is_debit' => false,
                                                'is_credit' => false,
                                                'is_wallet' => false,
                                                'icon' => 'http://localdomain.test:8000/assets/images/payment-methods/cash.png',
                                                'waletable_type' => null,
                                                'waletable_id' => null,
                                                'created_at' => '2026-01-24T09:02:30.000000Z',
                                                'updated_at' => '2026-01-24T09:02:30.000000Z',
                                                'deleted_at' => null,
                                            ],
                                            'selling_details' => [
                                                [
                                                    'id' => 9,
                                                    'selling_id' => 7,
                                                    'product_id' => 2,
                                                    'qty' => 1,
                                                    'cost' => 6500000,
                                                    'price' => 8500000,
                                                    'discount' => 0,
                                                    'discount_price' => 8500000,
                                                    'created_at' => '2026-01-25T03:01:13.000000Z',
                                                    'updated_at' => '2026-01-25T03:01:13.000000Z',
                                                    'product' => [
                                                        'id' => 2,
                                                        'name' => 'Laptop Air 13',
                                                        'category' => [
                                                            'id' => 1,
                                                            'name' => 'UMUM',
                                                            'created_at' => '2026-01-24T09:02:29.000000Z',
                                                            'updated_at' => '2026-01-24T09:02:29.000000Z',
                                                        ],
                                                        'category_id' => 1,
                                                        'initial_price' => 6500000,
                                                        'selling_price' => 8500000,
                                                        'type' => 'product',
                                                        'unit' => 'PCS',
                                                        'stock' => 21,
                                                        'is_non_stock' => false,
                                                        'hero_images' => [],
                                                        'sku' => 'LTP-002',
                                                        'barcode' => '9876543210000',
                                                        'show' => 1,
                                                    ],
                                                ],
                                                [
                                                    'id' => 10,
                                                    'selling_id' => 7,
                                                    'product_id' => 1,
                                                    'qty' => 1,
                                                    'cost' => 9000000,
                                                    'price' => 12000000,
                                                    'discount' => 0,
                                                    'discount_price' => 12000000,
                                                    'created_at' => '2026-01-25T03:01:13.000000Z',
                                                    'updated_at' => '2026-01-25T03:01:13.000000Z',
                                                    'product' => [
                                                        'id' => 1,
                                                        'name' => 'Laptop Pro 15',
                                                        'category' => [
                                                            'id' => 1,
                                                            'name' => 'UMUM',
                                                            'created_at' => '2026-01-24T09:02:29.000000Z',
                                                            'updated_at' => '2026-01-24T09:02:29.000000Z',
                                                        ],
                                                        'category_id' => 1,
                                                        'initial_price' => 9000000,
                                                        'selling_price' => 12000000,
                                                        'type' => 'product',
                                                        'unit' => 'PCS',
                                                        'stock' => 6,
                                                        'is_non_stock' => false,
                                                        'hero_images' => [],
                                                        'sku' => 'LTP-001',
                                                        'barcode' => '1234567890123',
                                                        'show' => 1,
                                                    ],
                                                ],
                                            ],
                                            'cashier' => [
                                                'id' => 1,
                                                'is_owner' => 1,
                                                'name' => 'Mickey Gudiel',
                                                'email' => 'mickeyanthonygudiel@gmail.com',
                                                'email_verified_at' => null,
                                                'created_at' => '2026-01-24T08:59:01.000000Z',
                                                'updated_at' => '2026-01-25T02:48:43.000000Z',
                                                'deleted_at' => null,
                                            ],
                                        ],
                                    ],
                                    'links' => [
                                        'first' => 'http://tenantdemo.localdomain.test:8000/api/transaction/selling?page=1',
                                        'prev' => null,
                                        'next' => null,
                                    ],
                                    'meta' => [
                                        'current_page' => 1,
                                        'from' => 1,
                                        'path' => 'http://tenantdemo.localdomain.test:8000/api/transaction/selling',
                                        'per_page' => 10,
                                        'to' => 1,
                                    ],
                                ],
                                'message' => 'success get sellings',
                            ])
                        );

                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('List sellings')
                                ->setContent('application/json', $schema),
                            Response::make(401)
                                ->setDescription('Unauthenticated')
                                ->setContent('application/json', Schema::fromType(
                                    (new ObjectType)->example([
                                        'message' => 'Unauthenticated.',
                                    ])
                                )),
                        ];
                    }

                    if ($operation = $path->operations['post'] ?? null) {
                        $schema = Schema::fromType(
                            (new ObjectType)->example([
                                'success' => true,
                                    'data' => [
                                        'id' => 7,
                                        'member_id' => null,
                                        'user_id' => 1,
                                        'code' => 'SELL0007',
                                        'payment_method_id' => 1,
                                        'payed_money' => 21000000,
                                        'money_changes' => 90000,
                                        'total_price' => 20910000,
                                        'grand_total_price' => 20500000,
                                    'total_cost' => 15500000,
                                    'discount' => 0,
                                    'discount_price' => 20910000,
                                    'total_discount_per_item' => 0,
                                    'total_discount' => 0,
                                    'friend_price' => null,
                                    'tax' => '2',
                                        'tax_price' => 410000,
                                        'total_qty' => 2,
                                        'note' => null,
                                        'created_at' => '2026-01-25T03:01:13.000000Z',
                                        'updated_at' => '2026-01-25T03:01:13.000000Z',
                                        'member' => null,
                                        'payment_method' => [
                                            'id' => 1,
                                            'name' => 'Cash',
                                        'is_cash' => true,
                                        'is_debit' => false,
                                        'is_credit' => false,
                                        'is_wallet' => false,
                                        'icon' => 'http://localdomain.test:8000/assets/images/payment-methods/cash.png',
                                        'waletable_type' => null,
                                        'waletable_id' => null,
                                        'created_at' => '2026-01-24T09:02:30.000000Z',
                                        'updated_at' => '2026-01-24T09:02:30.000000Z',
                                        'deleted_at' => null,
                                        ],
                                        'selling_details' => [
                                            [
                                                'id' => 9,
                                                'selling_id' => 7,
                                                'product_id' => 2,
                                                'qty' => 1,
                                                'cost' => 6500000,
                                                'price' => 8500000,
                                                'discount' => 0,
                                                'discount_price' => 8500000,
                                                'created_at' => '2026-01-25T03:01:13.000000Z',
                                                'updated_at' => '2026-01-25T03:01:13.000000Z',
                                                'product' => [
                                                    'id' => 2,
                                                    'name' => 'Laptop Air 13',
                                                    'category' => [
                                                        'id' => 1,
                                                        'name' => 'UMUM',
                                                        'created_at' => '2026-01-24T09:02:29.000000Z',
                                                        'updated_at' => '2026-01-24T09:02:29.000000Z',
                                                    ],
                                                    'category_id' => 1,
                                                    'initial_price' => 6500000,
                                                    'selling_price' => 8500000,
                                                    'type' => 'product',
                                                    'unit' => 'PCS',
                                                    'stock' => 21,
                                                    'is_non_stock' => false,
                                                    'hero_images' => [],
                                                    'sku' => 'LTP-002',
                                                    'barcode' => '9876543210000',
                                                    'show' => 1,
                                            ],
                                        ],
                                            [
                                                'id' => 10,
                                                'selling_id' => 7,
                                                'product_id' => 1,
                                                'qty' => 1,
                                                'cost' => 9000000,
                                                'price' => 12000000,
                                                'discount' => 0,
                                                'discount_price' => 12000000,
                                                'created_at' => '2026-01-25T03:01:13.000000Z',
                                                'updated_at' => '2026-01-25T03:01:13.000000Z',
                                                'product' => [
                                                    'id' => 1,
                                                    'name' => 'Laptop Pro 15',
                                                    'category' => [
                                                        'id' => 1,
                                                    'name' => 'UMUM',
                                                    'created_at' => '2026-01-24T09:02:29.000000Z',
                                                    'updated_at' => '2026-01-24T09:02:29.000000Z',
                                                ],
                                                    'category_id' => 1,
                                                    'initial_price' => 9000000,
                                                    'selling_price' => 12000000,
                                                    'type' => 'product',
                                                    'unit' => 'PCS',
                                                    'stock' => 6,
                                                    'is_non_stock' => false,
                                                    'hero_images' => [],
                                                    'sku' => 'LTP-001',
                                                    'barcode' => '1234567890123',
                                                    'show' => 1,
                                            ],
                                        ],
                                        ],
                                        'cashier' => [
                                            'id' => 1,
                                            'is_owner' => 1,
                                            'name' => 'Mickey Gudiel',
                                            'email' => 'mickeyanthonygudiel@gmail.com',
                                            'email_verified_at' => null,
                                            'created_at' => '2026-01-24T08:59:01.000000Z',
                                            'updated_at' => '2026-01-25T02:48:43.000000Z',
                                            'deleted_at' => null,
                                        ],
                                    ],
                                    'message' => 'success create selling',
                                ])
                        );

                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('Selling created')
                                ->setContent('application/json', $schema),
                            Response::make(401)
                                ->setDescription('Unauthenticated')
                                ->setContent('application/json', Schema::fromType(
                                    (new ObjectType)->example([
                                        'message' => 'Unauthenticated.',
                                    ])
                                )),
                        ];

                        // Request body example.
                        $productItemType = (new \Dedoc\Scramble\Support\Generator\Types\ObjectType)
                            ->addProperty('product_id', (new \Dedoc\Scramble\Support\Generator\Types\IntegerType)->example(2))
                            ->addProperty('qty', (new \Dedoc\Scramble\Support\Generator\Types\IntegerType)->example(1))
                            ->addProperty('price', (new \Dedoc\Scramble\Support\Generator\Types\NumberType)->example(8500000))
                            ->addProperty('discount_price', (new \Dedoc\Scramble\Support\Generator\Types\NumberType)->example(0));

                        $requestType = (new ObjectType)
                            ->addProperty('payment_method_id', (new \Dedoc\Scramble\Support\Generator\Types\IntegerType)->example(1))
                            ->addProperty('friend_price', (new \Dedoc\Scramble\Support\Generator\Types\BooleanType)->example(false))
                            ->addProperty('payed_money', (new \Dedoc\Scramble\Support\Generator\Types\NumberType)->example(21000000))
                            ->addProperty('products', (new \Dedoc\Scramble\Support\Generator\Types\ArrayType)
                                ->setItems($productItemType)
                                ->example([
                                    ['product_id' => 2, 'qty' => 1, 'price' => 8500000, 'discount_price' => 0],
                                    ['product_id' => 1, 'qty' => 1, 'price' => 12000000, 'discount_price' => 0],
                                ])
                            )
                            ->setRequired(['payment_method_id', 'friend_price', 'payed_money', 'products']);

                        $operation->addRequestBodyObject(
                            RequestBodyObject::make()
                                ->setContent('application/json', Schema::fromType($requestType))
                                ->required()
                                ->description('Selling payload')
                        );
                    }

                    if ($operation = $path->operations['get_id'] ?? $path->operations['get'] ?? null) {
                        // handled above for list; below handles detail when path has {selling}
                    }
                }

                // Example for GET /api/transaction/selling/{selling}
                foreach ($openApi->paths as $path) {
                    if ($path->path !== 'transaction/selling/{selling}') {
                        continue;
                    }

                    $operation = $path->operations['get'] ?? null;
                    if (! $operation) {
                        continue;
                    }

                    $schema = Schema::fromType(
                        (new ObjectType)->example([
                            'success' => true,
                            'data' => [
                                'id' => 7,
                                'member_id' => null,
                                'user_id' => 1,
                                'code' => 'SELL0007',
                                'payment_method_id' => 1,
                                'payed_money' => 21000000,
                                'money_changes' => 90000,
                                'total_price' => 20910000,
                                'grand_total_price' => 20500000,
                                'total_cost' => 15500000,
                                'discount' => 0,
                                'discount_price' => 20910000,
                                'total_discount_per_item' => 0,
                                'total_discount' => 0,
                                'friend_price' => 0,
                                'tax' => 2,
                                'tax_price' => '410000.00',
                                'total_qty' => 2,
                                'note' => null,
                                'created_at' => '2026-01-25T03:01:13.000000Z',
                                'updated_at' => '2026-01-25T03:01:13.000000Z',
                                'member' => null,
                                'payment_method' => [
                                    'id' => 1,
                                    'name' => 'Cash',
                                    'is_cash' => true,
                                    'is_debit' => false,
                                    'is_credit' => false,
                                    'is_wallet' => false,
                                    'icon' => 'http://localdomain.test:8000/assets/images/payment-methods/cash.png',
                                    'waletable_type' => null,
                                    'waletable_id' => null,
                                    'created_at' => '2026-01-24T09:02:30.000000Z',
                                    'updated_at' => '2026-01-24T09:02:30.000000Z',
                                    'deleted_at' => null,
                                ],
                                'selling_details' => [
                                    [
                                        'id' => 9,
                                        'selling_id' => 7,
                                        'product_id' => 2,
                                        'qty' => 1,
                                        'cost' => 6500000,
                                        'price' => 8500000,
                                        'discount' => 0,
                                        'discount_price' => 8500000,
                                        'created_at' => '2026-01-25T03:01:13.000000Z',
                                        'updated_at' => '2026-01-25T03:01:13.000000Z',
                                    ],
                                    [
                                        'id' => 10,
                                        'selling_id' => 7,
                                        'product_id' => 1,
                                        'qty' => 1,
                                        'cost' => 9000000,
                                        'price' => 12000000,
                                        'discount' => 0,
                                        'discount_price' => 12000000,
                                        'created_at' => '2026-01-25T03:01:13.000000Z',
                                        'updated_at' => '2026-01-25T03:01:13.000000Z',
                                    ],
                                ],
                                'cashier' => [
                                    'id' => 1,
                                    'is_owner' => 1,
                                    'name' => 'Mickey Gudiel',
                                    'email' => 'mickeyanthonygudiel@gmail.com',
                                    'email_verified_at' => null,
                                    'created_at' => '2026-01-24T08:59:01.000000Z',
                                    'updated_at' => '2026-01-25T02:48:43.000000Z',
                                    'deleted_at' => null,
                                ],
                            ],
                            'message' => 'success get selling',
                        ])
                    );

                    $operation->responses = [
                        Response::make(200)
                            ->setDescription('Selling detail')
                            ->setContent('application/json', $schema),
                        Response::make(401)
                            ->setDescription('Unauthenticated')
                            ->setContent('application/json', Schema::fromType(
                                (new ObjectType)->example([
                                    'message' => 'Unauthenticated.',
                                ])
                            )),
                    ];

                    if ($operation = $path->operations['post'] ?? null) {
                        $memberType = (new ObjectType)
                            ->addProperty('id', new \Dedoc\Scramble\Support\Generator\Types\IntegerType)
                            ->addProperty('name', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('Maria Lopez'))
                            ->addProperty('identity_type', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('other'))
                            ->addProperty('identity_number', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('001-010101-0001X'))
                            ->addProperty('joined_date', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('2026-01-25 00:00:00'))
                            ->addProperty('code', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('CUS0002'))
                            ->addProperty('address', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('Residencial Las Colinas'))
                            ->addProperty('email', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('maria@example.com'))
                            ->addProperty('created_at', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('2026-01-25T02:02:29.000000Z'))
                            ->addProperty('updated_at', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('2026-01-25T02:02:29.000000Z'));

                        $createdType = (new ObjectType)
                            ->addProperty('success', (new \Dedoc\Scramble\Support\Generator\Types\BooleanType)->example(true))
                            ->addProperty('data', (new \Dedoc\Scramble\Support\Generator\Types\ArrayType)
                                ->setItems($memberType)
                                ->example([
                                    [
                                        'id' => 2,
                                        'name' => 'Maria Lopez',
                                        'identity_type' => 'other',
                                        'identity_number' => '001-010101-0001X',
                                        'joined_date' => '2026-01-25 00:00:00',
                                        'code' => 'CUS0002',
                                        'address' => 'Residencial Las Colinas',
                                        'email' => 'maria@example.com',
                                        'created_at' => '2026-01-25T02:02:29.000000Z',
                                        'updated_at' => '2026-01-25T02:02:29.000000Z',
                                    ],
                                ])
                            )
                            ->addProperty('message', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('success creating items'))
                            ->setRequired(['success', 'data', 'message'])
                            ->example([
                                'success' => true,
                                'data' => [
                                    [
                                        'id' => 2,
                                        'name' => 'Maria Lopez',
                                        'identity_type' => 'other',
                                        'identity_number' => '001-010101-0001X',
                                        'joined_date' => '2026-01-25 00:00:00',
                                        'code' => 'CUS0002',
                                        'address' => 'Residencial Las Colinas',
                                        'email' => 'maria@example.com',
                                        'created_at' => '2026-01-25T02:02:29.000000Z',
                                        'updated_at' => '2026-01-25T02:02:29.000000Z',
                                    ],
                                ],
                                'message' => 'success creating items',
                            ]);

                        $createdSchema = Schema::fromType($createdType);

                        $validationSchema = Schema::fromType(
                            (new ObjectType)->example([
                                'message' => 'El campo nombre es obligatorio.',
                                'errors' => [
                                    'name' => ['El campo nombre es obligatorio.'],
                                ],
                            ])
                        );

                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('Member created example')
                                ->setContent('application/json', $createdSchema),
                            Response::make(422)
                                ->setDescription('Validation error')
                                ->setContent('application/json', $validationSchema),
                        ];
                        // Ensure replacement wins over auto-generated schema.
                        $operation->responses = array_values($operation->responses);
                    }
                }

                // Auth/me and logout
                foreach ($openApi->paths as $path) {
                    if ($path->path === 'auth/me') {
                        if ($operation = $path->operations['get'] ?? null) {
                            $schema = Schema::fromType(
                                (new ObjectType)->example([
                                    'success' => true,
                                    'data' => [
                                        'id' => 1,
                                        'is_owner' => 1,
                                        'name' => 'MICKEY GUDIEL REYES',
                                        'email' => 'mickeyanthonygudiel@gmail.com',
                                        'email_verified_at' => null,
                                        'created_at' => '2026-01-24T08:59:01.000000Z',
                                        'updated_at' => '2026-01-24T23:19:37.000000Z',
                                        'deleted_at' => null,
                                    ],
                                ])
                            );

                            $operation->responses = [
                                Response::make(200)
                                    ->setDescription('Authenticated user')
                                    ->setContent('application/json', $schema),
                            ];
                        }
                        if ($operation = $path->operations['put'] ?? null) {
                            $schema = Schema::fromType(
                                (new ObjectType)->example([
                                    'success' => true,
                                    'message' => 'Profile updated successfully',
                                ])
                            );

                            $operation->responses = [
                                Response::make(200)
                                    ->setDescription('Profile updated')
                                    ->setContent('application/json', $schema),
                            ];

                            $requestType = (new ObjectType)
                                ->addProperty('name', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('Mickey Gudiel'))
                                ->addProperty('phone', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('8888999999'))
                                ->addProperty('address', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('Managua, Nicaragua'))
                                ->setRequired(['name']);

                            $operation->addRequestBodyObject(
                                RequestBodyObject::make()
                                    ->setContent('application/json', Schema::fromType($requestType))
                                    ->required()
                                    ->description('Profile payload')
                            );
                        }
                    }

                    if ($path->path === 'auth/logout' && ($operation = $path->operations['post'] ?? null)) {
                        $schema = Schema::fromType(
                            (new ObjectType)->example([
                                'message' => 'Successfully logged out',
                            ])
                        );

                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('Logged out')
                                ->setContent('application/json', $schema),
                        ];
                    }
                }

                // Payment methods
                foreach ($openApi->paths as $path) {
                    if ($path->path !== 'master/payment-method') {
                        continue;
                    }

                    $operation = $path->operations['get'] ?? null;
                    if (! $operation) {
                        continue;
                    }

                    $schema = Schema::fromType(
                        (new ObjectType)->example([
                            'success' => true,
                            'data' => [
                                [
                                    'id' => 1,
                                    'name' => 'Cash',
                                    'is_cash' => true,
                                    'is_debit' => false,
                                    'is_credit' => false,
                                    'is_wallet' => false,
                                    'icon' => 'http://localdomain.test:8000/assets/images/payment-methods/cash.png',
                                    'waletable_type' => null,
                                    'waletable_id' => null,
                                    'created_at' => '2026-01-24T09:02:30.000000Z',
                                    'updated_at' => '2026-01-24T09:02:30.000000Z',
                                    'deleted_at' => null,
                                ],
                            ],
                            'message' => 'success get payment methods',
                        ])
                    );

                    $operation->responses = [
                        Response::make(200)
                            ->setDescription('List payment methods')
                            ->setContent('application/json', $schema),
                    ];
                }

                // Category
                foreach ($openApi->paths as $path) {
                    if ($path->path !== 'master/category') {
                        continue;
                    }
                    if ($operation = $path->operations['post'] ?? null) {
                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('Category created')
                                ->setContent('application/json', Schema::fromType(
                                    (new ObjectType)->example([
                                        'success' => true,
                                        'message' => 'success creating items',
                                    ])
                                )),
                            Response::make(422)
                                ->setDescription('Validation error')
                                ->setContent('application/json', Schema::fromType(
                                    (new ObjectType)->example([
                                        'message' => 'The name field is required.',
                                        'errors' => ['name' => ['The name field is required.']],
                                    ])
                                )),
                        ];
                    }
                    // list already handled earlier; detail/update/delete handled below
                }

                // Category detail/update/delete
                foreach ($openApi->paths as $path) {
                    if ($path->path !== 'master/category/{category}') {
                        continue;
                    }

                    if ($operation = $path->operations['get'] ?? null) {
                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('Category detail')
                                ->setContent('application/json', Schema::fromType(
                                    (new ObjectType)->example([
                                        'success' => true,
                                        'data' => [
                                            'id' => 1,
                                            'name' => 'UMUM',
                                            'created_at' => '2026-01-24T09:02:29.000000Z',
                                            'updated_at' => '2026-01-24T09:02:29.000000Z',
                                        ],
                                    ])
                                )),
                        ];
                    }

                    if ($operation = $path->operations['put'] ?? null) {
                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('Category updated')
                                ->setContent('application/json', Schema::fromType(
                                    (new ObjectType)->example([
                                        'success' => true,
                                        'message' => 'success updating items',
                                    ])
                                )),
                        ];
                    }

                    if ($operation = $path->operations['delete'] ?? null) {
                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('Category deleted')
                                ->setContent('application/json', Schema::fromType(
                                    (new ObjectType)->example([
                                        'success' => true,
                                        'message' => 'success deleting items',
                                    ])
                                )),
                        ];
                    }
                }

                // Cash drawer open/close/list
                foreach ($openApi->paths as $path) {
                    if ($path->path === 'transaction/cash-drawer' && ($operation = $path->operations['get'] ?? null)) {
                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('Cash drawer status')
                                ->setContent('application/json', Schema::fromType(
                                    (new ObjectType)->example([
                                        'success' => false,
                                        'message' => 'cash drawer already closed or not opened yet',
                                    ])
                                )),
                        ];
                    }
                    if ($path->path === 'transaction/cash-drawer' && ($operation = $path->operations['post'] ?? null)) {
                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('Cash drawer opened')
                                ->setContent('application/json', Schema::fromType(
                                    (new ObjectType)->example([
                                        'success' => true,
                                        'message' => 'cash drawer opened',
                                    ])
                                )),
                        ];
                    }
                    if ($path->path === 'transaction/cash-drawer/close' && ($operation = $path->operations['post'] ?? null)) {
                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('Cash drawer closed')
                                ->setContent('application/json', Schema::fromType(
                                    (new ObjectType)->example([
                                        'success' => true,
                                        'message' => 'cash drawer closed',
                                    ])
                                )),
                        ];
                    }
                }

                // Dashboard totals
                foreach ($openApi->paths as $path) {
                    if (in_array($path->path, ['transaction/dashboard/total-revenue', 'transaction/dashboard/total-gross-profit', 'transaction/dashboard/total-sales'])) {
                        $operation = $path->operations['get'] ?? null;
                        if (! $operation) {
                            continue;
                        }
                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('Dashboard metric')
                                ->setContent('application/json', Schema::fromType(
                                    (new ObjectType)->example([
                                        'success' => true,
                                        'data' => [
                                            'total' => match ($path->path) {
                                                'transaction/dashboard/total-revenue' => 20500000,
                                                'transaction/dashboard/total-gross-profit' => 5000000,
                                                'transaction/dashboard/total-sales' => 6,
                                                default => 0,
                                            },
                                        ],
                                    ])
                                )),
                        ];
                    }
                }

                // Notifications
                foreach ($openApi->paths as $path) {
                    if ($path->path === 'notification' && ($operation = $path->operations['get'] ?? null)) {
                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('List notifications')
                                ->setContent('application/json', Schema::fromType(
                                    (new ObjectType)->example([
                                        'success' => true,
                                        'data' => [
                                            [
                                                'id' => 1,
                                                'title' => 'Stock alert',
                                                'body' => 'Laptop Pro 15 is below minimum stock',
                                                'type' => 'stock',
                                                'created_at' => '2026-01-25T01:05:00.000000Z',
                                                'product' => [
                                                    'id' => 1,
                                                    'name' => 'Laptop Pro 15',
                                                    'stock' => 7,
                                                ],
                                            ],
                                        ],
                                        'message' => 'success get notifications',
                                    ])
                                )),
                        ];
                    }
                    if ($path->path === 'notification/{notification}/{product}' && ($operation = $path->operations['put'] ?? null)) {
                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('Notification updated')
                                ->setContent('application/json', Schema::fromType(
                                    (new ObjectType)->example([
                                        'success' => true,
                                        'message' => 'success update notification',
                                        'data' => [
                                            'id' => 1,
                                            'title' => 'Stock alert',
                                            'body' => 'Laptop Pro 15 is below minimum stock',
                                            'type' => 'stock',
                                            'product' => [
                                                'id' => 1,
                                                'name' => 'Laptop Pro 15',
                                                'stock' => 7,
                                            ],
                                            'updated_at' => '2026-01-25T01:06:00.000000Z',
                                        ],
                                    ])
                                )),
                        ];
                    }
                    if ($path->path === 'notification/clear' && ($operation = $path->operations['delete'] ?? null)) {
                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('Notifications cleared')
                                ->setContent('application/json', Schema::fromType(
                                    (new ObjectType)->example([
                                        'success' => true,
                                        'message' => 'success clear notification',
                                        'data' => [],
                                    ])
                                )),
                        ];
                    }
                }

                // Forgot/reset/email verification
                foreach ($openApi->paths as $path) {
                    if ($path->path === 'auth/forgot-password' && ($operation = $path->operations['post'] ?? null)) {
                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('Reset link sent')
                                ->setContent('application/json', Schema::fromType(
                                    (new ObjectType)->example([
                                        'status' => 'passwords.sent',
                                    ])
                                )),
                        ];
                    }
                    if ($path->path === 'auth/email/verification-notification' && ($operation = $path->operations['post'] ?? null)) {
                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('Verification email sent')
                                ->setContent('application/json', Schema::fromType(
                                    (new ObjectType)->example([
                                        'status' => 'verification-link-sent',
                                        'message' => 'Email verification link sent',
                                    ])
                                )),
                        ];
                    }
                    if ($path->path === 'auth/verify-email/{id}/{hash}' && ($operation = $path->operations['get'] ?? null)) {
                        $operation->description('Mark the authenticated user\'s email as verified (uses signed URL from email).');

                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('Email verified')
                                ->setContent('application/json', Schema::fromType(
                                    (new ObjectType)->example([
                                        'message' => 'Email verified successfully.',
                                    ])
                                )),
                            Response::make(403)
                                ->setDescription('Invalid or expired signature')
                                ->setContent('application/json', Schema::fromType(
                                    (new ObjectType)->example([
                                        'message' => 'Invalid signature.',
                                    ])
                                )),
                        ];
                    }
                }

                // Printer
                foreach ($openApi->paths as $path) {
                    if ($path->path === 'printer' && ($operation = $path->operations['get'] ?? null)) {
                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('List printers')
                                ->setContent('application/json', Schema::fromType(
                                    (new ObjectType)->example([
                                        'success' => true,
                                        'data' => [
                                            [
                                                'id' => 1,
                                                'name' => 'Thermal POS',
                                                'type' => 'network',
                                                'address' => '192.168.0.50',
                                                'width' => 80,
                                                'auto_cut' => true,
                                                'created_at' => '2026-01-25T01:10:00.000000Z',
                                                'updated_at' => '2026-01-25T01:10:00.000000Z',
                                            ],
                                        ],
                                        'message' => 'success get printers',
                                    ])
                                )),
                        ];
                    }
                    if ($path->path === 'printer/{printer}' && ($operation = $path->operations['put'] ?? null)) {
                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('Printer updated')
                                ->setContent('application/json', Schema::fromType(
                                    (new ObjectType)->example([
                                        'success' => true,
                                        'message' => 'success update printer',
                                    ])
                                )),
                        ];
                    }
                    if ($path->path === 'printer' && ($operation = $path->operations['post'] ?? null)) {
                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('Printer created')
                                ->setContent('application/json', Schema::fromType(
                                    (new ObjectType)->example([
                                        'success' => true,
                                        'message' => 'success creating printer',
                                        'data' => [
                                            'id' => 1,
                                            'name' => 'Thermal POS',
                                            'type' => 'network',
                                            'address' => '192.168.0.50',
                                            'width' => 80,
                                            'auto_cut' => true,
                                            'created_at' => '2026-01-25T01:10:00.000000Z',
                                            'updated_at' => '2026-01-25T01:10:00.000000Z',
                                        ],
                                    ])
                                )),
                        ];
                    }
                    if ($path->path === 'printer/{printer}' && ($operation = $path->operations['delete'] ?? null)) {
                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('Printer deleted')
                                ->setContent('application/json', Schema::fromType(
                                    (new ObjectType)->example([
                                        'success' => true,
                                        'message' => 'success deleting printer',
                                    ])
                                )),
                        ];
                    }
                }

                // FCM token
                foreach ($openApi->paths as $path) {
                    if ($path->path === 'register-fcm-token' && ($operation = $path->operations['post'] ?? null)) {
                        $requestType = (new ObjectType)
                            ->addProperty('token', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('fcm-token-123'))
                            ->addProperty('device', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('iPhone 14 Pro'))
                            ->setRequired(['token']);

                        $operation->addRequestBodyObject(
                            RequestBodyObject::make()
                                ->setContent('application/json', Schema::fromType($requestType))
                                ->required()
                                ->description('FCM token payload')
                        );

                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('FCM token stored')
                                ->setContent('application/json', Schema::fromType(
                                    (new ObjectType)->example([
                                        'success' => true,
                                        'message' => 'success register device',
                                    ])
                                )),
                        ];
                    }
                }

                // Domain register
                foreach ($openApi->paths as $path) {
                    if ($path->path === 'domain/register' && ($operation = $path->operations['post'] ?? null)) {
                        $requestType = (new ObjectType)
                            ->addProperty('domain', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('tenantdemo'))
                            ->addProperty('email', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('owner@example.com'))
                            ->addProperty('password', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('Secret123'))
                            ->addProperty('password_confirmation', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('Secret123'))
                            ->addProperty('business_type', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->enum(['retail', 'wholesale', 'fnb', 'fashion', 'pharmacy', 'other'])->example('retail'))
                            ->addProperty('other_business_type', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example(null)->nullable(true))
                            ->addProperty('full_name', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('Tenant Demo'))
                            ->setRequired(['domain', 'email', 'password', 'password_confirmation', 'business_type', 'full_name']);

                        $operation->addRequestBodyObject(
                            RequestBodyObject::make()
                                ->setContent('application/json', Schema::fromType($requestType))
                                ->required()
                                ->description('Tenant registration payload')
                        );

                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('Tenant registered')
                                ->setContent('application/json', Schema::fromType(
                                    (new ObjectType)->example([
                                        'success' => true,
                                        'data' => [
                                            'id' => 1,
                                            'tenancy_db_name' => 'lakasir_tenantdemo',
                                            'domain' => 'tenantdemo.localdomain.test',
                                        ],
                                        'message' => 'tenant registered',
                                    ])
                                )),
                            Response::make(422)
                                ->setDescription('Validation error')
                                ->setContent('application/json', Schema::fromType(
                                    (new ObjectType)->example([
                                        'message' => 'The domain has already been taken.',
                                        'errors' => ['domain' => ['The domain has already been taken.']],
                                    ])
                                )),
                        ];
                    }
                }

                // Secure initial price endpoints
                foreach ($openApi->paths as $path) {
                    if ($path->path === 'setting/secure-initial-price' && ($operation = $path->operations['post'] ?? null)) {
                        $operation->description('Set or update the extra password required to view initial product prices (secure_initial_price_enabled must be true).');

                        $requestType = (new ObjectType)
                            ->addProperty('old_password', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('OldP@ss!')->nullable(true))
                            ->addProperty('password', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('NewP@ssw0rd!'))
                            ->addProperty('password_confirmation', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('NewP@ssw0rd!'))
                            ->setRequired(['password', 'password_confirmation']);

                        $operation->addRequestBodyObject(
                            RequestBodyObject::make()
                                ->setContent('application/json', Schema::fromType($requestType))
                                ->required()
                                ->description('Set secure initial price password')
                        );

                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('Secure initial price enabled')
                                ->setContent('application/json', Schema::fromType(
                                    (new ObjectType)->example([
                                        'success' => true,
                                        'message' => 'secure initial price password has been set',
                                    ])
                                )),
                            Response::make(403)
                                ->setDescription('Secure initial price not enabled / already set / old password mismatch')
                                ->setContent('application/json', Schema::fromType(
                                    (new ObjectType)->example([
                                        'success' => false,
                                        'message' => 'secure initial price password has already been set',
                                    ])
                                )),
                        ];
                    }
                    if ($path->path === 'setting/secure-initial-price/verify' && ($operation = $path->operations['post'] ?? null)) {
                        $operation->description('Validate the secure initial price password before exposing initial costs.');

                        $requestType = (new ObjectType)
                            ->addProperty('password', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('P@ssw0rd!'))
                            ->setRequired(['password']);

                        $operation->addRequestBodyObject(
                            RequestBodyObject::make()
                                ->setContent('application/json', Schema::fromType($requestType))
                                ->required()
                                ->description('Verify secure initial price password')
                        );

                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('Secure initial price verified')
                                ->setContent('application/json', Schema::fromType(
                                    (new ObjectType)->example([
                                        'success' => true,
                                        'message' => 'secure initial price password is valid',
                                    ])
                                )),
                            Response::make(403)
                                ->setDescription('Secure initial price not enabled or invalid password')
                                ->setContent('application/json', Schema::fromType(
                                    (new ObjectType)->example([
                                        'success' => false,
                                        'message' => 'secure initial price password is not valid',
                                    ])
                                )),
                        ];
                    }
                }

                // Product stock endpoints
                foreach ($openApi->paths as $path) {
                    if ($path->path === 'master/product/{product}/stock' && ($operation = $path->operations['get'] ?? null)) {
                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('Product stock history')
                                ->setContent('application/json', Schema::fromType(
                                    (new ObjectType)->example([
                                        'success' => true,
                                        'data' => [
                                            'data' => [
                                                [
                                                    'id' => 1,
                                                    'product' => 'Laptop Pro 15',
                                                    'stock' => 6,
                                                    'init_stock' => 10,
                                                    'initial_price' => 9000000,
                                                    'selling_price' => 12000000,
                                                    'type' => 'in',
                                                    'date' => '2026-01-24',
                                                ],
                                            ],
                                            'links' => [
                                                'first' => 'http://tenantdemo.localdomain.test:8000/api/master/product/1/stock?page=1',
                                                'prev' => null,
                                                'next' => null,
                                            ],
                                            'meta' => [
                                                'current_page' => 1,
                                                'from' => null,
                                                'path' => 'http://tenantdemo.localdomain.test:8000/api/master/product/1/stock',
                                                'per_page' => 15,
                                                'to' => null,
                                            ],
                                        ],
                                    ])
                                )),
                        ];
                    }
                    if ($path->path === 'master/product/{product}/stock' && ($operation = $path->operations['post'] ?? null)) {
                        $requestType = (new ObjectType)
                            ->addProperty('stock', (new \Dedoc\Scramble\Support\Generator\Types\IntegerType)->example(5))
                            ->addProperty('initial_price', (new \Dedoc\Scramble\Support\Generator\Types\NumberType)->example(9000000))
                            ->addProperty('selling_price', (new \Dedoc\Scramble\Support\Generator\Types\NumberType)->example(12500000))
                            ->addProperty('type', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->enum(['in', 'out'])->example('in'))
                            ->addProperty('date', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('2026-02-01'))
                            ->setRequired(['stock', 'initial_price', 'selling_price', 'type', 'date']);

                        $operation->addRequestBodyObject(
                            RequestBodyObject::make()
                                ->setContent('application/json', Schema::fromType($requestType))
                                ->required()
                                ->description('Product stock payload')
                        );

                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('Product stock added')
                                ->setContent('application/json', Schema::fromType(
                                    (new ObjectType)->example([
                                        'success' => true,
                                        'message' => 'success creating stock for Laptop Pro 15',
                                    ])
                                )),
                        ];
                    }
                    if ($path->path === 'master/product/{product}/stock/{stock}' && ($operation = $path->operations['delete'] ?? null)) {
                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('Product stock deleted')
                                ->setContent('application/json', Schema::fromType(
                                    (new ObjectType)->example([
                                        'success' => true,
                                        'message' => 'success deleting items',
                                    ])
                                )),
                        ];
                    }
                }

                // Proforma endpoints
                foreach ($openApi->paths as $path) {
                    $pathValue = ltrim($path->path, '/');
                    if ($pathValue === 'master/proforma' && ($operation = $path->operations['get'] ?? null)) {
                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('List proformas')
                                ->setContent('application/json', Schema::fromType(
                                    (new ObjectType)->example([
                                        'success' => true,
                                        'data' => [
                                            'data' => [
                                                [
                                                    'id' => 1,
                                                    'number' => 'PRO-202601250301-ABCD',
                                                    'status' => 'pending',
                                                    'total_price' => 20500000,
                                                    'tax_price' => 410000,
                                                    'discount_price' => 0,
                                                    'total_discount_per_item' => 0,
                                                    'tax' => 2,
                                                    'grand_total_price' => 20090000,
                                                    'notes' => null,
                                                    'member' => null,
                                                    'user' => [
                                                        'id' => 1,
                                                        'name' => 'Mickey Gudiel',
                                                    ],
                                                    'details' => [
                                                        [
                                                            'product_id' => 2,
                                                            'qty' => 1,
                                                            'price' => 8500000,
                                                            'discount_price' => 0,
                                                            'product' => [
                                                                'id' => 2,
                                                                'name' => 'Laptop Air 13',
                                                            ],
                                                        ],
                                                        [
                                                            'product_id' => 1,
                                                            'qty' => 1,
                                                            'price' => 12000000,
                                                            'discount_price' => 0,
                                                            'product' => [
                                                                'id' => 1,
                                                                'name' => 'Laptop Pro 15',
                                                            ],
                                                        ],
                                                    ],
                                                ],
                                            ],
                                            'links' => [
                                                'first' => 'http://tenantdemo.localdomain.test:8000/api/master/proforma?page=1',
                                                'prev' => null,
                                                'next' => null,
                                            ],
                                            'meta' => [
                                                'current_page' => 1,
                                                'from' => 1,
                                                'path' => 'http://tenantdemo.localdomain.test:8000/api/master/proforma',
                                                'per_page' => 15,
                                                'to' => 1,
                                            ],
                                        ],
                                        'message' => '',
                                    ])
                                )),
                        ];
                    }

                    if ($pathValue === 'master/proforma' && ($operation = $path->operations['post'] ?? null)) {
                        $detailType = (new \Dedoc\Scramble\Support\Generator\Types\ObjectType)
                            ->addProperty('product_id', (new \Dedoc\Scramble\Support\Generator\Types\IntegerType)->example(2))
                            ->addProperty('qty', (new \Dedoc\Scramble\Support\Generator\Types\IntegerType)->example(1))
                            ->addProperty('price', (new \Dedoc\Scramble\Support\Generator\Types\NumberType)->example(8500000))
                            ->addProperty('discount_price', (new \Dedoc\Scramble\Support\Generator\Types\NumberType)->example(0));

                        $requestType = (new ObjectType)
                            ->addProperty('member_id', (new \Dedoc\Scramble\Support\Generator\Types\IntegerType)->example(null)->nullable(true))
                            ->addProperty('tax', (new \Dedoc\Scramble\Support\Generator\Types\NumberType)->example(2))
                            ->addProperty('discount_price', (new \Dedoc\Scramble\Support\Generator\Types\NumberType)->example(0))
                            ->addProperty('status', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->enum(['pending', 'converted', 'cancelled'])->example('pending'))
                            ->addProperty('notes', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example(null)->nullable(true))
                            ->addProperty('details', (new \Dedoc\Scramble\Support\Generator\Types\ArrayType)
                                ->setItems($detailType)
                                ->example([
                                    ['product_id' => 2, 'qty' => 1, 'price' => 8500000, 'discount_price' => 0],
                                    ['product_id' => 1, 'qty' => 1, 'price' => 12000000, 'discount_price' => 0],
                                ])
                            )
                            ->setRequired(['details']);

                        $operation->addRequestBodyObject(
                            RequestBodyObject::make()
                                ->setContent('application/json', Schema::fromType($requestType))
                                ->required()
                                ->description('Proforma payload')
                        );

                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('Proforma created')
                                ->setContent('application/json', Schema::fromType(
                                    (new ObjectType)->example([
                                        'success' => true,
                                        'data' => [
                                            'id' => 1,
                                            'number' => 'PRO-202601250301-ABCD',
                                            'status' => 'pending',
                                            'total_price' => 20500000,
                                            'tax_price' => 410000,
                                            'discount_price' => 0,
                                            'total_discount_per_item' => 0,
                                            'tax' => 2,
                                            'grand_total_price' => 20090000,
                                            'notes' => null,
                                            'member' => null,
                                            'user' => [
                                                'id' => 1,
                                                'name' => 'Mickey Gudiel',
                                            ],
                                            'details' => [
                                                [
                                                    'product_id' => 2,
                                                    'qty' => 1,
                                                    'price' => 8500000,
                                                    'discount_price' => 0,
                                                ],
                                            ],
                                        ],
                                        'message' => 'success creating proforma',
                                    ])
                                )),
                            Response::make(422)
                                ->setDescription('Validation error')
                                ->setContent('application/json', Schema::fromType(
                                    (new ObjectType)->example([
                                        'message' => 'The details field is required.',
                                        'errors' => [
                                            'details' => ['The details field is required.'],
                                        ],
                                    ])
                                )),
                        ];
                    }

                    if ($pathValue === 'master/proforma/{proforma}' && ($operation = $path->operations['get'] ?? null)) {
                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('Proforma detail')
                                ->setContent('application/json', Schema::fromType(
                                    (new ObjectType)->example([
                                        'success' => true,
                                        'data' => [
                                            'id' => 1,
                                            'number' => 'PRO-202601250301-ABCD',
                                            'status' => 'pending',
                                            'total_price' => 20500000,
                                            'tax_price' => 410000,
                                            'discount_price' => 0,
                                            'total_discount_per_item' => 0,
                                            'tax' => 2,
                                            'grand_total_price' => 20090000,
                                            'notes' => null,
                                            'member' => null,
                                            'user' => [
                                                'id' => 1,
                                                'name' => 'Mickey Gudiel',
                                            ],
                                            'details' => [
                                                [
                                                    'product_id' => 2,
                                                    'qty' => 1,
                                                    'price' => 8500000,
                                                    'discount_price' => 0,
                                                ],
                                            ],
                                        ],
                                    ])
                                )),
                        ];
                    }

                    if ($pathValue === 'master/proforma/{proforma}' && ($operation = $path->operations['put'] ?? null)) {
                        $detailType = (new \Dedoc\Scramble\Support\Generator\Types\ObjectType)
                            ->addProperty('product_id', (new \Dedoc\Scramble\Support\Generator\Types\IntegerType)->example(1))
                            ->addProperty('qty', (new \Dedoc\Scramble\Support\Generator\Types\IntegerType)->example(2))
                            ->addProperty('price', (new \Dedoc\Scramble\Support\Generator\Types\NumberType)->example(12000000))
                            ->addProperty('discount_price', (new \Dedoc\Scramble\Support\Generator\Types\NumberType)->example(0));

                        $requestType = (new ObjectType)
                            ->addProperty('status', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->enum(['pending', 'converted', 'cancelled'])->example('converted'))
                            ->addProperty('details', (new \Dedoc\Scramble\Support\Generator\Types\ArrayType)
                                ->setItems($detailType)
                                ->example([
                                    ['product_id' => 1, 'qty' => 2, 'price' => 12000000, 'discount_price' => 0],
                                ])
                            );

                        $operation->addRequestBodyObject(
                            RequestBodyObject::make()
                                ->setContent('application/json', Schema::fromType($requestType))
                                ->required()
                                ->description('Proforma payload')
                        );

                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('Proforma updated')
                                ->setContent('application/json', Schema::fromType(
                                    (new ObjectType)->example([
                                        'success' => true,
                                        'data' => [
                                            'id' => 1,
                                            'status' => 'converted',
                                            'details' => [
                                                [
                                                    'product_id' => 1,
                                                    'qty' => 2,
                                                    'price' => 12000000,
                                                    'discount_price' => 0,
                                                ],
                                            ],
                                        ],
                                        'message' => 'success updating proforma',
                                    ])
                                )),
                        ];
                    }

                    if ($pathValue === 'master/proforma/{proforma}' && ($operation = $path->operations['delete'] ?? null)) {
                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('Proforma deleted')
                                ->setContent('application/json', Schema::fromType(
                                    (new ObjectType)->example([
                                        'success' => true,
                                        'message' => 'success deleting proforma',
                                    ])
                                )),
                        ];
                    }
                }

                // Supplier
                foreach ($openApi->paths as $path) {
                    if ($path->path === 'master/supplier' && ($operation = $path->operations['get'] ?? null)) {
                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('List suppliers')
                                ->setContent('application/json', Schema::fromType(
                                    (new ObjectType)->example([
                                        'success' => true,
                                        'data' => [
                                            [
                                                'id' => 2,
                                                'name' => 'ACME Supplies',
                                                'phone_number' => '5559876',
                                                'contact_name' => null,
                                                'email' => 'acme@example.com',
                                                'address' => '123 Main St',
                                                'city' => 'Managua',
                                                'country' => 'NI',
                                                'postal_code' => '15100',
                                                'created_at' => '2026-01-25T03:12:27.000000Z',
                                                'updated_at' => '2026-01-25T03:12:58.000000Z',
                                                'deleted_at' => null,
                                            ],
                                            [
                                                'id' => 1,
                                                'name' => 'TIMETEC',
                                                'phone_number' => '211122222',
                                                'contact_name' => 'ALice',
                                                'email' => 'alice@timetec.com',
                                                'address' => 'Tipitapa, detras de pollos frit',
                                                'city' => 'Tipitapa',
                                                'country' => 'Nicaragua',
                                                'postal_code' => '15100',
                                                'created_at' => '2026-01-24T23:20:51.000000Z',
                                                'updated_at' => '2026-01-24T23:20:51.000000Z',
                                                'deleted_at' => null,
                                            ],
                                        ],
                                        'message' => '',
                                    ])
                                )),
                        ];
                    }
                    if ($path->path === 'master/supplier' && ($operation = $path->operations['post'] ?? null)) {
                        $requestType = (new ObjectType)
                            ->addProperty('name', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('ACME Supplies'))
                            ->addProperty('phone_number', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('5559876')->nullable(true))
                            ->addProperty('email', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('acme@example.com')->nullable(true))
                            ->addProperty('address', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('123 Main St')->nullable(true))
                            ->addProperty('city', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('Managua')->nullable(true))
                            ->addProperty('country', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('NI')->nullable(true))
                            ->addProperty('postal_code', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('15100')->nullable(true))
                            ->setRequired(['name']);

                        $operation->addRequestBodyObject(
                            RequestBodyObject::make()
                                ->setContent('application/json', Schema::fromType($requestType))
                                ->required()
                                ->description('Supplier payload')
                        );

                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('Supplier created')
                                ->setContent('application/json', Schema::fromType(
                                    (new ObjectType)->example([
                                        'success' => true,
                                        'data' => [
                                            'id' => 2,
                                            'name' => 'ACME Supplies',
                                            'phone_number' => '5551234',
                                            'email' => 'acme@example.com',
                                            'address' => '123 Main St',
                                            'city' => 'Managua',
                                            'country' => 'NI',
                                            'postal_code' => '15100',
                                            'created_at' => '2026-01-25T03:12:27.000000Z',
                                            'updated_at' => '2026-01-25T03:12:27.000000Z',
                                        ],
                                        'message' => 'success creating supplier',
                                    ])
                                )),
                            Response::make(422)
                                ->setDescription('Validation error')
                                ->setContent('application/json', Schema::fromType(
                                    (new ObjectType)->example([
                                        'message' => 'The name field is required.',
                                        'errors' => [
                                            'name' => ['The name field is required.'],
                                        ],
                                    ])
                                )),
                        ];
                    }
                    if (in_array($path->path, ['master/supplier/{id}', 'master/supplier/{supplier}']) && ($operation = $path->operations['get'] ?? null)) {
                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('Supplier detail')
                                ->setContent('application/json', Schema::fromType(
                                    (new ObjectType)->example([
                                        'success' => true,
                                        'data' => [
                                            'id' => 2,
                                            'name' => 'ACME Supplies',
                                            'phone_number' => '5559876',
                                            'contact_name' => null,
                                            'email' => 'acme@example.com',
                                            'address' => '123 Main St',
                                            'city' => 'Managua',
                                            'country' => 'NI',
                                            'postal_code' => '15100',
                                            'created_at' => '2026-01-25T03:12:27.000000Z',
                                            'updated_at' => '2026-01-25T03:12:58.000000Z',
                                            'deleted_at' => null,
                                        ],
                                    ])
                                )),
                        ];
                    }
                    if (in_array($path->path, ['master/supplier/{id}', 'master/supplier/{supplier}']) && ($operation = $path->operations['put'] ?? null)) {
                        $requestType = (new ObjectType)
                            ->addProperty('name', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('ACME Supplies'))
                            ->addProperty('phone_number', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('5559876')->nullable(true))
                            ->addProperty('email', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('acme@example.com')->nullable(true))
                            ->addProperty('address', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('123 Main St')->nullable(true))
                            ->addProperty('city', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('Managua')->nullable(true))
                            ->addProperty('country', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('NI')->nullable(true))
                            ->addProperty('postal_code', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('15100')->nullable(true))
                            ->setRequired(['name']);

                        $operation->addRequestBodyObject(
                            RequestBodyObject::make()
                                ->setContent('application/json', Schema::fromType($requestType))
                                ->required()
                                ->description('Supplier payload')
                        );

                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('Supplier updated')
                                ->setContent('application/json', Schema::fromType(
                                    (new ObjectType)->example([
                                        'success' => true,
                                        'data' => [
                                            'id' => 1,
                                            'name' => 'TIMETEC',
                                            'phone_number' => '211122222',
                                            'contact_name' => 'ALice',
                                            'email' => 'alice@timetec.com',
                                            'address' => 'Tipitapa, detras de pollos frit',
                                            'city' => 'Tipitapa',
                                            'country' => 'Nicaragua',
                                            'postal_code' => '15100',
                                            'created_at' => '2026-01-24T23:20:51.000000Z',
                                            'updated_at' => '2026-01-24T23:20:51.000000Z',
                                            'deleted_at' => null,
                                        ],
                                        'message' => 'success updating supplier',
                                    ])
                                )),
                            Response::make(422)
                                ->setDescription('Validation error')
                                ->setContent('application/json', Schema::fromType(
                                    (new ObjectType)->example([
                                        'message' => 'The name field is required.',
                                        'errors' => [
                                            'name' => ['The name field is required.'],
                                        ],
                                    ])
                                )),
                        ];
                    }
                    if (in_array($path->path, ['master/supplier/{id}', 'master/supplier/{supplier}']) && ($operation = $path->operations['delete'] ?? null)) {
                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('Supplier deleted')
                                ->setContent('application/json', Schema::fromType(
                                    (new ObjectType)->example([
                                        'success' => true,
                                        'message' => 'success deleting supplier',
                                    ])
                                )),
                        ];
                    }
                }

                // Upload temp
                foreach ($openApi->paths as $path) {
                    if ($path->path === 'temp/upload' && ($operation = $path->operations['post'] ?? null)) {
                        $requestType = (new ObjectType)
                            ->addProperty('file', (new \Dedoc\Scramble\Support\Generator\Types\StringType)->example('dummy.txt'));

                        $operation->addRequestBodyObject(
                            RequestBodyObject::make()
                                ->setContent('multipart/form-data', Schema::fromType($requestType))
                                ->required()
                                ->description('Upload file (multipart/form-data)')
                        );

                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('File uploaded')
                                ->setContent('application/json', Schema::fromType(
                                    (new ObjectType)->example([
                                        'success' => true,
                                        'data' => [
                                            'name' => '7THsDUbHRAFLXFpMBXVNPpIOH80kYa41UdpSm3Dh.txt',
                                            'url' => 'http://localdomain.test:8000/tmp/7THsDUbHRAFLXFpMBXVNPpIOH80kYa41UdpSm3Dh.txt',
                                            'original_name' => 'dummy.txt',
                                        ],
                                        'message' => '',
                                    ])
                                )),
                        ];
                    }
                }

                // Misc simple endpoints
                foreach ($openApi->paths as $path) {
                    $pathValue = ltrim($path->path, '/');
                    if ($pathValue === 'test' && ($operation = $path->operations['get'] ?? null)) {
                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('Ping test')
                                ->setContent('application/json', Schema::fromType(
                                    (new ObjectType)->example([
                                        'message' => 'Success!',
                                    ])
                                )),
                        ];
                    }

                    if ($pathValue === 'user' && ($operation = $path->operations['get'] ?? null)) {
                        $operation->responses = [
                            Response::make(404)
                                ->setDescription('Not found')
                                ->setContent('text/html', Schema::fromType(
                                    (new ObjectType)->example('<html><body><h1>404 Not Found</h1></body></html>')
                                )),
                        ];
                    }

                    if ($path->path === '/' && ($operation = $path->operations['get'] ?? null)) {
                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('Landing page HTML')
                                ->setContent('text/html', Schema::fromType(
                                    (new ObjectType)->example('<!doctype html><html><body>Welcome</body></html>')
                                )),
                        ];
                    }
                }

                // Cashier report
                foreach ($openApi->paths as $path) {
                    if ($path->path === 'report/cashier' && ($operation = $path->operations['post'] ?? null)) {
                        $operation->responses = [
                            Response::make(200)
                                ->setDescription('Cashier report generated')
                                ->setContent('application/json', Schema::fromType(
                                    (new ObjectType)->example([
                                        'success' => true,
                                        'data' => [
                                            'report_url' => 'http://tenantdemo.localdomain.test:8000/storage/reports/cashier.pdf',
                                        ],
                                        'message' => 'success generate report',
                                    ])
                                )),
                        ];
                    }
                }
            });
        }

        Builder::macro('filter', function (Request $request) {
            /* WIP:  <07-08-22, sheenazien8> */
            $columns = $request->filters;
            $query = $this;
            if ($columns) {
                foreach ($columns as $filterColumn) {
                    $column = $filterColumn['column'];

                    if ($filterColumn['condition'] == 'equals') {
                        $condition = '=';
                    } else {
                        $condition = $filterColumn['condition'];
                    }
                    if ($filterColumn['condition'] == 'like') {
                        $value = '%'.$filterColumn['value'].'%';
                    } else {
                        $value = $filterColumn['value'];
                    }
                    if (! $value) {
                        return $this;
                    }
                    $query = optional($this)->where($column, $condition, $value);
                }
            }

            return $columns ? $query : $this;
        });
        if (! config('tenancy.central_domains')[0]) {
            $mainPath = database_path('migrations');
            $directories = glob($mainPath.'/*', GLOB_ONLYDIR);

            $this->loadMigrationsFrom($directories);
        }

        Feature::resolveScopeUsing(fn ($driver) => null);
        Feature::discover();
    }
}
