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
    // public function index(Request $request): JsonResponse
    // {
    //     $query = UserCountryAccount::with(['user', 'country'])
    //         ->latest();

    //     if ($request->filled('user_id')) {
    //         $query->where('user_id', $request->user_id);
    //     }

    //     if ($request->filled('search')) {
    //         $search = $request->search;
    //         $query->where(function ($q) use ($search) {
    //             $q->where('client_name', 'like', "%{$search}%")
    //                 ->orWhere('phone_number', 'like', "%{$search}%")
    //                 ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$search}%"));
    //         });
    //     }

    //     $accounts = $query->paginate($request->input('per_page', 20));

    //     return response()->json([
    //         'success' => true,
    //         'data'    => $accounts->items(),
    //         'meta'    => [
    //             'current_page' => $accounts->currentPage(),
    //             'last_page'    => $accounts->lastPage(),
    //             'per_page'     => $accounts->perPage(),
    //             'total'        => $accounts->total(),
    //         ],
    //     ]);
    // }


    public function index(Request $request): JsonResponse
    {
        $query = UserCountryAccount::with(['user', 'country'])
            ->where('user_id', $request->user()->id) // ✅ Always scope to auth user
            ->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('client_name', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%");
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




    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id'      => 'required|exists:users,id',
            'country_id'   => 'required|exists:countries,id',
            'client_name'  => 'nullable|string|max:255',   // ← nullable, not sometimes
            'phone_number' => 'nullable|string|max:20',
            'alt_number'   => 'nullable|string|max:20',
            'country_code' => 'nullable|string|max:5',
            'token'        => 'nullable|string|max:255',
            'is_default'   => 'boolean',
        ]);

        if (!empty($validated['is_default'])) {
            UserCountryAccount::where('user_id', $validated['user_id'])
                ->update(['is_default' => false]);
        }

        $account = UserCountryAccount::create($validated);

        return response()->json(['success' => true, 'data' => $account->load('country')], 201);
    }

    public function update(Request $request, UserCountryAccount $userCountryAccount): JsonResponse
    {
        // $this->authorize('update', $userCountryAccount); // optional policy check

        $validated = $request->validate([
            'country_id'   => 'sometimes|exists:countries,id',
            'client_name'  => 'sometimes|string|max:255',
            'phone_number' => 'sometimes|string|max:255',
            'alt_number'   => 'nullable|string|max:255',
            'country_code' => 'sometimes|string|max:5',
            'token'        => 'nullable|string|max:255',
            'is_default'   => 'boolean',
        ]);

        if (!empty($validated['is_default'])) {
            UserCountryAccount::where('user_id', $userCountryAccount->user_id)
                ->where('id', '!=', $userCountryAccount->id)
                ->update(['is_default' => false]);
        }

        $userCountryAccount->update($validated);

        return response()->json(['success' => true, 'data' => $userCountryAccount->load('country')]);
    }

    public function setDefault(UserCountryAccount $userCountryAccount): JsonResponse
    {
        UserCountryAccount::where('user_id', $userCountryAccount->user_id)
            ->update(['is_default' => false]);

        $userCountryAccount->update(['is_default' => true]);

        return response()->json(['success' => true, 'data' => $userCountryAccount]);
    }

    public function destroy(UserCountryAccount $userCountryAccount): JsonResponse
    {
        $userCountryAccount->delete();

        return response()->json(['success' => true]);
    }
    // /**
    //  * Show the form for creating a new resource.
    //  */
    // public function create()
    // {
    //     //
    // }

    // /**
    //  * Store a newly created resource in storage.
    //  */
    // public function store(StoreUserCountryAccountRequest $request)
    // {
    //     //
    // }

    // /**
    //  * Display the specified resource.
    //  */
    // public function show(UserCountryAccount $userCountryAccount)
    // {
    //     //
    // }

    // /**
    //  * Show the form for editing the specified resource.
    //  */
    // public function edit(UserCountryAccount $userCountryAccount)
    // {
    //     //
    // }

    // /**
    //  * Update the specified resource in storage.
    //  */
    // public function update(UpdateUserCountryAccountRequest $request, UserCountryAccount $userCountryAccount)
    // {
    //     //
    // }

    // /**
    //  * Remove the specified resource from storage.
    //  */
    // public function destroy(UserCountryAccount $userCountryAccount)
    // {
    //     //
    // }
}
