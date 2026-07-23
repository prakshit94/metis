{{-- ══ TAB: Order Products ══ --}}
<div x-show="activeTab === 'order'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-cloak>
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">

        {{-- Table Header: Title + Filters + Search --}}
        <div class="card-header bg-light border-bottom p-4">

            {{-- Row 1: Title + Cart Badge --}}
            <div class="d-flex align-items-center justify-content-between gap-4 mb-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="bi bi-box-seam fs-5"></i>
                    </div>
                    <div>
                        <h3 class="h5 fw-bold mb-0 text-dark">Available Products</h3>
                        <p class="mb-0 text-muted fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 1px;">
                            <span x-text="productTotal"></span> products &nbsp;·&nbsp; Showing <span x-text="productFrom"></span>–<span x-text="productTo"></span>
                        </p>
                    </div>
                </div>
                <button type="button" @click="activeTab = 'review'"
                    class="btn btn-outline-success d-flex align-items-center gap-2 position-relative rounded-pill px-4 fw-bold text-uppercase shadow-sm" style="font-size: 11px; letter-spacing: 1px;">
                    <i class="bi bi-cart3 fs-6"></i>
                    <span>Review Cart</span>
                    <span x-show="cart.length > 0" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-success" x-text="cart.length" x-cloak></span>
                </button>
            </div>

            {{-- Row 2: Filters --}}
            <div class="d-flex flex-wrap align-items-center gap-2">

                {{-- Per Page --}}
                <select x-model="productPerPage" @change="searchProducts(true)"
                    class="form-select form-select-sm fw-bold w-auto shadow-sm" style="font-size: 11px;">
                    <option value="10">10 / page</option>
                    <option value="15" selected>15 / page</option>
                    <option value="25">25 / page</option>
                    <option value="50">50 / page</option>
                </select>

                {{-- Stock Filter Tabs --}}
                <div class="btn-group shadow-sm" role="group">
                    <template x-for="opt in [{v:'available',l:'In Stock'},{v:'out_of_stock',l:'Out of Stock'},{v:'',l:'All'}]" :key="opt.v">
                        <button type="button" @click="productStockFilter = opt.v; searchProducts(true)"
                            :class="productStockFilter === opt.v ? 'btn btn-sm btn-primary fw-bold text-uppercase' : 'btn btn-sm btn-light fw-bold text-uppercase text-muted'"
                            style="font-size: 10px; letter-spacing: 1px;"
                            x-text="opt.l">
                        </button>
                    </template>
                </div>

                {{-- Category Filter --}}
                <select x-model="productCategoryFilter" @change="searchProducts(true)"
                    class="form-select form-select-sm fw-bold w-auto shadow-sm" style="font-size: 11px; min-width: 140px;">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>

                {{-- Search --}}
                <div class="input-group input-group-sm shadow-sm" style="max-width: 250px;">
                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" x-model="productSearchQuery"
                        @input.debounce.400ms="searchProducts(true)"
                        placeholder="Search by name, SKU..."
                        class="form-control border-start-0 ps-0 fw-semibold" style="font-size: 12px;">
                    <span x-show="searchingProducts" class="input-group-text bg-white border-start-0 text-primary" x-cloak>
                        <span class="spinner-border spinner-border-sm" role="status"></span>
                    </span>
                </div>

                {{-- Clear button --}}
                <button type="button" x-show="productSearchQuery || productStockFilter !== 'available'" x-cloak
                    @click="productSearchQuery = ''; productStockFilter = 'available'; productCategoryFilter = ''; searchProducts(true)"
                    class="btn btn-sm btn-link text-danger fw-bold text-uppercase text-decoration-none" style="font-size: 10px; letter-spacing: 1px;">
                    Clear
                </button>
            </div>
        </div>

        {{-- Table --}}
        <div class="table-responsive" style="max-height:520px;">
            <table class="table table-striped table-hover align-middle mb-0" style="font-size: 13px;">
                <thead class="table-light sticky-top" style="z-index: 10;">
                    <tr>
                        <th class="py-3 text-muted fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 1px; min-width: 250px;">Product</th>
                        <th class="py-3 text-muted fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 1px;">SKU / Category</th>
                        <th class="py-3 text-muted fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 1px;">Pricing</th>
                        <th class="py-3 text-muted fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 1px;">Stock</th>
                        <th class="py-3 text-muted fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 1px; width: 140px;">Qty & Disc</th>
                        <th class="py-3 text-muted fw-bold text-uppercase text-end" style="font-size: 10px; letter-spacing: 1px; width: 120px;">Action</th>
                    </tr>
                </thead>
                <tbody class="border-top-0">

                    {{-- Loading state --}}
                    <template x-if="searchingProducts">
                        <tr>
                            <td colspan="6" class="py-5 text-center text-muted">
                                <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;"></div>
                                <p class="fw-bold text-uppercase mb-0" style="font-size: 12px; letter-spacing: 1px;">Loading products...</p>
                            </td>
                        </tr>
                    </template>

                    {{-- Empty state --}}
                    <template x-if="!searchingProducts && productSearchResults.length === 0">
                        <tr>
                            <td colspan="6" class="py-5 text-center text-muted">
                                <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 64px; height: 64px;">
                                    <i class="bi bi-box-seam text-secondary fs-2"></i>
                                </div>
                                <p class="fw-bold text-uppercase text-dark mb-1" style="font-size: 12px; letter-spacing: 1px;">No products found</p>
                                <p class="small mb-0">Try adjusting your search or filters</p>
                            </td>
                        </tr>
                    </template>

                    {{-- Product rows --}}
                    <template x-for="product in productSearchResults" :key="product.id">
                        <tr :class="cart.find(i => i.id === product.id) ? 'table-success' : ''" style="transition: all 0.2s;">

                            {{-- Product Identity --}}
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="bg-light border rounded-3 d-flex align-items-center justify-content-center overflow-hidden flex-shrink-0" style="width: 48px; height: 48px;">
                                        <template x-if="product.image_url">
                                            <img :src="product.image_url" class="w-100 h-100" style="object-fit: cover;">
                                        </template>
                                        <template x-if="!product.image_url">
                                            <i class="bi bi-image text-muted opacity-50 fs-4"></i>
                                        </template>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="mb-0 fw-bold text-dark text-truncate" x-text="product.name"></p>
                                        <div x-show="product.brand">
                                            <span class="text-muted small fw-semibold" x-text="product.brand"></span>
                                        </div>
                                        {{-- In-cart indicator --}}
                                        <template x-if="cart.find(i => i.id === product.id)">
                                            <span class="badge bg-success-subtle text-success border border-success-subtle mt-1 d-inline-flex align-items-center gap-1" style="font-size: 9px; letter-spacing: 1px;">
                                                <i class="bi bi-check-circle-fill"></i> In Cart
                                            </span>
                                        </template>
                                    </div>
                                </div>
                            </td>

                            {{-- SKU / Category --}}
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    <span class="badge bg-secondary-subtle text-secondary font-monospace text-start w-auto align-self-start" x-text="product.sku" style="font-size: 11px;"></span>
                                    <div x-show="product.category">
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle align-self-start" x-text="product.category" style="font-size: 10px; letter-spacing: 0.5px;"></span>
                                    </div>
                                    <div x-show="product.tax_label">
                                        <span class="text-muted fw-bold" style="font-size: 10px;" x-text="'GST: ' + product.tax_rate + '%'"></span>
                                    </div>
                                </div>
                            </td>

                            {{-- Pricing --}}
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="fw-bold text-dark" style="font-size: 14px;" x-text="'Rs ' + Number(product.selling_price).toFixed(2)"></span>
                                    <span class="text-muted text-decoration-line-through" style="font-size: 10px;" x-show="product.mrp && product.mrp > product.selling_price" x-text="'MRP Rs ' + Number(product.mrp).toFixed(2)"></span>
                                    <span class="text-muted" style="font-size: 10px;" x-show="product.purchase_price" x-text="'Cost Rs ' + Number(product.purchase_price).toFixed(2)"></span>
                                </div>
                            </td>

                            {{-- Stock --}}
                            <td>
                                <div class="d-flex flex-column gap-1" x-data="{
                                    get dynamicStock() {
                                        let qtyInCart = cart.find(i => i.id === product.id)?.quantity || 0;
                                        return Math.max(0, product.physical_available - qtyInCart);
                                    }
                                }">
                                    <span class="badge fw-bold text-uppercase align-self-start py-1" style="font-size: 10px; letter-spacing: 1px;"
                                        :class="dynamicStock > 0
                                            ? 'bg-success-subtle text-success border border-success-subtle'
                                            : 'bg-danger-subtle text-danger border border-danger-subtle'"
                                        x-text="dynamicStock > 0
                                            ? (product.physical_available >= 999 ? 'In Stock' : dynamicStock + ' units')
                                            : 'Out of Stock'">
                                    </span>
                                    <span class="text-muted" style="font-size: 10px;" x-show="product.min_stock_level > 0" x-text="'Min: ' + product.min_stock_level"></span>
                                    <span x-show="product.allow_overselling" class="text-warning fw-bold d-flex align-items-center gap-1" style="font-size: 10px;" x-cloak>
                                        <i class="bi bi-lightning-charge-fill"></i> Oversell OK <span x-show="product.overselling_qty > 0" x-text="'(Max: ' + product.overselling_qty + ')'"></span>
                                    </span>
                                </div>
                            </td>

                            {{-- Qty + Discount inline before adding --}}
                            <td>
                                <div class="d-flex flex-column gap-2" x-data="{
                                    get dynamicStock() {
                                        let qtyInCart = cart.find(i => i.id === product.id)?.quantity || 0;
                                        return Math.max(0, product.available_stock - qtyInCart);
                                    }
                                }">
                                    <div class="input-group input-group-sm" style="width: 100px;">
                                        <button type="button" @click="product._qty = Math.max(1, (parseInt(product._qty) || 1) - 1)"
                                            class="btn btn-outline-secondary px-2">
                                            <i class="bi bi-dash"></i>
                                        </button>
                                        <input type="number" x-model="product._qty" min="1" 
                                            :max="product.available_stock < 999 ? dynamicStock : 9999"
                                            class="form-control text-center fw-bold px-1" style="font-size: 12px; appearance: textfield;">
                                        <button type="button" @click="product._qty = (parseInt(product._qty) || 1) + 1"
                                            :disabled="product.available_stock !== 999 && product._qty >= dynamicStock"
                                            class="btn btn-outline-secondary px-2">
                                            <i class="bi bi-plus"></i>
                                        </button>
                                    </div>
                                    {{-- Discount: read-only, auto-applied from product default --}}
                                    <div class="d-flex align-items-center">
                                        <template x-if="product.default_discount > 0">
                                            <span class="badge bg-success-subtle text-success border border-success-subtle d-flex align-items-center gap-1" style="font-size: 10px;">
                                                <i class="bi bi-tag-fill"></i>
                                                <span x-text="(product.default_discount_type === 'flat' ? 'Rs ' : '') + Number(product.default_discount).toFixed(product.default_discount % 1 === 0 ? 0 : 2) + (product.default_discount_type === 'flat' ? ' off' : '% off')">
                                                </span>
                                            </span>
                                        </template>
                                        <template x-if="!product.default_discount || product.default_discount <= 0">
                                            <span class="text-muted fw-bold fst-italic" style="font-size: 10px;">No disc</span>
                                        </template>
                                    </div>
                                </div>
                            </td>

                            {{-- Add Button --}}
                            <td class="text-end">
                                <button type="button"
                                    @click.prevent="addToCartWithOptions(product)"
                                    :disabled="product.available_stock !== 999 && Math.max(0, product.available_stock - (cart.find(i => i.id === product.id)?.quantity || 0)) <= 0"
                                    class="btn btn-sm d-flex align-items-center justify-content-center gap-1 ms-auto shadow-sm rounded-pill px-3 fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 1px; transition: all 0.2s;"
                                    :class="cart.find(i => i.id === product.id)
                                        ? 'btn-outline-success bg-success-subtle'
                                        : 'btn-primary'">
                                    <template x-if="cart.find(i => i.id === product.id)">
                                        <i class="bi bi-plus-lg"></i>
                                    </template>
                                    <template x-if="!cart.find(i => i.id === product.id)">
                                        <i class="bi bi-cart-plus"></i>
                                    </template>
                                    <span x-text="cart.find(i => i.id === product.id) ? 'Add More' : 'Add'"></span>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        {{-- Pagination Footer --}}
        <div class="card-footer bg-light border-top d-flex flex-column flex-sm-row align-items-center justify-content-between gap-3 p-3">
            <p class="mb-0 text-muted fw-semibold" style="font-size: 11px;">
                Showing <span class="fw-bold text-dark" x-text="productFrom"></span>–<span class="fw-bold text-dark" x-text="productTo"></span>
                of <span class="fw-bold text-dark" x-text="productTotal"></span> products
            </p>
            <div class="d-flex align-items-center gap-1">
                <button type="button" @click="productPage = 1; searchProducts()"
                    :disabled="productPage <= 1"
                    class="btn btn-sm btn-outline-secondary border-0 text-muted">
                    <i class="bi bi-chevron-double-left"></i>
                </button>
                <button type="button" @click="productPage--; searchProducts()"
                    :disabled="productPage <= 1"
                    class="btn btn-sm btn-outline-secondary border-0 text-muted">
                    <i class="bi bi-chevron-left"></i>
                </button>
                
                <span class="px-3 text-muted fw-bold text-uppercase" style="font-size: 10px; letter-spacing: 1px;">
                    Page <span class="text-dark fs-6 ms-1 me-1" x-text="productPage"></span> of <span class="text-dark fs-6 ms-1 me-1" x-text="productLastPage"></span>
                </span>

                <button type="button" @click="productPage++; searchProducts()"
                    :disabled="productPage >= productLastPage"
                    class="btn btn-sm btn-outline-secondary border-0 text-muted">
                    <i class="bi bi-chevron-right"></i>
                </button>
                <button type="button" @click="productPage = productLastPage; searchProducts()"
                    :disabled="productPage >= productLastPage"
                    class="btn btn-sm btn-outline-secondary border-0 text-muted">
                    <i class="bi bi-chevron-double-right"></i>
                </button>
            </div>
        </div>
    </div>
</div>
