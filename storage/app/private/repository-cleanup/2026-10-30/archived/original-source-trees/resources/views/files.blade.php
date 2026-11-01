@extends('layouts.app')

@section('title', 'File Manager')
@section('page', 'files')

@section('content')
<div class="files-management" x-data="filesComponent" x-cloak>
    <div class="files-page-header d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 mb-lg-5">
        <div>
            <h1 class="h3 fw-bold mb-1"><i class="bi bi-folder2-open text-primary me-2" aria-hidden="true"></i>File Manager</h1>
            <p class="text-body-secondary mb-0">Manage uploaded files, images, and application assets.</p>
        </div>
        <div class="files-page-actions d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary" @click="refreshFiles()" :disabled="loading" aria-label="Refresh files">
                <i class="bi bi-arrow-clockwise me-2" aria-hidden="true"></i>Refresh
            </button>
            @can('files-create')
<button type="button" class="btn btn-primary" @click="uploadFile()" :disabled="uploading">
                <span x-show="uploading" class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>
                <i x-show="!uploading" class="bi bi-cloud-arrow-up me-2" aria-hidden="true"></i><span x-text="uploading ? 'Uploading…' : 'Upload Files'"></span>
            </button>
@endcan
        </div>
    </div>

    <div class="row g-4 mb-4 mb-lg-5">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card stats-card h-100 border-start border-4 border-primary">
                <div class="card-body d-flex align-items-center gap-3 p-3 p-lg-4">
                    <div class="stats-icon bg-primary bg-opacity-10 text-primary flex-shrink-0"><i class="bi bi-folder2-open" aria-hidden="true"></i></div>
                    <div class="min-w-0">
                        <p class="h6 mb-1 text-muted">Total Files</p>
                        <div class="h3 fw-bold mb-1 text-body-emphasis" aria-live="polite" x-text="allFiles.length || '0'"></div>
                        <small class="text-body-secondary">Uploaded files and core assets</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card stats-card h-100 border-start border-4 border-info">
                <div class="card-body d-flex align-items-center gap-3 p-3 p-lg-4">
                    <div class="stats-icon bg-info bg-opacity-10 text-info flex-shrink-0"><i class="bi bi-image" aria-hidden="true"></i></div>
                    <div class="min-w-0">
                        <p class="h6 mb-1 text-muted">Images</p>
                        <div class="h3 fw-bold mb-1 text-body-emphasis" aria-live="polite" x-text="allFiles.filter(file => file.type === 'image').length"></div>
                        <small class="text-body-secondary">Photos, logos, and illustrations</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card stats-card h-100 border-start border-4 border-success">
                <div class="card-body d-flex align-items-center gap-3 p-3 p-lg-4">
                    <div class="stats-icon bg-success bg-opacity-10 text-success flex-shrink-0"><i class="bi bi-file-earmark-text" aria-hidden="true"></i></div>
                    <div class="min-w-0">
                        <p class="h6 mb-1 text-muted">Documents</p>
                        <div class="h3 fw-bold mb-1 text-body-emphasis" aria-live="polite" x-text="allFiles.filter(file => file.type === 'document').length"></div>
                        <small class="text-body-secondary">Documents in your library</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card stats-card h-100 border-start border-4 border-warning">
                <div class="card-body d-flex align-items-center gap-3 p-3 p-lg-4">
                    <div class="stats-icon bg-warning bg-opacity-10 text-warning flex-shrink-0"><i class="bi bi-hdd" aria-hidden="true"></i></div>
                    <div class="min-w-0">
                        <p class="h6 mb-1 text-muted">Storage Used</p>
                        <div class="h3 fw-bold mb-1 text-body-emphasis" aria-live="polite" x-text="storageUsed"></div>
                        <small class="text-body-secondary">Total size of listed files</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card files-library">
        <div class="card-header p-3 p-lg-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <h2 class="h5 card-title mb-1">File Library</h2>
                    <p class="small text-body-secondary mb-0">Browse files, manage names, and choose your application images.</p>
                </div>
                <span class="files-library-count badge bg-primary-subtle text-primary-emphasis border border-primary-subtle rounded-pill px-3 py-2"><span x-text="allFiles.length"></span> files</span>
            </div>
            <div class="files-toolbar d-flex flex-wrap align-items-center gap-2 mt-4">
                <div class="files-search position-relative flex-grow-1">
                    <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-body-secondary" aria-hidden="true"></i>
                    <input type="search" aria-label="Search files" class="form-control form-control-sm ps-5" placeholder="Search files by name or type…" x-model="searchQuery" @input.debounce.400ms="filterFiles()">
                </div>
                <select aria-label="Sort files" class="form-select form-select-sm files-sort" x-model="sortBy" @change="sortFiles()">
                    <option value="name">Name: A to Z</option>
                    <option value="date">Recently modified</option>
                    <option value="size">Size: Small to large</option>
                    <option value="type">File type</option>
                </select>
                <div class="btn-group btn-group-sm files-view-toggle" role="group" aria-label="File view">
                    <button type="button" aria-label="List view" :aria-pressed="viewMode === 'list'" class="btn" :class="viewMode === 'list' ? 'btn-primary' : 'btn-outline-secondary'" @click="viewMode = 'list'"><i class="bi bi-list-ul" aria-hidden="true"></i></button>
                    <button type="button" aria-label="Grid view" :aria-pressed="viewMode === 'grid'" class="btn" :class="viewMode === 'grid' ? 'btn-primary' : 'btn-outline-secondary'" @click="viewMode = 'grid'"><i class="bi bi-grid" aria-hidden="true"></i></button>
                </div>
            </div>
        </div>

        <div class="files-library-navigation d-flex flex-wrap justify-content-between align-items-center gap-2 px-3 px-lg-4 pt-3 border-bottom">
            <nav class="nav nav-tabs border-0 gap-1" aria-label="File collections">
                <button type="button" class="nav-link" :class="{ 'active': !currentFolder }" @click="navigateToBreadcrumb(0)"><i class="bi bi-files me-2" aria-hidden="true"></i>All Files</button>
                <button type="button" class="nav-link" :class="{ 'active': currentFolder?.name === 'Uploads' }" @click="openFolder({name: 'Uploads'})"><i class="bi bi-cloud-arrow-up me-2" aria-hidden="true"></i>Uploads</button>
                <button type="button" class="nav-link" :class="{ 'active': currentFolder?.name === 'Assets' }" @click="openFolder({name: 'Assets'})"><i class="bi bi-shield-check me-2" aria-hidden="true"></i>Core Assets</button>
            </nav>
            <small class="text-body-secondary pb-3"><i class="bi bi-shield-lock me-1" aria-hidden="true"></i>Core assets are protected</small>
        </div>

        <div class="card-body p-0">
            <div class="bulk-actions-bar files-selection p-3 bg-primary bg-opacity-10 border-bottom border-primary border-opacity-25" x-show="selectedFiles.length > 0" x-transition>
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span class="fw-semibold text-primary small"><i class="bi bi-check-circle-fill me-2" aria-hidden="true"></i><strong x-text="selectedFiles.length"></strong> file(s) selected</span>
                    <div class="d-flex flex-wrap gap-2">
                        @can('files-download')
<button type="button" class="btn btn-sm btn-primary" @click="downloadSelected()" :disabled="downloading"><i class="bi bi-download me-1" aria-hidden="true"></i>Download ZIP</button>
@endcan
                        @can('files-delete')
<button type="button" class="btn btn-sm btn-outline-danger" @click="deleteSelected()" :disabled="bulkDeleting"><i class="bi bi-trash me-1" aria-hidden="true"></i>Delete</button>
@endcan
                        <button type="button" class="btn btn-sm btn-outline-secondary" @click="selectedFiles = []" aria-label="Clear file selection"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                    </div>
                </div>
            </div>
            <div x-show="loadError" x-text="loadError" class="alert alert-danger m-3" role="alert"></div>
            <div x-show="loading" class="files-loading text-center p-4 text-body-secondary" role="status"><span class="spinner-border spinner-border-sm text-primary me-2" aria-hidden="true"></span>Loading files…</div>

            <div x-show="!loading && !loadError && currentFiles.length === 0" class="files-empty text-center px-3 py-5">
                <div class="files-empty-icon mx-auto mb-3"><i class="bi bi-folder2-open" aria-hidden="true"></i></div>
                <h3 class="h5 fw-semibold mb-2">No files found</h3>
                <p class="text-body-secondary small mb-4">Upload a file or adjust your search to find what you need.</p>
                @can('files-create')
<button type="button" class="btn btn-primary" @click="uploadFile()" :disabled="uploading"><i class="bi bi-cloud-arrow-up me-2" aria-hidden="true"></i>Upload Files</button>
@endcan
            </div>

            <div class="table-responsive files-table-scroll" x-show="viewMode === 'list' && currentFiles.length > 0">
                <table class="table table-hover align-middle mb-0 files-table">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="files-selection-column"><input type="checkbox" class="form-check-input" aria-label="Select all visible files" :checked="allVisibleSelected" x-effect="$el.indeterminate = selectedFiles.length > 0 && !allVisibleSelected" @change="toggleSelectAll()"></th>
                            <th scope="col">File Name</th>
                            <th scope="col">Type</th>
                            <th scope="col">Size</th>
                            <th scope="col">Last Modified</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="file in currentFiles" :key="file.id">
                            <tr :class="{ 'table-active': selectedFiles.includes(file.id) }">
                                <td><input type="checkbox" class="form-check-input" :aria-label="'Select ' + file.name" :checked="selectedFiles.includes(file.id)" @change="toggleFileSelection(file.id)"></td>
                                <td class="files-name-column">
                                    <div class="d-flex align-items-center gap-3">
                                        <button type="button" class="files-thumbnail flex-shrink-0" @click.stop="openFile(file)" :aria-label="'Preview ' + file.name">
                                            <img x-show="file.type === 'image'" :src="file.type === 'image' ? file.url : null" alt="" x-on:error="if ($el.getAttribute('src') !== '/assets/images/default_avatar.jpeg') $el.src = '/assets/images/default_avatar.jpeg'; else $el.style.display = 'none'">
                                            <i x-show="file.type !== 'image'" :class="file.icon" class="fs-4 text-primary" aria-hidden="true"></i>
                                        </button>
                                        <div class="min-w-0">
                                            <a href="#" class="files-name fw-semibold text-body-emphasis text-decoration-none" :title="file.name" @click.prevent.stop="openFile(file)" x-text="file.name"></a>
                                            <div class="small text-body-secondary mt-1"><i x-show="file.isAsset" class="bi bi-shield-lock me-1" aria-hidden="true"></i><span x-text="file.isAsset ? 'Core asset' : 'Uploaded file'"></span></div>
                                            <div class="d-flex flex-wrap gap-1 mt-1" x-show="(file.defaultRoles || []).length">
                                                <template x-for="role in (file.defaultRoles || [])" :key="role"><span class="badge files-role bg-primary-subtle text-primary-emphasis border border-primary-subtle" x-text="role === 'App Logo (PNG)' ? 'App Logo' : role"></span></template>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge files-type bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle" x-text="file.typeLabel"></span></td>
                                <td class="small text-body-secondary text-nowrap font-monospace" x-text="file.size"></td>
                                <td class="small text-body-secondary text-nowrap" x-text="file.modifiedDate"></td>
                                <td class="text-end">
                                    <div class="dropdown">
                                        <button type="button" class="btn btn-sm btn-outline-secondary files-action-button" data-bs-toggle="dropdown" :aria-label="'Actions for ' + file.name" :disabled="!!busyFiles[file.id]"><i class="bi bi-three-dots" aria-hidden="true"></i></button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                        <li>@can('files-download')
<a class="dropdown-item" href="#" @click.prevent="downloadFile(file)">
                                        <i class="bi bi-download me-2 text-primary"></i>Download
                                        </a>
@endcan</li>
                                        <li x-show="file.type === 'image'">@can('settings-edit')
<a class="dropdown-item" href="#" @click.prevent="setLoginBackground(file)">
                                        <i class="bi bi-card-image me-2 text-success"></i>Set Login Background
                                        </a>
@endcan</li>
                                        <li x-show="file.type === 'image'"><hr class="dropdown-divider"></li>
                                        <li x-show="file.type === 'image'"><h6 class="dropdown-header">Use image as</h6></li>
                                        <li x-show="file.type === 'image'">@can('settings-edit')
<a class="dropdown-item" href="#" @click.prevent="setDefaultImage(file, 'logo.png')"><i class="bi bi-star me-2 text-primary"></i>App Logo</a>
@endcan</li>
                                        <li x-show="file.type === 'image'">@can('settings-edit')
<a class="dropdown-item" href="#" @click.prevent="setDefaultImage(file, 'default_avatar.jpeg')"><i class="bi bi-person me-2 text-primary"></i>Default Avatar</a>
@endcan</li>
                                        <li x-show="file.type === 'image'">@can('settings-edit')
<a class="dropdown-item" href="#" @click.prevent="setDefaultImage(file, 'product-placeholder.svg')"><i class="bi bi-box me-2 text-primary"></i>Product Placeholder</a>
@endcan</li>
                                        <li x-show="!file.isAsset"><hr class="dropdown-divider"></li>
                                        <li x-show="!file.isAsset">@can('files-edit')
<a class="dropdown-item" href="#" @click.prevent="renameFile(file)">
                                        <i class="bi bi-pencil me-2 text-warning"></i>Rename
                                        </a>
@endcan</li>
                                        <li x-show="!file.isAsset">@can('files-delete')
<a class="dropdown-item text-danger" href="#" @click.prevent="deleteFile(file)">
                                        <i class="bi bi-trash me-2"></i>Delete
                                        </a>
@endcan</li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="files-grid p-3 p-lg-4" x-show="viewMode === 'grid' && currentFiles.length > 0">
                <template x-for="file in currentFiles" :key="file.id">
                    <article class="card files-grid-card h-100" :class="selectedFiles.includes(file.id) ? 'border-primary bg-primary bg-opacity-10' : ''" @click="selectFile(file)" @dblclick="openFile(file)">
                        <div class="d-flex justify-content-between align-items-center p-3 pb-0">
                            <input type="checkbox" class="form-check-input mt-0" :aria-label="'Select ' + file.name" :checked="selectedFiles.includes(file.id)" @change="toggleFileSelection(file.id)" @click.stop>
                            <div class="dropdown" @click.stop @dblclick.stop>
                                <button type="button" class="btn btn-sm btn-outline-secondary files-action-button" data-bs-toggle="dropdown" :aria-label="'Actions for ' + file.name" :disabled="!!busyFiles[file.id]"><i class="bi bi-three-dots" aria-hidden="true"></i></button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                        <li>@can('files-download')
<a class="dropdown-item" href="#" @click.prevent="downloadFile(file)">
                                        <i class="bi bi-download me-2 text-primary"></i>Download
                                        </a>
@endcan</li>
                                        <li x-show="file.type === 'image'">@can('settings-edit')
<a class="dropdown-item" href="#" @click.prevent="setLoginBackground(file)">
                                        <i class="bi bi-card-image me-2 text-success"></i>Set Login Background
                                        </a>
@endcan</li>
                                        <li x-show="file.type === 'image'"><hr class="dropdown-divider"></li>
                                        <li x-show="file.type === 'image'"><h6 class="dropdown-header">Use image as</h6></li>
                                        <li x-show="file.type === 'image'">@can('settings-edit')
<a class="dropdown-item" href="#" @click.prevent="setDefaultImage(file, 'logo.png')"><i class="bi bi-star me-2 text-primary"></i>App Logo</a>
@endcan</li>
                                        <li x-show="file.type === 'image'">@can('settings-edit')
<a class="dropdown-item" href="#" @click.prevent="setDefaultImage(file, 'default_avatar.jpeg')"><i class="bi bi-person me-2 text-primary"></i>Default Avatar</a>
@endcan</li>
                                        <li x-show="file.type === 'image'">@can('settings-edit')
<a class="dropdown-item" href="#" @click.prevent="setDefaultImage(file, 'product-placeholder.svg')"><i class="bi bi-box me-2 text-primary"></i>Product Placeholder</a>
@endcan</li>
                                        <li x-show="!file.isAsset"><hr class="dropdown-divider"></li>
                                        <li x-show="!file.isAsset">@can('files-edit')
<a class="dropdown-item" href="#" @click.prevent="renameFile(file)">
                                        <i class="bi bi-pencil me-2 text-warning"></i>Rename
                                        </a>
@endcan</li>
                                        <li x-show="!file.isAsset">@can('files-delete')
<a class="dropdown-item text-danger" href="#" @click.prevent="deleteFile(file)">
                                        <i class="bi bi-trash me-2"></i>Delete
                                        </a>
@endcan</li>
                                        </ul>
                            </div>
                        </div>
                        <button type="button" class="files-grid-preview mx-3 mt-3" @click.stop="openFile(file)" @dblclick.stop :aria-label="'Preview ' + file.name">
                            <img x-show="file.type === 'image'" :src="file.type === 'image' ? file.url : null" alt="" loading="lazy" x-on:error="if ($el.getAttribute('src') !== '/assets/images/default_avatar.jpeg') $el.src = '/assets/images/default_avatar.jpeg'; else $el.style.display = 'none'">
                            <i x-show="file.type !== 'image'" :class="file.icon" class="text-primary" aria-hidden="true"></i>
                        </button>
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center gap-2 mb-2"><span class="badge files-type bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle" x-text="file.typeLabel"></span><small class="text-body-secondary" x-text="file.size"></small></div>
                            <a href="#" class="files-name fw-semibold text-body-emphasis text-decoration-none" :title="file.name" @click.prevent.stop="openFile(file)" x-text="file.name"></a>
                            <p class="small text-body-secondary mt-1 mb-2"><i x-show="file.isAsset" class="bi bi-shield-lock me-1" aria-hidden="true"></i><span x-text="file.isAsset ? 'Core asset' : 'Uploaded file'"></span></p>
                            <div class="d-flex flex-wrap gap-1" x-show="(file.defaultRoles || []).length"><template x-for="role in (file.defaultRoles || [])" :key="role"><span class="badge files-role bg-primary-subtle text-primary-emphasis border border-primary-subtle" x-text="role === 'App Logo (PNG)' ? 'App Logo' : role"></span></template></div>
                        </div>
                        <div class="card-footer px-3 py-2 d-flex justify-content-between align-items-center gap-2">
                            <small class="text-body-secondary files-grid-date" x-text="file.modifiedDate"></small>
                            @can('files-download')
<button type="button" class="btn btn-sm btn-outline-secondary flex-shrink-0" @click.stop="downloadFile(file)" @dblclick.stop :disabled="!!busyFiles[file.id]" :aria-label="'Download ' + file.name"><i class="bi bi-download" aria-hidden="true"></i></button>
@endcan
                        </div>
                    </article>
                </template>
            </div>
        </div>
        <div class="card-footer files-library-footer d-flex flex-wrap justify-content-between align-items-center gap-2 px-3 px-lg-4 py-3">
            <small class="text-body-secondary">Showing <strong class="text-body-emphasis" x-text="currentFiles.length"></strong> of <strong class="text-body-emphasis" x-text="allFiles.length"></strong> files</small>
            <small class="text-body-secondary"><i class="bi bi-info-circle me-1" aria-hidden="true"></i>Maximum upload size: 10 MB per file</small>
        </div>
    </div>
</div>
@endsection
