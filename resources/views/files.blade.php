@extends('layouts.app')

@section('title', 'File Manager')
@section('page', 'files')

@section('content')
<div class="files-management" x-data="filesComponent" x-init="init()">
    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1 fw-bold"><i class="bi bi-folder-fill text-primary me-2"></i>File Manager</h1>
            <p class="text-muted mb-0 small">Organize, share, and manage your files.</p>
        </div>
        <div>
            <button class="btn btn-primary" @click="uploadFile()">
                <i class="bi bi-cloud-upload me-1"></i>Upload File
            </button>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card h-100">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stats-icon bg-primary bg-opacity-10 text-primary fs-3 rounded-3 p-2 flex-shrink-0"><i class="bi bi-folder2-open"></i></div>
                        <div>
                            <p class="mb-1 small text-muted">Total Files</p>
                            <div class="h4 mb-0 fw-bold" x-text="allFiles.length || '0'"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card h-100">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stats-icon bg-info bg-opacity-10 text-info fs-3 rounded-3 p-2 flex-shrink-0"><i class="bi bi-image"></i></div>
                        <div>
                            <p class="mb-1 small text-muted">Images</p>
                            <div class="h4 mb-0 fw-bold text-info" x-text="allFiles.filter(f => f.type === 'image').length || '0'"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card h-100">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stats-icon bg-warning bg-opacity-10 text-warning fs-3 rounded-3 p-2 flex-shrink-0"><i class="bi bi-hdd-fill"></i></div>
                        <div class="w-100">
                            <p class="mb-1 small text-muted d-flex justify-content-between"><span>Storage Used</span><span x-text="`${storagePercentage.toFixed(0)}%`"></span></p>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-warning" :style="`width: ${storagePercentage}%`"></div>
                            </div>
                            <small class="text-muted mt-1 d-block" x-text="`${storageUsed}GB of ${storageTotal}GB`"></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6">
            <div class="card stats-card h-100">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stats-icon bg-success bg-opacity-10 text-success fs-3 rounded-3 p-2 flex-shrink-0"><i class="bi bi-hdd-network-fill"></i></div>
                        <div>
                            <p class="mb-1 small text-muted">Storage Free</p>
                            <div class="h4 mb-0 fw-bold text-success" x-text="`${storageRemaining} GB`"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Data Table Card --}}
    <div class="card">
        <div class="card-header">
            <div class="row align-items-center g-2">
                <div class="col"><h2 class="h5 card-title mb-0">Files Overview</h2></div>
                <div class="col-auto">
                    <div class="d-flex flex-wrap gap-2 justify-content-end align-items-center">
                        <div class="position-relative">
                            <input type="search" class="form-control form-control-sm pe-4" placeholder="Search files..." x-model="searchQuery" @input.debounce.400ms="filterFiles()" style="width:220px;">
                            <i class="bi bi-search position-absolute top-50 end-0 translate-middle-y me-2 text-muted small"></i>
                        </div>
                        <select class="form-select form-select-sm" x-model="sortBy" @change="sortFiles()" style="width:140px;">
                            <option value="name">Sort by Name</option>
                            <option value="date">Sort by Date</option>
                            <option value="size">Sort by Size</option>
                            <option value="type">Sort by Type</option>
                        </select>
                        <div class="btn-group btn-group-sm">
                            <button class="btn" :class="viewMode === 'grid' ? 'btn-primary' : 'btn-outline-secondary'" @click="viewMode = 'grid'"><i class="bi bi-grid"></i></button>
                            <button class="btn" :class="viewMode === 'list' ? 'btn-primary' : 'btn-outline-secondary'" @click="viewMode = 'list'"><i class="bi bi-list-ul"></i></button>
                        </div>
                        <button class="btn btn-sm btn-outline-secondary" @click="refreshFiles()" title="Refresh"><i class="bi bi-arrow-clockwise"></i></button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            {{-- Bulk Actions Bar --}}
            <div class="px-3 py-2 border-bottom bg-primary bg-opacity-10" x-show="selectedFiles.length > 0" x-transition x-cloak>
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span class="fw-medium text-primary small">
                        <i class="bi bi-check-circle-fill me-1"></i>
                        <strong x-text="selectedFiles.length"></strong> file(s) selected
                    </span>
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-sm btn-primary" @click="downloadSelected()">
                            <i class="bi bi-download me-1"></i>Download
                        </button>
                        <button class="btn btn-sm btn-outline-danger" @click="deleteSelected()">
                            <i class="bi bi-trash me-1"></i>Delete
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" @click="selectedFiles = []"><i class="bi bi-x-lg"></i></button>
                    </div>
                </div>
            </div>

            {{-- Empty/Upload State --}}
            <div x-show="currentFiles.length === 0" class="text-center py-5">
                <i class="bi bi-folder2-open fs-1 text-muted mb-3 d-block opacity-50"></i>
                <h5 class="text-muted">No files found</h5>
                <p class="small text-muted mt-1 mb-4">Upload your first file or adjust your search filters.</p>
                <div class="border border-dashed rounded-3 p-4 mx-auto" style="max-width: 400px; cursor: pointer; background: var(--bs-secondary-bg);" @click="uploadFile()">
                    <i class="bi bi-cloud-upload fs-3 text-primary mb-2 d-block"></i>
                    <span class="fw-medium">Click to upload files</span>
                </div>
            </div>

            {{-- List View --}}
            <div class="table-responsive" style="overflow: visible;" x-show="viewMode === 'list' && currentFiles.length > 0" x-cloak>
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:40px;">
                                <input type="checkbox" class="form-check-input border-secondary" @change="toggleSelectAll()" style="cursor:pointer;">
                            </th>
                            <th>Name</th>
                            <th>Size</th>
                            <th>Modified</th>
                            <th>Type</th>
                            <th style="width:100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="file in currentFiles" :key="file.id">
                            <tr :class="{ 'table-active': selectedFiles.includes(file.id) }">
                                <td>
                                    <input type="checkbox" class="form-check-input border-secondary" :checked="selectedFiles.includes(file.id)" @change="toggleFileSelection(file.id)" style="cursor:pointer;">
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div :class="file.type !== 'image' ? `file-icon ${file.type} text-center me-3 rounded text-white` : 'me-3'" :style="file.type !== 'image' ? 'width:36px; height:36px; display:flex; align-items:center; justify-content:center; overflow:hidden;' : ''">
                                            <img x-show="file.type === 'image'" :src="file.url" alt="Preview" class="border" style="width: 40px; height: 40px; object-fit: cover; border-radius: 8px; cursor: pointer;" x-on:error="$el.style.display='none'" @click.stop="openFile(file)">
                                            <i x-show="file.type !== 'image'" :class="file.icon" class="fs-5"></i>
                                        </div>
                                        <div class="d-flex flex-column">
                                            <a href="#" class="fw-medium small text-decoration-none text-body-emphasis" @click.prevent.stop="openFile(file)" x-text="file.name"></a>
                                            <span x-show="file.isLoginBackground" class="badge bg-primary-subtle text-primary-emphasis mt-1 border border-primary-subtle" style="width:fit-content; font-size:0.65rem;">Login Background</span>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="small text-muted font-monospace" x-text="file.size"></span></td>
                                <td><span class="small text-muted" x-text="file.modifiedDate"></span></td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle small text-capitalize" x-text="file.typeLabel"></span>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            <i class="bi bi-three-dots"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            <li><a class="dropdown-item" href="#" @click.prevent="downloadFile(file)">
                                                <i class="bi bi-download me-2 text-primary"></i>Download
                                            </a></li>
                                            <li x-show="file.type === 'image'"><a class="dropdown-item" href="#" @click.prevent="setLoginBackground(file)">
                                                <i class="bi bi-card-image me-2 text-success"></i>Set Login Background
                                            </a></li>
                                            <li x-show="file.type === 'image'"><hr class="dropdown-divider"></li>
                                            <li x-show="file.type === 'image'"><h6 class="dropdown-header">Set as Default</h6></li>
                                            <li x-show="file.type === 'image'"><a class="dropdown-item" href="#" @click.prevent="setDefaultImage(file, 'default_avatar.jpeg')"><i class="bi bi-person me-2 text-primary"></i>Default Avatar</a></li>
                                            <li x-show="file.type === 'image'"><a class="dropdown-item" href="#" @click.prevent="setDefaultImage(file, 'product-placeholder.svg')"><i class="bi bi-box me-2 text-primary"></i>Product Placeholder</a></li>
                                            <li x-show="!file.isAsset"><hr class="dropdown-divider"></li>
                                            <li x-show="!file.isAsset"><a class="dropdown-item" href="#" @click.prevent="renameFile(file)">
                                                <i class="bi bi-pencil me-2 text-warning"></i>Rename
                                            </a></li>
                                            <li x-show="!file.isAsset"><a class="dropdown-item text-danger" href="#" @click.prevent="deleteFile(file)">
                                                <i class="bi bi-trash me-2"></i>Delete
                                            </a></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Grid View --}}
            <div class="p-4" x-show="viewMode === 'grid' && currentFiles.length > 0" x-cloak>
                <div class="row g-3">
                    <template x-for="file in currentFiles" :key="file.id">
                        <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">
                            <div class="card h-100 cursor-pointer border hover-shadow transition-all" 
                                 :class="selectedFiles.includes(file.id) ? 'border-primary bg-primary bg-opacity-10' : 'border-secondary-subtle'"
                                 @click="selectFile(file)" @dblclick="openFile(file)">
                                <div class="card-body text-center p-3 position-relative">
                                    <div class="position-absolute top-0 end-0 p-2">
                                        <input type="checkbox" class="form-check-input border-secondary" :checked="selectedFiles.includes(file.id)" @change="toggleFileSelection(file.id)" @click.stop style="cursor:pointer;">
                                    </div>
                                    <div :class="file.type !== 'image' ? `file-icon ${file.type} text-white rounded d-inline-flex align-items-center justify-content-center mb-2` : 'mb-2'" :style="file.type !== 'image' ? 'width:48px; height:48px; overflow:hidden;' : ''">
                                        <img x-show="file.type === 'image'" :src="file.url" alt="Preview" class="border" style="width: 48px; height: 48px; object-fit: cover; border-radius: 8px; cursor: pointer;" x-on:error="$el.style.display='none'" @click.stop="openFile(file)">
                                        <i x-show="file.type !== 'image'" :class="file.icon" class="fs-3"></i>
                                    </div>
                                    <h6 class="fw-semibold text-truncate small mb-1"><a href="#" class="text-decoration-none text-body-emphasis" @click.prevent.stop="openFile(file)" x-text="file.name" :title="file.name"></a></h6>
                                    <p class="text-muted small mb-0" style="font-size:0.7rem;" x-text="`${file.size} • ${file.modifiedDate}`"></p>
                                    <span x-show="file.isLoginBackground" class="badge bg-primary-subtle text-primary-emphasis mt-2 border border-primary-subtle" style="font-size:0.6rem;">Login Background</span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
.hover-shadow:hover {
    box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
    transform: translateY(-2px);
}
.transition-all {
    transition: all .2s ease-in-out;
}
.border-dashed {
    border-style: dashed !important;
}
</style>
@endsection
