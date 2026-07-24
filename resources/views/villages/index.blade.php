@extends('layouts.app')

@section('title', 'Village Management')
@section('page', 'villages')

@section('content')
<div class="village-management" x-data="villageTable" x-init="init()">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 mb-lg-5 mb-xl-6">
        <div>
            <h1 class="h3 mb-0">Village Management</h1>
            <p class="text-muted mb-0">Manage geolocations, pincodes, and service coverage</p>
        </div>
        <div class="d-flex gap-2">
            @can('village-export')
            <button type="button" class="btn btn-outline-secondary" @click="exportVillages()">
                <i class="bi bi-download me-2"></i>Export
            </button>
            @endcan
            <div class="dropdown">
                <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-upload me-2"></i>Import
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li><a class="dropdown-item" href="#" @click.prevent="document.getElementById('import-file').click()">
                        <i class="bi bi-file-earmark-arrow-up me-2 text-primary"></i>Upload CSV
                    </a></li>
                    <li><a class="dropdown-item" href="{{ route('api.villages.import-template') }}">
                        <i class="bi bi-file-earmark-arrow-down me-2 text-primary"></i>Download Template
                    </a></li>
                </ul>
            </div>
            <button type="button" class="btn btn-primary" @click="openCreateVillage()">
                <i class="bi bi-plus-circle me-2"></i>Add Village
            </button>
        </div>
    </div>

    <!-- Hidden CSV Import Form -->
    <form id="import-form" action="{{ route('api.villages.import') }}" method="POST" enctype="multipart/form-data" class="d-none">
        @csrf
        <input type="file" name="file" id="import-file" accept=".csv,.txt" @change="handleImportFileSelect($event)">
    </form>

    <!-- Stats Widgets -->
    <div class="row g-4 g-lg-5 g-xl-6 mb-5 mb-lg-5 mb-xl-6">
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-primary bg-opacity-10 text-primary me-3">
                            <i class="bi bi-geo-alt-fill"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Total Villages</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.total"></span></div>
                            <small class="text-success-emphasis">
                                Rural coverage
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-success bg-opacity-10 text-success me-3">
                            <i class="bi bi-mailbox"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Unique Pincodes</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.pincodes"></span></div>
                            <small class="text-success-emphasis">
                                Postal zones mapped
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-info bg-opacity-10 text-info me-3">
                            <i class="bi bi-map-fill"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Districts Covered</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.districts_count"></span></div>
                            <small class="text-success-emphasis">
                                Regions covered
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center">
                        <div class="stats-icon bg-warning bg-opacity-10 text-warning me-3">
                            <i class="bi bi-gear-fill"></i>
                        </div>
                        <div>
                            <p class="h6 mb-0 text-muted">Active Services</p>
                            <div class="h3 mb-0" aria-live="polite"><span x-text="stats.services"></span></div>
                            <small class="text-warning-emphasis">
                                Services mapped
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Service Mapping Trends Row -->
    <div class="row g-4 g-lg-5 g-xl-6 mb-5 mb-lg-5 mb-xl-6">
        <!-- Growth Chart (Village addition/region distribution) -->
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header">
                    <h2 class="h5 card-title mb-0">Service Coverage Distribution</h2>
                </div>
                <div class="card-body p-3 p-lg-4">
                    <div id="serviceDistributionChart" style="width: 100%; overflow: hidden;"></div>
                </div>
            </div>
        </div>

        <!-- Districts Breakdown list -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <h2 class="h5 card-title mb-0">Top Districts by Village Count</h2>
                </div>
                <div class="card-body p-3 p-lg-4">
                    <div>
                        <template x-for="d in districtBreakdown" :key="d.name">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="small fw-semibold" x-text="d.name"></span>
                                <div class="d-flex align-items-center">
                                    <div class="progress me-2" style="width: 100px; height: 6px;">
                                        <div class="progress-bar" 
                                             :style="`width: ${d.percentage}%; background-color: #6366f1`"></div>
                                    </div>
                                    <span class="small text-muted" x-text="d.count"></span>
                                </div>
                            </div>
                        </template>
                        <div x-show="districtBreakdown.length === 0" class="text-muted text-center py-4">
                            No district data available.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Table Container -->
    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="h5 card-title mb-0">Villages Directory</h2>
                </div>
                <div class="col-auto">
                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                        <!-- Search -->
                        <div class="position-relative">
                            <input type="search" 
                                   class="form-control form-control-sm" 
                                   placeholder="Search villages..."
                                   x-model="searchQuery"
                                   @input.debounce.300ms="searchVillages()"
                                   style="width: 200px; padding-right: 30px;">
                            <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-2 text-muted"></i>
                        </div>
                        
                        <!-- Deleted Filter -->
                        <select class="form-select form-select-sm"
                                x-model="deletedFilter"
                                @change="filterVillages()"
                                style="width: 130px;">
                            <option value="">Active</option>
                            <option value="with">All</option>
                            <option value="only">Deleted</option>
                        </select>

                        <!-- Items Per Page -->
                        <select class="form-select form-select-sm"
                                x-model.number="itemsPerPage"
                                @change="filterVillages()"
                                style="width: 110px;">
                            <option value="15">15 / page</option>
                            <option value="30">30 / page</option>
                            <option value="50">50 / page</option>
                            <option value="100">100 / page</option>
                        </select>

                        <!-- Advanced Filters Trigger -->
                        <button class="btn btn-sm"
                                :class="hasActiveAdvancedFilters() ? 'btn-primary' : 'btn-outline-secondary'"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#advancedFilters"
                                aria-expanded="false">
                            <i class="bi bi-funnel me-1"></i>Filters
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Collapsible Advanced Filters Drawer -->
        <div class="collapse" id="advancedFilters">
            <div class="p-3 bg-body-tertiary border-top border-bottom border-secondary-subtle">
                <div class="row g-3">
                    <!-- Service Filter -->
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold text-body-secondary">Service</label>
                        <select class="form-select form-select-sm" x-model="serviceFilter" @change="filterVillages()">
                            <option value="">All Services</option>
                            <template x-for="s in servicesOptions" :key="s.id">
                                <option :value="s.id" x-text="s.name"></option>
                            </template>
                        </select>
                    </div>

                    <!-- State Filter -->
                    <div class="col-md-2 position-relative" @click.away="showStateDropdown = false" :style="showStateDropdown ? 'z-index: 1050;' : ''">
                        <label class="form-label small fw-semibold text-body-secondary">
                            State <span class="badge bg-secondary rounded-pill ms-1" style="font-size: 0.65rem;" x-text="stateFilter.length + ' / ' + Object.keys(statesList).length"></span>
                        </label>
                        <div class="form-control form-control-sm d-flex flex-wrap align-items-center gap-1" style="min-height: 31px; cursor: text;" @click="showStateDropdown = true; $refs.stateSearch.focus()">
                            <template x-for="state in stateFilter" :key="state">
                                <div class="badge bg-primary bg-opacity-10 text-primary d-flex align-items-center gap-1 border border-primary-subtle">
                                    <span x-text="state" style="font-size: 11px;"></span>
                                    <i class="bi bi-x cursor-pointer" @click.stop="toggleFilter('state', state)" style="font-size: 13px;"></i>
                                </div>
                            </template>
                            <div class="flex-grow-1 position-relative" style="min-width: 50px;">
                                <input x-ref="stateSearch" type="text" x-model="stateSearch" @focus="showStateDropdown = true" placeholder="Search States..." class="border-0 w-100 bg-transparent text-body" style="font-size: 12px; outline: none !important; box-shadow: none;">
                            </div>
                        </div>
                        <div x-show="showStateDropdown && filteredStates.length > 0" class="position-absolute w-100 bg-body border rounded shadow-lg mt-1" style="max-height: 200px; overflow-y: auto; z-index: 1050;">
                            <div class="px-3 py-2 cursor-pointer border-bottom bg-body-tertiary d-flex align-items-center" @click.stop="toggleAllFilter('state')">
                                <input type="checkbox" :checked="stateFilter.length > 0 && stateFilter.length === Object.keys(statesList).length" class="me-2" style="cursor: pointer;">
                                <span style="font-size: 12px; font-weight: bold;">Select All</span>
                            </div>
                            <template x-for="state in filteredStates" :key="state">
                                <div class="px-3 py-1 cursor-pointer custom-hover-bg d-flex align-items-center" @click.stop="toggleFilter('state', state)">
                                    <input type="checkbox" :checked="stateFilter.includes(state)" class="me-2" style="cursor: pointer;">
                                    <span style="font-size: 12px;" x-text="state"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- District Filter -->
                    <div class="col-md-2 position-relative" @click.away="showDistrictDropdown = false" :style="showDistrictDropdown ? 'z-index: 1050;' : ''">
                        <label class="form-label small fw-semibold text-body-secondary">
                            District <span class="badge bg-secondary rounded-pill ms-1" style="font-size: 0.65rem;" x-text="districtFilter.length + ' / ' + Object.keys(districtsList).length"></span>
                        </label>
                        <div class="form-control form-control-sm d-flex flex-wrap align-items-center gap-1" style="min-height: 31px; cursor: text;" @click="showDistrictDropdown = true; $refs.districtSearch.focus()">
                            <template x-for="district in districtFilter" :key="district">
                                <div class="badge bg-primary bg-opacity-10 text-primary d-flex align-items-center gap-1 border border-primary-subtle">
                                    <span x-text="district" style="font-size: 11px;"></span>
                                    <i class="bi bi-x cursor-pointer" @click.stop="toggleFilter('district', district)" style="font-size: 13px;"></i>
                                </div>
                            </template>
                            <div class="flex-grow-1 position-relative" style="min-width: 50px;">
                                <input x-ref="districtSearch" type="text" x-model="districtSearch" @focus="showDistrictDropdown = true" placeholder="Search Districts..." class="border-0 w-100 bg-transparent text-body" style="font-size: 12px; outline: none !important; box-shadow: none;">
                            </div>
                        </div>
                        <div x-show="showDistrictDropdown && filteredDistricts.length > 0" class="position-absolute w-100 bg-body border rounded shadow-lg mt-1" style="max-height: 200px; overflow-y: auto; z-index: 1050;">
                            <div class="px-3 py-2 cursor-pointer border-bottom bg-body-tertiary d-flex align-items-center" @click.stop="toggleAllFilter('district')">
                                <input type="checkbox" :checked="districtFilter.length > 0 && districtFilter.length === Object.keys(districtsList).length" class="me-2" style="cursor: pointer;">
                                <span style="font-size: 12px; font-weight: bold;">Select All</span>
                            </div>
                            <template x-for="district in filteredDistricts" :key="district">
                                <div class="px-3 py-1 cursor-pointer custom-hover-bg d-flex align-items-center" @click.stop="toggleFilter('district', district)">
                                    <input type="checkbox" :checked="districtFilter.includes(district)" class="me-2" style="cursor: pointer;">
                                    <span style="font-size: 12px;" x-text="district"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Taluka Filter -->
                    <div class="col-md-2 position-relative" @click.away="showTalukaDropdown = false" :style="showTalukaDropdown ? 'z-index: 1050;' : ''">
                        <label class="form-label small fw-semibold text-body-secondary">
                            Taluka <span class="badge bg-secondary rounded-pill ms-1" style="font-size: 0.65rem;" x-text="talukaFilter.length + ' / ' + Object.keys(talukasList).length"></span>
                        </label>
                        <div class="form-control form-control-sm d-flex flex-wrap align-items-center gap-1" style="min-height: 31px; cursor: text;" @click="showTalukaDropdown = true; $refs.talukaSearch.focus()">
                            <template x-for="taluka in talukaFilter" :key="taluka">
                                <div class="badge bg-primary bg-opacity-10 text-primary d-flex align-items-center gap-1 border border-primary-subtle">
                                    <span x-text="taluka" style="font-size: 11px;"></span>
                                    <i class="bi bi-x cursor-pointer" @click.stop="toggleFilter('taluka', taluka)" style="font-size: 13px;"></i>
                                </div>
                            </template>
                            <div class="flex-grow-1 position-relative" style="min-width: 50px;">
                                <input x-ref="talukaSearch" type="text" x-model="talukaSearch" @focus="showTalukaDropdown = true" placeholder="Search Talukas..." class="border-0 w-100 bg-transparent text-body" style="font-size: 12px; outline: none !important; box-shadow: none;">
                            </div>
                        </div>
                        <div x-show="showTalukaDropdown && filteredTalukas.length > 0" class="position-absolute w-100 bg-body border rounded shadow-lg mt-1" style="max-height: 200px; overflow-y: auto; z-index: 1050;">
                            <div class="px-3 py-2 cursor-pointer border-bottom bg-body-tertiary d-flex align-items-center" @click.stop="toggleAllFilter('taluka')">
                                <input type="checkbox" :checked="talukaFilter.length > 0 && talukaFilter.length === Object.keys(talukasList).length" class="me-2" style="cursor: pointer;">
                                <span style="font-size: 12px; font-weight: bold;">Select All</span>
                            </div>
                            <template x-for="taluka in filteredTalukas" :key="taluka">
                                <div class="px-3 py-1 cursor-pointer custom-hover-bg d-flex align-items-center" @click.stop="toggleFilter('taluka', taluka)">
                                    <input type="checkbox" :checked="talukaFilter.includes(taluka)" class="me-2" style="cursor: pointer;">
                                    <span style="font-size: 12px;" x-text="taluka"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Village Filter -->
                    <div class="col-md-2 position-relative" @click.away="showVillageDropdown = false" :style="showVillageDropdown ? 'z-index: 1050;' : ''">
                        <label class="form-label small fw-semibold text-body-secondary">
                            Village <span class="badge bg-secondary rounded-pill ms-1" style="font-size: 0.65rem;" x-text="villageFilter.length + ' / ' + Object.keys(villagesList).length"></span>
                        </label>
                        <div class="form-control form-control-sm d-flex flex-wrap align-items-center gap-1" style="min-height: 31px; cursor: text;" @click="showVillageDropdown = true; $refs.villageSearch.focus()">
                            <template x-for="village in villageFilter" :key="village">
                                <div class="badge bg-primary bg-opacity-10 text-primary d-flex align-items-center gap-1 border border-primary-subtle">
                                    <span x-text="village" style="font-size: 11px;"></span>
                                    <i class="bi bi-x cursor-pointer" @click.stop="toggleFilter('village', village)" style="font-size: 13px;"></i>
                                </div>
                            </template>
                            <div class="flex-grow-1 position-relative" style="min-width: 50px;">
                                <input x-ref="villageSearch" type="text" x-model="villageSearch" @focus="showVillageDropdown = true" placeholder="Search Villages..." class="border-0 w-100 bg-transparent text-body" style="font-size: 12px; outline: none !important; box-shadow: none;">
                            </div>
                        </div>
                        <div x-show="showVillageDropdown && filteredVillages.length > 0" class="position-absolute w-100 bg-body border rounded shadow-lg mt-1" style="max-height: 200px; overflow-y: auto; z-index: 1050;">
                            <div class="px-3 py-2 cursor-pointer border-bottom bg-body-tertiary d-flex align-items-center" @click.stop="toggleAllFilter('village')">
                                <input type="checkbox" :checked="villageFilter.length > 0 && villageFilter.length === Object.keys(villagesList).length" class="me-2" style="cursor: pointer;">
                                <span style="font-size: 12px; font-weight: bold;">Select All</span>
                            </div>
                            <template x-for="village in filteredVillages" :key="village">
                                <div class="px-3 py-1 cursor-pointer custom-hover-bg d-flex align-items-center" @click.stop="toggleFilter('village', village)">
                                    <input type="checkbox" :checked="villageFilter.includes(village)" class="me-2" style="cursor: pointer;">
                                    <span style="font-size: 12px;" x-text="village"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Reset Filters -->
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" class="btn btn-sm btn-outline-secondary w-100 d-inline-flex align-items-center justify-content-center" @click="resetFilters()">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">

            <!-- Bulk Actions Banner -->
            <div class="bulk-actions-bar p-3 bg-primary bg-opacity-10 border-bottom border-primary border-opacity-25" 
                 x-show="selectedVillages.length > 0">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle-fill text-primary me-2"></i>
                        <span class="fw-medium text-primary">
                            <span x-text="selectedVillages.length"></span> village<span x-show="selectedVillages.length !== 1">s</span> selected
                        </span>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-primary" @click="exportSelectedVillages()" title="Export Selected to CSV">
                            <i class="bi bi-download me-1"></i>Export CSV
                        </button>
                        <button class="btn btn-sm btn-success" @click="openBulkServiceModal()" x-show="!hasSelectedDeletedVillages">
                            <i class="bi bi-gear me-1"></i>Update Services
                        </button>
                        <button class="btn btn-sm btn-danger" @click="bulkAction('delete')" x-show="!hasSelectedDeletedVillages">
                            <i class="bi bi-trash me-1"></i>Delete Selected
                        </button>
                        <button class="btn btn-sm btn-success" @click="bulkAction('restore')" x-show="hasSelectedDeletedVillages">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Restore
                        </button>
                        <button class="btn btn-sm btn-danger" @click="bulkAction('force-delete')" x-show="hasSelectedDeletedVillages">
                            <i class="bi bi-trash3 me-1"></i>Permanent Delete
                        </button>
                        <button class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center justify-content-center px-2" @click="selectedVillages = []" title="Clear selection">
                            <i class="bi bi-x-lg" style="margin-left: 7px"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 40px;" class="ps-3">
                                <input type="checkbox" 
                                       class="user-select-checkbox" 
                                       :checked="villages.length > 0 && selectedVillages.length === villages.length"
                                       @change="toggleAll($event.target.checked)">
                            </th>
                            <th @click="sort('village_name')" style="cursor: pointer;">
                                Village Name <i class="bi" :class="getSortIcon('village_name')"></i>
                            </th>
                            <th @click="sort('pincode')" style="cursor: pointer;">
                                Pincode <i class="bi" :class="getSortIcon('pincode')"></i>
                            </th>
                            <th>Post SO Name</th>
                            <th>Taluka</th>
                            <th>District</th>
                            <th>State</th>
                            <th>Mapped Services</th>
                            <th style="width: 80px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Loading State -->
                        <tr x-show="isLoading">
                            <td colspan="9" class="text-center py-5">
                                <div class="spinner-border text-primary" role="status"></div>
                                <p class="mt-2 text-muted mb-0">Loading villages...</p>
                            </td>
                        </tr>

                        <!-- Empty State -->
                        <tr x-show="!isLoading && villages.length === 0">
                            <td colspan="9" class="text-center py-5">
                                <i class="bi bi-geo-alt text-muted display-4"></i>
                                <p class="mt-2 fw-semibold mb-1">No villages found</p>
                                <p class="text-muted small mb-0">Upload a CSV or add a village manually.</p>
                            </td>
                        </tr>

                        <!-- Data Rows -->
                        <template x-for="v in villages" :key="v.id">
                            <tr class="user-row" :class="{'table-active': selectedVillages.includes(v.id), 'opacity-50': v.deleted_at}">
                                <td class="ps-3">
                                    <input type="checkbox" 
                                           class="user-select-checkbox" 
                                           :value="v.id" :checked="selectedVillages.includes(v.id)"
                                           @change="toggleVillage(v.id)">
                                </td>
                                <td>
                                    <div>
                                        <span class="fw-semibold text-body" x-text="v.village_name"></span>
                                        <span class="badge bg-danger-subtle text-danger ms-1 small" x-show="v.deleted_at">Deleted</span>
                                    </div>
                                </td>
                                <td x-text="v.pincode"></td>
                                <td x-text="v.post_so_name || '—'"></td>
                                <td x-text="v.taluka_name || '—'"></td>
                                <td x-text="v.district_name || '—'"></td>
                                <td x-text="v.state_name || '—'"></td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        <template x-for="(map, index) in v.active_mappings" :key="map.id">
                                            <span class="badge bg-success bg-opacity-10 text-success small" 
                                                  x-text="`${index + 1}. ${map.service.name}`"></span>
                                        </template>
                                        <span x-show="!v.active_mappings || v.active_mappings.length === 0" class="text-muted small">—</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                                type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-three-dots"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li x-show="!v.deleted_at">
                                                <a class="dropdown-item" href="#" @click.prevent="openServicesModal(v)">
                                                    <i class="bi bi-gear me-2"></i>Manage Services
                                                </a>
                                            </li>
                                            <li x-show="!v.deleted_at">
                                                <a class="dropdown-item" href="#" @click.prevent="editVillage(v)">
                                                    <i class="bi bi-pencil me-2"></i>Edit Details
                                                </a>
                                            </li>
                                            <li x-show="!v.deleted_at"><hr class="dropdown-divider"></li>
                                            <li x-show="!v.deleted_at">
                                                <a class="dropdown-item text-danger" href="#" @click.prevent="deleteVillage(v)">
                                                    <i class="bi bi-trash me-2"></i>Delete
                                                </a>
                                            </li>
                                            <li x-show="v.deleted_at">
                                                <a class="dropdown-item text-success" href="#" @click.prevent="restoreVillage(v)">
                                                    <i class="bi bi-arrow-counterclockwise me-2"></i>Restore
                                                </a>
                                            </li>
                                            <li x-show="v.deleted_at">
                                                <a class="dropdown-item text-danger" href="#" @click.prevent="forceDeleteVillage(v)">
                                                    <i class="bi bi-trash3 me-2"></i>Permanent Delete
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center p-3 border-top" x-show="villages.length > 0">
                <div class="text-muted small">
                    Showing <span x-text="pageFrom"></span> to <span x-text="pageTo"></span> of <span x-text="totalVillages"></span> results
                </div>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item" :class="{ 'disabled': currentPage === 1 }">
                            <a class="page-link" href="#" @click.prevent="goToPage(currentPage - 1)">Previous</a>
                        </li>
                        <template x-for="(page, index) in visiblePages" :key="`${page}-${index}`">
                            <li class="page-item" :class="{ 'active': page === currentPage }">
                                <a class="page-link" href="#" @click.prevent="goToPage(page)" x-text="page"></a>
                            </li>
                        </template>
                        <li class="page-item" :class="{ 'disabled': currentPage === totalPages }">
                            <a class="page-link" href="#" @click.prevent="goToPage(currentPage + 1)">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
{{-- ═══════════════════════ Add / Edit Village Modal ═══════════════════════════ --}}
<div class="modal fade" id="villageModal" tabindex="-1" aria-labelledby="villageModalLabel">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content" x-data="villageForm">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="villageModalLabel" x-text="editingVillageId ? 'Edit Village' : 'Add New Village'"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form @submit.prevent="saveVillage()">
                <div class="modal-body pt-3">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Village Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" x-model="form.village_name" placeholder="e.g. Kawatha" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Pincode <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" x-model="form.pincode" placeholder="e.g. 440001" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Post SO Name</label>
                            <input type="text" class="form-control" x-model="form.post_so_name" placeholder="e.g. Nagpur SO">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Taluka / Tehsil</label>
                            <input type="text" class="form-control" x-model="form.taluka_name" placeholder="e.g. Kamptee">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">District</label>
                            <input type="text" class="form-control" x-model="form.district_name" placeholder="e.g. Nagpur">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">State</label>
                            <input type="text" class="form-control" x-model="form.state_name" placeholder="e.g. Maharashtra">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" :disabled="saving">
                        <span x-show="saving" class="spinner-border spinner-border-sm me-1"></span>
                        <span x-text="editingVillageId ? 'Save Changes' : 'Create Village'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ═══════════════════════ Manage Village Services Modal ═══════════════════════════ --}}
<div class="modal fade" id="servicesModal" tabindex="-1" aria-labelledby="servicesModalLabel">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" x-data="villageServices">
        <div class="modal-content">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="servicesModalLabel">
                    <i class="bi bi-gear-wide-connected me-2"></i>Service Coverage Settings
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form @submit.prevent="saveServices()">
                <div class="modal-body pt-3">
                    <div class="mb-3 text-body-secondary">
                        Configure which logistics, delivery, or custom services are available in village: 
                        <strong class="text-body" x-text="villageName"></strong> (<span x-text="pincode"></span>)
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Service Name</th>
                                    <th>Availability</th>
                                    <th>Priority (1, 2, 3…)</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="s in services" :key="s.id">
                                    <tr>
                                        <td>
                                            <span class="fw-bold" x-text="s.name"></span>
                                            <div class="small text-muted" x-text="s.description"></div>
                                        </td>
                                        <td>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" :id="`servSwitch-${s.id}`" x-model="mappings[s.id].is_available" @change="ensureUniquePriority(s.id)">
                                                <label class="form-check-label small" :for="`servSwitch-${s.id}`" x-text="mappings[s.id].is_available ? 'Available' : 'Unavailable'"></label>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="number" min="1" class="form-control form-control-sm" style="max-width: 80px;" x-model.number="mappings[s.id].priority" :class="{'is-invalid': isPriorityDuplicate(s.id) || mappings[s.id].priority < 1}" :disabled="!mappings[s.id].is_available">
                                            <div class="invalid-feedback">Use a unique priority.</div>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm" x-model="mappings[s.id].remarks" placeholder="Notes..." :disabled="!mappings[s.id].is_available">
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" :disabled="saving">
                        <span x-show="saving" class="spinner-border spinner-border-sm me-1"></span>
                        Save Configuration
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ═══════════════════════ Bulk Update Services Modal ═══════════════════════════ --}}
<div class="modal fade" id="bulkServicesModal" tabindex="-1" aria-labelledby="bulkServicesModalLabel">
    <div class="modal-dialog modal-dialog-scrollable" x-data="bulkServicesForm">
        <div class="modal-content">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="bulkServicesModalLabel">Bulk Service Update</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form @submit.prevent="updateServices()">
                <div class="modal-body pt-3">
                    <div class="alert alert-info py-2">
                        Updating services for <strong x-text="count"></strong> selected village(s).
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Services</label>
                        <select class="form-select" multiple size="6" x-model="serviceIds" required>
                            <template x-for="s in services" :key="s.id">
                                <option :value="s.id" x-text="s.name"></option>
                            </template>
                        </select>
                        <div class="form-text">Hold Ctrl/Cmd to select multiple services.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Coverage Status</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="bulkStatus" id="statusAvail" value="available" x-model="status">
                            <label class="form-check-label" for="statusAvail">Set Available</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="bulkStatus" id="statusUnavail" value="unavailable" x-model="status">
                            <label class="form-check-label" for="statusUnavail">Set Unavailable</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" :disabled="saving">
                        <span x-show="saving" class="spinner-border spinner-border-sm me-1"></span>
                        Apply to Selected Services
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ═══════════════════════ CSV Import Preview Modal ═══════════════════════════ -->
<div class="modal fade" id="importPreviewModal" tabindex="-1" aria-labelledby="importPreviewModalLabel" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="importPreviewModalLabel">
                    <i class="bi bi-file-earmark-spreadsheet me-2 text-primary"></i>CSV Import Preview
                </h5>
                <button type="button" class="btn-close" @click="cancelImport()"></button>
            </div>
            <div class="modal-body pt-3">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle-fill me-2"></i>Please review the first 5 records of your CSV below before confirming.
                </div>
                <div class="table-responsive" style="max-height: 400px;">
                    <table class="table table-striped table-hover table-sm small align-middle mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>Village Name</th>
                                <th>Pincode</th>
                                <th>Post SO Name</th>
                                <th>Taluka</th>
                                <th>District</th>
                                <th>State</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(row, idx) in importRows" :key="idx">
                                <tr>
                                    <td class="fw-bold" x-text="row.village_name || row[Object.keys(row)[0]]"></td>
                                    <td x-text="row.pincode || row[Object.keys(row)[1]]"></td>
                                    <td x-text="row.post_so_name || row[Object.keys(row)[2]]"></td>
                                    <td x-text="row.taluka_name || row[Object.keys(row)[3]]"></td>
                                    <td x-text="row.district_name || row[Object.keys(row)[4]]"></td>
                                    <td x-text="row.state_name || row[Object.keys(row)[5]]"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-secondary" @click="cancelImport()">Cancel</button>
                <button type="button" class="btn btn-primary" @click="confirmImport()">
                    <span x-show="importing" class="spinner-border spinner-border-sm me-1"></span>
                    <span x-text="importing ? 'Importing...' : 'Confirm Import'"></span>
                </button>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@push('scripts')
<!-- Loaded via main.js or separate scripts -->
@endpush
