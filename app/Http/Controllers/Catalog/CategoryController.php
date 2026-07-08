<?php

declare(strict_types=1);

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View|\Illuminate\Http\JsonResponse
    {
        $search = trim((string) $request->string('search'));
        $perPage = (int) $request->integer('perPage', 10) ?: 10;

        $query = Category::query()
            ->with('parent')
            ->withCount('products')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->orderBy('parent_id')
            ->orderBy('name');

        $categories = $query->paginate($perPage)->withQueryString();
        $stats = $this->stats();
        $parentCategories = Category::query()
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get(['id', 'name']);

        if ($request->expectsJson()) {
            return response()->json([
                'table' => view('categories.partials.table', [
                    'categories' => $categories,
                ])->render(),
                'stats' => $stats,
            ]);
        }

        return view('categories.index', compact('categories', 'stats', 'parentCategories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateRequest($request);

        $category = new Category();
        $category->fill([
            'name' => $data['name'],
            'parent_id' => $this->normalizeParentId($data['parent_id'] ?? null),
            'status' => $data['status'],
            'slug' => Str::slug($data['name']) ?: Str::slug($data['name'] . '-' . Str::random(6)),
        ]);

        if ($request->hasFile('image')) {
            $category->image = $request->file('image')->store('categories', 'public');
        }

        $category->save();

        return back()->with('success', 'Category created successfully.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $this->validateRequest($request, $category->id);

        $category->fill([
            'name' => $data['name'],
            'parent_id' => $this->normalizeParentId($data['parent_id'] ?? null, $category->id),
            'status' => $data['status'],
            'slug' => Str::slug($data['name']) ?: Str::slug($data['name'] . '-' . $category->id),
        ]);

        if ($request->hasFile('image')) {
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }

            $category->image = $request->file('image')->store('categories', 'public');
        }

        $category->save();

        return back()->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->image) {
            Storage::disk('public')->delete($category->image);
        }

        $category->delete();

        return back()->with('success', 'Category deleted successfully.');
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $ids = collect(json_decode((string) $request->input('ids', '[]'), true))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($ids->isEmpty()) {
            return back()->with('error', 'No categories selected.');
        }

        Category::query()
            ->whereIn('id', $ids)
            ->get()
            ->each(function (Category $category): void {
                if ($category->image) {
                    Storage::disk('public')->delete($category->image);
                }
                $category->delete();
            });

        return back()->with('success', 'Selected categories deleted.');
    }

    private function validateRequest(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'status' => ['required', 'in:active,inactive'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);
    }

    private function normalizeParentId(mixed $parentId, ?int $currentId = null): ?int
    {
        $parentId = (int) $parentId;

        if ($parentId <= 0 || ($currentId !== null && $parentId === $currentId)) {
            return null;
        }

        return $parentId;
    }

    private function stats(): array
    {
        $categories = Category::query()->withTrashed()->get();

        return [
            'total' => $categories->count(),
            'active' => $categories->whereNull('deleted_at')->where('status', 'active')->count(),
            'newThisMonth' => $categories->whereNull('deleted_at')->where('created_at', '>=', now()->startOfMonth())->count(),
            'parentCategories' => $categories->whereNull('deleted_at')->whereNull('parent_id')->count(),
        ];
    }
}
