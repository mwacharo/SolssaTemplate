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
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;


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
            'images',
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




    public function storex(Request $request)
    {

        Log::info('Attempting to create product', ['request_data' => $request->all()]);
        $validated = $request->validate([
            'vendor_id'   => 'required|exists:users,id',
            'category_id' => 'required|exists:categories,id',
            // 'country_id' => 'required|exists:countries,id',
            'product_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sku'         => 'required|string|max:100|unique:products,sku',

            'attributes'  => 'array',
            'attributes.*.name'  => 'required|string|max:100',
            'attributes.*.value' => 'required|string|max:255',

            'images'      => 'array',
            // 'images.*'    => 'url',
            'base_price' => 'required|numeric|min:0',

            'price.currency'   => 'nullable|string|max:10',
            'price.amount'     => 'nullable|numeric|min:0',
            'price.sale_price' => 'nullable|numeric|min:0',
            'stock.quantity'      => 'nullable|integer|min:0',
            'stock.track'         => 'nullable|boolean',
            'stock.current_stock'   => 'nullable|integer|min:0',
            'stock.committed_stock' => 'nullable|integer|min:0',
            'stock.defected_stock'  => 'nullable|integer|min:0',
            'stock.historical_stock' => 'nullable|array',
            'stock.stock_threshold' => 'nullable|integer|min:0',
            'stock_threshold'       => 'required|integer|min:0',



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
                'product_name' => $validated['product_name'],
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
                // $product->media()->createMany(
                $product->image()->createMany(


                    collect($validated['images'])->map(function ($image, $index) {
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
            // if (!empty($validated['media'])) {
            //     $product->media()->createMany(
            //         collect($validated['media'])->map(function ($media) {
            //             return [
            //                 'media_type' => $media['type'] ?? 'other',
            //                 'url' => $media['url'],
            //                 'alt_text' => $media['alt_text'] ?? null,
            //                 'is_primary' => $media['is_primary'] ?? false,
            //                 'position' => $media['position'] ?? 0,
            //             ];
            //         })->toArray()
            //     );
            // }

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
                $stock_threshold = $validated['stock_threshold'] ?? 0;



                $validated['stock']['stock_threshold'] = $stock_threshold;

                // Add missing fields if not present
                $validated['stock']['current_stock'] = $validated['stock']['current_stock'] ?? 0;
                $validated['stock']['committed_stock'] = $validated['stock']['committed_stock'] ?? 0;
                $validated['stock']['defected_stock'] = $validated['stock']['defected_stock'] ?? 0;
                $validated['stock']['historical_stock'] = $validated['stock']['historical_stock'] ?? [];
                $validated['stock']['stock_threshold'] = $validated['stock']['stock_threshold'] ?? $stock_threshold;

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
            Log::error('Product creation failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

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
        $product = Product::with([
            'vendor',
            'category',
            'stocks',
            'statistics',
            'prices',
            'images',
            'media',
            'variants'
        ])->findOrFail($id);

        return response()->json(new ProductResource($product));
    }




    public function updatex(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        Log::info('Attempting to update product', [
            'product_id' => $id,
            'request_data' => $request->all()
        ]);

        $validated = $request->validate([
            'vendor_id'   => 'sometimes|exists:users,id',
            'category_id' => 'sometimes|exists:categories,id',
            'product_name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'sku'         => "sometimes|string|max:100|unique:products,sku,{$product->id}",

            'attributes'  => 'sometimes|array',
            'attributes.*.name'  => 'required_with:attributes|string|max:100',
            'attributes.*.value' => 'required_with:attributes|string|max:255',

            'images'      => 'sometimes|array',
            // 'images.*'    => 'url',
            'base_price' => 'nullable|numeric|min:0',

            'price.currency'   => 'sometimes|string|max:10',
            // 'price.base_price' => 'required|numeric|min:0',
            'price.sale_price' => 'nullable|numeric|min:0',

            // Stock operation validation
            'stock'                    => 'sometimes|array',
            'stock.type'               => 'sometimes|string|in:in,out,adjust',
            'stock.stock_type'         => 'sometimes|string|in:current,committed,defected',
            'stock.current_stock'   => 'sometimes|integer|min:0',
            'stock.committed_stock' => 'sometimes|integer|min:0',
            'stock.defected_stock'  => 'sometimes|integer|min:0',
            'stock.historical_stock' => 'sometimes|array',
            'stock.stock_threshold' => 'sometimes|integer|min:0',
            'stock_threshold'       => 'sometimes|integer|min:0',

            'stock.warehouse_id'       => 'sometimes|integer|exists:warehouses,id',
            'stock.track'              => 'sometimes|boolean',

            // Direct stock fields (for initial values)
            'current_stock'            => 'sometimes|integer|min:0',
            'committed_stock'          => 'sometimes|integer|min:0',
            'defected_stock'           => 'sometimes|integer|min:0',
            'historical_stock'         => 'sometimes|array',

            'offers'      => 'sometimes|array',
            'offers.*.type'       => 'required_with:offers|string',
            'offers.*.value'      => 'required_with:offers|numeric',
            'offers.*.start_date' => 'required_with:offers|date',
            'offers.*.end_date'   => 'required_with:offers|date',

            'media'       => 'sometimes|array',
            'media.*.type' => 'required_with:media|string',
            'media.*.url'  => 'required_with:media|url',

            'product_id'        => 'sometimes|integer|exists:products,id',
        ]);
        // log the validated data
        Log::info('Validated data for product update', [
            'product_id' => $id,
            'validated_data' => $validated
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
            // if (!empty($validated['media'])) {
            //     $product->media()->where('media_type', '!=', 'image')->delete();
            //     $product->media()->createMany(
            //         collect($validated['media'])->map(function ($media) {
            //             return [
            //                 'media_type' => $media['type'] ?? 'other',
            //                 'url'        => $media['url'],
            //                 'alt_text'   => $media['alt_text'] ?? null,
            //                 'is_primary' => $media['is_primary'] ?? false,
            //                 'position'   => $media['position'] ?? 0,
            //             ];
            //         })->toArray()
            //     );
            // }

            // 5. Update or Create Price
            // Handle price update (can be in 'price' object or direct 'price.base_price')
            if (!empty($validated['base_price']) && !empty($validated['vendor_id'])) {
                $currency = $validated['price']['currency'] ?? $validated['currency'] ?? 'KSH'; // Default to 'KSH' if not provided
                $product->prices()->updateOrCreate(
                    ['vendor_id' => $validated['vendor_id']],
                    [
                        'currency'      => $currency,
                        'base_price'    => $validated['price']['base_price'] ?? $validated['price']['amount'] ?? $validated['base_price'] ?? null,
                        'discount_price' => $validated['price']['sale_price'] ?? null,
                        'is_active'     => true,
                    ]
                );
            } elseif (isset($validated['price.base_price']) && !empty($validated['vendor_id'])) {
                $currency = $validated['price']['currency'] ?? $validated['currency'] ?? 'KSH'; // Default to 'KSH' if not provided
                $product->prices()->updateOrCreate(
                    ['vendor_id' => $validated['vendor_id']],
                    [
                        'currency'   => $currency,
                        'base_price' => $validated['price.base_price'],
                        'is_active'  => true,
                    ]
                );
            }

            // 6. Update Stock
            if (!empty($validated['stock'])) {
                $warehouseId = $validated['stock']['warehouse_id'] ?? 1;
                Log::debug('Updating stock', [
                    'warehouse_id' => $warehouseId,
                    'stock_data' => $validated['stock']
                ]);

                $stockType = $validated['stock']['type'] ?? null;
                $operationQuantity = $validated['stock']['current_stock'] ?? 0;
                $stockTypeField = $validated['stock']['stock_type'] ?? 'current'; // current, committed, defected

                $stockRecord = $product->stocks()->where('warehouse_id', $warehouseId)->first();

                Log::debug('Existing stock record', [
                    'stockRecord' => $stockRecord
                ]);

                // Get current values for each stock type
                $currentStock = $stockRecord ? $stockRecord->current_stock : 0;
                $committedStock = $stockRecord ? $stockRecord->committed_stock : 0;
                $defectedStock = $stockRecord ? $stockRecord->defected_stock : 0;

                // Apply operation based on stock type and operation type
                if ($stockTypeField === 'current') {
                    if ($stockType === 'in') {
                        $currentStock += $operationQuantity;
                    } elseif ($stockType === 'out') {
                        $currentStock = max(0, $currentStock - $operationQuantity);
                    } else { // adjust
                        $currentStock = $operationQuantity;
                    }
                } elseif ($stockTypeField === 'committed') {
                    if ($stockType === 'in') {
                        $committedStock += $operationQuantity;
                    } elseif ($stockType === 'out') {
                        $committedStock = max(0, $committedStock - $operationQuantity);
                    } else { // adjust
                        $committedStock = $operationQuantity;
                    }
                } elseif ($stockTypeField === 'defected') {
                    if ($stockType === 'in') {
                        $defectedStock += $operationQuantity;
                    } elseif ($stockType === 'out') {
                        $defectedStock = max(0, $defectedStock - $operationQuantity);
                    } else { // adjust
                        $defectedStock = $operationQuantity;
                    }
                }

                Log::debug('Stock calculation', [
                    'stock_type_field' => $stockTypeField,
                    'operation_type' => $stockType,
                    'operation_quantity' => $operationQuantity,
                    'new_current_stock' => $currentStock,
                    'new_committed_stock' => $committedStock,
                    'new_defected_stock' => $defectedStock
                ]);

                // Ensure stock_threshold is updated from either stock array or root validated array
                // Debug: Log how stock_threshold is determined
                // Log::debug('Determining stock_threshold', [
                //     'stock.stock_threshold' => $validated['stock']['stock_threshold'] ?? null,
                //     'root.stock_threshold' => $validated['stock_threshold'] ?? null,
                //     'existing_stock_threshold' => $stockRecord ? $stockRecord->stock_threshold : null,
                // ]);
                $stockThreshold = $validated['stock']['stock_threshold'] ?? $validated['stock_threshold'] ?? ($stockRecord ? $stockRecord->stock_threshold : 0);
                Log::debug('Final stock_threshold value', [
                    'stockThreshold' => $stockThreshold
                ]);

                // Update the stock record
                $product->stocks()->updateOrCreate(
                    ['warehouse_id' => $warehouseId],
                    [
                        'current_stock'    => $currentStock,
                        'committed_stock'  => $committedStock,
                        'defected_stock'   => $defectedStock,
                        // 'track'            => $validated['stock']['track'] ?? ($stockRecord ? $stockRecord->track : true),
                        'stock_threshold'  => $stockThreshold,
                    ]
                );

                Log::info('Stock updated', [
                    'warehouse_id' => $warehouseId,
                    'current_stock' => $currentStock,
                    'committed_stock' => $committedStock,
                    'defected_stock' => $defectedStock,
                    'stock_threshold' => $stockThreshold
                ]);
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
            Log::error('Product update failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json(['error' => 'Failed to update product.'], 500);
        }
    }




    public function store(Request $request)
    {
        Log::info('Attempting to create product', ['request_data' => $request->all()]);

        $validated = $request->validate([
            'vendor_id'   => 'required|exists:users,id',
            // NEW LINE
            'country_id' => 'sometimes|exists:countries,id',

            'category_id' => 'required|exists:categories,id',
            'product_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sku'         => 'required|string|max:100|unique:products,sku',

            'attributes'  => 'array',
            'attributes.*.name'  => 'required|string|max:100',
            'attributes.*.value' => 'required|string|max:255',

            'images'      => 'array',
            'images.*'    => 'file|image|max:5120', // Support file uploads (5MB max)

            'base_price' => 'required|numeric|min:0',
            'initial_quantity' => 'nullable|integer|min:0',
            'stock_threshold' => 'required|integer|min:0',

            'price.currency'   => 'nullable|string|max:10',
            'price.amount'     => 'nullable|numeric|min:0',
            'price.sale_price' => 'nullable|numeric|min:0',

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
            // 1. Create Product
            $product = Product::create([
                'vendor_id'   => $validated['vendor_id'],
                'category_id' => $validated['category_id'],
                'product_name' => $validated['product_name'],
                'description' => $validated['description'] ?? null,
                'sku'         => $validated['sku'],
            ]);

            // 2. Handle Attributes
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

            // 3. Handle Image Uploads
            // 3. Handle Image Uploads
            if (!empty($validated['images'])) {
                $imageData = [];

                foreach ($validated['images'] as $index => $image) {
                    Log::info('🔍 Checking image', ['index' => $index, 'type' => get_class($image)]);

                    if ($image instanceof UploadedFile) {
                        $path = $image->store('products', 'public');
                        $url = Storage::url($path);
                        Log::info('✅ Uploaded file stored', ['path' => $path, 'url' => $url]);
                    } else {
                        $url = $image;
                        Log::info('ℹ️ Using existing image URL', ['url' => $url]);
                    }

                    $imageData[] = [
                        'image_path' => $url,
                        'is_featured' => $index === 0,
                    ];
                }

                Log::info('✅ Final image data before saving', $imageData);

                if (!empty($imageData) && isset($imageData[0]['image_path'])) {
                    $product->images()->createMany($imageData);
                    Log::info('✅ Images successfully saved');
                } else {
                    Log::error('❌ image_path missing from imageData', $imageData);
                }
            }
            // 4. Create Price
            $product->prices()->create([
                'vendor_id' => $validated['vendor_id'],
                'currency' => $validated['price']['currency'] ?? 'KSH',
                'base_price' => $validated['base_price'],
                'discount_price' => $validated['price']['sale_price'] ?? null,
                'is_active' => true,
            ]);

            // 5. Stock
            // Ensure warehouse_id exists, otherwise return error
            if (!empty($validated['stock']) && is_array($validated['stock'])) {
                $warehouseId = $validated['stock']['warehouse_id'] ?? 1;
                $stock_threshold = $validated['stock_threshold'] ?? 0;
                $stock_threshold = $validated['stock_threshold'] ?? 0;



                $validated['stock']['stock_threshold'] = $stock_threshold;

                // Add missing fields if not present
                $validated['stock']['current_stock'] = $validated['stock']['current_stock'] ?? 0;
                $validated['stock']['committed_stock'] = $validated['stock']['committed_stock'] ?? 0;
                $validated['stock']['defected_stock'] = $validated['stock']['defected_stock'] ?? 0;
                $validated['stock']['historical_stock'] = $validated['stock']['historical_stock'] ?? [];
                $validated['stock']['stock_threshold'] = $validated['stock']['stock_threshold'] ?? $stock_threshold;

                if (!\App\Models\Warehouse::where('id', $warehouseId)->exists()) {
                    throw new \Exception("Warehouse with id {$warehouseId} does not exist.");
                }
                $product->stocks()->create(array_merge($validated['stock'], [
                    'warehouse_id' => $warehouseId
                ]));
            }
            // 6. Handle Offers
            if (!empty($validated['offers'])) {
                $product->offers()->createMany($validated['offers']);
            }

            // 7. Handle Additional Media (videos, documents, etc.)
            if (!empty($validated['media'])) {
                $mediaData = collect($validated['media'])->map(function ($media) {
                    return [
                        'media_type' => $media['type'] ?? 'other',
                        'url' => $media['url'],
                        'alt_text' => $media['alt_text'] ?? null,
                        'is_primary' => $media['is_primary'] ?? false,
                        'position' => $media['position'] ?? 0,
                    ];
                })->toArray();

                $product->media()->createMany($mediaData);
            }

            DB::commit();

            return response()->json([
                'message' => 'Product created successfully',
                'product' => $product->load([
                    'category',
                    'vendor',
                    'images',
                    'media',
                    'prices',
                    'stocks',
                    'offers',
                    'variants',
                ]),
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Product creation failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to create product.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        Log::info('Attempting to update product', [
            'product_id' => $id,
            'request_data' => $request->all()
        ]);

        $validated = $request->validate([
            'vendor_id'   => 'sometimes|exists:users,id',
            'category_id' => 'sometimes|exists:categories,id',
            'product_name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'sku'         => "sometimes|string|max:100|unique:products,sku,{$product->id}",

            'attributes'  => 'sometimes|array',
            'attributes.*.name'  => 'required_with:attributes|string|max:100',
            'attributes.*.value' => 'required_with:attributes|string|max:255',

            'images'      => 'sometimes|array',
            'images.*'    => 'file|image|max:5120',

            'base_price' => 'sometimes|numeric|min:0',
            'initial_quantity' => 'sometimes|integer|min:0',
            'stock_threshold' => 'sometimes|integer|min:0',

            'price.currency'   => 'sometimes|string|max:10',
            'price.sale_price' => 'nullable|numeric|min:0',

            // Stock operation validation
            'stock'                    => 'sometimes|array',
            'stock.type'               => 'sometimes|string|in:in,out,adjust',
            'stock.stock_type'         => 'sometimes|string|in:current,committed,defected',
            // 'stock.quantity'           => 'sometimes|integer|min:0',
            'stock.current_stock' => 'sometimes|integer|min:0',

            'stock.warehouse_id'       => 'sometimes|integer|exists:warehouses,id',

            'offers'      => 'sometimes|array',
            'offers.*.type'       => 'required_with:offers|string',
            'offers.*.value'      => 'required_with:offers|numeric',
            'offers.*.start_date' => 'required_with:offers|date',
            'offers.*.end_date'   => 'required_with:offers|date',

            'media'       => 'sometimes|array',
            'media.*.type' => 'required_with:media|string',
            'media.*.url'  => 'required_with:media|url',
        ]);

        Log::info('Validated data for product update', [
            'product_id' => $id,
            'validated_data' => $validated
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
            if (!empty($updateData)) {
                $product->update($updateData);
            }

            // 2. Sync Attributes
            if (isset($validated['attributes'])) {
                ProductAttributeValue::where('product_id', $product->id)->delete();
                foreach ($validated['attributes'] as $attr) {
                    $attribute = ProductAttribute::firstOrCreate(['name' => $attr['name']]);
                    ProductAttributeValue::create([
                        'product_id'           => $product->id,
                        'product_attribute_id' => $attribute->id,
                        'value'                => $attr['value'],
                    ]);
                }
            }

            // 3. Replace Images
            if (isset($validated['images'])) {
                // Delete old images
                $oldImages = $product->images()->get();
                foreach ($oldImages as $oldImage) {
                    // Delete physical file if stored locally
                    if (str_starts_with($oldImage->url, '/storage/')) {
                        Storage::disk('public')->delete(str_replace('/storage/', '', $oldImage->url));
                    }
                }
                $product->images()->delete();

                // Add new images
                $imageData = [];
                foreach ($validated['images'] as $index => $image) {
                    if ($image instanceof \Illuminate\Http\UploadedFile) {
                        $path = $image->store('products', 'public');
                        $url = Storage::url($path);
                    } else {
                        $url = $image;
                    }

                    $imageData[] = [
                        'image_path' => $url,
                        'alt_text' => null,
                        'is_primary' => $index === 0,
                        'position' => $index,
                    ];
                }

                $product->images()->createMany($imageData);
            }

            // 4. Update Price
            if (isset($validated['base_price'])) {
                $vendorId = $validated['vendor_id'] ?? $product->vendor_id;

                $product->prices()->updateOrCreate(
                    ['vendor_id' => $vendorId],
                    [
                        'currency' => $validated['price']['currency'] ?? 'KSH',
                        'base_price' => $validated['base_price'],
                        'discount_price' => $validated['price']['sale_price'] ?? null,
                        'is_active' => true,
                    ]
                );
            }

            // 5. Update Stock
            $warehouseId = $validated['stock']['warehouse_id'] ?? 1;
            $stockRecord = $product->stocks()->where('warehouse_id', $warehouseId)->first();

            // Handle simple quantity update (from form submission)
            if (isset($validated['initial_quantity']) || isset($validated['stock_threshold'])) {
                $stockData = [];

                if (isset($validated['initial_quantity'])) {
                    $stockData['current_stock'] = $validated['initial_quantity'];
                }

                if (isset($validated['stock_threshold'])) {
                    $stockData['stock_threshold'] = $validated['stock_threshold'];
                }

                if (!empty($stockData)) {
                    $product->stocks()->updateOrCreate(
                        ['warehouse_id' => $warehouseId],
                        $stockData
                    );

                    Log::info('Stock updated (simple)', [
                        'warehouse_id' => $warehouseId,
                        'stock_data' => $stockData
                    ]);
                }
            }

            // Handle advanced stock operations (in/out/adjust)
            elseif (isset($validated['stock'])) {
                $stockType = $validated['stock']['type'] ?? null;
                // $operationQuantity = $validated['stock']['quantity'] ?? 0;
                $operationQuantity = $validated['stock']['quantity']
                    ?? $validated['stock']['current_stock']
                    ?? 0;

                $stockTypeField = $validated['stock']['stock_type'] ?? 'current';

                $currentStock = $stockRecord ? $stockRecord->current_stock : 0;
                $committedStock = $stockRecord ? $stockRecord->committed_stock : 0;
                $defectedStock = $stockRecord ? $stockRecord->defected_stock : 0;

                // Apply operation
                if ($stockTypeField === 'current') {
                    if ($stockType === 'in') {
                        $currentStock += $operationQuantity;
                    } elseif ($stockType === 'out') {
                        $currentStock = max(0, $currentStock - $operationQuantity);
                    } else {
                        $currentStock = $operationQuantity;
                    }
                } elseif ($stockTypeField === 'committed') {
                    if ($stockType === 'in') {
                        $committedStock += $operationQuantity;
                    } elseif ($stockType === 'out') {
                        $committedStock = max(0, $committedStock - $operationQuantity);
                    } else {
                        $committedStock = $operationQuantity;
                    }
                } elseif ($stockTypeField === 'defected') {
                    if ($stockType === 'in') {
                        $defectedStock += $operationQuantity;
                    } elseif ($stockType === 'out') {
                        $defectedStock = max(0, $defectedStock - $operationQuantity);
                    } else {
                        $defectedStock = $operationQuantity;
                    }
                }

                $product->stocks()->updateOrCreate(
                    ['warehouse_id' => $warehouseId],
                    [
                        'current_stock' => $currentStock,
                        'committed_stock' => $committedStock,
                        'defected_stock' => $defectedStock,
                        'stock_threshold' => $validated['stock']['stock_threshold'] ??
                            ($stockRecord ? $stockRecord->stock_threshold : 0),
                    ]
                );

                Log::info('Stock updated (operation)', [
                    'warehouse_id' => $warehouseId,
                    'operation' => $stockType,
                    'stock_type' => $stockTypeField,
                    'quantity' => $operationQuantity,
                    'new_values' => [
                        'current' => $currentStock,
                        'committed' => $committedStock,
                        'defected' => $defectedStock
                    ]
                ]);
            }

            // 6. Replace Offers
            if (isset($validated['offers'])) {
                $product->offers()->delete();
                $product->offers()->createMany($validated['offers']);
            }

            // 7. Replace Media
            if (isset($validated['media'])) {
                $product->media()->delete();

                $mediaData = collect($validated['media'])->map(function ($media) {
                    return [
                        'media_type' => $media['type'] ?? 'other',
                        'url' => $media['url'],
                        'alt_text' => $media['alt_text'] ?? null,
                        'is_primary' => $media['is_primary'] ?? false,
                        'position' => $media['position'] ?? 0,
                    ];
                })->toArray();

                $product->media()->createMany($mediaData);
            }

            DB::commit();

            return response()->json([
                'message' => 'Product updated successfully',
                'product' => $product->load([
                    'category',
                    'vendor',
                    'images',
                    'media',
                    'prices',
                    'stocks',
                    'offers',
                    'variants',
                ]),
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Product update failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to update product.',
                'message' => $e->getMessage()
            ], 500);
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
