<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use App\Http\Resources\ClientResource;
use Illuminate\Http\JsonResponse;

class ClientController extends Controller
{
    public function index(): JsonResponse
    {
        $clients = Client::paginate(15);
        return response()->json(ClientResource::collection($clients));
    }

    public function store(StoreClientRequest $request): JsonResponse
    {
        $client = Client::create($request->validated());
        return response()->json(new ClientResource($client), 201);
    }

    public function show(Client $client): JsonResponse
    {
        return response()->json(new ClientResource($client));
    }

    public function update(UpdateClientRequest $request, Client $client): JsonResponse
    {
        $client->update($request->validated());
        return response()->json(new ClientResource($client));
    }

    public function destroy(Client $client): JsonResponse
    {
        $client->delete();
        return response()->json(null, 204);
    }
}
