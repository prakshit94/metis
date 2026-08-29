<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Controllers;

use App\Modules\Catalog\Models\Brand;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\HsnCode;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductAttribute;
use App\Modules\Catalog\Models\ProductAttributeValue;
use App\Modules\Catalog\Models\TaxRate;
use App\Modules\Catalog\Models\UnitOfMeasure;
use App\Modules\Catalog\Models\Warehouse;
use App\Modules\Core\Controllers\Controller;
use App\Modules\Inventory\Models\Stock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('product-view'), 403);

        $products = Product::query()
            ->with(['category.parent', 'brand', 'taxRate', 'hsnCode', 'uom', 'warehouse', 'attributeValues.attribute', 'supplier'])
            ->withSum('stocks as stocks_sum_quantity', 'quantity')
            ->withSum('stocks as stocks_sum_reserved_qty', 'reserved_qty')
            ->withSum('stocks as stocks_sum_dispatched_qty', 'dispatched_qty')
            ->withSum('pendingOrderItems as pending_orders_qty', 'quantity')
            ->latest()
            ->get()
            ->map(fn (Product $product) => $this->transform($product))
            ->values();

        return response()->json([
            'data' => $products,
            'stats' => $this->stats($products),
            'options' => $this->catalogOptions(),
        ]);
    }

    public function show(Request $request, Product $product): JsonResponse
    {
        abort_unless($request->user()?->can('product-view'), 403);

        $product->load(['category.parent', 'brand', 'taxRate', 'hsnCode', 'uom', 'warehouse', 'attributeValues.attribute', 'supplier']);
        $product->loadSum('stocks as stocks_sum_quantity', 'quantity');
        $product->loadSum('stocks as stocks_sum_reserved_qty', 'reserved_qty');
        $product->loadSum('stocks as stocks_sum_dispatched_qty', 'dispatched_qty');
        $product->loadSum('pendingOrderItems as pending_orders_qty', 'quantity');

        return response()->json([
            'data' => $this->transform($product),
            'options' => $this->catalogOptions(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('product-create'), 403);

        $data = $this->validatePayload($request);

        $product = new Product;
        $this->fillProduct($product, $data, $request);
        $product->save();
        $this->syncAttributes($product, $data['attributes'] ?? []);
        $this->syncStock($product, (int) ($data['stock'] ?? $data['stock_quantity'] ?? 0));

        return response()->json([
            'message' => 'Product created successfully.',
            'data' => $this->transform($product->fresh(['category.parent', 'brand', 'taxRate', 'hsnCode', 'uom', 'warehouse', 'attributeValues.attribute', 'supplier'])
                ->loadSum('stocks as stocks_sum_quantity', 'quantity')
                ->loadSum('stocks as stocks_sum_reserved_qty', 'reserved_qty')
                ->loadSum('stocks as stocks_sum_dispatched_qty', 'dispatched_qty')
                ->loadSum('pendingOrderItems as pending_orders_qty', 'quantity')),
        ], 201);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        abort_unless($request->user()?->can('product-edit'), 403);

        $data = $this->validatePayload($request, $product);
        $this->fillProduct($product, $data, $request);
        $product->save();

        if (array_key_exists('attributes', $data)) {
            $this->syncAttributes($product, $data['attributes']);
        }

        // Only sync stock if stock value was explicitly submitted
        if (array_key_exists('stock', $data) || array_key_exists('stock_quantity', $data)) {
            $this->syncStock($product, (int) ($data['stock'] ?? $data['stock_quantity'] ?? 0));
        }

        return response()->json([
            'message' => 'Product updated successfully.',
            'data' => $this->transform($product->fresh(['category.parent', 'brand', 'taxRate', 'hsnCode', 'uom', 'warehouse', 'attributeValues.attribute', 'supplier'])
                ->loadSum('stocks as stocks_sum_quantity', 'quantity')
                ->loadSum('stocks as stocks_sum_reserved_qty', 'reserved_qty')
                ->loadSum('stocks as stocks_sum_dispatched_qty', 'dispatched_qty')
                ->loadSum('pendingOrderItems as pending_orders_qty', 'quantity')),
        ]);
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        abort_unless($request->user()?->can('product-delete'), 403);

        $product->delete();

        return response()->json([
            'message' => 'Product moved to trash.',
        ]);
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('product-delete'), 403);

        $ids = $this->extractIds($request->input('ids'));

        if (empty($ids)) {
            return response()->json(['message' => 'No products selected.'], 422);
        }

        Product::whereIn('id', $ids)->delete();

        return response()->json([
            'message' => 'Selected products deleted.',
        ]);
    }

    public function bulkStatus(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('product-edit'), 403);

        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:products,id'],
            'status' => ['required', 'in:published,draft,pending,active,out_of_stock'],
        ]);

        $status = $this->normalizeStatus((string) $data['status']);
        Product::whereIn('id', $data['ids'])->update([
            'status' => $status,
            'is_active' => in_array($status, ['published', 'active'], true),
        ]);

        return response()->json([
            'message' => 'Selected products updated successfully.',
        ]);
    }

    public function bulkDisableSku(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('product-edit'), 403);

        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:products,id'],
        ]);

        Product::whereIn('id', $data['ids'])->update([
            'is_sku_enabled' => false,
        ]);

        return response()->json([
            'message' => 'SKUs disabled for selected products.',
        ]);
    }

    public function bulkEnableSku(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('product-edit'), 403);

        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:products,id'],
        ]);

        Product::whereIn('id', $data['ids'])->update([
            'is_sku_enabled' => true,
        ]);

        return response()->json([
            'message' => 'SKUs enabled for selected products.',
        ]);
    }

    public function restore(Request $request, string $product): JsonResponse
    {
        abort_unless($request->user()?->can('product-restore'), 403);

        $product = Product::withTrashed()->findOrFail($product);
        $product->restore();

        return response()->json([
            'message' => 'Product restored successfully.',
            'data' => $this->transform($product->fresh(['category.parent', 'brand', 'taxRate', 'hsnCode', 'uom', 'warehouse', 'attributeValues.attribute', 'supplier'])
                ->loadSum('stocks as stocks_sum_quantity', 'quantity')
                ->loadSum('stocks as stocks_sum_reserved_qty', 'reserved_qty')
                ->loadSum('stocks as stocks_sum_dispatched_qty', 'dispatched_qty')
                ->loadSum('pendingOrderItems as pending_orders_qty', 'quantity')),
        ]);
    }

    public function forceDelete(Request $request, string $product): JsonResponse
    {
        abort_unless($request->user()?->can('product-permanent-delete'), 403);

        $product = Product::withTrashed()->findOrFail($product);
        $this->deleteImage($product);
        $product->forceDelete();

        return response()->json([
            'message' => 'Product permanently deleted.',
        ]);
    }

    public function searchApi(Request $request): JsonResponse
    {
        $query = Product::query()
            ->with(['category', 'brand', 'taxRate', 'uom', 'stocks.warehouse'])
            ->withSum('stocks as stocks_sum_quantity', 'quantity')
            ->withSum('stocks as stocks_sum_reserved_qty', 'reserved_qty')
            ->withSum('stocks as stocks_sum_dispatched_qty', 'dispatched_qty')
            ->withSum('pendingOrderItems as pending_orders_qty', 'quantity');

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($qb) use ($q) {
                $qb->where('name', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->filled('stock')) {
            if ($request->stock === 'available') {
                $query->havingRaw('(COALESCE(stocks_sum_quantity, 0) - COALESCE(stocks_sum_reserved_qty, 0) - COALESCE(pending_orders_qty, 0)) > 0');
            } elseif ($request->stock === 'out_of_stock') {
                $query->havingRaw('(COALESCE(stocks_sum_quantity, 0) - COALESCE(stocks_sum_reserved_qty, 0) - COALESCE(pending_orders_qty, 0)) <= 0');
            }
        }

        $perPage = (int) $request->input('perPage', 12);
        $paginator = $query->latest()->paginate($perPage);

        $data = $paginator->through(function (Product $p) {
            $totalQty = (float) ($p->stocks_sum_quantity ?? 0);
            $reservedQty = (float) ($p->stocks_sum_reserved_qty ?? 0);
            $dispatchedQty = (float) ($p->stocks_sum_dispatched_qty ?? 0);
            $pendingQty = (float) ($p->pending_orders_qty ?? 0);

            $rawAvailable = $totalQty - $reservedQty - $pendingQty;
            $netAvailable = max(0.0, $rawAvailable);

            if ($p->allow_overselling) {
                $maxAllowedQty = max(0.0, $rawAvailable + (float) ($p->overselling_qty ?: 999));
            } else {
                $maxAllowedQty = $netAvailable;
            }

            return [
                'id' => $p->id,
                'warehouse_stocks' => $p->stocks->map(fn($s) => [
                    'warehouse_id' => $s->warehouse_id,
                    'warehouse_name' => $s->warehouse?->name ?? 'Unknown',
                    'quantity' => $s->quantity,
                    'available' => max(0, $s->quantity - $s->reserved_qty)
                ])->values()->toArray(),
                'name' => $p->name,
                'sku' => $p->sku,
                'barcode' => $p->barcode,
                'selling_price' => (float) $p->selling_price,
                'purchase_price' => (float) $p->purchase_price,
                'mrp' => (float) $p->mrp,
                'image_url' => $p->image_path ? asset('storage/'.$p->image_path) : null,
                'stock_qty' => $totalQty,
                'reserved_qty' => $reservedQty,
                'pending_qty' => $pendingQty,
                'dispatched_qty' => $dispatchedQty,
                'available_stock' => $maxAllowedQty,
                'physical_available' => $netAvailable,
                'overselling_qty' => (int) ($p->overselling_qty ?? 0),
                'allow_overselling' => (bool) $p->allow_overselling,
                'status' => $p->status,
                'category' => $p->category?->name,
                'category_id' => $p->category_id,
                'brand' => $p->brand?->name,
                'tax_rate' => (float) ($p->taxRate?->rate ?? 0),
                'tax_label' => $p->taxRate?->name,
                'min_stock_level' => $p->min_stock_level ?? 0,
                'weight' => $p->weight,
                'is_sku_enabled' => (bool) $p->is_sku_enabled,
                'default_discount' => (float) ($p->default_discount ?? 0),
                'default_discount_type' => $p->default_discount_type ?? 'percent',
                'grade' => $p->grade,
                'uom' => $p->uom?->name,
                'uom_id' => $p->uom_id,
                'batch_tracking' => (bool) $p->batch_tracking,
                'expiry_tracking' => (bool) $p->expiry_tracking,
                'manage_stock' => (bool) $p->manage_stock,
            ];
        });

        return response()->json([
            'data' => $data->items(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ]);
    }

    public function duplicate(Request $request, Product $product): JsonResponse
    {
        abort_unless($request->user()?->can('product-create'), 403);

        $clone = $product->replicate([
            'sku',
            'slug',
            'created_at',
            'updated_at',
            'deleted_at',
        ]);
        $clone->name = $product->name.' (Copy)';
        $clone->sku = $this->generateUniqueSku($product->sku);
        $clone->slug = $this->generateUniqueSlug($clone->name);
        $clone->status = 'draft';
        $clone->is_active = false;
        $clone->save();
        $clone->attributeValues()->sync($product->attributeValues->pluck('id')->all());

        return response()->json([
            'message' => 'Product duplicated successfully.',
            'data' => $this->transform($clone->fresh(['category.parent', 'brand', 'taxRate', 'hsnCode', 'uom', 'warehouse', 'attributeValues.attribute', 'supplier'])
                ->loadSum('stocks as stocks_sum_quantity', 'quantity')
                ->loadSum('stocks as stocks_sum_reserved_qty', 'reserved_qty')
                ->loadSum('stocks as stocks_sum_dispatched_qty', 'dispatched_qty')
                ->loadSum('pendingOrderItems as pending_orders_qty', 'quantity')),
        ], 201);
    }

    public function import(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('product-import'), 403);

        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
            'import_mode' => ['nullable', 'in:overwrite,increment'],
        ]);

        $importMode = $data['import_mode'] ?? 'overwrite';

        $handle = fopen($data['file']->getRealPath(), 'r');
        if ($handle === false) {
            return response()->json(['message' => 'Unable to read import file.'], 422);
        }

        $errors = [];
        $imported = 0;
        $rowNum = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;
            if (empty($headers)) {
                $headers = array_map(
                    fn ($value) => Str::of((string) $value)->trim()->lower()->toString(),
                    $row,
                );

                continue;
            }

            if (count($row) !== count($headers)) {
                $errors[] = "Row {$rowNum}: Column count mismatch.";
                continue;
            }

            $record = array_combine($headers, $row);
            if (! is_array($record)) {
                $errors[] = "Row {$rowNum}: Invalid record format.";
                continue;
            }

            $payload = [
                'name' => trim((string) ($record['name'] ?? '')),
                'sku' => trim((string) ($record['sku'] ?? '')),
                'category_id' => $this->resolveCategoryId($record['category_id'] ?? $record['category'] ?? null),
                'brand_id' => $this->resolveId($record['brand_id'] ?? null),
                'uom_id' => $this->resolveId($record['uom_id'] ?? null),
                'tax_rate_id' => $this->resolveId($record['tax_rate_id'] ?? null),
                'hsn_code_id' => $this->resolveId($record['hsn_code_id'] ?? null),
                'default_warehouse_id' => $this->resolveId($record['default_warehouse_id'] ?? null),
                'barcode' => $this->nullableString($record['barcode'] ?? null),
                'weight' => $this->nullableString($record['weight'] ?? null),
                'purchase_price' => (float) ($record['purchase_price'] ?? 0),
                'mrp' => (float) ($record['mrp'] ?? 0),
                'selling_price' => (float) ($record['selling_price'] ?? $record['price'] ?? 0),
                'stock' => (int) ($record['stock'] ?? $record['stock_quantity'] ?? 0),
                'min_stock_level' => (int) ($record['min_stock_level'] ?? 0),
                'allow_overselling' => filter_var($record['allow_overselling'] ?? false, FILTER_VALIDATE_BOOL),
                'manage_stock' => filter_var($record['manage_stock'] ?? true, FILTER_VALIDATE_BOOL),
                'batch_tracking' => filter_var($record['batch_tracking'] ?? false, FILTER_VALIDATE_BOOL),
                'expiry_tracking' => filter_var($record['expiry_tracking'] ?? false, FILTER_VALIDATE_BOOL),
                'application_instructions' => $this->nullableString($record['application_instructions'] ?? null),
                'status' => (string) ($record['status'] ?? 'draft'),
                'description' => trim((string) ($record['description'] ?? '')),
                'overselling_qty' => (int) ($record['overselling_qty'] ?? 0),
                'default_discount' => (float) ($record['default_discount'] ?? 0),
                'default_discount_type' => (string) ($record['default_discount_type'] ?? 'percent'),
                'grade' => $this->nullableString($record['grade'] ?? null),
                'is_sku_enabled' => filter_var($record['is_sku_enabled'] ?? true, FILTER_VALIDATE_BOOL),
            ];

            if ($payload['name'] === '') {
                $errors[] = "Row {$rowNum}: Missing product name.";
                continue;
            }
            if ($payload['sku'] === '') {
                $errors[] = "Row {$rowNum}: Missing SKU for product '{$payload['name']}'.";
                continue;
            }
            if (! $payload['category_id']) {
                $errors[] = "Row {$rowNum}: Invalid or missing category for SKU '{$payload['sku']}'.";
                continue;
            }

            $product = Product::withTrashed()->firstOrNew(['sku' => $payload['sku']]);
            $this->fillProduct($product, $payload, null);
            $product->save();

            $this->syncStock($product, (int) $payload['stock'], $importMode);

            if ($product->trashed()) {
                $product->restore();
            }

            $imported++;
        }

        fclose($handle);

        return response()->json([
            'message' => "Imported {$imported} products successfully.",
            'imported' => $imported,
            'errors' => $errors,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        abort_unless($request->user()?->can('product-export') || $request->user()?->can('product-view'), 403);

        $filename = 'products-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'name',
                'sku',
                'category_id',
                'brand_id',
                'uom_id',
                'tax_rate_id',
                'hsn_code_id',
                'price',
                'purchase_price',
                'mrp',
                'stock',
                'min_stock_level',
                'default_discount',
                'default_discount_type',
                'status',
                'grade',
                'description',
                'batch_tracking',
                'expiry_tracking',
                'allow_overselling',
                'overselling_qty',
            ]);

            Product::query()
                ->with(['category', 'brand', 'uom', 'taxRate', 'hsnCode'])
                ->withSum('stocks as stocks_sum_quantity', 'quantity')
                ->latest()
                ->chunk(100, function ($products) use ($handle): void {
                    foreach ($products as $product) {
                        fputcsv($handle, [
                            $product->name,
                            $product->sku,
                            $product->category_id,
                            $product->brand_id,
                            $product->uom_id,
                            $product->tax_rate_id,
                            $product->hsn_code_id,
                            $product->selling_price,
                            $product->purchase_price,
                            $product->mrp,
                            $product->stocks_sum_quantity ?? 0,
                            $product->min_stock_level,
                            $product->default_discount,
                            $product->default_discount_type,
                            $product->status,
                            $product->grade,
                            $product->description,
                            $product->batch_tracking ? '1' : '0',
                            $product->expiry_tracking ? '1' : '0',
                            $product->allow_overselling ? '1' : '0',
                            $product->overselling_qty,
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function validatePayload(Request $request, ?Product $product = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:255', 'unique:products,sku'.($product ? ','.$product->id : '')],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'uom_id' => ['required', 'integer', 'exists:units_of_measure,id'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'default_warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'tax_rate_id' => ['required', 'integer', 'exists:tax_rates,id'],
            'hsn_code_id' => ['required', 'integer', 'exists:hsn_codes,id'],
            'barcode' => ['nullable', 'string', 'max:255'],
            'weight' => ['required', 'string', 'max:255'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'mrp' => ['nullable', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            // stock / stock_quantity are accepted interchangeably
            'stock' => ['nullable', 'integer', 'min:0'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'min_stock_level' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:active,draft,out_of_stock,published,pending'],
            'allow_overselling' => ['nullable', 'boolean'],
            'manage_stock' => ['nullable', 'boolean'],
            'batch_tracking' => ['nullable', 'boolean'],
            'expiry_tracking' => ['nullable', 'boolean'],
            'application_instructions' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:5120'],
            'attributes' => ['nullable', 'array'],
            'attributes.*' => ['integer', 'exists:product_attribute_values,id'],
            'overselling_qty' => ['nullable', 'integer', 'min:0'],
            'default_discount' => ['nullable', 'numeric', 'min:0'],
            'default_discount_type' => ['nullable', 'in:percent,flat'],
            'grade' => ['nullable', 'in:A,B,C,D'],
            'weight_g' => ['nullable', 'numeric', 'min:0'],
            'length_cm' => ['nullable', 'numeric', 'min:0'],
            'width_cm' => ['nullable', 'numeric', 'min:0'],
            'height_cm' => ['nullable', 'numeric', 'min:0'],
        ]);

        $validated['attributes'] = $validated['attributes'] ?? null;

        return $validated;
    }

    private function fillProduct(Product $product, array $data, ?Request $request): void
    {
        $product->name = trim((string) $data['name']);
        $product->sku = trim((string) $data['sku']);
        $product->slug = $this->generateUniqueSlug($product->name, $product->exists ? $product->id : null);
        $product->category_id = (int) $data['category_id'];
        $product->brand_id = $this->resolveId($data['brand_id'] ?? null);
        $product->uom_id = $this->resolveId($data['uom_id'] ?? null);
        $product->supplier_id = $this->resolveId($data['supplier_id'] ?? null);
        $product->default_warehouse_id = $this->resolveId($data['default_warehouse_id'] ?? null);
        $product->tax_rate_id = $this->resolveId($data['tax_rate_id'] ?? null);
        $product->hsn_code_id = $this->resolveId($data['hsn_code_id'] ?? null);
        $product->barcode = $this->nullableString($data['barcode'] ?? null);
        $product->weight = $this->nullableString($data['weight'] ?? null);
        $product->weight_g = isset($data['weight_g']) ? (float) $data['weight_g'] : null;
        $product->length_cm = isset($data['length_cm']) ? (float) $data['length_cm'] : null;
        $product->width_cm = isset($data['width_cm']) ? (float) $data['width_cm'] : null;
        $product->height_cm = isset($data['height_cm']) ? (float) $data['height_cm'] : null;
        $product->purchase_price = (float) $data['purchase_price'];
        $product->mrp = isset($data['mrp']) && $data['mrp'] !== '' ? (float) $data['mrp'] : (float) $data['selling_price'];
        $product->selling_price = (float) $data['selling_price'];
        $product->min_stock_level = (int) ($data['min_stock_level'] ?? 0);
        $product->batch_tracking = filter_var($data['batch_tracking'] ?? false, FILTER_VALIDATE_BOOL);
        $product->expiry_tracking = filter_var($data['expiry_tracking'] ?? false, FILTER_VALIDATE_BOOL);
        $product->allow_overselling = filter_var($data['allow_overselling'] ?? false, FILTER_VALIDATE_BOOL);
        $product->overselling_qty = (int) ($data['overselling_qty'] ?? 0);
        $product->manage_stock = filter_var($data['manage_stock'] ?? true, FILTER_VALIDATE_BOOL);
        $product->is_sku_enabled = filter_var($data['is_sku_enabled'] ?? true, FILTER_VALIDATE_BOOL);
        $product->application_instructions = $this->nullableString($data['application_instructions'] ?? null);
        $product->status = $this->normalizeStatus((string) $data['status']);
        $product->is_active = in_array($product->status, ['published', 'active'], true);
        $product->description = $this->nullableString($data['description'] ?? null);
        $product->default_discount = (float) ($data['default_discount'] ?? 0);
        $product->default_discount_type = (string) ($data['default_discount_type'] ?? 'percent');
        $product->grade = $this->nullableString($data['grade'] ?? null);

        if ($request?->hasFile('image')) {
            $this->deleteImage($product);

            $file = $request->file('image');
            $extension = $file->extension() ?: 'jpg';
            $filename = Str::slug($product->sku).'-'.time().'.'.$extension;

            $product->image_path = $file->storeAs('products', $filename, 'public');
        }
    }

    private function transform(Product $product): array
    {
        $totalQty = (float) (array_key_exists('stocks_sum_quantity', $product->getAttributes()) ? $product->stocks_sum_quantity : $product->stock_quantity);
        $reservedQty = (float) ($product->stocks_sum_reserved_qty ?? 0);
        $dispatchedQty = (float) ($product->stocks_sum_dispatched_qty ?? 0);
        $pendingQty = (float) ($product->pending_orders_qty ?? 0);

        $rawAvailable = $totalQty - $reservedQty - $pendingQty;
        $netAvailable = max(0.0, $rawAvailable);

        if ($product->allow_overselling) {
            $maxAllowedQty = max(0.0, $rawAvailable + (float) ($product->overselling_qty ?: 999));
        } else {
            $maxAllowedQty = $netAvailable;
        }

        return [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'category' => $product->category?->slug ?? '',
            'category_id' => $product->category_id,
            'category_label' => $product->category?->name ?? 'Uncategorized',
            'category_data' => $product->category,
            'brand_id' => $product->brand_id,
            'brand' => $product->brand?->name,
            'brand_data' => $product->brand,
            'uom_id' => $product->uom_id,
            'uom' => $product->uom?->name,
            'uom_data' => $product->uom,
            'supplier_id' => $product->supplier_id,
            'supplier' => trim($product->supplier?->company_name ?: ($product->supplier?->firstname . ' ' . $product->supplier?->lastname)),
            'supplier_data' => $product->supplier,
            'tax_rate_id' => $product->tax_rate_id,
            'tax_rate' => $product->taxRate?->rate,
            'tax_label' => $product->taxRate?->name,
            'tax_rate_data' => $product->taxRate,
            'hsn_code_id' => $product->hsn_code_id,
            'hsn_code' => $product->hsnCode?->code,
            'hsn_code_data' => $product->hsnCode,
            'warehouse_id' => $product->default_warehouse_id,
            'warehouse' => $product->warehouse?->name,
            'warehouse_data' => $product->warehouse,
            'barcode' => $product->barcode,
            'weight' => $product->weight,
            'weight_g' => $product->weight_g,
            'length_cm' => $product->length_cm,
            'width_cm' => $product->width_cm,
            'height_cm' => $product->height_cm,
            'price' => (float) $product->selling_price,
            'selling_price' => (float) $product->selling_price,
            'purchase_price' => (float) $product->purchase_price,
            'mrp' => (float) $product->mrp,
            'stock' => (int) $totalQty,
            'stock_quantity' => (int) $totalQty,
            'stock_qty' => $totalQty,
            'reserved_qty' => $reservedQty,
            'pending_qty' => $pendingQty,
            'dispatched_qty' => $dispatchedQty,
            'available_stock' => $maxAllowedQty,
            'physical_available' => $netAvailable,
            'min_stock_level' => (int) $product->min_stock_level,
            'allow_overselling' => (bool) $product->allow_overselling,
            'manage_stock' => (bool) $product->manage_stock,
            'batch_tracking' => (bool) $product->batch_tracking,
            'expiry_tracking' => (bool) $product->expiry_tracking,
            'application_instructions' => $product->application_instructions,
            'overselling_qty' => (int) $product->overselling_qty,
            'default_discount' => (float) $product->default_discount,
            'default_discount_type' => $product->default_discount_type,
            'status' => $product->status,
            'created' => optional($product->created_at)->toDateString(),
            'image' => $product->image_url ?? asset('assets/images/product-placeholder.svg'),
            'description' => $product->description,
            'deleted_at' => optional($product->deleted_at)?->toDateTimeString(),
            'is_sku_enabled' => (bool) $product->is_sku_enabled,
            'grade' => $product->grade,
            'attributes' => $product->attributeValues->map(fn (ProductAttributeValue $value) => [
                'id' => $value->id,
                'attribute_id' => $value->product_attribute_id,
                'attribute' => $value->attribute?->name,
                'value' => $value->value,
                'color_code' => $value->color_code,
            ])->values(),
        ];
    }

    private function stats(iterable $products): array
    {
        $products = collect($products);

        return [
            'total' => $products->count(),
            'active' => $products->whereIn('status', ['published', 'active'])->count(),
            'inStock' => $products->where('stock', '>', 20)->count(),
            'lowStock' => $products->where('stock', '>', 0)->where('stock', '<=', 20)->count(),
            'outOfStock' => $products->where('stock', '<=', 0)->count(),
            'totalValue' => round($products->sum(fn (array $product) => (float) $product['price'] * (int) $product['stock']), 2),
        ];
    }

    private function catalogOptions(): array
    {
        return \Illuminate\Support\Facades\Cache::remember('catalog_options', 3600, function () {
            return [
                'categories' => Category::query()
                    ->whereNull('parent_id')
                    ->with('children')
                    ->orderBy('name')
                    ->get()
                    ->map(fn (Category $category) => [
                        'id' => $category->id,
                        'name' => $category->name,
                        'slug' => $category->slug,
                        'children' => $category->children->map(fn (Category $child) => [
                            'id' => $child->id,
                            'name' => $child->name,
                            'slug' => $child->slug,
                        ])->values(),
                    ])->values(),
                'brands' => Brand::query()->orderBy('name')->get(['id', 'name'])->values(),
                'suppliers' => \App\Modules\Inventory\Models\Supplier::query()->orderBy('company_name')->get(['id', 'company_name', 'firstname', 'lastname'])->values(),
                'uoms' => UnitOfMeasure::query()->where('status', 'active')->orderBy('name')->get(['id', 'name', 'short_name'])->values(),
                'taxRates' => TaxRate::query()->where('status', 'active')->orderBy('rate')->get(['id', 'name', 'rate'])->values(),
                'hsnCodes' => HsnCode::query()->where('status', 'active')->orderBy('code')->get(['id', 'code', 'description'])->values(),
                'warehouses' => Warehouse::query()->orderBy('name')->get(['id', 'name'])->values(),
                'attributes' => ProductAttribute::query()
                    ->where('status', 'active')
                    ->with(['values' => fn ($query) => $query->where('status', 'active')->orderBy('value')])
                    ->orderBy('name')
                    ->get()
                    ->map(fn (ProductAttribute $attribute) => [
                        'id' => $attribute->id,
                        'name' => $attribute->name,
                        'type' => $attribute->type,
                        'values' => $attribute->values->map(fn (ProductAttributeValue $value) => [
                            'id' => $value->id,
                            'value' => $value->value,
                            'color_code' => $value->color_code,
                        ])->values(),
                    ])->values(),
                'statusList' => [
                    ['value' => 'active', 'label' => 'Active'],
                    ['value' => 'published', 'label' => 'Published'],
                    ['value' => 'pending', 'label' => 'Pending'],
                    ['value' => 'draft', 'label' => 'Draft'],
                    ['value' => 'out_of_stock', 'label' => 'Out of Stock'],
                ],
            ];
        });
    }

    private function syncAttributes(Product $product, ?array $attributeIds): void
    {
        if ($attributeIds === null) {
            return;
        }

        $product->attributeValues()->sync(array_values(array_filter(array_map('intval', $attributeIds))));
    }

    /**
     * Helper to insert or update the primary stock record when a product is saved.
     * This ensures the InventoryService logic doesn't fail when no stock is registered.
     */
    private function syncStock(Product $product, int $qty, string $importMode = 'overwrite'): void
    {
        // Determine the warehouse to assign stock to
        $warehouseId = $product->default_warehouse_id;

        // If no warehouse is assigned, try to use the first available warehouse
        if (! $warehouseId) {
            $warehouseId = Warehouse::query()->value('id');
            if ($warehouseId) {
                $product->default_warehouse_id = $warehouseId;
                $product->saveQuietly();
            }
        }

        if (! $warehouseId) {
            // No warehouse exists — skip stock table entry
            return;
        }

        $stock = Stock::withTrashed()->firstOrNew([
            'product_id' => $product->id, 
            'warehouse_id' => $warehouseId
        ]);

        if ($importMode === 'increment') {
            $stock->quantity = ($stock->quantity ?? 0) + $qty;
        } else {
            $stock->quantity = $qty;
        }

        $stock->reserved_qty = $stock->reserved_qty ?? 0;
        $stock->dispatched_qty = $stock->dispatched_qty ?? 0;
        $stock->committed_qty = $stock->committed_qty ?? 0;
        $stock->in_transit_qty = $stock->in_transit_qty ?? 0;
        $stock->status = 'active';
        $stock->deleted_at = null;
        $stock->save();
    }

    private function resolveCategoryId(mixed $value): ?int
    {
        if ($value === null || trim((string) $value) === '') {
            $value = 'Uncategorized';
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        $name = trim((string) $value);
        $slug = Str::slug($name);

        $category = Category::query()->where('slug', $slug)->first();
        if (! $category) {
            $category = Category::create([
                'name' => $name,
                'slug' => $slug,
            ]);
        }

        return $category->id;
    }

    private function resolveId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function normalizeStatus(string $status): string
    {
        $status = Str::of($status)->lower()->trim()->toString();

        return match ($status) {
            'published' => 'published',
            'active' => 'active',
            'out_of_stock' => 'out_of_stock',
            'pending', 'review' => 'pending',
            default => 'draft',
        };
    }

    private function generateUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 1;

        while (
            Product::query()
                ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $base.'-'.++$counter;
        }

        return $slug;
    }

    private function generateUniqueSku(string $sku): string
    {
        $base = Str::upper(Str::slug($sku));
        $candidate = $base.'-COPY';
        $counter = 1;

        while (Product::where('sku', $candidate)->exists()) {
            $candidate = $base.'-COPY-'.$counter++;
        }

        return $candidate;
    }

    private function deleteImage(Product $product): void
    {
        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function extractIds(mixed $value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map('intval', $value), static fn (int $id): bool => $id > 0));
    }
}
