<?php

declare(strict_types=1);

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function index(Request $request): View|\Illuminate\Http\JsonResponse
    {
        $search = trim((string) $request->string('search'));
        $perPage = (int) $request->integer('perPage', 12) ?: 12;

        $query = Brand::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->orderBy('name');

        $brands = $query->paginate($perPage)->withQueryString();
        $stats = $this->stats();

        if ($request->expectsJson()) {
            return response()->json([
                'table' => view('brands.partials.table', compact('brands'))->render(),
                'stats' => $stats,
            ]);
        }

        return view('brands.index', compact('brands', 'stats'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateRequest($request);

        $brand = new Brand();
        $brand->fill([
            'name' => $data['name'],
            'status' => $data['status'],
            'slug' => Str::slug($data['name']) ?: Str::slug($data['name'] . '-' . Str::random(6)),
        ]);

        if ($request->hasFile('image')) {
            $brand->image = $request->file('image')->store('brands', 'public');
        }

        $brand->save();

        return back()->with('success', 'Brand created successfully.');
    }

    public function update(Request $request, Brand $brand): RedirectResponse
    {
        $data = $this->validateRequest($request, $brand->id);

        $brand->fill([
            'name' => $data['name'],
            'status' => $data['status'],
            'slug' => Str::slug($data['name']) ?: Str::slug($data['name'] . '-' . $brand->id),
        ]);

        if ($request->hasFile('image')) {
            if ($brand->image) {
                Storage::disk('public')->delete($brand->image);
            }

            $brand->image = $request->file('image')->store('brands', 'public');
        }

        $brand->save();

        return back()->with('success', 'Brand updated successfully.');
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        if ($brand->image) {
            Storage::disk('public')->delete($brand->image);
        }

        $brand->delete();

        return back()->with('success', 'Brand deleted successfully.');
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $ids = collect(json_decode((string) $request->input('ids', '[]'), true))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($ids->isEmpty()) {
            return back()->with('error', 'No brands selected.');
        }

        Brand::query()
            ->whereIn('id', $ids)
            ->get()
            ->each(function (Brand $brand): void {
                if ($brand->image) {
                    Storage::disk('public')->delete($brand->image);
                }
                $brand->delete();
            });

        return back()->with('success', 'Selected brands deleted.');
    }

    private function validateRequest(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:active,inactive'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);
    }

    private function stats(): array
    {
        $brands = Brand::query()->withTrashed()->get();

        return [
            'total' => $brands->count(),
            'active' => $brands->whereNull('deleted_at')->where('status', 'active')->count(),
            'inactive' => $brands->whereNull('deleted_at')->where('status', 'inactive')->count(),
        ];
    }
}
