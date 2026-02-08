<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // 'localhost:3000/*',
        'livewire/*',
    ];

    public function __construct(\Illuminate\Contracts\Foundation\Application $app, \Illuminate\Contracts\Encryption\Encrypter $encrypter)
    {
        parent::__construct($app, $encrypter);
        
        if (env('STANDALONE_MODE')) {
            $this->except[] = 'api/*';
        }
    }
}
