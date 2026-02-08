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
