<?php
$file = 'resources/js/components/villages.js';
$content = file_get_contents($file);

$search1 = <<<TXT
    init() {
      this.loadServicesOptions();
      this.loadVillages();
      window.addEventListener('village-updated', () => this.loadVillages());
    },
TXT;

$replace1 = <<<TXT
    init() {
      this.loadServicesOptions();
      this.loadVillages();
      window.addEventListener('village-updated', () => this.loadVillages());
      
      const pendingSync = localStorage.getItem('syncingPincodes');
      if (pendingSync) {
          if (pendingSync !== 'ALL') this.searchQuery = pendingSync;
          setTimeout(() => this.syncPincodes(), 500);
      }
    },
TXT;

$search2 = <<<TXT
    async syncPincodes() {
      try {
        this.syncing = true;
        this.stopSyncing = false;
        
        let url = '/api/villages/sync-indiapost';
TXT;

$replace2 = <<<TXT
    async syncPincodes() {
      window.isNavigating = false;
      const unloadHandler = () => { window.isNavigating = true; };
      window.addEventListener('beforeunload', unloadHandler);

      try {
        this.syncing = true;
        this.stopSyncing = false;
        
        localStorage.setItem('syncingPincodes', (this.searchQuery && /^\d+$/.test(this.searchQuery)) ? this.searchQuery : 'ALL');

        let url = '/api/villages/sync-indiapost';
TXT;

$search3 = <<<TXT
            if (this.stopSyncing) {
                showToast('Sync stopped by user.', 'info');
                break;
            }
            
            if (res.message && res.message.includes('stopped to prevent timeout')) {
                showToast('Syncing batch... please wait', 'info');
                // Small pause to prevent hammering the server
                await new Promise(r => setTimeout(r, 1000));
            } else {
                showToast(res.message || 'Synced successfully.', 'success');
                isFinished = true;
            }
TXT;

$replace3 = <<<TXT
            if (this.stopSyncing) {
                showToast('Sync stopped by user.', 'info');
                localStorage.removeItem('syncingPincodes');
                break;
            }
            
            if (res.message && res.message.includes('stopped to prevent timeout')) {
                showToast('Syncing batch... please wait', 'info');
                // Small pause to prevent hammering the server
                await new Promise(r => setTimeout(r, 1000));
            } else {
                showToast(res.message || 'Synced successfully.', 'success');
                localStorage.removeItem('syncingPincodes');
                isFinished = true;
            }
TXT;

$search4 = <<<TXT
      } catch (err) {
        showToast(err.message || 'Failed to sync pincodes.', 'danger');
      } finally {
        this.syncing = false;
        this.stopSyncing = false;
      }
    },
TXT;

$replace4 = <<<TXT
      } catch (err) {
        if (!window.isNavigating) {
            localStorage.removeItem('syncingPincodes');
            showToast(err.message || 'Failed to sync pincodes.', 'danger');
        }
      } finally {
        window.removeEventListener('beforeunload', unloadHandler);
        if (!window.isNavigating) {
            this.syncing = false;
            this.stopSyncing = false;
        }
      }
    },
TXT;

$content = str_replace($search1, $replace1, $content);
$content = str_replace($search2, $replace2, $content);
$content = str_replace($search3, $replace3, $content);
$content = str_replace($search4, $replace4, $content);

file_put_contents($file, $content);
echo "Patched JS successfully\n";
