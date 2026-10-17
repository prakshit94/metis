// Run: node tests/regression/clipboard.cjs. No server or database required.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const layout = fs.readFileSync('resources/views/layouts/app.blade.php', 'utf8');
const source = layout.slice(layout.indexOf('window.copyText ='), layout.indexOf('</script>', layout.indexOf('window.copyText =')));

async function scenario({native, fallback = false, modal = false, throws = false}) {
  let host, removed = false, focused = false, restored = false, alerts = 0, copied;
  const modalHost = {appendChild: () => {host = 'modal';}};
  const active = {closest: () => modal ? modalHost : null, focus: () => {restored = true;}};
  const textarea = {
    style: {}, setAttribute() {}, focus: () => {focused = true;},
    select() {}, setSelectionRange() {}, remove: () => {removed = true;},
  };
  const context = {
    window: {alert: () => alerts++},
    navigator: native ? {clipboard: {writeText: value => {copied = value; return native();}}} : {},
    document: {
      activeElement: active, getSelection: () => null,
      querySelector: () => modal ? modalHost : null,
      createElement: () => textarea,
      body: {appendChild: () => {host = 'body';}},
      execCommand: command => {
        assert.equal(command, 'copy');
        assert.equal(focused, true);
        if (throws) throw Error('Blocked');
        return fallback;
      },
    },
  };
  vm.runInNewContext(source, context);
  const success = await context.window.copyText('GJ161020261');
  assert.equal(removed, true);
  assert.equal(restored, true);
  assert.equal(host, modal ? 'modal' : 'body');
  if (native) assert.equal(copied, 'GJ161020261');
  return {success, alerts};
}
(async () => {
  assert.deepEqual(await scenario({native: () => Promise.resolve()}), {success: true, alerts: 0});
  assert.deepEqual(await scenario({fallback: true}), {success: true, alerts: 0});
  assert.deepEqual(await scenario({fallback: true, modal: true}), {success: true, alerts: 0});
  assert.deepEqual(await scenario({native: () => Promise.reject(Error('Denied')), fallback: true}), {success: true, alerts: 0});
  assert.deepEqual(await scenario({native: () => {throw Error('Denied');}, fallback: true}), {success: true, alerts: 0});
  assert.deepEqual(await scenario({native: () => Promise.reject(Error('Denied'))}), {success: false, alerts: 1});
  assert.deepEqual(await scenario({throws: true}), {success: false, alerts: 1});
  await new Promise(resolve => setImmediate(resolve));
  console.log('PASS: native success, HTTP fallback, modal host, native rejection, synchronous denial, failure feedback, cleanup and focus restoration.');
})().catch(error => {console.error(error); process.exitCode = 1;});
