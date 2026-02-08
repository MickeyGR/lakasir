<?php

use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\InitializeTenancyByDomain;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Generator;

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    RestrictedDocsAccess::class,
])->group(function () {
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
        $generator = app(Generator::class);
        
        return response()->json($generator($config), options: JSON_PRETTY_PRINT);
    })->name('scramble.docs.index');
});
