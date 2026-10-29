@php($navigationCategories = $categories ?? App\Modules\Catalog\Models\Category::where('is_active', true)->orderBy('name')->get(['id', 'name']))
<nav class="category-bar" aria-label="Product categories">
    <a class="{{ request()->routeIs('shop.index') && !request('category') ? 'selected' : '' }}" href="{{ route('shop.index') }}" @if(request()->routeIs('shop.index') && !request('category')) aria-current="page" @endif>All products</a>
    @foreach($navigationCategories as $category)
    <a class="{{ request()->routeIs('shop.index') && request('category') == $category->id ? 'selected' : '' }}" href="{{ route('shop.index', ['category' => $category->id]) }}" @if(request()->routeIs('shop.index') && request('category') == $category->id) aria-current="page" @endif>{{ $category->name }}</a>
    @endforeach
</nav>
