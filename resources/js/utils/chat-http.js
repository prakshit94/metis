// Keep chat transport in the application's built assets, with the existing response shape.
async function request(method, url, body, options = {}) {
  const target = new URL(url, window.location.origin);
  if (target.origin !== window.location.origin)
    throw new Error('Chat requests must stay on this application.');
  Object.entries(options.params || {}).forEach(([key, value]) => {
    if (value != null) target.searchParams.set(key, value);
  });
  const headers = { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
  if (csrf) headers['X-CSRF-TOKEN'] = csrf;
  const form = body instanceof FormData;
  if (body != null && !form) headers['Content-Type'] = 'application/json';
  const response = await fetch(target.href, {
    method,
    headers,
    credentials: 'same-origin',
    body: body == null ? undefined : form ? body : JSON.stringify(body),
  });
  let data,
    invalid = false;
  if (options.responseType === 'blob' && response.ok) data = await response.blob();
  else {
    const raw = await response.text();
    try {
      data = raw ? JSON.parse(raw) : {};
    } catch {
      invalid = true;
      data = {
        message: 'Your chat session or response is unavailable. Refresh the page and try again.',
      };
    }
  }
  const result = {
    data,
    status: response.status,
    headers: Object.fromEntries(response.headers.entries()),
  };
  if (!response.ok || invalid) {
    const error = new Error(data?.message || 'Chat request failed.');
    error.response = result;
    throw error;
  }
  return result;
}
export const chatHttp = {
  get: (url, options) => request('GET', url, null, options),
  post: (url, body, options) => request('POST', url, body, options),
  put: (url, body, options) => request('PUT', url, body, options),
  delete: (url, options) => request('DELETE', url, null, options),
};
export const chatStorage = {
  getItem(key) {
    try {
      return localStorage.getItem(key);
    } catch {
      return null;
    }
  },
  setItem(key, value) {
    try {
      localStorage.setItem(key, value);
    } catch {}
  },
  removeItem(key) {
    try {
      localStorage.removeItem(key);
    } catch {}
  },
};
