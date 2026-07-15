<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Controllers;

use App\Modules\Core\Controllers\Controller;
use App\Modules\Catalog\Models\Brand;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\HsnCode;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductAttribute;
use App\Modules\Catalog\Models\ProductAttributeValue;
use App\Modules\Catalog\Models\TaxRate;
use App\Modules\Catalog\Models\UnitOfMeasure;
use App\Modules\Catalog\Models\Warehouse;
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
            ->with(['category.parent', 'brand', 'taxRate', 'hsnCode', 'uom', 'warehouse', 'attributeValues.attribute'])
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

        $product->load(['category.parent', 'brand', 'taxRate', 'hsnCode', 'uom', 'warehouse', 'attributeValues.attribute']);

        return response()->json([
            'data' => $this->transform($product),
            'options' => $this->catalogOptions(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('product-create'), 403);

        $data = $this->validatePayload($request);

        $product = new Product();
        $this->fillProduct($product, $data, $request);
        $product->save();
        $this->syncAttributes($product, $data['attributes'] ?? []);

        return response()->json([
            'message' => 'Product created successfully.',
            'data' => $this->transform($product->fresh(['category.parent', 'brand', 'taxRate', 'hsnCode', 'uom', 'warehouse', 'attributeValues.attribute'])),
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

        return response()->json([
            'message' => 'Product updated successfully.',
            'data' => $this->transform($product->fresh(['category.parent', 'brand', 'taxRate', 'hsnCode', 'uom', 'warehouse', 'attributeValues.attribute'])),
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

    public function restore(Request $request, string $product): JsonResponse
    {
        abort_unless($request->user()?->can('product-restore'), 403);

        $product = Product::withTrashed()->findOrFail($product);
        $product->restore();

        return response()->json([
            'message' => 'Product restored successfully.',
            'data' => $this->transform($product->fresh(['category.parent', 'brand', 'taxRate', 'hsnCode', 'uom', 'warehouse', 'attributeValues.attribute'])),
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
            ->with(['category', 'brand', 'taxRate'])
            ->withSum('stocks as stocks_sum_quantity', 'quantity')
            ->withSum('stocks as stocks_sum_reserved_qty', 'reserved_qty')
            ->withSum('stocks as stocks_sum_dispatched_qty', 'dispatched_qty');

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

        $perPage = (int) $request->input('perPage', 12);
        $paginator = $query->latest()->paginate($perPage);

        $data = $paginator->through(function (Product $p) {
            $totalQty      = (float) ($p->stocks_sum_quantity ?? 0);
            $reservedQty   = (float) ($p->stocks_sum_reserved_qty ?? 0);
            $dispatchedQty = (float) ($p->stocks_sum_dispatched_qty ?? 0);
            $pendingQty    = (float) ($p->pending_orders_qty ?? 0);

            $rawAvailable = $totalQty - $reservedQty - $pendingQty;
            $netAvailable = max(0.0, $rawAvailable);

            if ($p->allow_overselling) {
                $maxAllowedQty = max(0.0, $rawAvailable + (float) ($p->overselling_qty ?: 999));
            } else {
                $maxAllowedQty = $netAvailable;
            }

            return [
                'id'                   => $p->id,
                'name'                 => $p->name,
                'sku'                  => $p->sku,
                'barcode'              => $p->barcode,
                'selling_price'        => (float) $p->selling_price,
                'purchase_price'       => (float) $p->purchase_price,
                'mrp'                  => (float) $p->mrp,
                'image_url'            => $p->image_path ? asset('storage/' . $p->image_path) : null,
                'stock_qty'            => $totalQty,
                'reserved_qty'         => $reservedQty,
                'pending_qty'          => $pendingQty,
                'dispatched_qty'       => $dispatchedQty,
                'available_stock'      => $maxAllowedQty,
                'physical_available'   => $netAvailable,
                'overselling_qty'      => (int) ($p->overselling_qty ?? 0),
                'allow_overselling'    => (bool) $p->allow_overselling,
                'status'               => $p->status,
                'category'             => $p->category?->name,
                'category_id'          => $p->category_id,
                'brand'                => $p->brand?->name,
                'tax_rate'             => (float) ($p->taxRate?->rate ?? 0),
                'tax_label'            => $p->taxRate?->name,
                'min_stock_level'      => $p->min_stock_level ?? 0,
                'weight'               => $p->weight,
                'is_sku_enabled'       => (bool) $p->is_sku_enabled,
                'default_discount'     => (float) ($p->default_discount ?? 0),
                'default_discount_type' => $p->default_discount_type ?? 'percent',
            ];
        });

        return response()->json([
            'data'         => $data->items(),
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
            'per_page'     => $paginator->perPage(),
            'total'        => $paginator->total(),
            'from'         => $paginator->firstItem(),
            'to'           => $paginator->lastItem(),
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
        $clone->name = $product->name . ' (Copy)';
        $clone->sku = $this->generateUniqueSku($product->sku);
        $clone->slug = $this->generateUniqueSlug($clone->name);
        $clone->status = 'draft';
        $clone->is_active = false;
        $clone->save();
        $clone->attributeValues()->sync($product->attributeValues->pluck('id')->all());

        return response()->json([
            'message' => 'Product duplicated successfully.',
            'data' => $this->transform($clone->fresh(['category.parent', 'brand', 'taxRate', 'hsnCode', 'uom', 'warehouse', 'attributeValues.attribute'])),
        ], 201);
    }

    public function import(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('product-import'), 403);

        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        $handle = fopen($data['file']->getRealPath(), 'r');
        if ($handle === false) {
            return response()->json(['message' => 'Unable to read import file.'], 422);
        }

        $headers = [];
        $imported = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (empty($headers)) {
                $headers = array_map(
                    fn ($value) => Str::of((string) $value)->trim()->lower()->toString(),
                    $row,
                );
                continue;
            }

            $record = array_combine($headers, $row);
            if (! is_array($record)) {
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

            if ($payload['name'] === '' || $payload['sku'] === '' || ! $payload['category_id']) {
                continue;
            }

            $product = Product::withTrashed()->firstOrNew(['sku' => $payload['sku']]);
            $this->fillProduct($product, $payload, null);
            $product->save();

            if ($product->trashed()) {
                $product->restore();
            }

            $imported++;
        }

        fclose($handle);

        return response()->json([
            'message' => "Imported {$imported} products successfully.",
            'imported' => $imported,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        abort_unless($request->user()?->can('product-export') || $request->user()?->can('product-view'), 403);

        $filename = 'products-' . now()->format('Y-m-d-His') . '.csv';

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
            ]);

            Product::query()
                ->with(['category', 'brand', 'uom', 'taxRate', 'hsnCode'])
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
                            $product->stock_quantity,
                            $product->min_stock_level,
                            $product->default_discount,
                            $product->default_discount_type,
                            $product->status,
                            $product->grade,
                            $product->description,
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
            'sku' => ['required', 'string', 'max:255', 'unique:products,sku' . ($product ? ',' . $product->id : '')],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'uom_id' => ['nullable', 'integer', 'exists:units_of_measure,id'],
            'default_warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'tax_rate_id' => ['nullable', 'integer', 'exists:tax_rates,id'],
            'hsn_code_id' => ['nullable', 'integer', 'exists:hsn_codes,id'],
            'barcode' => ['nullable', 'string', 'max:255'],
            'weight' => ['nullable', 'string', 'max:255'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'mrp' => ['nullable', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'min_stock_level' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:active,draft,out_of_stock,published,pending'],
            'allow_overselling' => ['nullable', 'boolean'],
            'manage_stock' => ['nullable', 'boolean'],
            'batch_tracking' => ['nullable', 'boolean'],
            'expiry_tracking' => ['nullable', 'boolean'],
            'application_instructions' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:4096'],
            'attributes' => ['nullable', 'array'],
            'attributes.*' => ['integer', 'exists:product_attribute_values,id'],
            'overselling_qty' => ['nullable', 'integer', 'min:0'],
            'default_discount' => ['nullable', 'numeric', 'min:0'],
            'default_discount_type' => ['nullable', 'in:percent,flat'],
            'grade' => ['nullable', 'in:A,B,C,D'],
            'is_sku_enabled' => ['nullable', 'boolean'],
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
        $product->default_warehouse_id = $this->resolveId($data['default_warehouse_id'] ?? null);
        $product->tax_rate_id = $this->resolveId($data['tax_rate_id'] ?? null);
        $product->hsn_code_id = $this->resolveId($data['hsn_code_id'] ?? null);
        $product->barcode = $this->nullableString($data['barcode'] ?? null);
        $product->weight = $this->nullableString($data['weight'] ?? null);
        $product->purchase_price = (float) $data['purchase_price'];
        $product->mrp = isset($data['mrp']) && $data['mrp'] !== '' ? (float) $data['mrp'] : (float) $data['selling_price'];
        $product->selling_price = (float) $data['selling_price'];
        $product->stock_quantity = (int) ($data['stock_quantity'] ?? $data['stock'] ?? 0);
        $product->min_stock_level = (int) ($data['min_stock_level'] ?? 0);
        $product->batch_tracking = (bool) ($data['batch_tracking'] ?? false);
        $product->expiry_tracking = (bool) ($data['expiry_tracking'] ?? false);
        $product->allow_overselling = (bool) ($data['allow_overselling'] ?? false);
        $product->overselling_qty = (int) ($data['overselling_qty'] ?? 0);
        $product->manage_stock = (bool) ($data['manage_stock'] ?? true);
        $product->is_sku_enabled = (bool) ($data['is_sku_enabled'] ?? true);
        $product->application_instructions = $this->nullableString($data['application_instructions'] ?? null);
        $product->status = $this->normalizeStatus((string) $data['status']);
        $product->is_active = in_array($product->status, ['published', 'active'], true);
        $product->description = $this->nullableString($data['description'] ?? null);
        $product->default_discount = (float) ($data['default_discount'] ?? 0);
        $product->default_discount_type = (string) ($data['default_discount_type'] ?? 'percent');
        $product->grade = $this->nullableString($data['grade'] ?? null);

        if ($request?->hasFile('image')) {
            $this->deleteImage($product);
            $product->image_path = $request->file('image')->store('products', 'public');
        }
    }

    private function transform(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'category' => $product->category?->slug ?? '',
            'category_id' => $product->category_id,
            'category_label' => $product->category?->name ?? 'Uncategorized',
            'brand_id' => $product->brand_id,
            'brand' => $product->brand?->name,
            'uom_id' => $product->uom_id,
            'uom' => $product->uom?->name,
            'tax_rate_id' => $product->tax_rate_id,
            'tax_rate' => $product->taxRate?->rate,
            'tax_label' => $product->taxRate?->name,
            'hsn_code_id' => $product->hsn_code_id,
            'hsn_code' => $product->hsnCode?->code,
            'warehouse_id' => $product->default_warehouse_id,
            'warehouse' => $product->warehouse?->name,
            'barcode' => $product->barcode,
            'weight' => $product->weight,
            'price' => (float) $product->selling_price,
            'selling_price' => (float) $product->selling_price,
            'purchase_price' => (float) $product->purchase_price,
            'mrp' => (float) $product->mrp,
            'stock' => (int) $product->stock_quantity,
            'stock_quantity' => (int) $product->stock_quantity,
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
                ['value' => 'draft', 'label' => 'Draft'],
                ['value' => 'out_of_stock', 'label' => 'Out of Stock'],
                ['value' => 'published', 'label' => 'Published'],
                ['value' => 'pending', 'label' => 'Pending'],
            ],
        ];
    }

    private function syncAttributes(Product $product, ?array $attributeIds): void
    {
        if ($attributeIds === null) {
            return;
        }

        $product->attributeValues()->sync(array_values(array_filter(array_map('intval', $attributeIds))));
    }

    private function resolveCategoryId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        $slug = Str::slug((string) $value);
        $category = Category::query()->where('slug', $slug)->first();

        return $category?->id;
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
            'active', 'published' => 'published',
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
            $slug = $base . '-' . ++$counter;
        }

        return $slug;
    }

    private function generateUniqueSku(string $sku): string
    {
        $base = Str::upper(Str::slug($sku));
        $candidate = $base . '-COPY';
        $counter = 1;

        while (Product::where('sku', $candidate)->exists()) {
            $candidate = $base . '-COPY-' . $counter++;
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
