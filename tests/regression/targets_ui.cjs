// Run with: node tests/regression/targets_ui.cjs
// Exercise client-side requests without writing target records.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const source = fs.readFileSync(
  path.join(__dirname, '../../resources/views/targets/index.blade.php'),
  'utf8'
);
const code = source
  .slice(source.indexOf('function targetsModule()'), source.lastIndexOf('</script>'))
  .replace('@json($availableAssignees)', '{}');
const ids = ['targetsTableBody', 'targetsStatsRow', 'targetsActions'];
const nodes = Object.fromEntries(ids.map((id) => [id, { innerHTML: 'initial' }]));
const pending = [];
const history = [];
const context = {
  document: { getElementById: (id) => nodes[id] },
  window: {
    location: { href: '' },
    history: { pushState: (_state, _unused, url) => history.push(url) },
  },
  fetch: () => new Promise((resolve, reject) => pending.push({ resolve, reject })),
  DOMParser: class {
    parseFromString(html) {
      return {
        getElementById: (id) => (html === 'missing' ? null : { innerHTML: `${html}:${id}` }),
      };
    }
  },
  URLSearchParams,
  FormData: class {
    constructor(form) {
      assert.equal(form.rendered, true);
    }
    entries() {
      return [
        ['search[]', 'First'],
        ['search[]', 'Second'],
        ['period_type', 'monthly'],
      ];
    }
  },
};
vm.createContext(context);
vm.runInContext(code, context);
const respond = (index, text, ok = true) => pending[index].resolve({ ok, text: async () => text });
(async () => {
  const ui = context.targetsModule();
  const old = ui.fetchUrl('/targets?month=1');
  const latest = ui.fetchUrl('/targets?month=2');
  respond(1, 'latest');
  await latest;
  respond(0, 'stale');
  await old;
  assert.deepEqual(history, ['/targets?month=2']);
  ids.forEach((id) => assert.equal(nodes[id].innerHTML, `latest:${id}`));
  assert.equal(ui.isFiltering, false);

  const form = { action: '/targets', rendered: false };
  ui.$nextTick = async () => {
    form.rendered = true;
  };
  let submitted;
  ui.fetchUrl = async (url) => {
    submitted = url;
  };
  await ui.filterTable(form);
  const query = new URL(submitted, 'http://localhost').searchParams;
  assert.deepEqual(query.getAll('search[]'), ['First', 'Second']);
  assert.equal(query.get('period_type'), 'monthly');

  const failedUi = context.targetsModule();
  const failure = failedUi.fetchUrl('/targets?month=3');
  respond(2, '', false);
  await failure;
  assert.equal(context.window.location.href, '/targets?month=3');
  assert.equal(failedUi.isFiltering, false);
  const missing = failedUi.fetchUrl('/targets?month=4');
  respond(3, 'missing');
  await missing;
  assert.equal(context.window.location.href, '/targets?month=4');
  console.log(
    'PASS: latest filter wins; table/stats/action scopes stay aligned; render settles before serialization; repeated selections preserved; failed and incomplete responses navigate safely.'
  );
})().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});
