<?php

namespace App\Http\Controllers;

use App\Http\Requests\Categories\StoreAssetCategoryRequest;
use App\Http\Requests\Categories\UpdateAssetCategoryRequest;
use App\Http\Resources\AssetCategoryResource;
use App\Models\AssetCategory;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class AssetCategoryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return AssetCategoryResource::collection(AssetCategory::query()->paginate());
    }

    public function store(StoreAssetCategoryRequest $request): AssetCategoryResource
    {
        return new AssetCategoryResource(AssetCategory::query()->create($request->validated()));
    }

    public function show(AssetCategory $assetCategory): AssetCategoryResource
    {
        return new AssetCategoryResource($assetCategory);
    }

    public function update(UpdateAssetCategoryRequest $request, AssetCategory $assetCategory): AssetCategoryResource
    {
        $assetCategory->update($request->validated());

        return new AssetCategoryResource($assetCategory);
    }

    public function destroy(AssetCategory $assetCategory): Response
    {
        $assetCategory->delete();

        return response()->noContent();
    }
}
