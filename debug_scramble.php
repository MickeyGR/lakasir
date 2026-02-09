<?php

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Generator;
use Illuminate\Support\Facades\Log;

// Recupere la configuración y el generador
$config = Scramble::getGeneratorConfig('default');
$generator = app(Generator::class);

// Genere la especificación
$spec = $generator($config);

// Registre la sección de seguridad y componentes
Log::info('Scramble Security Debug:', [
    'security' => $spec['security'] ?? 'MISSING',
    'components_securitySchemes' => $spec['components']['securitySchemes'] ?? 'MISSING',
]);

echo "Debug logged to laravel.log";
