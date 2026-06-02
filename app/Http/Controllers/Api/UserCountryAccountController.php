<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserCountryAccountRequest;
use App\Http\Requests\UpdateUserCountryAccountRequest;
use App\Models\UserCountryAccount;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;



class UserCountryAccountController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = UserCountryAccount::with(['user', 'country'])
            ->latest();

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('client_name', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%")
                    ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }

        $accounts = $query->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data'    => $accounts->items(),
            'meta'    => [
                'current_page' => $accounts->currentPage(),
                'last_page'    => $accounts->lastPage(),
                'per_page'     => $accounts->perPage(),
                'total'        => $accounts->total(),
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserCountryAccountRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(UserCountryAccount $userCountryAccount)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(UserCountryAccount $userCountryAccount)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserCountryAccountRequest $request, UserCountryAccount $userCountryAccount)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserCountryAccount $userCountryAccount)
    {
        //
    }
}
