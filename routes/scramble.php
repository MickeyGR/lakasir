<?php

use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\InitializeTenancyByDomain;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Generator;

$middleware = [
    'web',
    InitializeTenancyByDomain::class,
];

if (! config('scalar.standalone_mode')) {
    $middleware[] = RestrictedDocsAccess::class;
}

Route::middleware($middleware)->group(function () {
    Route::get('docs/api', function () {
        $config = Scramble::getGeneratorConfig('default');
        $generator = app(Generator::class);

        // Force 'url' into config using Reflection because there is no simple getter for the whole array
        // and we want to preserve existing config.
        try {
            $reflection = new \ReflectionClass($config);
            $property = $reflection->getProperty('config');
            $property->setAccessible(true);
            $currentConfig = $property->getValue($config);
            
            $currentConfig['url'] = '/docs/api-json';
            
            $config->config($currentConfig);
        } catch (\Throwable $e) {
            // Fallback if reflection fails, though unlikely
            // We just continue, but maybe log it?
            // For now, silent fail is better than crash 500
        }
        
        return view('scramble::docs', [
            'spec' => $generator($config),
            'config' => $config,
        ]);
    });

    Route::get('docs/api-json', function () {
        $config = Scramble::getGeneratorConfig('default');
        try {
            $generator = app(\Dedoc\Scramble\Generator::class);
            $docs = $generator($config);

            // Manual Security Injection (Nuclear Fix)
            if (is_array($docs)) {
                $docs['components']['securitySchemes']['bearerAuth'] = [
                    'type' => 'http',
                    'scheme' => 'bearer',
                    'bearerFormat' => 'JWT',
                ];
                $docs['security'] = [
                    ['bearerAuth' => []]
                ];
            }

            return response()->json($docs, options: JSON_PRETTY_PRINT);
        } catch (\Throwable $e) {
            // Handle error, e.g., log it or return an error response
            // For now, re-throwing or returning a generic error is an option
            throw $e; // Or return response()->json(['error' => $e->getMessage()], 500);
        }
    })->name('scramble.docs.index');
});
