@extends('layouts.app')
@section('title', '⭐ Referral Programs')
@section('page', 'referrals.programs.index')

@section('content')
<div class="user-management" x-data="referralPrograms()">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 mb-lg-5 mb-xl-6">
        <div>
            <h1 class="h3 mb-0"><i class="bi bi-people-fill text-primary me-2"></i>Referral Programs</h1>
            <p class="text-muted mb-0">Manage tiered and time-based referral campaigns</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createProgramModal">
                <i class="bi bi-plus-lg me-2"></i>New Program
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 rounded-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 rounded-4" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li><i class="bi bi-exclamation-triangle-fill me-2"></i> {{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Stats Row -->
    <div class="row g-4 g-lg-5 g-xl-6 mb-5 mb-lg-5 mb-xl-6">
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card h-100 border-0 shadow-sm rounded-4">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-primary bg-opacity-10 text-primary me-3 rounded-circle d-flex justify-content-center align-items-center" style="width: 48px; height: 48px;">
                            <i class="bi bi-diagram-3-fill fs-4"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Total Programs</p>
                            <div class="h3 mb-0 fw-bold">{{ $programs->count() }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card h-100 border-0 shadow-sm rounded-4">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-success bg-opacity-10 text-success me-3 rounded-circle d-flex justify-content-center align-items-center" style="width: 48px; height: 48px;">
                            <i class="bi bi-check-circle-fill fs-4"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Active</p>
                            <div class="h3 mb-0 fw-bold">{{ $programs->where('is_active', true)->count() }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card h-100 border-0 shadow-sm rounded-4">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-info bg-opacity-10 text-info me-3 rounded-circle d-flex justify-content-center align-items-center" style="width: 48px; height: 48px;">
                            <i class="bi bi-infinity fs-4"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Permanent</p>
                            <div class="h3 mb-0 fw-bold">{{ $programs->whereNull('start_date')->count() }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card h-100 border-0 shadow-sm rounded-4">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-warning bg-opacity-10 text-warning me-3 rounded-circle d-flex justify-content-center align-items-center" style="width: 48px; height: 48px;">
                            <i class="bi bi-hourglass-split fs-4"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Time-Bound</p>
                            <div class="h3 mb-0 fw-bold">{{ $programs->whereNotNull('start_date')->count() }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header border-bottom py-3">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="h5 card-title mb-0 fw-bold">Programs Directory</h2>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <!-- Bulk Actions Bar -->
            <div class="bulk-actions-bar p-3 bg-primary bg-opacity-10 border-bottom border-primary border-opacity-25" x-show="selected.length > 0" style="display: none;">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle-fill text-primary me-2"></i>
                        <span class="fw-medium text-primary">
                            <span x-text="selected.length"></span> selected
                        </span>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-success" @click="bulkAction('activate')"><i class="bi bi-check-circle me-1"></i>Activate (First only)</button>
                        <button class="btn btn-sm btn-warning" @click="bulkAction('deactivate')"><i class="bi bi-pause-circle me-1"></i>Deactivate</button>
                        <button class="btn btn-sm btn-danger" @click="bulkAction('delete')"><i class="bi bi-trash me-1"></i>Delete</button>
                        <button class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center justify-content-center px-2" @click="selected = []" title="Clear selection">
                            <i class="bi bi-x-lg" style="margin-left: 7px"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="text-uppercase small text-muted">
                        <tr>
                            <th style="width:40px" class="ps-4 py-3"><input type="checkbox" class="form-check-input" @change="$event.isTrusted && toggleAll($event)" :checked="allSelected"></th>
                            <th class="py-3">Program Name</th>
                            <th class="py-3">Duration</th>
                            <th class="py-3">Status</th>
                            <th class="py-3">Milestones</th>
                            <th class="text-end pe-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($programs as $program)
                            <tr>
                                <td class="ps-4">
                                    <input type="checkbox" class="form-check-input" value="{{ $program->id }}" x-model="selected">
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="p-2 rounded-circle me-3 d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary" style="width: 38px; height: 38px;">
                                            <i class="fs-5 bi bi-diagram-3-fill"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-body-emphasis">{{ $program->name }}</div>
                                            <div class="text-muted small" style="font-size: 10px;">ID: #{{ $program->id }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        @if(!$program->start_date && !$program->end_date)
                                            <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle px-2 py-1 align-self-start">
                                                <i class="bi bi-infinity me-1"></i>Always Active
                                            </span>
                                        @else
                                            <div class="small" style="font-size: 11px;">
                                                <div class="text-nowrap text-muted"><span class="fw-semibold text-body-emphasis">From:</span> {{ $program->start_date ? $program->start_date->format('M d, Y') : '∞' }}</div>
                                                <div class="text-nowrap text-muted"><span class="fw-semibold text-body-emphasis">To:</span> {{ $program->end_date ? $program->end_date->format('M d, Y') : '∞' }}</div>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <form action="{{ route('referrals.programs.toggle', $program->id) }}" method="POST" class="d-inline m-0 p-0">
                                        @csrf
                                        @method('PATCH')
                                        <span class="badge rounded-pill px-3 py-2 fw-medium border cursor-pointer custom-hover-opacity {{ $program->is_active ? 'bg-success-subtle text-success-emphasis border-success-subtle' : 'bg-secondary-subtle text-secondary-emphasis border-secondary-subtle' }}" onclick="this.closest('form').submit()" title="Click to toggle status">
                                            <span class="d-inline-block rounded-circle me-1 {{ $program->is_active ? 'bg-success' : 'bg-secondary' }}" style="width: 6px; height: 6px; vertical-align: middle;"></span>
                                            {{ $program->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </form>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach($program->milestones as $milestone)
                                            <span class="badge bg-body-tertiary text-body-emphasis border px-2 py-1 d-inline-flex align-items-center gap-1">
                                                @if($milestone->reward_type === 'wallet')
                                                    <i class="bi bi-wallet2 text-primary"></i>
                                                @elseif($milestone->reward_type === 'coupon')
                                                    <i class="bi bi-tag-fill text-warning"></i>
                                                @else
                                                    <i class="bi bi-box2-heart-fill text-success"></i>
                                                @endif
                                                <span>{{ $milestone->required_referrals == 0 ? 'Every Ref' : $milestone->required_referrals . ' Refs' }} = {{ ucfirst($milestone->reward_type) }}: {{ $milestone->reward_value }}</span>
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" data-bs-boundary="window" title="Actions">
                                            <i class="bi bi-three-dots"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                            <li>
                                                <a class="dropdown-item" href="#" @click.prevent="editProgram({{ $program->toJson() }})">
                                                    <i class="bi bi-pencil me-2"></i>Edit
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form action="{{ route('referrals.programs.destroy', $program->id) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Are you sure you want to delete this program?');">
                                                        <i class="bi bi-trash me-2"></i>Delete
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-inboxes fs-1 d-block mb-2"></i>
                                    No referral programs found. Create one to get started!
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Create Modal Glossy Style -->
    <div class="modal fade" id="createProgramModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg rounded-4 bg-body">
                
                {{-- GLOSSY STYLE HEADER WITH BOOTSTRAP --}}
                <div class="modal-header bg-body-tertiary border-bottom d-flex align-items-center justify-content-between p-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 48px; height: 48px;">
                            <i class="bi bi-magic fs-4 text-primary"></i>
                        </div>
                        <div>
                            <h4 class="mb-0 fw-bold text-body" x-text="form.id ? 'Edit Program' : 'Create New Program'"></h4>
                            <p class="mb-0 small text-muted">Configure referral rules, milestones, and rewards</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" @click="resetForm()"></button>
                </div>

                <form :action="form.id ? `{{ url('/promotions/referral-programs') }}/${form.id}` : `{{ route('referrals.programs.store') }}`" method="POST">
                    @csrf
                    <input type="hidden" name="_method" :value="form.id ? 'PUT' : 'POST'">
                    <div class="modal-body p-4 p-md-5 pt-4">
                        
                        <div class="row g-4">
                            <div class="col-12">
                                
                                {{-- Card 1: Basic Information --}}
                                <div class="card mb-4 border border-secondary border-opacity-25 shadow-sm rounded-4 bg-body-secondary">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center gap-2 pb-3 mb-4 border-bottom border-secondary border-opacity-25">
                                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                                <i class="bi bi-tag-fill fs-6"></i>
                                            </div>
                                            <h6 class="mb-0 fw-bolder text-uppercase text-body" style="font-size: 11px; letter-spacing: 1.5px;">Basic Information</h6>
                                        </div>
                                        <div class="row g-4">
                                            <div class="col-md-12">
                                                <label class="form-label mb-2 fw-bold text-muted text-uppercase" style="font-size: 10px; letter-spacing: 0.1em;">Program Name *</label>
                                                <input type="text" name="name" x-model="form.name" class="form-control form-control-lg fw-semibold rounded-3 bg-body border-secondary border-opacity-25 shadow-none px-3" required placeholder="e.g. Summer Mega Drive" style="font-size: 14px;">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label mb-2 fw-bold text-muted text-uppercase" style="font-size: 10px; letter-spacing: 0.1em;">Start Date</label>
                                                <input type="date" name="start_date" x-model="form.start_date" class="form-control form-control-lg fw-semibold rounded-3 bg-body border-secondary border-opacity-25 shadow-none px-3" style="font-size: 14px;">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label mb-2 fw-bold text-muted text-uppercase" style="font-size: 10px; letter-spacing: 0.1em;">End Date</label>
                                                <input type="date" name="end_date" x-model="form.end_date" class="form-control form-control-lg fw-semibold rounded-3 bg-body border-secondary border-opacity-25 shadow-none px-3" style="font-size: 14px;">
                                            </div>
                                            <div class="col-12 mt-4 pt-3 border-top border-secondary border-opacity-25">
                                                <div class="d-flex align-items-center justify-content-between p-3 rounded-3 border border-secondary border-opacity-10 bg-body">
                                                    <div>
                                                        <h6 class="mb-1 fw-bold text-body" style="font-size: 14px;">Program Status</h6>
                                                        <p class="mb-0 text-muted" style="font-size: 12px;">Toggle to activate or deactivate this program.</p>
                                                    </div>
                                                    <div class="form-check form-switch cursor-pointer m-0">
                                                        <input class="form-check-input border-secondary border-opacity-50" type="checkbox" name="is_active" value="1" x-model="form.is_active" id="isActiveToggle" style="width: 2.5em; height: 1.25em; cursor: pointer;">
                                                        <label class="form-check-label fw-bold ms-2" :class="form.is_active ? 'text-success' : 'text-muted'" for="isActiveToggle" style="cursor: pointer;" x-text="form.is_active ? 'Active' : 'Inactive'"></label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Card 2: Milestones & Rewards --}}
                                <div class="card mb-4 border border-secondary border-opacity-25 shadow-sm rounded-4 bg-body-secondary">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom border-secondary border-opacity-25">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                                    <i class="bi bi-gift-fill fs-6"></i>
                                                </div>
                                                <h6 class="mb-0 fw-bolder text-uppercase text-body" style="font-size: 11px; letter-spacing: 1.5px;">Milestones & Rewards</h6>
                                            </div>
                                        </div>
                                        
                                        <div class="alert alert-info py-2 px-3 shadow-sm border-0 d-flex align-items-center gap-2 mb-4" style="font-size: 12px;">
                                            <i class="bi bi-info-circle-fill"></i>
                                            <span><strong>Tip:</strong> Create a milestone with <strong>0 Required Referrals</strong> to give a base reward for <em>every</em> referral!</span>
                                        </div>

                                        <template x-for="(milestone, index) in milestones" :key="index">
                                            <div class="p-3 mb-3 bg-body border border-secondary border-opacity-25 rounded-3 shadow-sm position-relative">
                                                <button type="button" class="btn btn-sm btn-light text-danger border-0 position-absolute top-0 end-0 m-2" @click="removeMilestone(index)" x-show="milestones.length > 1" style="width: 28px; height: 28px; padding: 0;">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                                
                                                <div class="row g-3">
                                                    <div class="col-md-3">
                                                        <label class="form-label mb-2 fw-bold text-muted text-uppercase" style="font-size: 10px; letter-spacing: 0.1em;">Referrals (0=Every)</label>
                                                        <input type="number" x-model="milestone.required_referrals" :name="`milestones[${index}][required_referrals]`" class="form-control fw-semibold border-secondary border-opacity-25 shadow-none" required min="0" style="font-size: 13px;">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label mb-2 fw-bold text-muted text-uppercase" style="font-size: 10px; letter-spacing: 0.1em;">Reward Type</label>
                                                        <select x-model="milestone.reward_type" :name="`milestones[${index}][reward_type]`" class="form-select fw-semibold border-secondary border-opacity-25 shadow-none" required style="font-size: 13px;">
                                                            <option value="wallet">Wallet Balance</option>
                                                            <option value="product">Free Product</option>
                                                            <option value="coupon">Discount Coupon</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <label class="form-label mb-2 fw-bold text-muted text-uppercase" style="font-size: 10px; letter-spacing: 0.1em;">
                                                            <span x-text="milestone.reward_type === 'product' ? 'Select Free Product' : 'Value (Amount)'"></span>
                                                        </label>
                                                        
                                                        <input type="number" x-show="milestone.reward_type !== 'product'" x-model="milestone.reward_value" :name="milestone.reward_type !== 'product' ? `milestones[${index}][reward_value]` : ''" class="form-control fw-semibold border-secondary border-opacity-25 shadow-none" :required="milestone.reward_type !== 'product'" style="font-size: 13px;" :placeholder="milestone.reward_type === 'wallet' ? 'Enter Wallet Rs' : 'Enter Discount Rs'">
                                                        
                                                        <div x-show="milestone.reward_type === 'product'" x-data="{ open: false, search: '' }" class="position-relative" @click.away="open = false">
                                                            <input type="hidden" :name="milestone.reward_type === 'product' ? `milestones[${index}][reward_value]` : ''" :value="milestone.reward_value">
                                                            
                                                            <div class="form-control fw-semibold border-secondary border-opacity-25 shadow-none d-flex align-items-center justify-content-between cursor-pointer" style="font-size: 13px; min-height: 38px;" @click="open = !open">
                                                                <span class="text-truncate" x-text="milestone.reward_value ? (allProducts.find(p => p.id == milestone.reward_value) ? allProducts.find(p => p.id == milestone.reward_value).name + (allProducts.find(p => p.id == milestone.reward_value).sku ? ' (' + allProducts.find(p => p.id == milestone.reward_value).sku + ')' : '') : 'Select a product...') : 'Select a product...'"></span>
                                                                <i class="bi bi-chevron-down text-muted" style="font-size: 11px;"></i>
                                                            </div>
                                                            
                                                            <div x-show="open" class="position-absolute w-100 bg-body border border-secondary border-opacity-25 rounded-3 shadow-lg mt-1 overflow-hidden" style="display: none; z-index: 1050; top: 100%; left: 0;">
                                                                <div class="p-2 bg-body-tertiary border-bottom border-secondary border-opacity-25">
                                                                    <div class="position-relative">
                                                                        <input type="text" x-model="search" class="form-control form-control-sm border-secondary border-opacity-25 shadow-none pe-4" placeholder="Search product name or SKU..." @click.stop x-ref="searchInput" x-init="$watch('open', val => { if(val) setTimeout(() => $refs.searchInput.focus(), 50) })">
                                                                        <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-2 text-muted" style="font-size: 10px;"></i>
                                                                    </div>
                                                                </div>
                                                                <div class="overflow-y-auto" style="max-height: 200px;">
                                                                    <template x-for="product in allProducts.filter(p => (p.name + ' ' + (p.sku || '')).toLowerCase().includes(search.toLowerCase()))" :key="product.id">
                                                                        <div class="px-3 py-2 cursor-pointer border-bottom border-secondary border-opacity-10 custom-hover-bg transition-all" 
                                                                            @click="milestone.reward_value = product.id; open = false; search = ''" 
                                                                            :class="milestone.reward_value == product.id ? 'bg-primary bg-opacity-10 text-primary fw-bold' : ''">
                                                                            <span x-text="product.name" style="font-size: 12px;"></span>
                                                                            <span class="text-muted ms-1" style="font-size: 10px;" x-text="product.sku ? '(' + product.sku + ')' : ''"></span>
                                                                        </div>
                                                                    </template>
                                                                    <div x-show="allProducts.filter(p => (p.name + ' ' + (p.sku || '')).toLowerCase().includes(search.toLowerCase())).length === 0" class="p-3 text-center text-muted" style="font-size: 11px;">
                                                                        No products found.
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>

                                        <button type="button" class="btn btn-sm btn-outline-primary fw-bold mt-2 rounded-pill px-3" @click="addMilestone">
                                            <i class="bi bi-plus-circle-fill me-1"></i> Add Milestone Tier
                                        </button>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                    
                    {{-- Form Actions --}}
                    <div class="modal-footer bg-body-tertiary border-top p-4">
                        <div class="d-flex w-100 justify-content-between align-items-center">
                            <button type="button" data-bs-dismiss="modal" class="btn btn-outline-secondary rounded-pill px-4 fw-bold text-uppercase" style="font-size: 12px; letter-spacing: 1px;" @click="resetForm()">Cancel</button>
                            <button type="submit" class="btn btn-primary rounded-pill px-5 py-2 fw-bold shadow-sm d-flex align-items-center">
                                <span x-text="form.id ? 'Save Changes' : 'Create Program'"></span>
                                <i class="bi bi-arrow-right ms-2"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.custom-hover-bg:hover { background-color: rgba(var(--bs-primary-rgb), 0.1); }
.custom-hover-opacity { transition: all 0.2s; }
.custom-hover-opacity:hover { opacity: 1 !important; color: var(--bs-danger) !important; transform: scale(1.1); }
.cursor-pointer { cursor: pointer; }
</style>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('referralPrograms', () => ({
            programs: @json($programs->pluck('id')),
            allProducts: @json($products ?? []),
            selected: [],
            get allSelected() { 
                return this.programs.length > 0 && this.selected.length === this.programs.length; 
            },
            toggleAll(e) { 
                this.selected = e.target.checked ? [...this.programs] : []; 
            },
            async bulkAction(action) {
                if (!this.selected.length) return;
                if (action === 'delete' && !confirm(`Delete ${this.selected.length} program(s)?`)) return;
                if (action === 'activate' && this.selected.length > 1) {
                    if (!confirm('Only one referral program can be active at a time. This will activate the first selected program and deactivate all others. Continue?')) return;
                }
                
                try {
                    const res = await fetch('{{ route('referrals.programs.bulk') }}', { 
                        method: 'POST', 
                        headers: { 
                            'Content-Type': 'application/json', 
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json' 
                        }, 
                        body: JSON.stringify({ action, ids: this.selected }) 
                    });
                    
                    if (res.ok) {
                        window.location.reload();
                    } else {
                        alert('An error occurred while performing bulk action.');
                    }
                } catch (e) {
                    alert('Network error.');
                }
            },
            form: { id: null, name: '', start_date: '', end_date: '', is_active: true },
            milestones: [
                { required_referrals: 0, reward_type: 'wallet', reward_value: '100' }
            ],
            resetForm() {
                this.form = { id: null, name: '', start_date: '', end_date: '', is_active: true };
                this.milestones = [
                    { required_referrals: 0, reward_type: 'wallet', reward_value: '100' }
                ];
            },
            editProgram(program) {
                this.form = {
                    id: program.id,
                    name: program.name,
                    start_date: program.start_date ? program.start_date.split('T')[0] : '',
                    end_date: program.end_date ? program.end_date.split('T')[0] : '',
                    is_active: program.is_active
                };
                
                this.milestones = program.milestones.map(m => ({
                    required_referrals: m.required_referrals,
                    reward_type: m.reward_type,
                    reward_value: m.reward_value
                }));

                if (this.milestones.length === 0) {
                    this.addMilestone();
                }

                new bootstrap.Modal(document.getElementById('createProgramModal')).show();
            },
            addMilestone() {
                this.milestones.push({ required_referrals: '', reward_type: 'wallet', reward_value: '' });
            },
            removeMilestone(index) {
                if (this.milestones.length > 1) {
                    this.milestones.splice(index, 1);
                }
            }
        }));
    });
</script>
@endsection
