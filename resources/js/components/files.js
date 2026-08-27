import Alpine from 'alpinejs';
import { createSearchComponent } from '../utils/search-component.js';

document.addEventListener('alpine:init', () => {
  Alpine.data('filesComponent', () => ({
    // UI State
    sidebarVisible: false,
    viewMode: 'list',
    sortBy: 'name',
    searchQuery: '',
    selectedFiles: [],
    showUploadZone: false,
    
    // Storage Information
    storageUsed: 45.2,
    storageTotal: 100,
    
    // Current Navigation
    currentFolder: null,
    breadcrumbs: [{ name: 'My Files', path: '/' }],
    
    // Data
    folders: [],
    currentFiles: [],
    recentFiles: [],
    quickAccess: [],
    allFiles: [],

    async init() {
      await this.loadFiles();
      this.sortFiles();
      
      // Show upload zone if folder is empty
      this.showUploadZone = this.currentFiles.length === 0;
    },

    // Computed Properties
    get storagePercentage() {
      return (this.storageUsed / this.storageTotal) * 100;
    },

    get storageRemaining() {
      return (this.storageTotal - this.storageUsed).toFixed(1);
    },

    async loadFiles() {
      try {
        const response = await fetch('/api/files');
        const files = await response.json();
        
        this.allFiles = files.map(file => ({ ...file, folder: 'Uploads' }));
        this.currentFiles = [...this.allFiles];
        
        this.folders = [
          { id: 1, name: 'Uploads', fileCount: this.allFiles.length, icon: 'bi-folder-fill' }
        ];

        this.recentFiles = [...this.allFiles].sort((a, b) => new Date(b.modifiedDate) - new Date(a.modifiedDate)).slice(0, 5);
        
        this.quickAccess = [
          { name: 'Recent', icon: 'bi-clock-history', count: this.recentFiles.length, type: 'recent' },
          { name: 'Images', icon: 'bi-image', count: this.allFiles.filter(f => f.type === 'image').length, type: 'images' },
          { name: 'Documents', icon: 'bi-file-earmark-text', count: this.allFiles.filter(f => f.type === 'document').length, type: 'documents' }
        ];

        this.sortFiles();
      } catch (error) {
        console.error('Error loading files:', error);
        this.showNotification('Failed to load files', 'error');
      }
    },

    filterFiles() {
      if (!this.searchQuery) {
        this.currentFiles = [...this.allFiles];
      } else {
        const query = this.searchQuery.toLowerCase();
        this.currentFiles = this.allFiles.filter(file => 
          file.name.toLowerCase().includes(query) || 
          file.type.toLowerCase().includes(query)
        );
      }
      this.sortFiles();
    },

    getModifiedTimestamp(modifiedStr) {
      const now = new Date();
      if (modifiedStr.includes('hour')) {
        const hours = parseInt(modifiedStr);
        return now.getTime() - (hours * 60 * 60 * 1000);
      } else if (modifiedStr.includes('day')) {
        const days = parseInt(modifiedStr);
        return now.getTime() - (days * 24 * 60 * 60 * 1000);
      } else if (modifiedStr.includes('week')) {
        const weeks = parseInt(modifiedStr);
        return now.getTime() - (weeks * 7 * 24 * 60 * 60 * 1000);
      } else if (modifiedStr.includes('month')) {
        const months = parseInt(modifiedStr);
        return now.getTime() - (months * 30 * 24 * 60 * 60 * 1000);
      }
      return now.getTime();
    },

    // File Operations
    sortFiles() {
      this.currentFiles.sort((a, b) => {
        switch (this.sortBy) {
          case 'name':
            return a.name.localeCompare(b.name);
          case 'date':
            return this.getModifiedTimestamp(a.modifiedDate) - this.getModifiedTimestamp(b.modifiedDate);
          case 'size':
            return this.parseSize(a.size) - this.parseSize(b.size);
          case 'type':
            return a.typeLabel.localeCompare(b.typeLabel);
          default:
            return 0;
        }
      });
    },

    parseSize(sizeStr) {
      const parts = sizeStr.split(' ');
      const value = parseFloat(parts[0]);
      const unit = parts[1];
      
      switch (unit) {
        case 'KB':
          return value * 1024;
        case 'MB':
          return value * 1024 * 1024;
        case 'GB':
          return value * 1024 * 1024 * 1024;
        default:
          return value;
      }
    },

    selectFile(file) {
      const index = this.selectedFiles.indexOf(file.id);
      if (index > -1) {
        this.selectedFiles.splice(index, 1);
      } else {
        this.selectedFiles.push(file.id);
      }
    },

    toggleFileSelection(fileId) {
      const index = this.selectedFiles.indexOf(fileId);
      if (index > -1) {
        this.selectedFiles.splice(index, 1);
      } else {
        this.selectedFiles.push(fileId);
      }
    },

    selectAll() {
      if (this.selectedFiles.length === this.currentFiles.length) {
        this.selectedFiles = [];
      } else {
        this.selectedFiles = this.currentFiles.map(f => f.id);
      }
    },

    toggleSelectAll() {
      this.selectAll();
    },

    // Navigation
    openFolder(folder) {
      this.currentFolder = folder;
      this.currentFiles = this.allFiles.filter(f => f.folder === folder.name);
      // Replace breadcrumbs properly - don't just push
      this.breadcrumbs = [
        { name: 'My Files', path: '/' },
        { name: folder.name, path: `/${folder.name}` }
      ];
      this.selectedFiles = [];
      this.showUploadZone = this.currentFiles.length === 0;
      this.sortFiles();
    },

    navigateToBreadcrumb(index) {
      // Properly slice breadcrumbs and navigate
      this.breadcrumbs = this.breadcrumbs.slice(0, index + 1);
      
      if (index === 0) {
        // Back to root - My Files
        this.currentFolder = null;
        this.currentFiles = [...this.allFiles];
        this.breadcrumbs = [{ name: 'My Files', path: '/' }];
      } else {
        // Navigate to specific folder
        const folderName = this.breadcrumbs[index].name;
        
        // Check if it's a quick access item
        const quickAccessItem = this.quickAccess.find(q => q.name === folderName);
        if (quickAccessItem) {
          this.navigateToQuickAccessItem(quickAccessItem);
          return;
        }
        
        // Otherwise it's a regular folder
        const folder = this.folders.find(f => f.name === folderName);
        if (folder) {
          this.currentFolder = folder;
          this.currentFiles = this.allFiles.filter(f => f.folder === folder.name);
        } else {
          // Fallback to root if folder not found
          this.currentFolder = null;
          this.currentFiles = [...this.allFiles];
          this.breadcrumbs = [{ name: 'My Files', path: '/' }];
        }
      }
      
      this.selectedFiles = [];
      this.showUploadZone = this.currentFiles.length === 0;
      this.sortFiles();
    },

    navigateToQuickAccess(item) {
      this.navigateToQuickAccessItem(item);
    },

    navigateToQuickAccessItem(item) {
      // Set proper breadcrumbs for quick access
      this.breadcrumbs = [
        { name: 'My Files', path: '/' },
        { name: item.name, path: `/${item.type}` }
      ];
      this.currentFolder = null;
      
      switch (item.type) {
        case 'recent':
          this.currentFiles = [...this.recentFiles];
          break;
        case 'images':
          this.currentFiles = this.allFiles.filter(f => f.type === 'image');
          break;
        case 'documents':
          this.currentFiles = this.allFiles.filter(f => f.type === 'document');
          break;
        case 'shared':
          this.currentFiles = this.allFiles.filter(f => f.folder === 'Shared');
          break;
        case 'trash':
          this.currentFiles = [];
          break;
        default:
          this.currentFiles = [...this.allFiles];
      }
      
      this.selectedFiles = [];
      this.showUploadZone = this.currentFiles.length === 0;
      this.sortFiles();
    },

    // View Controls
    setViewMode(mode) {
      this.viewMode = mode;
    },

    toggleSidebar() {
      this.sidebarVisible = !this.sidebarVisible;
    },

    // File Actions
    openFile(file) {
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          title: `Opening ${file.name}`,
          html: `
            <div class="text-start">
              ${file.type === 'image' ? `<div class="mb-4 text-center"><img src="${file.url}" alt="Preview" class="img-fluid rounded shadow-sm border border-secondary border-opacity-25" style="max-height: 250px; object-fit: contain;"></div>` : ''}
              <p><strong>📁 File:</strong> ${file.name}</p>
              <p><strong>📏 Size:</strong> ${file.size}</p>
              <p><strong>📅 Modified:</strong> ${file.modifiedDate}</p>
              <p><strong>📂 Folder:</strong> ${file.folder}</p>
              <p><strong>🏷️ Type:</strong> ${file.typeLabel}</p>
            </div>
          `,
          icon: 'info',
          showCancelButton: true,
          confirmButtonText: 'Open',
          cancelButtonText: 'Close',
          customClass: {
            confirmButton: 'btn btn-primary me-2',
            cancelButton: 'btn btn-secondary',
            popup: 'bg-body text-body rounded-4 shadow-lg border-0',
            title: 'text-body-emphasis fs-4 fw-bold mt-2',
            htmlContainer: 'text-body'
          },
          buttonsStyling: false,
          background: 'transparent'
        }).then((result) => {
          if (result.isConfirmed) {
            this.showNotification(`Opening ${file.name} in default application...`, 'success');
          }
        });
      } else {
        this.showNotification(`Opening ${file.name}`, 'info');
      }
    },

    downloadFile(file) {
      const a = document.createElement('a');
      a.href = file.url;
      a.download = file.name;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
    },

    downloadSelected() {
      if (this.selectedFiles.length === 0) {
        this.showNotification('❌ No files selected', 'warning');
        return;
      }
      
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          title: 'Download Selected Files',
          text: `Download ${this.selectedFiles.length} selected files as a ZIP archive?`,
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: 'Download ZIP',
          cancelButtonText: 'Cancel',
          customClass: {
            confirmButton: 'btn btn-primary me-2',
            cancelButton: 'btn btn-secondary',
            popup: 'bg-body text-body rounded-4 shadow-lg border-0',
            title: 'text-body-emphasis fs-4 fw-bold mt-2',
            htmlContainer: 'text-body-secondary'
          },
          buttonsStyling: false,
          background: 'transparent'
        }).then((result) => {
          if (result.isConfirmed) {
            this.showNotification(`📦 Creating ZIP archive with ${this.selectedFiles.length} files...`, 'info');
            this.performZipDownload();
          }
        });
      } else {
        this.showNotification(`Downloading ${this.selectedFiles.length} files...`, 'success');
      }
    },

    shareFile(file) {
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          title: `Share ${file.name}`,
          html: `
            <div class="text-start">
              <div class="mb-3">
                <label class="form-label">Share with:</label>
                <input type="email" class="form-control bg-body text-body border-secondary border-opacity-25" placeholder="Enter email address..." id="shareEmail">
              </div>
              <div class="mb-3">
                <label class="form-label">Permissions:</label>
                <select class="form-select bg-body text-body border-secondary border-opacity-25" id="sharePermissions">
                  <option value="view">View only</option>
                  <option value="edit">Can edit</option>
                  <option value="download">Can download</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">Share link:</label>
                <div class="input-group">
                  <input type="text" class="form-control bg-body text-body border-secondary border-opacity-25" value="https://files.app/share/${file.id}" readonly>
                  <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText('https://files.app/share/${file.id}')">Copy</button>
                </div>
              </div>
            </div>
          `,
          showCancelButton: true,
          confirmButtonText: 'Send Invite',
          cancelButtonText: 'Close',
          customClass: {
            confirmButton: 'btn btn-primary me-2',
            cancelButton: 'btn btn-secondary',
            popup: 'bg-body text-body rounded-4 shadow-lg border-0',
            title: 'text-body-emphasis fs-4 fw-bold mt-2',
            htmlContainer: 'text-body'
          },
          buttonsStyling: false,
          background: 'transparent'
        });
      } else {
        this.showNotification('Share dialog would open here', 'info');
      }
    },

    renameFile(file) {
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          title: 'Rename File',
          input: 'text',
          inputValue: file.name,
          inputPlaceholder: 'Enter new file name...',
          showCancelButton: true,
          confirmButtonText: 'Rename',
          cancelButtonText: 'Cancel',
          customClass: {
            confirmButton: 'btn btn-primary me-2',
            cancelButton: 'btn btn-secondary',
            popup: 'bg-body text-body rounded-4 shadow-lg border-0',
            title: 'text-body-emphasis fs-4 fw-bold mt-2',
            input: 'form-control bg-body text-body border-secondary border-opacity-25',
            htmlContainer: 'text-body-secondary'
          },
          buttonsStyling: false,
          background: 'transparent',
          inputValidator: (value) => {
            if (!value || value.trim() === '') {
              return 'Please enter a valid file name';
            }
            if (value === file.name) {
              return 'Please enter a different name';
            }
          }
        }).then((result) => {
          if (result.isConfirmed && result.value) {
            const oldName = file.name;
            const newName = result.value.trim();
            this.performFileRename(file, oldName, newName);
          }
        });
      } else {
        const newName = prompt('Enter new file name:', file.name);
        if (newName && newName !== file.name) {
          this.performFileRename(file, file.name, newName);
        }
      }
    },

    async performZipDownload() {
      try {
        const filesToDownload = this.currentFiles.filter(f => this.selectedFiles.includes(f.id)).map(f => f.id);
        
        const response = await fetch('/api/files/download-zip', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify({ ids: filesToDownload })
        });
        
        if (response.ok) {
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'download_' + new Date().getTime() + '.zip';
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
            this.showNotification(`✅ ZIP archive downloaded successfully!`, 'success');
            this.selectedFiles = [];
        } else {
            this.showNotification('Failed to create ZIP', 'error');
        }
      } catch (error) {
        console.error('Download failed', error);
        this.showNotification('Download failed', 'error');
      }
    },

    async performFileRename(file, oldName, newName) {
      try {
        const response = await fetch('/api/files/rename', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify({ id: file.id, newName })
        });
        
        if (response.ok) {
            this.showNotification(`📝 "${oldName}" renamed to "${newName}"`, 'success');
            this.loadFiles();
        } else {
            const error = await response.json();
            this.showNotification(error.error || 'Failed to rename file', 'error');
        }
      } catch (error) {
        console.error('Rename failed', error);
        this.showNotification('Rename failed', 'error');
      }
    },

    deleteFile(file) {
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          title: 'Delete File',
          text: `Are you sure you want to delete "${file.name}"? This action cannot be undone.`,
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Delete',
          cancelButtonText: 'Cancel',
          customClass: {
            confirmButton: 'btn btn-danger me-2',
            cancelButton: 'btn btn-secondary',
            popup: 'bg-body text-body rounded-4 shadow-lg border-0',
            title: 'text-body-emphasis fs-4 fw-bold mt-2',
            htmlContainer: 'text-body-secondary'
          },
          buttonsStyling: false,
          background: 'transparent'
        }).then((result) => {
          if (result.isConfirmed) {
            this.performFileDelete(file);
            this.showNotification(`🗑️ "${file.name}" moved to trash`, 'success');
          }
        });
      } else {
        if (confirm(`Are you sure you want to delete "${file.name}"?`)) {
          this.performFileDelete(file);
          this.showNotification('File deleted successfully', 'success');
        }
      }
    },

    async performFileDelete(file) {
      try {
        await fetch('/api/files', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify({ id: file.id })
        });
        this.selectedFiles = this.selectedFiles.filter(id => id !== file.id);
        this.loadFiles();
      } catch (error) {
        console.error('Delete failed', error);
        this.showNotification('Delete failed', 'error');
      }
    },

    async setLoginBackground(file) {
        try {
            await fetch('/api/files/login-background', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                body: JSON.stringify({ url: file.url })
            });
            this.showNotification('Login background updated', 'success');
            this.loadFiles();
        } catch (error) {
            console.error('Failed to set login background', error);
            this.showNotification('Failed to update background', 'error');
        }
    },

    deleteSelected() {
      if (this.selectedFiles.length === 0) {
        this.showNotification('❌ No files selected', 'warning');
        return;
      }
      
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          title: 'Delete Selected Files',
          text: `Are you sure you want to delete ${this.selectedFiles.length} selected files? This action cannot be undone.`,
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Delete All',
          cancelButtonText: 'Cancel',
          customClass: {
            confirmButton: 'btn btn-danger me-2',
            cancelButton: 'btn btn-secondary',
            popup: 'bg-body text-body rounded-4 shadow-lg border-0',
            title: 'text-body-emphasis fs-4 fw-bold mt-2',
            htmlContainer: 'text-body-secondary'
          },
          buttonsStyling: false,
          background: 'transparent'
        }).then(async (result) => {
          if (result.isConfirmed) {
            const deletedCount = this.selectedFiles.length;
            const filesToDelete = this.currentFiles.filter(f => this.selectedFiles.includes(f.id));
            await Promise.all(filesToDelete.map(f => this.performFileDelete(f)));
            this.selectedFiles = [];
            this.showNotification(`🗑️ ${deletedCount} files moved to trash`, 'success');
          }
        });
      } else {
        if (confirm(`Are you sure you want to delete ${this.selectedFiles.length} files?`)) {
          this.currentFiles = this.currentFiles.filter(f => !this.selectedFiles.includes(f.id));
          this.allFiles = this.allFiles.filter(f => !this.selectedFiles.includes(f.id));
          this.selectedFiles = [];
          this.showNotification('Files deleted successfully', 'success');
        }
      }
    },

    // File Management
    uploadFile() {
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          title: 'Upload Files',
          html: `
            <div class="text-start">
              <div class="mb-3">
                <label class="form-label">Select files to upload:</label>
                <input type="file" class="form-control bg-body text-body border-secondary border-opacity-25" multiple accept="*/*" id="fileUpload">
              </div>
              <div class="mb-3">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="overwriteFiles">
                  <label class="form-check-label" for="overwriteFiles">
                    Overwrite existing files
                  </label>
                </div>
              </div>
              <div class="upload-preview d-none">
                <h6>Files to upload:</h6>
                <ul id="fileList" class="list-unstyled"></ul>
              </div>
            </div>
          `,
          showCancelButton: true,
          confirmButtonText: 'Upload',
          cancelButtonText: 'Cancel',
          customClass: {
            confirmButton: 'btn btn-primary me-2',
            cancelButton: 'btn btn-secondary',
            popup: 'bg-body text-body rounded-4 shadow-lg border-0',
            title: 'text-body-emphasis fs-4 fw-bold mt-2',
            htmlContainer: 'text-body'
          },
          buttonsStyling: false,
          background: 'transparent',
          preConfirm: () => {
            const fileInput = document.getElementById('fileUpload');
            if (fileInput.files.length === 0) {
              Swal.showValidationMessage('Please select at least one file');
              return false;
            }
            return Array.from(fileInput.files);
          }
        }).then((result) => {
          if (result.isConfirmed) {
            const files = result.value;
            this.showNotification(`☁️ Uploading ${files.length} files...`, 'info');
            
            Promise.all(files.map(file => {
                const formData = new FormData();
                formData.append('file', file);
                return fetch('/api/files/upload', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: formData
                }).then(async response => {
                    if (!response.ok) {
                        const err = await response.json().catch(() => ({ message: 'Upload failed' }));
                        throw new Error(err.message || 'Upload failed');
                    }
                    return response.json();
                });
            })).then(() => {
                this.showNotification(`✅ ${files.length} files uploaded successfully!`, 'success');
                this.loadFiles();
            }).catch(error => {
                console.error('Upload failed', error);
                this.showNotification(error.message || 'Upload failed', 'error');
            });
          }
        });
      } else {
        this.showNotification('File upload dialog would open here', 'info');
      }
    },

    createFolder() {
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          title: 'Create New Folder',
          input: 'text',
          inputPlaceholder: 'Enter folder name...',
          showCancelButton: true,
          confirmButtonText: 'Create',
          cancelButtonText: 'Cancel',
          customClass: {
            confirmButton: 'btn btn-primary me-2',
            cancelButton: 'btn btn-secondary',
            popup: 'bg-body text-body rounded-4 shadow-lg border-0',
            title: 'text-body-emphasis fs-4 fw-bold mt-2',
            input: 'form-control bg-body text-body border-secondary border-opacity-25',
            htmlContainer: 'text-body-secondary'
          },
          buttonsStyling: false,
          background: 'transparent',
          inputValidator: (value) => {
            if (!value || value.trim() === '') {
              return 'Please enter a folder name';
            }
            if (this.folders.some(f => f.name.toLowerCase() === value.toLowerCase())) {
              return 'A folder with this name already exists';
            }
          }
        }).then((result) => {
          if (result.isConfirmed && result.value) {
            const newFolder = {
              id: this.folders.length + 1,
              name: result.value.trim(),
              fileCount: 0,
              icon: 'bi-folder-fill'
            };
            this.folders.push(newFolder);
            this.showNotification(`📁 Folder "${newFolder.name}" created successfully`, 'success');
          }
        });
      } else {
        const folderName = prompt('Enter folder name:');
        if (folderName) {
          const newFolder = {
            id: this.folders.length + 1,
            name: folderName,
            fileCount: 0,
            icon: 'bi-folder-fill'
          };
          this.folders.push(newFolder);
          this.showNotification(`Folder "${folderName}" created successfully`, 'success');
        }
      }
    },

    refreshFiles() {
      this.showNotification('🔄 Refreshing files...', 'info');
      // Simulate refresh
      setTimeout(() => {
        this.loadSampleData();
        this.sortFiles();
        this.showNotification('✅ Files refreshed successfully!', 'success');
      }, 1000);
    },

    showNotification(message, type = 'info') {
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          title: message,
          icon: type === 'success' ? 'success' : type === 'error' ? 'error' : type === 'warning' ? 'warning' : 'info',
          toast: true,
          position: 'top-end',
          showConfirmButton: false,
          timer: 3000,
          background: 'transparent',
          customClass: {
            popup: 'colored-toast bg-body text-body shadow-lg rounded-3 border-0',
            title: 'text-body-emphasis'
          }
        });
      } else {
        alert(message);
      }
    }
  }));

  // Search component for header
  Alpine.data('searchComponent', createSearchComponent({
    minLength: 3,
    getResults(query) {
      const q = query.toLowerCase();
      return [
        { title: 'Calendar Events', url: '/calendar', type: 'Page' },
        { title: 'File Manager', url: '/files', type: 'Page' },
        { title: 'User Settings', url: '/settings', type: 'Page' },
      ].filter((item) => item.title.toLowerCase().includes(q));
    },
  }));

  // Theme switch component
  Alpine.data('themeSwitch', () => ({
    currentTheme: 'light',

    init() {
      this.currentTheme = localStorage.getItem('theme') || 'light';
      document.documentElement.setAttribute('data-bs-theme', this.currentTheme);
    },

    toggle() {
      this.currentTheme = this.currentTheme === 'light' ? 'dark' : 'light';
      document.documentElement.setAttribute('data-bs-theme', this.currentTheme);
      localStorage.setItem('theme', this.currentTheme);
    }
  }));
});