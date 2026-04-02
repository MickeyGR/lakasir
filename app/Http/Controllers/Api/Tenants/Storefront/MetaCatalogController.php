<?php

namespace App\Http\Controllers\Api\Tenants\Storefront;

use App\Http\Controllers\Controller;
use App\Services\Tenants\MetaCatalogFeedService;
use Illuminate\Http\Request;

class MetaCatalogController extends Controller
{
    /**
     * Stream the tenant storefront catalog in Meta-compatible CSV format.
     *
     * @summary Meta/Facebook catalog feed
     * @operationId storefront.meta.catalog
     * @tags Storefront
     *
     * @response 200 scenario="CSV feed" text/csv
     * @response 500 {"success":false,"message":"Storefront public base URL is not configured for this tenant."}
     */
    public function index(Request $request, MetaCatalogFeedService $metaCatalogFeedService)
    {
        $context = $metaCatalogFeedService->resolveContext();

        if ($context === null) {
            return $this->buildResponse()
                ->setCode(500)
                ->setMessage('Storefront public base URL is not configured for this tenant.')
                ->present();
        }

        if ($metaCatalogFeedService->shouldReturnNotModified($request, $context)) {
            return response('', 304, $metaCatalogFeedService->headers($context));
        }

        return $metaCatalogFeedService->stream($context);
    }
}
