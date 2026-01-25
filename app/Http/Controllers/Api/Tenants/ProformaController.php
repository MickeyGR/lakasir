<?php

namespace App\Http\Controllers\Api\Tenants;

use App\Http\Controllers\Controller;
use App\Models\Tenants\Proforma;
use App\Services\Tenants\ProformaService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProformaController extends Controller
{
    public function __construct(private readonly ProformaService $service)
    {
    }

    public function index()
    {
        $proformas = Proforma::with(['details.product', 'member', 'user'])
            ->latest()
            ->paginate();

        return $this->buildResponse()
            ->setData($proformas)
            ->setMessage('')
            ->present();
    }

    public function store(Request $request)
    {
        $validated = $this->validatePayload($request);

        $payload = array_merge($validated, [
            'user_id' => auth()->id(),
            'number' => 'PRO-'.now()->format('YmdHis').'-'.Str::random(4),
            'status' => $validated['status'] ?? 'pending',
        ]);

        $proforma = $this->service->create($payload)->load(['details.product', 'member', 'user']);

        return $this->buildResponse()
            ->setData($proforma)
            ->setMessage('success creating proforma')
            ->present();
    }

    public function show(Proforma $proforma)
    {
        $proforma->load(['details.product', 'member', 'user']);

        return $this->buildResponse()
            ->setData($proforma)
            ->present();
    }

    public function update(Request $request, Proforma $proforma)
    {
        $validated = $this->validatePayload($request, $proforma);

        $payload = array_merge($validated, [
            'user_id' => auth()->id(),
        ]);

        $proforma = $this->service->update($proforma, $payload)->load(['details.product', 'member', 'user']);

        return $this->buildResponse()
            ->setData($proforma)
            ->setMessage('success updating proforma')
            ->present();
    }

    public function destroy(Proforma $proforma)
    {
        $proforma->delete();

        return $this->buildResponse()
            ->setMessage('success deleting proforma')
            ->present();
    }

    private function validatePayload(Request $request, ?Proforma $proforma = null): array
    {
        return $this->validate($request, [
            'member_id' => ['nullable', 'exists:members,id'],
            'tax' => ['nullable', 'numeric'],
            'discount_price' => ['nullable', 'numeric'],
            'status' => ['nullable', 'in:pending,converted,cancelled'],
            'notes' => ['nullable', 'string'],
            'details' => ['required', 'array', 'min:1'],
            'details.*.product_id' => ['required', 'exists:products,id'],
            'details.*.qty' => ['required', 'numeric', 'min:1'],
            'details.*.price' => ['required', 'numeric'],
            'details.*.discount_price' => ['nullable', 'numeric'],
        ]);
    }
}
