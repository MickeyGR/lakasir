<?php

namespace App\Http\Controllers\Api\Tenants\Master;

use App\Http\Controllers\Controller;
use App\Models\Tenants\Supplier;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class SupplierController extends Controller
{
    /**
     * @requestMediaType application/json
     * @response array{success: true, data: array, message: string}
     */
    public function index()
    {
        $suppliers = QueryBuilder::for(Supplier::class)
            ->allowedFilters(['email', 'name', 'phone_number', 'address', 'city', 'country'])
            ->latest()
            ->get();

        return $this->buildResponse()
            ->setData($suppliers)
            ->setMessage('')
            ->present();
    }

    /**
     * @requestMediaType application/json
     * @body array{name: "ACME Supplies", phone_number?: "555-1234", email?: "acme@example.com", address?: "Main St", city?: "Managua", country?: "NI"}
     * @response array{success: true, message: "success creating supplier"}
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'phone_number' => 'nullable|unique:suppliers,phone_number',
            'email' => 'nullable|email|unique:suppliers,email',
            'postal_code' => 'nullable|string',
        ]);

        $supplier = Supplier::create($request->only([
            'name',
            'phone_number',
            'email',
            'address',
            'city',
            'country',
            'postal_code',
        ]));

        return $this->buildResponse()
            ->setData($supplier)
            ->setMessage('success creating supplier')
            ->present();
    }

    public function show(Supplier $supplier)
    {
        return $this->buildResponse()
            ->setData($supplier)
            ->present();
    }

    public function update(Request $request, Supplier $supplier)
    {
        $this->validate($request, [
            'name' => 'required',
            'phone_number' => 'nullable|unique:suppliers,phone_number,'.$supplier->id,
            'email' => 'nullable|email|unique:suppliers,email,'.$supplier->id,
            'postal_code' => 'nullable|string',
        ]);

        $supplier->fill($request->only([
            'name',
            'phone_number',
            'email',
            'address',
            'city',
            'country',
            'postal_code',
        ]));
        $supplier->save();

        return $this->buildResponse()
            ->setData($supplier)
            ->setMessage('success updating supplier')
            ->present();
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();

        return $this->buildResponse()
            ->setMessage('success deleting supplier')
            ->present();
    }
}
