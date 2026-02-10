<?php

namespace App\Http\Controllers\Api\Tenants\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Resources\AboutResource;
use App\Models\Tenants\About;

class AboutController extends Controller
{
    /**
     * Get storefront about
     *
     * @summary Get store profile (public)
     * @operationId storefront.about.index
     * @tags Storefront
     *
     * @response array{
     *   success: true,
     *   data: array{
     *     shop_name: "NicaPC",
     *     shop_location: "Managua",
     *     owner_name: "MICKEY GUDIEL REYES",
     *     business_type: "other",
     *     other_business_type: "Computo",
     *     phone: "+50589897898",
     *     facebook: "https://facebook.com/nicapc",
     *     messenger: "nicapc",
     *     website: "https://nicapc.com",
     *     linkedin: "https://linkedin.com/company/nicapc",
     *     currency: "NIO",
     *     photo_url: ""
     *   }
     * }
     */
    public function index()
    {
        return $this->buildResponse()
            ->setData(new AboutResource(About::first()))
            ->present();
    }
}
