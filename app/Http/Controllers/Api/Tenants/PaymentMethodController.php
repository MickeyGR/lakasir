<?php

namespace App\Http\Controllers\Api\Tenants;

use App\Http\Controllers\Controller;
use App\Models\Tenants\PaymentMethod;

class PaymentMethodController extends Controller
{
    /**
     * @response array{
     *   success: true,
     *   message: "success get payment methods",
     *   data: array{
     *     array{id: 1, name: "Cash", type: "cash", is_default: true},
     *     array{id: 2, name: "Bank Transfer", type: "bank_transfer", is_default: false}
     *   }
     * }
     */
    public function index()
    {
        $paymentMethods = PaymentMethod::all();

        return $this->buildResponse()
            ->setData($paymentMethods)
            ->setMessage('success get payment methods')
            ->present();
    }
}
