<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Http\Resources\ProductResource;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Request; // Remove this line

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    // Default relationships to load with product
    private const DEFAULT_RELATIONS = [
        'category',
        'vendor',
        'images',
        'prices',
        'stocks',
        'offers',
        'media',
        'variants',
    ];

    // List all products (with vendor & relationships)
    public function index(): JsonResponse
    {
        $products = Product::with([
            'vendor',
            'category',
            'stocks',
            'statistics',
            'prices',
            'media',
            'variants'
        ])->paginate(10);

        return response()->json(ProductResource::collection($products));
    }

    // Create new product (vendor scoped)
    // public function store(StoreProductRequest $request): JsonResponse
    // {
    //     $data = $request->validated();
    //     $data['vendor_id'] = Auth::user()->vendor_id ?? $data['vendor_id']; // auto-assign vendor

    //     $product = Product::create($data);

    //     return response()->json(new ProductResource($product), 201);
    // }




public function store(Request $request)
{

    Log::info('Attempting to create product', ['request_data' => $request->all()]);
    $validated = $request->validate([
        'vendor_id'   => 'required|exists:vendors,id',
        'category_id' => 'required|exists:categories,id',
        'product_name'=> 'required|string|max:255',
        'description' => 'nullable|string',
        'sku'         => 'required|string|max:100|unique:products,sku',

        'attributes'  => 'array',
        'attributes.*.name'  => 'required|string|max:100',
        'attributes.*.value' => 'required|string|max:255',

        'images'      => 'array',
        'images.*'    => 'url',
        'price.currency'   => 'nullable|string|max:10',
        'price.amount'     => 'nullable|numeric|min:0',
        'price.sale_price' => 'nullable|numeric|min:0',

        'stock.quantity' => 'nullable|integer|min:0',
        'stock.track'    => 'nullable|boolean',
        'stock_threshold' => 'required|integer|min:0',
        'offers'      => 'nullable|array',
        'offers.*.type'       => 'required|string',
        'offers.*.value'      => 'required|numeric',
        'offers.*.start_date' => 'required|date',
        'offers.*.end_date'   => 'required|date',

        'media'       => 'array',
        'media.*.type' => 'required|string',
        'media.*.url'  => 'required|url'
    ]);

    DB::beginTransaction();

    try {
        // 1. Product
        $product = Product::create([
            'vendor_id'   => $validated['vendor_id'],
            'category_id' => $validated['category_id'],
            'product_name'=> $validated['product_name'],
            'description' => $validated['description'] ?? null,
            // 'sku'         => $validated['sku'],
            'sku'         => $this->createSkuIfNotProvided($validated)['sku'],
        ]);

        // 2. Attributes
        if (!empty($validated['attributes'])) {
            foreach ($validated['attributes'] as $attr) {
                $attribute = ProductAttribute::firstOrCreate(['name' => $attr['name']]);

                ProductAttributeValue::create([
                    'product_id'           => $product->id,
                    'product_attribute_id' => $attribute->id,
                    'value'                => $attr['value'],
                ]);
            }
        }

        // 3. Images (handle separately from media)
        if (!empty($validated['images'])) {
            $product->media()->createMany(
            collect($validated['images'])->map(function($image, $index) {
                return [
                'media_type' => 'image',
                'url' => $image,
                'alt_text' => null,
                'is_primary' => $index === 0, // first image is primary
                'position' => $index,
                ];
            })->toArray()
            );
        }

        // 7. Media (handle non-image media)
        if (!empty($validated['media'])) {
            $product->media()->createMany(
            collect($validated['media'])->map(function($media) {
                return [
                'media_type' => $media['type'] ?? 'other',
                'url' => $media['url'],
                'alt_text' => $media['alt_text'] ?? null,
                'is_primary' => $media['is_primary'] ?? false,
                'position' => $media['position'] ?? 0,
                ];
            })->toArray()
            );
        }

        // 4. Price
        if (!empty($validated['price']) && is_array($validated['price'])) {
            $product->prices()->create(array_merge($validated['price'], [
                'vendor_id' => $validated['vendor_id'],
                'base_price' => $validated['price']['amount'] ?? null // Ensure base_price is set
            ]));
        }

        // 5. Stock
        // Ensure warehouse_id exists, otherwise return error
        if (!empty($validated['stock']) && is_array($validated['stock'])) {
            $warehouseId = $validated['stock']['warehouse_id'] ?? 1;
            $stock_threshold = $validated['stock_threshold'] ?? 0;
            $validated['stock']['stock_threshold'] = $stock_threshold;
            if (!\App\Models\Warehouse::where('id', $warehouseId)->exists()) {
                throw new \Exception("Warehouse with id {$warehouseId} does not exist.");
            }
            $product->stocks()->create(array_merge($validated['stock'], [
                'warehouse_id' => $warehouseId
            ]));
        }

        // 6. Offers
        if (!empty($validated['offers'])) {
            $product->offers()->createMany($validated['offers']);
        }

        // 7. Media
        if (!empty($validated['media'])) {
            $product->media()->createMany($validated['media']);
        }

        DB::commit();

        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product->load([
                'category',
                'vendor',
                'images',
                'prices',
                'stocks',
                'offers',
                'media',
                'variants',
            ]),
        ], 201);

    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('Product creation failed: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

        return response()->json(['error' => 'Failed to create product.'], 500);
    }
}


// write a helper function to create sku if not provided

    protected function createSkuIfNotProvided(array $data): array
    {
        if (empty($data['sku'])) {
            $data['sku'] = 'SKU-' . strtoupper(uniqid());
        }
        return $data;
    }

    // Show a single product
    public function show($id): JsonResponse
    {
        $product = Product::findOrFail($id);
        return response()->json(new ProductResource($product));
    }

    // Update product (must belong to vendor)
    // public function update(UpdateProductRequest $request, Product $product): JsonResponse
    // {
    //     if ($product->vendor_id !== Auth::user()->vendor_id) {
    //         return response()->json(['error' => 'Unauthorized'], 403);
    //     }

    //     $product->update($request->validated());

    //     return response()->json(new ProductResource($product));
    // }



    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        Log::info('Attempting to update product', [
            'product_id' => $id,
            'request_data' => $request->all()
        ]);

        $validated = $request->validate([
            'vendor_id'   => 'sometimes|exists:vendors,id',
            'category_id' => 'sometimes|exists:categories,id',
            'product_name'=> 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'sku'         => "sometimes|string|max:100|unique:products,sku,{$product->id}",

            'attributes'  => 'sometimes|array',
            'attributes.*.name'  => 'required_with:attributes|string|max:100',
            'attributes.*.value' => 'required_with:attributes|string|max:255',

            'images'      => 'sometimes|array',
            'images.*'    => 'url',

            'price.currency'   => 'sometimes|string|max:10',
            'price.amount'     => 'sometimes|numeric|min:0',
            'price.sale_price' => 'nullable|numeric|min:0',

            'stock.quantity' => 'sometimes|integer|min:0',
            'stock.track'    => 'sometimes|boolean',

            'offers'      => 'sometimes|array',
            'offers.*.type'       => 'required_with:offers|string',
            'offers.*.value'      => 'required_with:offers|numeric',
            'offers.*.start_date' => 'required_with:offers|date',
            'offers.*.end_date'   => 'required_with:offers|date',

            'media'       => 'sometimes|array',
            'media.*.type' => 'required_with:media|string',
            'media.*.url'  => 'required_with:media|url'
        ]);

        DB::beginTransaction();

        try {
            // 1. Update Product base info
            $updateData = [];
            foreach (['vendor_id', 'category_id', 'product_name', 'description', 'sku'] as $field) {
                if (array_key_exists($field, $validated)) {
                    $updateData[$field] = $validated[$field];
                }
            }
            $product->update($updateData);

            // 2. Sync Attributes
            if (!empty($validated['attributes'])) {
                ProductAttributeValue::where('product_attribute_id', $product->id)->delete();
                foreach ($validated['attributes'] as $attr) {
                    $attribute = ProductAttribute::firstOrCreate(['name' => $attr['name']]);
                    ProductAttributeValue::create([
                        'product_id'           => $product->id,
                        'product_attribute_id' => $attribute->id,
                        'value'                => $attr['value'],
                    ]);
                }
            }

            // 3. Replace Images (reset + insert)
            if (!empty($validated['images'])) {
                $product->media()->where('media_type', 'image')->delete();
                $product->media()->createMany(
                    collect($validated['images'])->map(function ($image, $index) {
                        return [
                            'media_type' => 'image',
                            'url'        => $image,
                            'alt_text'   => null,
                            'is_primary' => $index === 0,
                            'position'   => $index,
                        ];
                    })->toArray()
                );
            }

            // 4. Replace Non-image Media
            if (!empty($validated['media'])) {
                $product->media()->where('media_type', '!=', 'image')->delete();
                $product->media()->createMany(
                    collect($validated['media'])->map(function ($media) {
                        return [
                            'media_type' => $media['type'] ?? 'other',
                            'url'        => $media['url'],
                            'alt_text'   => $media['alt_text'] ?? null,
                            'is_primary' => $media['is_primary'] ?? false,
                            'position'   => $media['position'] ?? 0,
                        ];
                    })->toArray()
                );
            }

            // 5. Update or Create Price
            if (!empty($validated['price']) && !empty($validated['vendor_id'])) {
                $product->prices()->updateOrCreate(
                    ['vendor_id' => $validated['vendor_id']],
                    [
                        'currency'   => $validated['price']['currency'],
                        'base_price' => $validated['price']['amount'],
                        'discount_price' => $validated['price']['sale_price'] ?? null,
                        'is_active'  => true,
                    ]
                );
            }

            // 6. Update Stock
            if (!empty($validated['stock'])) {
                $warehouseId = $validated['stock']['warehouse_id'] ?? 1;
                if (!\App\Models\Warehouse::where('id', $warehouseId)->exists()) {
                    throw new \Exception("Warehouse with id {$warehouseId} does not exist.");
                }
                $product->stocks()->updateOrCreate(
                    ['warehouse_id' => $warehouseId],
                    [
                        'current_quantity' => $validated['stock']['current_quantity'],
                        'track'    => $validated['stock']['track'],
                    ]
                );
            }

            // 7. Replace Offers
            if (!empty($validated['offers'])) {
                $product->offers()->delete();
                $product->offers()->createMany($validated['offers']);
            }

            DB::commit();

            return response()->json([
                'message' => 'Product updated successfully',
                'product' => $product->load([
                    'category',
                    'vendor',
                    'images',
                    'prices',
                    'stocks',
                    'offers',
                    'media',
                    'variants',
                ]),
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Product update failed: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json(['error' => 'Failed to update product.'], 500);
        }
    }



    // Delete product (must belong to vendor)
    public function destroy($id): JsonResponse
    {
        Log::info('Attempting to delete product', ['product_id' => $id, 'user_id' => Auth::id()]);

        $product = Product::findOrFail($id);

        // if ($product->vendor_id !== Auth::user()->vendor_id) {
        //     Log::warning('Unauthorized product delete attempt', [
        //         'product_id' => $id,
        //         'user_vendor_id' => Auth::user()->vendor_id,
        //         'product_vendor_id' => $product->vendor_id
        //     ]);
        //     return response()->json(['error' => 'Unauthorized'], 403);
        // }

        $product->delete();

        Log::info('Product deleted successfully', ['product_id' => $id]);

        return response()->json([
            'message' => 'Product deleted successfully'
        ], 204);
    }

    // Products belonging to a specific vendor
    public function productsByVendor($vendorId): JsonResponse
    {
        $products = Product::where('vendor_id', $vendorId)
            ->with(['category', 'stocks', 'prices', 'variants'])
            ->paginate(10);

        return response()->json(ProductResource::collection($products));
    }

    // Bulk product creation (important for integrations)
    public function bulkStore(StoreProductRequest $request): JsonResponse
    {
        $productsData = $request->validated()['products'];
        $created = [];

        foreach ($productsData as $data) {
            $data['vendor_id'] = Auth::user()->vendor_id ?? $data['vendor_id'];
            $created[] = Product::create($data);
        }

        return response()->json(ProductResource::collection($created), 201);
    }
}
