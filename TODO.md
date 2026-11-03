# Redesign Resources Tab Section (Reference Design)

## Plan
1. **index.html** — Redesign the React `Resources` component:
   - Compact horizontal nav bar (`.resources-tabs`) inside a white rounded container with thin light-blue border.
   - Tab buttons: small blue text, moderate spacing; active = solid bright-blue rounded rectangle with white text, no black outline.
   - Functional tab switching: Quality Management System, Annual Report, Privacy Notice each show their own document/preview panel; only selected content shown.
   - White content card with thin light-blue border, rounded corners, compact padding.
   - Document preview cards: very light blue bg, thin light-blue border, rounded corners, small document icon on left, blue title, smaller "Click to view" text. Links to quality-policy.html / annual-report.html / ra-10173.html.
   - Keep PBAC tab + Add Document, Search, delete buttons working.

2. **styles.css** — Append override styles:
   - White rounded container, thin light-blue border, subtle shadow.
   - Compact horizontal tab bar (small blue text, active bright-blue pill, no outline).
   - White content card with thin light-blue border, rounded corners, compact padding.
   - Document preview card styling (very light blue bg, thumbnail icon left, blue title, "Click to view").
   - Very light gray-blue page background.
   - Responsive rules for mobile.

## Dependent files to edit
- index.html
- styles.css

## Follow-up steps
- Reload page in browser to verify visuals and tab switching.

## Completed alignment adjustment
- `.resources-root-wrapper` max-width changed from `1200px` to `1240px` and horizontal padding removed (`0 16px` → `0`), so the navigation/content container now matches the exact width and left/right edges of the image/banner section (`1240px`) and the ADVISORY section (`1240px`).
- No changes were made to navigation design, colors, fonts, document cards, spacing, or content.
