<?php

namespace App\Http\Controllers\Api\Tenants\Transaction;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Sellings\TransactionSellingStoreRequest;
use App\Http\Resources\SellingCollection;
use App\Models\Tenants\Selling;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class SellingController extends Controller
{
    /**
     * @response array{
     *   success: true,
     *   data: array{
     *     links: array(first: "http://...", prev: null, next: null),
     *     meta: array(current_page: 1, from: 1, per_page: 10, to: 10),
     *     data: array{
     *       array{
     *         id: 1,
     *         code: "SELL0001",
     *         grand_total_price: 19980,
     *         total_price: 20380,
     *         discount: 0,
     *         member: array(id: 1, name: "Joe Mendez"),
     *         payment_method: array(id: 3, name: "Fiado"),
     *         cashier: array(id: 1, name: "MICKEY GUDIEL REYES"),
     *         selling_details: array{
     *           array{product_id: 2, qty: 1, price: 8500000, product: array(name: "Laptop Air 13")},
     *           array{product_id: 1, qty: 2, price: 12000000, product: array(name: "Laptop Pro 15")}
     *         }
     *       }
     *     }
     *   },
     *   message: "success get sellings"
     * }
     */
    public function index(Request $request)
    {
        $sellings = QueryBuilder::for(Selling::class)
            ->allowedFilters([
                'code',
                'member_id',
                'date',
                'code',
                'payed_money',
                'money_changes',
                'total_price',
                'total_qty',
                'created_at',
                'updated_at',
                'sellingDetails.product_id',
            ])
            ->with(['member', 'paymentMethod', 'sellingDetails.product', 'user'])
            ->isPaid()
            ->defaultSort('-created_at')
            ->simplePaginate($request->get('per_page', 10));

        return $this->buildResponse()
            ->setData(SellingCollection::collection($sellings))
            ->setMessage('success get sellings')
            ->present();
    }

    /**
     * @requestMediaType application/json
     * @body array{
     *   member_id: 1,
     *   payment_method_id: 3,
     *   note: "Customer note",
     *   payed_money: 20000,
     *   cart: array{
     *     array{product_id: 1, qty: 2, price: 12000000, discount: 0, note: "Extra wrapping"}
     *   }
     * }
     * @response array{success: true, message: "success create selling", data: array(id: 2, code: "SELL0002")}
     */
    public function store(TransactionSellingStoreRequest $request)
    {
        $selling = $request->store();
        $selling->load(['member', 'paymentMethod', 'sellingDetails.product', 'user']);

        return $this->buildResponse()
            ->setMessage('success create selling')
            ->setData(new SellingCollection($selling))
            ->present();
    }

    /**
     * @response array{success: true, message: "success get selling", data: array(id: 1, code: "SELL0001", grand_total_price: 19980)}
     */
    public function show(Selling $selling)
    {
        $selling->load(['member', 'paymentMethod', 'sellingDetails', 'user']);

        return $this->buildResponse()
            ->setData(new SellingCollection($selling))
            ->setMessage('success get selling')
            ->present();
    }
}
