@php
    $current = request()->route() ? request()->route()->getName() : 'dashboard';
@endphp

<!-- Sidebar -->
<aside class="admin-sidebar" id="admin-sidebar">
    <div class="sidebar-content">
        <nav class="sidebar-nav">
            <ul class="nav flex-column">

                {{-- ── MAIN ───────────────────────────────────────── --}}
                <li class="nav-item sidebar-section-label">
                    <small class="text-muted px-3 text-uppercase fw-bold">Main</small>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ $current === 'dashboard' ? 'active' : '' }}" href="{{ route('dashboard') }}">
                        <i class="bi bi-grid-1x2-fill"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $current === 'analytics' ? 'active' : '' }}" href="{{ route('analytics') }}">
                        <i class="bi bi-bar-chart-line-fill"></i>
                        <span>Analytics</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $current === 'reports' ? 'active' : '' }}" href="{{ route('reports') }}">
                        <i class="bi bi-file-earmark-bar-graph-fill"></i>
                        <span>Reports</span>
                    </a>
                </li>

                {{-- ── OPERATIONS ──────────────────────────────────── --}}
                <li class="nav-item sidebar-section-label mt-3">
                    <small class="text-muted px-3 text-uppercase fw-bold">Operations</small>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ $current === 'orders' ? 'active' : '' }}" href="{{ route('orders') }}">
                        <i class="bi bi-bag-check-fill"></i>
                        <span>Orders</span>
                    </a>
                </li>

                {{-- Catalog Dropdown --}}
                <li class="nav-item">
                    <a class="nav-link {{ Str::startsWith($current, 'catalog') ? 'active' : '' }}"
                       href="#"
                       data-bs-toggle="collapse"
                       data-bs-target="#catalogSubmenu"
                       aria-expanded="{{ Str::startsWith($current, 'catalog') ? 'true' : 'false' }}"
                       aria-controls="catalogSubmenu">
                        <i class="bi bi-shop-window"></i>
                        <span>Catalog</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse {{ Str::startsWith($current, 'catalog') ? 'show' : '' }}" id="catalogSubmenu">
                        <ul class="nav nav-submenu">
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'catalog.products' ? 'active' : '' }}" href="{{ route('catalog.products') }}">
                                    <i class="bi bi-box-seam-fill"></i>
                                    <span>Products</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'catalog.categories' ? 'active' : '' }}" href="{{ route('catalog.categories') }}">
                                    <i class="bi bi-diagram-3-fill"></i>
                                    <span>Categories</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'catalog.brands' ? 'active' : '' }}" href="{{ route('catalog.brands') }}">
                                    <i class="bi bi-patch-check-fill"></i>
                                    <span>Brands</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'catalog.attributes' ? 'active' : '' }}" href="{{ route('catalog.attributes') }}">
                                    <i class="bi bi-sliders2"></i>
                                    <span>Attributes</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'catalog.uom' ? 'active' : '' }}" href="{{ route('catalog.uom') }}">
                                    <i class="bi bi-rulers"></i>
                                    <span>Units of Measure</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'catalog.tax-rates' ? 'active' : '' }}" href="{{ route('catalog.tax-rates') }}">
                                    <i class="bi bi-percent"></i>
                                    <span>Tax Rates</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'catalog.hsn-codes' ? 'active' : '' }}" href="{{ route('catalog.hsn-codes') }}">
                                    <i class="bi bi-upc-scan"></i>
                                    <span>HSN Codes</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'catalog.warehouses' ? 'active' : '' }}" href="{{ route('catalog.warehouses') }}">
                                    <i class="bi bi-buildings-fill"></i>
                                    <span>Warehouses</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                {{-- ── PEOPLE ──────────────────────────────────────── --}}
                <li class="nav-item sidebar-section-label mt-3">
                    <small class="text-muted px-3 text-uppercase fw-bold">People</small>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ $current === 'users' ? 'active' : '' }}" href="{{ route('users') }}">
                        <i class="bi bi-people-fill"></i>
                        <span>Users</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $current === 'roles-permissions' ? 'active' : '' }}" href="{{ route('roles-permissions') }}">
                        <i class="bi bi-shield-lock-fill"></i>
                        <span>Roles &amp; Permissions</span>
                    </a>
                </li>

                {{-- ── COMMUNICATION ───────────────────────────────── --}}
                <li class="nav-item sidebar-section-label mt-3">
                    <small class="text-muted px-3 text-uppercase fw-bold">Communication</small>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ $current === 'messages' ? 'active' : '' }}" href="{{ route('messages') }}">
                        <i class="bi bi-chat-dots-fill"></i>
                        <span>Messages</span>
                        <span class="badge bg-danger rounded-pill ms-auto">3</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $current === 'calendar' ? 'active' : '' }}" href="{{ route('calendar') }}">
                        <i class="bi bi-calendar-week-fill"></i>
                        <span>Calendar</span>
                    </a>
                </li>

                {{-- ── CONTENT ─────────────────────────────────────── --}}
                <li class="nav-item sidebar-section-label mt-3">
                    <small class="text-muted px-3 text-uppercase fw-bold">Content</small>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ $current === 'files' ? 'active' : '' }}" href="{{ route('files') }}">
                        <i class="bi bi-folder2-open"></i>
                        <span>Files</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $current === 'forms' ? 'active' : '' }}" href="{{ route('forms') }}">
                        <i class="bi bi-ui-checks-grid"></i>
                        <span>Forms</span>
                    </a>
                </li>

                {{-- Elements Dropdown --}}
                <li class="nav-item">
                    <a class="nav-link {{ Str::startsWith($current, 'elements') ? 'active' : '' }}"
                       href="#"
                       data-bs-toggle="collapse"
                       data-bs-target="#elementsSubmenu"
                       aria-expanded="{{ Str::startsWith($current, 'elements') ? 'true' : 'false' }}"
                       aria-controls="elementsSubmenu">
                        <i class="bi bi-puzzle-fill"></i>
                        <span>Elements</span>
                        <i class="bi bi-chevron-down ms-auto"></i>
                    </a>
                    <div class="collapse {{ Str::startsWith($current, 'elements') ? 'show' : '' }}" id="elementsSubmenu">
                        <ul class="nav nav-submenu">
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'elements' ? 'active' : '' }}" href="{{ route('elements') }}">
                                    <i class="bi bi-grid-3x3-gap-fill"></i>
                                    <span>Overview</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'elements-buttons' ? 'active' : '' }}" href="{{ route('elements-buttons') }}">
                                    <i class="bi bi-hand-index-thumb-fill"></i>
                                    <span>Buttons</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'elements-alerts' ? 'active' : '' }}" href="{{ route('elements-alerts') }}">
                                    <i class="bi bi-exclamation-triangle-fill"></i>
                                    <span>Alerts</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'elements-badges' ? 'active' : '' }}" href="{{ route('elements-badges') }}">
                                    <i class="bi bi-tag-fill"></i>
                                    <span>Badges</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'elements-cards' ? 'active' : '' }}" href="{{ route('elements-cards') }}">
                                    <i class="bi bi-credit-card-2-front-fill"></i>
                                    <span>Cards</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'elements-modals' ? 'active' : '' }}" href="{{ route('elements-modals') }}">
                                    <i class="bi bi-window-stack"></i>
                                    <span>Modals</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'elements-forms' ? 'active' : '' }}" href="{{ route('elements-forms') }}">
                                    <i class="bi bi-input-cursor-text"></i>
                                    <span>Forms</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $current === 'elements-tables' ? 'active' : '' }}" href="{{ route('elements-tables') }}">
                                    <i class="bi bi-table"></i>
                                    <span>Tables</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                {{-- ── ADMIN ────────────────────────────────────────── --}}
                <li class="nav-item sidebar-section-label mt-3">
                    <small class="text-muted px-3 text-uppercase fw-bold">Admin</small>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ $current === 'settings' ? 'active' : '' }}" href="{{ route('settings') }}">
                        <i class="bi bi-gear-fill"></i>
                        <span>Settings</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $current === 'security' ? 'active' : '' }}" href="{{ route('security') }}">
                        <i class="bi bi-shield-fill-check"></i>
                        <span>Security</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $current === 'help' ? 'active' : '' }}" href="{{ route('help') }}">
                        <i class="bi bi-question-circle-fill"></i>
                        <span>Help &amp; Support</span>
                    </a>
                </li>

            </ul>
        </nav>
    </div>
</aside>
