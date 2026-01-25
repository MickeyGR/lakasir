<?php

namespace App\Http\Controllers\Api\Tenants;

use App\Http\Controllers\Controller;
use App\Http\Resources\PrinterResource;
use App\Models\Tenants\Printer;
use Illuminate\Http\Request;

class PrinterController extends Controller
{
    /**
     * Display a listing of printers.
     *
     * @response array{
     *   success: true,
     *   message: "Data retrieved successfully",
     *   data: array{
     *     array{
     *       id: 1,
     *       name: "Temp Printer",
     *       driver: "escpos",
     *       port: "9100",
     *       ip_address: "192.168.1.200",
     *       created_at: "2026-01-25T01:25:15.000000Z",
     *       updated_at: "2026-01-25T01:25:15.000000Z"
     *     }
     *   }
     * }
     */
    public function index()
    {
        return $this->buildResponse()
            ->setData(PrinterResource::collection(Printer::all()))
            ->setMessage('Data retrieved successfully')
            ->present();
    }

    /**
     * @requestMediaType application/json
     * @body array{name: "Kitchen Printer", ip_address: "192.168.1.101", port: "9100", driver: "escpos"}
     * @response array{success: true, message: "Data saved successfully"}
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'ip_address' => 'required',
            'port' => 'nullable',
            'driver' => 'required',
        ]);

        Printer::create($request->all());

        return $this->buildResponse()
            ->setMessage('Data saved successfully')
            ->present();
    }

    public function update(Request $request, Printer $printer)
    {
        $request->validate([
            'name' => 'required',
            'ip_address' => 'required',
            'port' => 'nullable',
            'driver' => 'required',
        ]);

        $printer->update($request->all());

        return $this->buildResponse()
            ->setMessage('Data updated successfully')
            ->present();
    }

    /**
     * @response array{success: true, message: "Data deleted successfully"}
     */
    public function destroy(Printer $printer)
    {
        $printer->delete();

        return $this->buildResponse()
            ->setMessage('Data deleted successfully')
            ->present();
    }
}
