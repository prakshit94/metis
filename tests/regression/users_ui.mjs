import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

const components = new Map(), requests = [], notices = [];
const modal = { dataset: { canSyncRoles: '1', canActivate: '1', defaultTeam: '7' } };
let activeForm, activeTable;
let reply = { data: {} };
const context = {
  Alpine: { data: (name, factory) => components.set(name, factory), $data: el => el === modal ? activeForm : activeTable },
  createUserActionMenu: () => ({}),
  Modal: { getOrCreateInstance: () => ({ show() {}, hide() {} }) },
  Swal: { fire: async () => ({ isConfirmed: true }) },
  window: { ProfileImages: { updateUserPhoto() {} }, location: { href: '' } },
  document: {
    hidden: false,
    addEventListener: (event, callback) => { if (event === 'alpine:init') callback(); },
    querySelector: selector => selector === '[x-data="userForm"]' || selector === '#userModal' ? modal : null,
    querySelectorAll: () => [],
    getElementById: id => id === 'userModal' ? modal : null,
  },
  fetch: async (url, options = {}) => { requests.push({ url, ...options }); return { ok: true, text: async () => JSON.stringify(reply) }; },
  FormData, URL, URLSearchParams, Blob, console, setTimeout: () => {},
};
vm.runInNewContext(fs.readFileSync(new URL('../../resources/js/components/users.js', import.meta.url), 'utf8').replace(/^import .*;\n/gm, ''), context);
// Use a simple capture to test the component's messages without rendering Bootstrap.
vm.runInNewContext('showToast = (message, type) => captureToast(message, type)', Object.assign(context, { captureToast: (message, type) => notices.push({message,type}) }));
activeForm = components.get('userForm')();
activeTable = components.get('userTable')();
assert.equal(activeTable.securityFilter, '');
activeForm.resetForm();
assert.equal(activeForm.form.team_id, '7');
activeTable.$nextTick = () => {};
const sourceUser = {
  id: 42, name: 'Fixture User', first_name: 'Fixture', last_name: 'User', email: 'fixture@example.invalid', is_active: true,
  roles: [{name: 'User', pivot: {team_id: 7}}, {name: 'Reviewer', pivot: {team_id: 7}}],
  permissions: [{name: 'fixture-grant'}], two_factor_enabled: true, two_factor_method: 'email',
};
const user = activeTable._mapUser(sourceUser);
assert.equal(user.permissions.length, 1);
assert.equal(user.two_factor_enabled, true);
assert.equal(user.two_factor_method, 'email');
activeTable.editUser(user);
activeForm.form.first_name = 'Updated';
await activeForm.saveUser();
let payload = requests.at(-1).body;
assert.equal(payload.get('first_name'), 'Updated');
for (const field of ['roles[]', 'team_id', 'permissions[]', 'is_active']) assert.equal(payload.has(field), false, `unchanged ${field} must be omitted`);
assert.equal(requests.at(-1).cache, 'no-store');
activeTable.editUser(user);
activeForm.form.role = 'Manager';
await activeForm.saveUser();
payload = requests.at(-1).body;
assert.deepEqual(payload.getAll('roles[]'), ['Manager', 'Reviewer']);
assert.equal(payload.has('permissions[]'), false);
assert.equal(payload.get('team_id'), '7');
console.log('PASS: ordinary profile saves preserve assignments; explicit primary-role edits preserve additional roles');
modal.dataset.canSyncRoles = '0'; modal.dataset.canActivate = '0';
activeTable.editUser(user);
activeForm.form.role = 'Super Admin'; activeForm.form.is_active = false;
await activeForm.saveUser();
payload = requests.at(-1).body;
assert.equal(payload.has('roles[]'), false); assert.equal(payload.has('is_active'), false);
console.log('PASS: edit payload respects separate role-assignment and activation permissions');
activeForm.form.permissions = ['fixture-grant']; activeForm.form.photo = 'old-photo';
activeForm.resetForm();
assert.equal(activeForm.form.permissions.length, 0);assert.equal(activeForm.form.photo, '');
activeTable.teamFilter = '7'; activeTable.roleFilter = 'User'; activeTable.searchQuery = 'Fixture';
assert.equal(activeTable._queryParams().get('team_id'), '7');
activeTable.securityFilter = 'enabled';
assert.equal(activeTable._queryParams().get('two_factor'), 'enabled');
activeTable.users = [user]; activeTable.selectedUsers = ['42']; activeTable.sendBulkInvites();
assert.ok(context.window.location.href.startsWith('mailto:fixture@example.invalid?'));
reply = { data: [{...sourceUser, id: 50}], total: 1, last_page: 1, current_page: 1 };
activeTable.selectedUsers = ['42']; await activeTable.loadUsers();
assert.equal(activeTable.selectedUsers.length, 0);
console.log('PASS: exports retain LOB filter, invitation selections match IDs, and hidden stale selections are cleared');
const importer = components.get('importForm')();
await importer.handleFile({target: {files: [{text: async () => '\uFEFFFirst Name,Middle Name,Last Name,Email,Phone,password\r\n"First\nName",,Last,one@example.invalid,,Individual123!\r\n'}]}});
assert.equal(importer.parsedRows.length, 1);assert.equal(importer.parsedRows[0][0], 'First\nName');
importer.parsedRows = [['Missing', '', '', 'missing@example.invalid', '', '']];
const requestCount = requests.length;
await importer.importUsers();
assert.equal(requests.length, requestCount); assert.equal(importer.result.created, 0); assert.equal(importer.result.errors.length, 1);
assert.equal(vm.runInNewContext('csvEscape("=SUM(1,2)")', context), '"\'=SUM(1,2)"');
console.log('PASS: CSV handles quoted multiline fields and BOM, rejects missing passwords, and neutralizes spreadsheet formulas');
assert.equal(components.has('themeSwitch'), false); assert.equal(components.has('searchComponent'), false);
console.log('PASS: users page preserves the shared theme and search components');
reply = {data:{}};
modal.dataset.canManageSecurity = '1';
activeTable.editUser(user);
await new Promise(setImmediate);
activeForm.twoFactorRequired = true;
activeForm.securityCurrentPassword = 'fixture-admin-password';
activeForm.securityCode = 'fixture-admin-proof';
await activeForm.saveUser();
payload = requests.at(-1).body;
assert.equal(payload.get('two_factor_required'),'1');
assert.equal(payload.get('security_current_password'),'fixture-admin-password');
assert.equal(payload.get('security_code'),'fixture-admin-proof');
assert.equal(activeForm.securityCurrentPassword,'');
assert.equal(activeForm.securityCode,'');
modal.dataset.canManageSecurity = '0';
activeTable.editUser(user);
activeForm.twoFactorRequired = true;
await activeForm.saveUser();
assert.equal(requests.at(-1).body.has('two_factor_required'),false);
console.log('PASS: admin requirement proof is submitted only for authorized changes and cleared after saving');

reply = {enabled: true, method: 'email', email_available: true};
await new Promise(setImmediate);
await activeForm.loadActorSecurity();
assert.equal(activeForm.securityStatusLoaded, true);
assert.equal(activeForm.actorEmailAvailable, true);
assert.match(activeForm.adminCodeHelp, /own verified email/);
activeForm.twoFactorEnabled = true; activeForm.twoFactorMethod = 'email';
assert.equal(activeForm.enrollmentStatusLabel, 'Enabled · Email OTP');
activeForm.twoFactorMethod = 'authenticator';
assert.equal(activeForm.enrollmentStatusLabel, 'Enabled · Authenticator app');
activeForm.resetForm();
assert.equal(activeForm.enrollmentStatusLabel, 'Not enrolled');
activeForm.twoFactorRequired = true;
assert.equal(activeForm.enrollmentStatusLabel, 'Setup required at next login');
reply = {enabled: false, email_available: false};
await activeForm.loadActorSecurity();
assert.equal(activeForm.actorEmailAvailable, false);
assert.match(activeForm.adminCodeHelp, /Only your administrator password/);
const originalFetch = context.fetch;
context.fetch = async () => { throw new Error('fixture outage'); };
await activeForm.loadActorSecurity();
assert.equal(activeForm.securityStatusLoaded, false);
assert.match(activeForm.securityStatusError, /Could not load/);
modal.dataset.canManageSecurity = '1';
activeForm.originalTwoFactorRequired = false;
const beforeFailedStatusSave = requests.length;
await activeForm.saveUser();
assert.equal(requests.length, beforeFailedStatusSave);
context.fetch = originalFetch;
console.log('PASS: user forms show verified methods, distinguish required enrollment, refresh shared delivery, and block policy edits when proof status is unknown');

const usersMarkup = fs.readFileSync(new URL('../../resources/views/users/index.blade.php', import.meta.url), 'utf8');
assert.equal(usersMarkup.includes('Open my own Security page'), false);
assert.equal(usersMarkup.slice(usersMarkup.indexOf('<!-- Card 6: Account Settings -->')).includes("url('/security')"), false);
assert.ok(usersMarkup.includes('aria-controls="userTwoStepInfo"'));
assert.ok(usersMarkup.includes('@mouseenter="clearTimeout(infoCloseTimer); infoOpen = true"'));
assert.ok(usersMarkup.includes('@focus="infoOpen = true"'));
assert.ok(usersMarkup.includes('@click="infoOpen = true"'));
const infoStart = usersMarkup.indexOf('id="userTwoStepInfo"');
const proofStart = usersMarkup.indexOf('<div x-show="securityRequirementChanged"', infoStart);
assert.ok(usersMarkup.indexOf('Enrollment status', infoStart) < proofStart);
assert.ok(usersMarkup.indexOf('Recovery codes:', infoStart) < proofStart);
assert.ok(usersMarkup.indexOf('id="userSecurityPassword"', proofStart) > proofStart);
console.log('PASS: compact 2FA toggle keeps details in hover/focus/tap information panel, preserves save verification, and removes personal Security link');

assert.ok(usersMarkup.includes('<template x-teleport="#userModal">'));
assert.ok(usersMarkup.includes('class="position-fixed rounded border bg-body text-body shadow p-3"'));
assert.equal(usersMarkup.includes('position-absolute end-0 top-100'), false);
console.log('PASS: information overlay is rendered at modal root outside scrolling cards and constrained to viewport');
reply={data:[{id:8,team_id:7,name:'Fixture Office',code:'FIX',is_active:true}],last_page:1};
modal.dataset.canSyncRoles='1';activeForm.form.team_id='7';
await activeForm.loadOfficeOptions();
assert.equal(activeForm.offices[0].id,8);
activeTable.editUser({...user,office_id:8,office:reply.data[0]});
await new Promise(setImmediate);
assert.equal(activeForm.originalOffice,8);
activeForm.form.office_id='';
reply={data:{}};
await activeForm.saveUser();
assert.equal(requests.at(-1).body.get('office_id'),'');
assert.equal(requests.at(-1).body.has('roles[]'),false);
activeForm.resetForm();
assert.equal(activeForm.form.office_id,'');assert.equal(activeForm.currentOffice,null);
console.log('PASS: office assignment is preserved on edit, explicit clearing needs no role rewrite, and modal reset removes stale office state');
