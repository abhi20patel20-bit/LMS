<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProviderStoreRequest;
use App\Http\Requests\ProviderUpdateRequest;
use App\Models\Provider;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProviderController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:read providers', ['only' => ['index', 'show']]);
        $this->middleware('permission:create providers', ['only' => ['store']]);
        $this->middleware('permission:update providers', ['only' => ['update']]);
        $this->middleware('permission:delete providers', ['only' => ['destroy']]);
    }

    public function index(): Response
    {
        return Inertia::render('Provider/Index');
    }

    public function getProviders(): JsonResponse
    {
        $providers = Provider::visibleTo(auth()->user())
            ->get([
                'id',
                'name',
                'description',
                'created_at',
                'updated_at',
            ]);

        return response()->json(['providers' => $providers], 200);
    }

    public function store(ProviderStoreRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $provider = Provider::create([
                'name' => $request->name,
                'description' => $request->description,
            ]);

            DB::commit();

            return new JsonResponse([
                'message' => 'Provider created successfully',
                'data'    => $provider
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            report($th);

            return new JsonResponse([
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        $provider = Provider::visibleTo(auth()->user())
            ->findOrFail($id);

        return new JsonResponse(['data' => $provider], 200);
    }

    public function update(ProviderUpdateRequest $request, int $id): JsonResponse
    {
        DB::beginTransaction();

        try {
            $provider = Provider::findOrFail($id);

            $provider->update([
                'name' => $request->name ?? $provider->name,
                'description' => $request->description,
            ]);

            DB::commit();

            return new JsonResponse([
                'message' => 'Provider updated successfully',
                'data'    => $provider
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            report($th);

            return new JsonResponse([
                'message' => $th->getMessage()
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $provider = Provider::visibleTo(auth()->user())->findOrFail($id);
            $provider->delete();

            return new JsonResponse(['message' => 'Provider deleted successfully'], 200);
        } catch (\Throwable $th) {
            return new JsonResponse(['message' => $th->getMessage()], 500);
        }
    }
}
