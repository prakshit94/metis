import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

const observers = [];
const ticks = [], frames = new Map(), calls = [], listeners = new Map();
let focused, lastObserver;
let menuHasLayout = true;
const item = (name, disabled = false, visible = true) => ({
  name, getClientRects: () => visible ? [1] : [],
  classList: {contains: value => value === 'disabled' && disabled},
  getAttribute: () => null, focus() { focused = this; context.document.activeElement = this; },
});
const first = item('first'), disabled = item('disabled', true), hidden = item('hidden', false, false), last = item('last');
let triggerLeft = 50;
const trigger = {getBoundingClientRect: () => ({left: triggerLeft,right:triggerLeft+30,top:80,bottom:110}), closest: () => null, isConnected: true, contains: target => target === trigger, focus() {focused = trigger;}};
const menu = {getClientRects: () => menuHasLayout ? [1] : [], isConnected: true, contains: target => target === menu || [first,last].includes(target), querySelectorAll: () => [first, disabled, hidden, last]};
const context = {
  ResizeObserver: class { constructor(callback) {this.callback=callback;} observe(){lastObserver=this; observers.push(this);} disconnect(){this.disconnected=true;} },
  requestAnimationFrame: callback => { const id=frames.size+1; frames.set(id,callback); return id; },
  cancelAnimationFrame: id => frames.delete(id),
  document: {activeElement: null, addEventListener: (event, fn) => listeners.set(event, fn), removeEventListener: (event, fn) => {if(listeners.get(event) === fn) listeners.delete(event);}},
  window: {innerWidth:1000,innerHeight:800,addEventListener: (event, fn) => listeners.set(event, fn), removeEventListener: (event, fn) => {if(listeners.get(event) === fn) listeners.delete(event);}},
  createPopper: (reference, floating, options) => {
    const call = {reference, floating, options, destroyed: false}; calls.push(call);
    return {destroy: () => {call.destroyed = true;}, update: async () => {}};
  },
};
vm.runInNewContext(fs.readFileSync(new URL('../../resources/js/utils/user-action-menu.js', import.meta.url), 'utf8').replace(/^import .*;\n/gm,'').replace('export function','function'),context);
const state = vm.runInNewContext('createUserActionMenu()',context);
state.$refs = {trigger,menu}; state.$nextTick = callback => ticks.push(callback);
const flush = async () => {while(ticks.length) ticks.shift()(); while(observers.length) {const observer=observers.shift(); if(!observer.disconnected) observer.callback();} await Promise.resolve(); for (const [id,callback] of frames) {frames.delete(id);callback();}};
const key = value => ({key:value, preventDefault(){this.prevented=true;}, stopPropagation(){this.stopped=true;}});
state.init(); state.show(); await flush();
assert.equal(state.open,true); assert.equal(calls[0].options.strategy,'fixed');
assert.equal(calls[0].options.modifiers.find(m => m.name === 'preventOverflow').options.altAxis,true);
state.outside({target:trigger}); assert.equal(state.open,true);
state.keydown(key('ArrowDown')); assert.equal(focused,first);
state.keydown(key('ArrowDown')); assert.equal(focused,last);
state.keydown(key('ArrowDown')); assert.equal(focused,first);
state.keydown(key('End')); assert.equal(focused,last);
state.keydown(key('Home')); assert.equal(focused,first);
state.keydown(key('Escape')); assert.equal(state.open,false); assert.equal(focused,trigger); assert.equal(calls[0].destroyed,true);
console.log('PASS: viewport positioning, visible-item keyboard navigation, Escape and trigger focus restoration');
state.show(true); await flush(); assert.equal(focused,last);
listeners.get('scroll')({target:menu}); assert.equal(state.open,true);
listeners.get('scroll')({target:{}}); assert.equal(state.open,true);
triggerLeft = 1100; listeners.get('scroll')({target:{}}); assert.equal(state.open,false); triggerLeft=50;
state.show(); await flush(); listeners.get('resize')(); assert.equal(state.open,false);
state.show(); await flush(); state.outside({target:{}}); assert.equal(state.open,false);
state.show(); await flush(); state.keydown(key('Tab')); assert.equal(state.open,false);
console.log('PASS: outside click, scroll follows visible triggers, dismisses offscreen overlays, and preserves menu scrolling');
const hiddenCount = calls.length;
menuHasLayout = false; state.show(); await flush(); assert.equal(calls.length,hiddenCount);
menuHasLayout = true; lastObserver.callback(); await Promise.resolve();
assert.equal(calls.length,hiddenCount+1); state.close();
console.log('PASS: delayed Alpine visibility cannot position a menu using zero-height geometry');
const count = calls.length; state.show(); state.close(); await flush(); assert.equal(calls.length,count);
state.show(); await flush(); state.destroy(); assert.equal(listeners.size,0); assert.equal(calls.at(-1).destroyed,true);
console.log('PASS: canceled opens and row destruction clean up Popper instances and event listeners');
const blade = fs.readFileSync(new URL('../../resources/views/users/index.blade.php',import.meta.url),'utf8');
assert.ok(blade.includes('x-data="userActionMenu"')); assert.ok(blade.includes('<template x-teleport="body">'));
assert.ok(blade.includes('aria-haspopup="menu"')); assert.ok(blade.includes('role="menuitem"'));
console.log('PASS: action markup portals to body and preserves accessible menu controls');
