<?php

namespace App\Http\Requests\Auth;

use App\Enums\ShopType;
use App\Rules\Domain;
use App\Services\RegisterTenant;
use App\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class RegisterRequest extends FormRequest
{
    public function __construct(public ?RegisterTenant $registerTenant = null)
    {
        // Allow construction without explicit dependency (eg, during docs generation).
        $this->registerTenant ??= app(RegisterTenant::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        $domainStr = strtolower($this->domain);
        $centralDomain = config('tenancy.central_domains')[0];

        $cleanDomain = str_replace('.' . $centralDomain, '', $domainStr);

        $this->merge([
            'name' => $cleanDomain,
            'domain' => $cleanDomain . '.' . $centralDomain,
        ]);

        return [
            'domain' => ['required', 'string', 'max:255', 'unique:domains', new Domain],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:tenant_users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'business_type' => ['required', Rule::in(ShopType::cases())],
            'other_business_type' => ['required_if:business_type,other'],
        ];
    }

    public function register(): Tenant
    {
        try {
            return $this->registerTenant->create($this->all());
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);

            throw ValidationException::withMessages([
                'domain' => [$e->getMessage()],
            ]);
        }
    }
}
