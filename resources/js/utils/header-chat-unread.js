import { chatHttp, chatStorage } from './chat-http.js';

export function headerChatUnread(initialCount, userId, enabled = true) {
  return {
    chatUnread: Math.max(0, Number(initialCount) || 0),
    busy: false,
    stopped: false,
    initialized: false,
    revision: 0,
    retryAt: 0,
    timer: null,
    key: `metis_chat_unread_${userId}`,
    init() {
      if (this.initialized || !enabled) return;
      this.initialized = true;
      // Server-rendered state wins over potentially stale cached counts.
      this.onUnread = (event) => {
        if (event.detail?.count == null) return;
        this.revision++;
        this.setCount(event.detail.count);
      };
      this.onStorage = (event) => {
        if (event.key !== this.key || event.newValue == null) return;
        this.revision++;
        this.setCount(event.newValue, false);
      };
      this.onWake = () => this.refresh();
      window.addEventListener('metis:chat-unread', this.onUnread);
      window.addEventListener('storage', this.onStorage);
      window.addEventListener('online', this.onWake);
      document.addEventListener('visibilitychange', this.onWake);
      // The chat workspace already publishes the same count after each poll.
      if (document.body.dataset.page !== 'chat') {
        this.refresh();
        this.timer = setInterval(() => this.refresh(), 15000);
      }
    },
    setCount(value, persist = true) {
      const count = Number(value);
      if (!Number.isFinite(count) || count < 0) return;
      this.chatUnread = Math.floor(count);
      if (persist) chatStorage.setItem(this.key, String(this.chatUnread));
    },
    async refresh() {
      if (
        !enabled ||
        this.stopped ||
        this.busy ||
        document.hidden ||
        document.body.dataset.page === 'chat' ||
        Date.now() < this.retryAt
      )
        return;
      this.busy = true;
      const revision = this.revision;
      try {
        const response = await chatHttp.get('/api/chat/unread-count');
        if (this.stopped || document.hidden || revision !== this.revision) return;
        // The original `unread` field counts conversations; the badge counts messages.
        if (response.data.unread_messages != null) this.setCount(response.data.unread_messages);
      } catch (error) {
        if ([401, 403, 404, 419].includes(error.response?.status)) this.stopped = true;
        const seconds = Number(error.response?.headers?.['retry-after']) || 30;
        this.retryAt = Date.now() + Math.max(15000, Math.min(300000, seconds * 1000));
      } finally {
        this.busy = false;
      }
    },
    destroy() {
      this.stopped = true;
      clearInterval(this.timer);
      window.removeEventListener('metis:chat-unread', this.onUnread);
      window.removeEventListener('storage', this.onStorage);
      window.removeEventListener('online', this.onWake);
      document.removeEventListener('visibilitychange', this.onWake);
    },
  };
}
