<?php

namespace App\Http\Controllers\Api\Tenants\Master;

use App\Http\Controllers\Controller;
use App\Models\Tenants\Member;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\QueryBuilder\QueryBuilder;

class MemberController extends Controller
{
    /**
     * @response array{
     *   success: true,
     *   data: array{
     *     array{
     *       id: 1,
     *       name: "Joe Mendez",
     *       identity_type: "other",
     *       identity_number: "001-221220-1010W",
     *       joined_date: "2026-01-24 00:00:00",
     *       code: "CUS0001",
     *       address: "Bo Martin Luther King",
     *       email: "+50589897898"
     *     }
     *   },
     *   message: ""
     * }
     */
    public function index()
    {
        $members = QueryBuilder::for(Member::class)
            ->allowedFilters(['name', 'email'])
            ->orderByDesc('created_at')
            ->get();

        return $this->success($members);
    }

    /**
     * @requestMediaType application/json
     * @body array{name: "Joe Mendez", email: "+50589897898", address: "Bo Martin Luther King", identity_number: "001...", identity_type: "ktp"}
     * @response array{success: true, data: array(), message: "success creating items"}
     */
    public function store(Request $request)
    {
        $this->validate($request, $this->rules(new Member));
        $member = new Member();
        $member->fill($request->all());
        $member->save();

        return $this->success([], "success creating items");
    }

    /**
     * @response array{
     *   success: true,
     *   data: array{
     *     id: 1,
     *     name: "Joe Mendez",
     *     email: "+50589897898",
     *     code: "CUS0001"
     *   },
     *   message: ""
     * }
     */
    public function show(Member $member)
    {
        return $this->success($member);
    }

    /**
     * @requestMediaType application/json
     * @body array{name: "Joe Updated", email: "newemail@example.com"}
     * @response array{success: true, data: array(), message: "success updating items"}
     */
    public function update(Request $request, Member $member)
    {
        $this->validate($request, $this->rules($member));
        $member->fill($request->all());
        $member->update();

        return $this->success([], "success updating items");
    }

    /**
     * @response array{success: true, data: array(), message: "success deleting items"}
     */
    public function destroy(Member $member)
    {
        $member->delete();

        return $this->success([], "success deleting items");
    }

    private function rules(?Member $member): array
    {
        return [
            "name" => ["required", "min:3"],
            "email" => [Rule::unique("members")->ignore($member->id), "nullable"],
        ];
    }
}
