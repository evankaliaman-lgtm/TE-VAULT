<?php

namespace App\Http\Controllers;

use App\Http\Requests\Assets\StoreAssetRequest;
use App\Http\Requests\Assets\UpdateAssetRequest;
use App\Http\Resources\AssetResource;
use App\Models\Asset;
use App\Services\AuditLogService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class AssetController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return AssetResource::collection(Asset::query()->with('category')->paginate());
    }

    public function store(StoreAssetRequest $request, AuditLogService $audit): AssetResource
    {
        $asset = Asset::query()->create($request->validated());
        $audit->record($request->user(), 'asset.created', $asset, null, $asset->getAttributes());

        return new AssetResource($asset->load('category'));
    }

    public function show(Asset $asset): AssetResource
    {
        return new AssetResource($asset->load('category'));
    }

    public function update(UpdateAssetRequest $request, Asset $asset, AuditLogService $audit): AssetResource
    {
        $old = $asset->getOriginal();
        $asset->update($request->validated());
        $audit->record($request->user(), 'asset.updated', $asset, $old, $asset->getAttributes());

        return new AssetResource($asset->load('category'));
    }

    public function destroy(Asset $asset, AuditLogService $audit): Response
    {
        $old = $asset->getAttributes();
        $asset->delete();
        $audit->record(request()->user(), 'asset.deleted', $asset, $old, null);

        return response()->noContent();
    }
}
