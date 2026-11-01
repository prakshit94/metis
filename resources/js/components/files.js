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
    loadError: '',
    uploading: false,
    downloading: false,
    bulkDeleting: false,
    busyFiles: {},
    busySettings: {},

    // Storage Information
    loading: false,
    initialized: false,
    loadVersion: 0,

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
      if (this.initialized) return;
      this.initialized = true;
      await this.loadFiles();

      // Show upload zone if folder is empty
      this.showUploadZone = this.currentFiles.length === 0;
    },

    // Computed Properties
    get storageUsed() {
      const bytes = this.allFiles.reduce((sum, file) => sum + (Number(file.sizeBytes) || this.parseSize(file.size)), 0);
      return this.formatBytes(bytes);
    },

    formatBytes(bytes) {
      const units = ['B', 'KB', 'MB', 'GB', 'TB'];
      const index = bytes > 0 ? Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), 4) : 0;
      return `${Number((bytes / 1024 ** index).toFixed(2))} ${units[index]}`;
    },

    get allVisibleSelected() {
      return this.currentFiles.length > 0 && this.currentFiles.every(file => this.selectedFiles.includes(file.id));
    },

    escapeHtml(value) {
      return String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;'}[char]));
    },

    async loadFiles() {
      const version = ++this.loadVersion;
      this.loading = true;
      this.loadError = '';
      try {
        const response = await fetch('/api/files', { headers: { Accept: 'application/json' } });
        if (!response.ok || response.redirected) throw new Error('Failed to load files');
        const files = await response.json();
        if (!Array.isArray(files)) throw new Error('Invalid file response');
        if (version !== this.loadVersion) return false;
        const defaultAvatar = response.headers?.get('X-Default-Avatar');
        if (defaultAvatar !== null && defaultAvatar !== undefined) {
          window.ProfileImages?.setDefaultAvatar(decodeURIComponent(defaultAvatar));
        }

        this.allFiles = files.map((file) => ({
          ...file,
          folder: file.isAsset ? 'Assets' : 'Uploads',
        }));
        this.currentFiles = [...this.allFiles];

        this.folders = [
          {
            id: 1,
            name: 'Uploads',
            fileCount: this.allFiles.filter((f) => f.folder === 'Uploads').length,
            icon: 'bi-folder-fill',
          },
          {
            id: 2,
            name: 'Assets',
            fileCount: this.allFiles.filter((f) => f.folder === 'Assets').length,
            icon: 'bi-images',
          },
        ];

        this.recentFiles = [...this.allFiles]
          .sort((a, b) => this.getModifiedTimestamp(b.modifiedAt || b.modifiedDate) - this.getModifiedTimestamp(a.modifiedAt || a.modifiedDate))
          .slice(0, 5);

        this.quickAccess = [
          {
            name: 'Recent',
            icon: 'bi-clock-history',
            count: this.recentFiles.length,
            type: 'recent',
          },
          {
            name: 'Images',
            icon: 'bi-image',
            count: this.allFiles.filter((f) => f.type === 'image').length,
            type: 'images',
          },
          {
            name: 'Documents',
            icon: 'bi-file-earmark-text',
            count: this.allFiles.filter((f) => f.type === 'document').length,
            type: 'documents',
          },
        ];

        this.filterFiles();
        return true;
      } catch (error) {
        if (version !== this.loadVersion) return false;
        console.error('Error loading files:', error);
        this.loadError = error.message || 'Failed to load files';
        this.showNotification(this.loadError, 'error');
        return false;
      } finally {
        if (version === this.loadVersion) this.loading = false;
      }
    },

    filterFiles() {
      let files = this.allFiles;
      if (this.currentFolder) files = files.filter(file => file.folder === this.currentFolder.name);
      const quick = this.breadcrumbs[1]?.path;
      if (quick === '/recent') files = files.filter(file => this.recentFiles.some(recent => recent.id === file.id));
      if (quick === '/images') files = files.filter(file => file.type === 'image');
      if (quick === '/documents') files = files.filter(file => file.type === 'document');
      if (quick === '/shared') files = files.filter(file => file.folder === 'Shared');
      if (quick === '/trash') files = [];
      const query = this.searchQuery.trim().toLowerCase();
      this.currentFiles = files.filter(file => !query || String(file.name || '').toLowerCase().includes(query) || String(file.type || '').toLowerCase().includes(query));
      this.selectedFiles = this.selectedFiles.filter(id => this.currentFiles.some(file => file.id === id));
      this.showUploadZone = this.currentFiles.length === 0;
      this.sortFiles();
    },

    getModifiedTimestamp(modifiedStr) {
      modifiedStr = String(modifiedStr || '');
      const parsed = Date.parse(modifiedStr);
      if (Number.isFinite(parsed)) return parsed;
      const now = new Date();
      if (modifiedStr.includes('hour')) {
        const hours = parseInt(modifiedStr);
        return now.getTime() - hours * 60 * 60 * 1000;
      } else if (modifiedStr.includes('day')) {
        const days = parseInt(modifiedStr);
        return now.getTime() - days * 24 * 60 * 60 * 1000;
      } else if (modifiedStr.includes('week')) {
        const weeks = parseInt(modifiedStr);
        return now.getTime() - weeks * 7 * 24 * 60 * 60 * 1000;
      } else if (modifiedStr.includes('month')) {
        const months = parseInt(modifiedStr);
        return now.getTime() - months * 30 * 24 * 60 * 60 * 1000;
      }
      return 0;
    },

    // File Operations
    sortFiles() {
      this.currentFiles.sort((a, b) => {
        switch (this.sortBy) {
          case 'name':
            return a.name.localeCompare(b.name);
          case 'date':
            return (
              this.getModifiedTimestamp(b.modifiedAt || b.modifiedDate) - this.getModifiedTimestamp(a.modifiedAt || a.modifiedDate)
            );
          case 'size':
            return (Number(a.sizeBytes) || this.parseSize(a.size)) - (Number(b.sizeBytes) || this.parseSize(b.size));
          case 'type':
            return a.typeLabel.localeCompare(b.typeLabel);
          default:
            return 0;
        }
      });
    },

    parseSize(sizeStr) {
      const parts = String(sizeStr || '0 B').split(' ');
      const value = parseFloat(parts[0]) || 0;
      const unit = parts[1];

      switch (unit) {
        case 'KB':
          return value * 1024;
        case 'MB':
          return value * 1024 * 1024;
        case 'GB':
          return value * 1024 * 1024 * 1024;
        case 'TB':
          return value * 1024 ** 4;
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
      if (this.allVisibleSelected) {
        this.selectedFiles = [];
      } else {
        this.selectedFiles = this.currentFiles.map((f) => f.id);
      }
    },

    toggleSelectAll() {
      this.selectAll();
    },

    // Navigation
    openFolder(folder) {
      this.currentFolder = folder;
      this.currentFiles = this.allFiles.filter((f) => f.folder === folder.name);
      // Replace breadcrumbs properly - don't just push
      this.breadcrumbs = [
        { name: 'My Files', path: '/' },
        { name: folder.name, path: `/${folder.name}` },
      ];
      this.selectedFiles = [];
      this.filterFiles();
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
        const quickAccessItem = this.quickAccess.find((q) => q.name === folderName);
        if (quickAccessItem) {
          this.navigateToQuickAccessItem(quickAccessItem);
          return;
        }

        // Otherwise it's a regular folder
        const folder = this.folders.find((f) => f.name === folderName);
        if (folder) {
          this.currentFolder = folder;
          this.currentFiles = this.allFiles.filter((f) => f.folder === folder.name);
        } else {
          // Fallback to root if folder not found
          this.currentFolder = null;
          this.currentFiles = [...this.allFiles];
          this.breadcrumbs = [{ name: 'My Files', path: '/' }];
        }
      }

      this.selectedFiles = [];
      this.filterFiles();
    },

    navigateToQuickAccess(item) {
      this.navigateToQuickAccessItem(item);
    },

    navigateToQuickAccessItem(item) {
      // Set proper breadcrumbs for quick access
      this.breadcrumbs = [
        { name: 'My Files', path: '/' },
        { name: item.name, path: `/${item.type}` },
      ];
      this.currentFolder = null;

      switch (item.type) {
        case 'recent':
          this.currentFiles = [...this.recentFiles];
          break;
        case 'images':
          this.currentFiles = this.allFiles.filter((f) => f.type === 'image');
          break;
        case 'documents':
          this.currentFiles = this.allFiles.filter((f) => f.type === 'document');
          break;
        case 'shared':
          this.currentFiles = this.allFiles.filter((f) => f.folder === 'Shared');
          break;
        case 'trash':
          this.currentFiles = [];
          break;
        default:
          this.currentFiles = [...this.allFiles];
      }

      this.selectedFiles = [];
      this.filterFiles();
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
        if (file.type === 'image') {
          Swal.fire({
            titleText: file.name,
            imageUrl: file.url,
            imageAlt: file.name,
            width: 'auto',
            padding: '1em',
            showConfirmButton: false,
            showCloseButton: true,
            background: 'transparent',
            customClass: {
              popup: 'bg-body text-body rounded-4 shadow-lg border-0',
              image: 'img-fluid rounded',
              title: 'text-body-emphasis fs-5 mb-2',
            },
          });
        } else {
          Swal.fire({
            titleText: `Opening ${file.name}`,
            html: `
              <div class="text-start">
                <p><strong>📁 File:</strong> ${this.escapeHtml(file.name)}</p>
                <p><strong>📏 Size:</strong> ${this.escapeHtml(file.size)}</p>
                <p><strong>📅 Modified:</strong> ${this.escapeHtml(file.modifiedDate)}</p>
                <p><strong>📂 Folder:</strong> ${this.escapeHtml(file.folder)}</p>
                <p><strong>🏷️ Type:</strong> ${this.escapeHtml(file.typeLabel)}</p>
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
              htmlContainer: 'text-body',
            },
            buttonsStyling: false,
            background: 'transparent',
          }).then((result) => {
            if (result.isConfirmed) {
              window.open(file.url, '_blank', 'noopener,noreferrer');
            }
          });
        }
      } else {
        window.open(file.url, '_blank', 'noopener,noreferrer');
      }
    },

    async downloadFile(file) {
      if (this.busyFiles[file.id]) return;
      this.busyFiles[file.id] = true;
      let url;
      try {
        const response = await fetch(file.isAsset ? file.url : '/api/files/download?id=' + encodeURIComponent(file.id), {
          headers: { Accept: 'application/json' },
        });
        if (!response.ok || response.redirected) throw new Error(await this.responseError(response, 'Download failed'));
        url = window.URL.createObjectURL(await response.blob());
        const a = document.createElement('a');
        a.href = url;
        a.download = file.name;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
      } catch (error) {
        this.showNotification(error.message || 'Download failed', 'error');
      } finally {
        if (url) window.URL.revokeObjectURL(url);
        delete this.busyFiles[file.id];
      }
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
            htmlContainer: 'text-body-secondary',
          },
          buttonsStyling: false,
          background: 'transparent',
        }).then((result) => {
          if (result.isConfirmed) {
            this.showNotification(
              `📦 Creating ZIP archive with ${this.selectedFiles.length} files...`,
              'info'
            );
            this.performZipDownload();
          }
        });
      } else {
        this.performZipDownload();
      }
    },

    renameFile(file) {
      if (file.isAsset) {
        this.showNotification('Cannot rename core asset files', 'warning');
        return;
      }
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
            htmlContainer: 'text-body-secondary',
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
          },
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
      if (this.downloading) return;
      this.downloading = true;
      try {
        const filesToDownload = this.currentFiles
          .filter((f) => this.selectedFiles.includes(f.id))
          .map((f) => f.id);

        const response = await fetch('/api/files/download-zip', {
          method: 'POST',
          headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document
              .querySelector('meta[name="csrf-token"]')
              ?.getAttribute('content'),
          },
          body: JSON.stringify({ ids: filesToDownload }),
        });

        if (response.ok && !response.redirected) {
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
          this.showNotification(await this.responseError(response, 'Failed to create ZIP'), 'error');
        }
      } catch (error) {
        console.error('Download failed', error);
        this.showNotification(error.message || 'Download failed', 'error');
      } finally {
        this.downloading = false;
      }
    },

    async responseError(response, fallback) {
      const data = await response.json().catch(() => ({}));
      return (data.errors ? Object.values(data.errors).flat().join(' ') : '') || data.message || data.error || fallback;
    },

    async performFileRename(file, oldName, newName) {
      if (file.isAsset || this.busyFiles[file.id]) return false;
      this.busyFiles[file.id] = true;
      try {
        const response = await fetch('/api/files/rename', {
          method: 'POST',
          headers: {
            Accept: 'application/json', 'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
          },
          body: JSON.stringify({ id: file.id, newName: String(newName || '').trim() }),
        });
        if (!response.ok || response.redirected) throw new Error(await this.responseError(response, 'Failed to rename file'));
        const result = await response.json();
        await this.loadFiles();
        this.showNotification(`"${oldName}" renamed to "${result.name || newName}"`, 'success');
        return true;
      } catch (error) {
        this.showNotification(error.message || 'Rename failed', 'error');
        return false;
      } finally {
        delete this.busyFiles[file.id];
      }
    },

    deleteFile(file) {
      if (file.isAsset) {
        this.showNotification('Cannot delete core asset files', 'warning');
        return;
      }
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
            htmlContainer: 'text-body-secondary',
          },
          buttonsStyling: false,
          background: 'transparent',
        }).then((result) => {
          if (result.isConfirmed) {
            this.performFileDelete(file);
          }
        });
      } else {
        if (confirm(`Are you sure you want to delete "${file.name}"?`)) {
          this.performFileDelete(file);
        }
      }
    },

    async performFileDelete(file, refresh = true) {
      if (file.isAsset || this.busyFiles[file.id]) return false;
      this.busyFiles[file.id] = true;
      try {
        const response = await fetch('/api/files', {
          method: 'DELETE',
          headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document
              .querySelector('meta[name="csrf-token"]')
              ?.getAttribute('content'),
          },
          body: JSON.stringify({ id: file.id }),
        });
        if (!response.ok || response.redirected) throw new Error(await this.responseError(response, 'Delete failed'));
        this.selectedFiles = this.selectedFiles.filter((id) => id !== file.id);
        if (refresh) {
          await this.loadFiles();
          this.showNotification('File deleted successfully', 'success');
        }
        return true;
      } catch (error) {
        console.error('Delete failed', error);
        this.showNotification(error.message || 'Delete failed', 'error');
        return false;
      } finally {
        delete this.busyFiles[file.id];
      }
    },

    async setLoginBackground(file) {
      if (this.busySettings.background) return;
      this.busySettings.background = true;
      try {
        const response = await fetch('/api/files/login-background', {
          method: 'POST',
          headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document
              .querySelector('meta[name="csrf-token"]')
              ?.getAttribute('content'),
          },
          body: JSON.stringify({ file_id: String(file.id), url: file.url }),
        });
        if (!response.ok || response.redirected) throw new Error(await this.responseError(response, 'Failed to update background'));
        this.showNotification('Login background updated', 'success');
        await this.loadFiles();
      } catch (error) {
        console.error('Failed to set login background', error);
        this.showNotification(error.message || 'Failed to update background', 'error');
      } finally {
        delete this.busySettings.background;
      }
    },

    async setDefaultImage(file, targetName) {
      if (this.busySettings[targetName]) return;
      this.busySettings[targetName] = true;
      try {
        const response = await fetch('/api/files/set-default-image', {
          method: 'POST',
          headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document
              .querySelector('meta[name="csrf-token"]')
              ?.getAttribute('content'),
          },
          body: JSON.stringify({ file_id: file.id, target_name: targetName }),
        });

        if (response.ok && !response.redirected) {
          const result = await response.json();
          if (targetName === 'default_avatar.jpeg') window.ProfileImages?.setDefaultAvatar(result.url);
          this.showNotification(`Default image ${targetName} updated`, 'success');
          await this.loadFiles();
        } else {
          this.showNotification(await this.responseError(response, 'Failed to update default image'), 'error');
        }
      } catch (error) {
        console.error('Failed to update default image', error);
        this.showNotification(error.message || 'Failed to update default image', 'error');
      } finally {
        delete this.busySettings[targetName];
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
            htmlContainer: 'text-body-secondary',
          },
          buttonsStyling: false,
          background: 'transparent',
        }).then(async (result) => {
          if (result.isConfirmed) {
            await this.performSelectedDelete();
          }
        });
      } else {
        if (confirm(`Are you sure you want to delete ${this.selectedFiles.length} files?`)) {
          this.performSelectedDelete();
        }
      }
    },

    async performSelectedDelete() {
      if (this.bulkDeleting) return;
      const files = this.currentFiles.filter(file => this.selectedFiles.includes(file.id) && !file.isAsset);
      if (!files.length) {
        this.showNotification('Cannot delete core asset files', 'warning');
        return;
      }
      this.bulkDeleting = true;
      try {
        const protectedCount = this.currentFiles.filter(file => this.selectedFiles.includes(file.id) && file.isAsset).length;
        const results = await Promise.all(files.map(file => this.performFileDelete(file, false)));
        await this.loadFiles();
        const count = results.filter(Boolean).length;
        if (count) this.showNotification(`${count} file(s) deleted successfully`, 'success');
        if (protectedCount) this.showNotification('Core asset files were not deleted', 'warning');
      } finally {
        this.bulkDeleting = false;
      }
    },

    // File Management
    uploadFile() {
      if (this.uploading) return;
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          title: 'Upload Files',
          html: `
            <div class="text-start">
              <div class="mb-3">
                <label class="form-label">Select files to upload:</label>
                <input type="file" class="form-control bg-body text-body border-secondary border-opacity-25" multiple accept="*/*" id="fileUpload">
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
            htmlContainer: 'text-body',
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
          },
        }).then((result) => {
          if (result.isConfirmed) {
            if (this.uploading) return;
            const files = result.value;
            this.uploading = true;
            this.showNotification(`☁️ Uploading ${files.length} files...`, 'info');

            Promise.allSettled(
              files.map((file) => {
                const formData = new FormData();
                formData.append('file', file);
                return fetch('/api/files/upload', {
                  method: 'POST',
                  credentials: 'same-origin',
                  headers: {
                    'X-CSRF-TOKEN': document
                      .querySelector('meta[name="csrf-token"]')
                      ?.getAttribute('content'),
                    Accept: 'application/json',
                  },
                  body: formData,
                }).then(async (response) => {
                  if (!response.ok || response.redirected) {
                    const err = await response.json().catch(() => ({ message: 'Upload failed' }));
                    throw new Error(err.message || 'Upload failed');
                  }
                  return response.json();
                });
              })
            )
              .then(async results => {
                await this.loadFiles();
                const succeeded = results.filter(result => result.status === 'fulfilled').length;
                if (succeeded) this.showNotification(`${succeeded} file(s) uploaded successfully`, 'success');
                const failed = results.find(result => result.status === 'rejected');
                if (failed) this.showNotification(failed.reason.message || 'Some uploads failed', 'error');
              })
              .catch((error) => {
                console.error('Upload failed', error);
                this.showNotification(error.message || 'Upload failed', 'error');
              })
              .finally(() => { this.uploading = false; });
          }
        });
      } else {
        this.showNotification('File upload dialog would open here', 'info');
      }
    },

    async refreshFiles() {
      if (await this.loadFiles()) this.showNotification('Files refreshed successfully', 'success');
    },

    showNotification(message, type = 'info') {
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          titleText: message,
          icon:
            type === 'success'
              ? 'success'
              : type === 'error'
                ? 'error'
                : type === 'warning'
                  ? 'warning'
                  : 'info',
          toast: true,
          position: 'top-end',
          showConfirmButton: false,
          timer: 3000,
          background: 'transparent',
          customClass: {
            popup: 'colored-toast bg-body text-body shadow-lg rounded-3 border-0',
            title: 'text-body-emphasis',
          },
        });
      } else {
        alert(message);
      }
    },
  }));

  // Search component for header
  Alpine.data(
    'searchComponent',
    createSearchComponent({
      minLength: 3,
      getResults(query) {
        const q = query.toLowerCase();
        return [
          { title: 'Calendar Events', url: '/admin/calendar', type: 'Page' },
          { title: 'File Manager', url: '/admin/files', type: 'Page' },
          { title: 'User Settings', url: '/settings', type: 'Page' },
        ].filter((item) => item.title.toLowerCase().includes(q));
      },
    })
  );

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
    },
  }));
});
