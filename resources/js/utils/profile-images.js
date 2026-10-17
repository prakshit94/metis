// Shared display-only avatar handling. Personal photos always take priority.
const genericAvatar = '/assets/images/default_avatar.jpeg';
const failedUrls = new Set();
const builtInAvatars = new Set([genericAvatar, '/assets/images/default_male.png', '/assets/images/default_female.png']);

export function normalizePhotoUrl(value) {
  if (typeof value !== 'string' || !value.trim()) return '';
  let path = value.trim().replaceAll('\\', '/');
  if (path.startsWith('blob:')) return path;
  if (/^https?:\/\//i.test(path) || path.startsWith('//')) {
    try {
      const url = new URL(path, window.location.origin);
      if ([window.location.hostname, 'localhost', '127.0.0.1'].includes(url.hostname)) return url.pathname + url.search;
      return url.href;
    } catch (_) { return ''; }
  }
  if (/^[a-z][a-z\d+.-]*:/i.test(path)) return '';
  path = path.replace(/^\/?public\//, '').replace(/^\/+/, '');
  return /^(storage|assets|api\/files)\//.test(path) ? '/' + path : '/storage/' + path;
}

export function defaultUserPhoto(user) {
  const configured = normalizePhotoUrl(document.querySelector('meta[name="default-avatar"]')?.content);
  const genderDefault = user?.gender === 'Male' ? '/assets/images/default_male.png'
    : user?.gender === 'Female' ? '/assets/images/default_female.png' : genericAvatar;
  return [configured, genderDefault, genericAvatar].find(url => url && !failedUrls.has(url)) || genericAvatar;
}

export function userPhotoUrl(user) {
  const photo = normalizePhotoUrl(user?.photo || user?.avatar);
  // Legacy records sometimes contain a built-in placeholder as their photo.
  const configured = document.querySelector('meta[name="default-avatar"]')?.content;
  if (!photo || failedUrls.has(photo) || (builtInAvatars.has(photo) && configured)) return defaultUserPhoto(user);
  return photo;
}

export function handlePhotoError(event, user) {
  const image = event.target;
  const source = image.getAttribute('src');
  if (source) failedUrls.add(source);
  const fallback = defaultUserPhoto(user);
  if (fallback !== source) image.setAttribute('src', fallback);
  else image.style.visibility = 'hidden';
}

export function setDefaultAvatar(url, broadcast = true) {
  const meta = document.querySelector('meta[name="default-avatar"]');
  if (!meta) return;
  const normalized = normalizePhotoUrl(url);
  if (meta.content === normalized) return;
  meta.content = normalized;
  failedUrls.clear();
  refreshProfileImages();
  if (broadcast) {
    try { localStorage.setItem('metis:default-avatar', JSON.stringify({url:meta.content, at:Date.now()})); } catch (_) {}
  }
}

export function refreshProfileImages() {
  document.querySelectorAll('img[data-profile-avatar]').forEach(image => {
    image.style.visibility = '';
    image.setAttribute('src', userPhotoUrl({photo:image.dataset.profilePhoto, gender:image.dataset.profileGender}));
  });
}

export function updateUserPhoto(user) {
  if (user?.id == null) return;
  document.querySelectorAll('img[data-profile-avatar]').forEach(image => {
    if (String(image.dataset.profileUserId) !== String(user.id)) return;
    image.dataset.profilePhoto = user.photo || '';
    if (user.gender != null) image.dataset.profileGender = user.gender;
    image.style.visibility = '';
    image.setAttribute('src', userPhotoUrl({photo:user.photo, gender:image.dataset.profileGender}));
  });
}
