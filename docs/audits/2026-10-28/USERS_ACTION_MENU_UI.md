# Users action-menu UI follow-up

The action dropdown was inside the responsive table and an `overflow-hidden` card.
A shared `:has(.dropdown-menu.show)` rule also changed the table wrapper to visible
overflow while open. Reproduction at 1280px put the menu's right edge at 1672px,
outside both the card and the viewport. A larger z-index could not fix that clip.

The Users row menu now uses an Alpine body teleport with its original user scope,
permission conditions and action handlers. Existing Popper supplies fixed viewport
positioning, flip and overflow prevention. The menu waits until Alpine reveals it
before measuring, and a ResizeObserver recalculates positions when its size changes.
This also fixes the zero-height measurement that prevented bottom-edge flipping.

The overlay has viewport-bounded width and height, internal scrolling on short
screens, outside-click/Escape dismissal, arrow/Home/End navigation and focus return.
It follows visible triggers during scrolling, closes when its trigger moves out of
view or the viewport resizes, and cleans up observers/listeners when rows disappear.
Only one row menu remains open. Buttons with no permitted actions are hidden, and
deleted-user menus do not show an empty divider above Restore/Permanent Delete.

No backend endpoints, permissions, validation or business action implementations
were changed in this follow-up.

Verification:

- `node tests/regression/user_action_menu.mjs`: viewport configuration, keyboard
  navigation, visibility timing, scrolling, dismissal and lifecycle cleanup.
- Existing Users UI, two-factor UI and theme checks passed.
- The complete Users Blade compiled and its generated PHP passed syntax checking.
- Production build and `git diff --check` passed. The pre-existing chunk-size
  warning remains.
- Read-only browser fixtures: at 320, 375, 768 and 1280 CSS pixels, all active-user
  action items were inside the viewport and passed DOM hit tests; page width stayed
  contained. Desktop bottom-edge placement flipped to `top-end`. At 375x240 the
  menu was 224px high, scrolled internally, and keyboard focus reached the final
  Delete item with a successful hit test. Light and dark themes were checked.
- Edit still opened the correct form and closed the menu. Deleted rows showed
  only Restore and Permanent Delete. Switching row menus closed the previous menu.
  No live account was edited and no destructive action was submitted.

Fixture screenshots: [desktop](/tmp/metis-users-actions-fixed.jpg),
[mobile](/tmp/metis-users-actions-mobile-fixed.jpg).
