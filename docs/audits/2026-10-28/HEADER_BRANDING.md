# Header branding fixes

The saved logo and default PNG both returned HTTP 200 during investigation; a missing-file failure was not reproduced. The shared header had no image-error fallback, cached custom logos using the default logo's timestamp, and forced circular presentation. Default asset URLs also depended on APP_URL despite the app being accessed using a different local host/port.

Implemented a public branding resolver for the shared header/layout: local files use same-origin paths and their own modification timestamps; missing/invalid local assets fall back to the packaged logo; external HTTP(S) logo URLs remain unchanged, including signed query strings. The header has a non-recursive runtime fallback, aspect-ratio-preserving rendering and a light image surface suitable for both themes. The loading logo uses the same resolution. Removed conflicting SVG/PNG declarations for the same favicon.

Header Targets, Files and Call Tags shortcuts now match existing endpoint permissions. Targets supports either target-view or target-view-all. Updated the Teams shortcut label to States & Offices. No permissions, saved logo settings, credentials, navigation destinations or business algorithms were changed.

Verification: 12 branding/Blade checks passed; permission, route and response contracts passed. Browser preview with a nonexistent sample identity showed the configured logo in dark/light themes, a working theme control and a visible packaged fallback when the configured image request intentionally failed. Both logo files were verified over HTTP. No real login or business mutation was performed.
