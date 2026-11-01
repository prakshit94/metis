import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

const partial = fs.readFileSync(new URL('../../resources/views/partials/loading-screen-script.blade.php', import.meta.url), 'utf8');
const script = partial.match(/<script>([\s\S]*?)<\/script>/)[1];
const layout = fs.readFileSync(new URL('../../resources/views/layouts/app.blade.php', import.meta.url), 'utf8');
assert.ok(layout.includes("@include('partials.loading-screen-script')"));

function page(readyState = 'loading', hasScreen = true) {
    const documentEvents = new Map();
    const windowEvents = new Map();
    const timers = [];
    const screen = { hidden: false, removed: false, attributes: {}, classList: { add(value) { screen.hidden = value === 'hidden'; } }, setAttribute(key, value) { this.attributes[key] = value; }, remove() { this.removed = true; } };
    const document = { readyState, getElementById: () => hasScreen ? screen : null, addEventListener: (event, fn) => documentEvents.set(event, fn), removeEventListener: event => documentEvents.delete(event) };
    const window = { addEventListener: (event, fn) => windowEvents.set(event, fn), removeEventListener: event => windowEvents.delete(event) };
    // Deliberately no AdminApp, Alpine or bundle execution in this context.
    vm.runInNewContext(script, { document, window, setTimeout: fn => timers.push(fn) });
    return { document, screen, timers, documentEvents, windowEvents };
}

const stalledBundle = page();
assert.equal(stalledBundle.screen.hidden, false);
stalledBundle.document.readyState = 'interactive';
stalledBundle.documentEvents.get('readystatechange')();
assert.equal(stalledBundle.screen.hidden, true);
assert.equal(stalledBundle.screen.attributes['aria-hidden'], 'true');
assert.equal(stalledBundle.documentEvents.size, 0);
assert.equal(stalledBundle.windowEvents.size, 0);
stalledBundle.timers[0]();
assert.equal(stalledBundle.screen.removed, true);
console.log('PASS: loader dismisses when HTML is parsed even if main bundle never runs');

for (const state of ['interactive', 'complete']) {
    const loaded = page(state);
    assert.equal(loaded.screen.hidden, true);
    assert.equal(loaded.timers.length, 1);
}
console.log('PASS: late script execution dismisses immediately with no duplicate cleanup');
const restored = page();
restored.document.readyState = 'complete';
restored.windowEvents.get('pageshow')();
assert.equal(restored.screen.hidden, true);
restored.timers[0]();
assert.equal(restored.screen.removed, true);
console.log('PASS: restored page clears overlay without relying on transition events');
assert.doesNotThrow(() => page('complete', false));
console.log('PASS: already removed loader is harmless');
