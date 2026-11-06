# Matrix Wave — Complete Mobile-First UI/UX Redesign Specification

**Project:** Matrix Wave (ماتركس وايف) Laravel website\
**Document type:** UI/UX specification and implementation plan\
**Scope:** Visual design and user experience only\
**Primary priority:** Excellent smartphone experience for customers and administrators

---

## 1. Objective

Redesign the existing Matrix Wave website interfaces for both customers and administrators so the entire site feels modern, professional, coherent, and as easy to use as a well-designed mobile application.

Use familiar mobile-app navigation patterns from applications such as Instagram and Facebook as usability inspiration only. Do not copy their branding, logos, or exact interfaces.

Adopt a **mobile-first** approach: design and validate smartphone layouts first, then adapt them to tablets and desktop screens.

**This task includes the visual/UI/UX redesign and the notification functionality explicitly specified in Section 7. Preserve all unrelated application functionality.**

## 2. Mandatory Constraints

1. Inspect the existing project and its files before planning or implementing changes.
2. Read all relevant files in `project_documentation` and understand their requirements and constraints.
3. Inspect every existing customer-facing, public, and administrator page in scope.
4. Preserve all existing business logic, routes, permissions, product and order behavior, validation rules, data handling, and integrations except for the notification feature explicitly defined in Section 10.
5. Do not change database schemas, migrations, models, controllers, services, or business logic for visual-only requirements. Changes to these layers are permitted only when genuinely required to implement the explicitly requested notification feature.
6. Do not remove existing functionality because it is difficult to display on a phone. Design an appropriate mobile presentation instead.
7. Do not replace the existing frontend stack or add large libraries without a clear need. Inspect the current architecture first and explain any proposed dependency.
8. Inspect existing icon, carousel, authentication, settings, order-status, and notification-related code before adding packages or duplicate implementations.
9. Prefer reusable components and consistent design tokens over duplicated markup and styling.
10. Preserve the existing Matrix Wave brand identity and any established brand colors or documented design requirements.
11. Ensure correct Arabic RTL alignment, reading order, and directional behavior wherever applicable.
12. Report unrelated functional defects separately; do not fix them within this task without approval.
13. Never claim that a test passed or a page works unless it was actually verified.
14. Keep all code clean, professionally organized, readable, maintainable, and consistent with the project's existing architecture and Laravel conventions.

## 3. Initial Inspection

Before editing files, inspect:

- All relevant files under `project_documentation`.
- All public and customer-facing pages.
- All administrator pages.
- Blade layouts, views, partials, and components.
- CSS, JavaScript, and frontend assets.
- The current frontend build system, if any.
- Existing icon and carousel libraries.
- How product images, homepage banners, and other assets are rendered.
- Existing customer navigation and the administrator drawer.
- Forms, buttons, tables, cards, dialogs, alerts, and feedback states.
- Current responsive behavior at smartphone, tablet, and desktop sizes.
- Existing design rules, brand colors, and typography.

Produce an inventory of actual pages and their responsible files. Do not assume a page or component exists based only on a filename. Inspect how it is used.

## 4. Shared Design System

Propose and implement a consistent design system that respects the current Matrix Wave brand.

### 4.1 Colors

Define or reuse:

- Primary, secondary, and accent colors.
- Page and card backgrounds.
- Primary and secondary text colors.
- Borders and dividers.
- Success, warning, and error states.
- Button, focus, hover, and active-navigation states.

Use documented brand choices and inspect the current design. Do not invent a replacement identity.

### 4.2 Typography

Use a readable Arabic-friendly font and consistent typography for:

- Page and section headings.
- Body text.
- Product names and prices.
- Form labels and hints.
- Buttons and navigation labels.

Maintain comfortable line height and correct RTL alignment.

### 4.3 Reusable Components

Create or refine consistent patterns for:

- Primary and secondary buttons.
- Icon buttons.
- Form fields.
- Product cards.
- Dashboard summary cards.
- Status badges.
- Search and filter controls.
- Navigation items.
- Admin drawer items.
- Tables and responsive lists.
- Dialogs and confirmation prompts.
- Empty, loading, success, and error states where applicable.

Keep the system straightforward and maintainable; do not over-engineer it.

## 5. Customer Interface Redesign

Redesign all existing customer-facing pages within the project, not only the homepage. Inspect the project to establish the definitive list. Relevant pages may include:

- Homepage.
- Product catalog and categories.
- Search, filtering, and sorting.
- Product details and similar products.
- Cart and checkout, if present.
- Login and registration.
- Customer account and profile.
- Customer orders and order details.
- About Us.
- Other existing public pages.

This list is illustrative; follow the actual application.

### 5.1 Mobile Bottom Navigation

On smartphone layouts, introduce a fixed bottom navigation bar for the primary customer destinations, using existing pages and routes only:

1. **Home (الرئيسية)** — home icon.
2. **Products (المنتجات)** — product/store icon.
3. **Orders (الطلبات)** — order/receipt icon.
4. **My Account (حسابي)** — user icon.

Requirements:

- Show clear icons and Arabic labels.
- Clearly indicate the active destination.
- Provide comfortable touch targets.
- Respect device safe areas.
- Add sufficient content padding so the bar never covers page content, form controls, checkout actions, or buttons.
- Preserve existing authentication and authorization behavior.
- Do not create placeholder pages or invent functionality for missing destinations. Report such gaps and request a decision.
- Adapt navigation for tablet and desktop instead of forcing a mobile-only layout onto large screens.

### 5.2 Product Discovery

Improve product browsing on mobile using:

- Consistent product cards and image ratios.
- Clear product names, prices, and availability.
- Readable category navigation and search/filter controls.
- Easy access to product details and existing actions.
- Minimal unnecessary horizontal scrolling.
- No image distortion.

Preserve all current product information and behavior.

### 5.3 Customer Forms

Improve the visual presentation of existing login, registration, account, address, and order forms:

- Clear field labels and grouping.
- Comfortable spacing and touch-friendly controls.
- Readable validation feedback.
- Correct Arabic RTL behavior.
- Consistent primary and secondary actions.
- Appropriate mobile keyboard and input behavior.

Do not alter form submission, validation rules, or backend processing.

## 6. Administrator Interface Redesign

Redesign all existing admin pages to be comfortable and efficient on smartphones as well as desktop screens. Administrators are also expected to use mobile devices frequently.

### 6.1 Preserve and Improve the Existing Admin Drawer

Keep the current admin drawer as the foundation of administrator navigation.

- Do not replace it with the customer bottom navigation.
- Preserve all existing destinations, permissions, and behavior.
- Improve spacing, readability, active-item highlighting, and visual hierarchy.
- Add consistent, meaningful icons to menu items.
- Ensure correct RTL alignment.
- Make opening, closing, scrolling, and selecting drawer items comfortable on phones and desktop screens.

### 6.2 Admin Dashboard

Improve the existing dashboard presentation without changing its data or logic:

- Responsive summary cards.
- Clear headings and section hierarchy.
- Appropriate icons.
- Readable figures.
- Responsive charts if already present.
- Well-organized recent orders or activity if already present.
- No oversized desktop layout forced onto a phone screen.

Do not invent new data or functionality.

### 6.3 Product and Order Management

Redesign existing admin product and order lists, forms, and detail pages:

- Responsive create/edit forms.
- Logical field grouping.
- Readable order details and status indicators.
- Clear action placement.
- Convert wide tables into mobile-friendly cards or use carefully controlled horizontal scrolling where appropriate.
- Keep important actions easy to reach.
- Preserve every existing admin operation.

Do not remove functionality simply to make the layout easier.

## 7. Consistent Icons Throughout the Website

Use one consistent icon library where possible. Inspect existing dependencies before adding another package.

Use suitable icons for existing actions such as:

- Add: Plus.
- Edit: Pencil/Edit.
- Delete: Trash.
- Save: Save/Check.
- Cancel/Close: X/Close.
- Search: Search.
- Filter: Filter.
- View details: Eye or an appropriate details icon.
- Back/forward navigation: directionally correct arrows.
- Notifications: Bell.
- Settings: Gear.
- Account: User.
- Orders: Receipt/Clipboard.
- Products: Box/Shopping Bag.
- Logout: Logout.

Requirements:

- Avoid mixing unrelated icon styles.
- Keep icon sizes and visual weights consistent.
- Pair icons with text labels where an icon alone could be unclear.
- Provide accessible labels for icon-only controls.
- Ensure sufficient contrast and mobile legibility.
- Respect RTL direction and expected action semantics.
- Adding an icon must not change the action's existing behavior.
- Preserve existing delete confirmations and other safeguards.

## 9. Mobile-First UI/UX Validation — Mandatory

**Smartphone testing is a mandatory acceptance requirement, not an optional final check.**

Test the customer interface and administrator interface separately at realistic smartphone viewport sizes. Do not rely only on desktop inspection or on a browser window that has merely been narrowed without checking mobile-specific behavior.

At minimum, test at these CSS viewport sizes:

- **320 × 568** — small smartphone.
- **360 × 800** — common Android smartphone.
- **390 × 844** — larger smartphone.
- **430 × 932** — large smartphone.

Use the browser's responsive/device emulation or another suitable method available in the project environment. If a real device is available, use it as an additional check rather than a substitute for documenting the viewport sizes.

For **both customer and admin interfaces**, inspect and verify:

1. No unintended horizontal overflow.
2. No clipped or overlapping text, icons, buttons, fields, or cards.
3. The customer bottom navigation is positioned correctly and does not cover content.
4. The admin drawer opens, closes, scrolls, and remains usable at small widths.
5. Primary actions can be reached and tapped comfortably.
6. Forms are readable and usable with mobile keyboards.
7. Product cards, product details, order lists, tables, and dashboard cards adapt to narrow widths.
8. RTL layout and directional controls behave correctly.
9. Sticky/fixed elements do not overlap each other or hide important content.
10. Font sizes, spacing, and touch targets remain practical.
11. Long product names, prices, status labels, and order details wrap or truncate appropriately without breaking the layout.
12. No critical action is available only through hover.
13. Existing workflows still function, including relevant customer and admin actions.

Also check tablet and desktop layouts to prevent regressions.

### Testing honesty

- Record the actual viewport size and pages tested.
- Report observed defects and whether they were fixed within the approved visual scope.
- Run available automated tests and relevant build checks when appropriate.
- Do not claim that mobile behavior was tested if only desktop rendering was inspected.
- If a route cannot be tested because authentication, test data, browser tooling, or environment access is unavailable, state the limitation and explain what remains unverified.
- Do not invent screenshots, test results, or successful outcomes.

## 10. Notification System — Required Functional Feature

Unlike the visual-only sections of this document, this section explicitly requests a functional notification system. Inspect the existing authentication, user settings, admin navigation, order workflow, and database architecture before implementation. Make only the backend/database changes that are genuinely required for these notifications; preserve unrelated behavior.

### 10.1 Notification Types and Recipients

Implement persistent, user-specific in-app notifications for the following events:

**For administrators**

1. When a new customer account is successfully registered, create a notification for the administrator indicating that a new user registered.
2. Show an unread-notification badge/count on the existing admin entry/button in the public/customer-facing site header, if that button is present. The count must represent unread admin notifications and update when practical without requiring the administrator to manually search for the event.
3. Inside the admin interface, show the number of new/unread orders on the existing Orders navigation item. Use the project's existing order semantics and clearly define whether this means unread/new orders or all pending orders; do not silently conflate the two. Prefer an unread/new-order count if the application has a reliable way to track it. If the existing system has no such concept, inspect the current order workflow and report the recommended minimal implementation before changing its meaning.
4. Clicking a notification or its related action should take the administrator to the relevant existing user or order page where possible. Do not invent routes; inspect existing routes and permissions.

**For customers**

1. When a customer successfully signs in, show a welcome/login notification to that customer. Avoid repeatedly creating persistent database notifications on every page refresh. Inspect the intended meaning of “new user” in the existing authentication flow and implement a sensible, non-spammy behavior: for example, a welcome notification for a newly registered account and/or a lightweight login confirmation per successful login session, without creating duplicate notifications on every request. If this distinction would materially change the experience, ask me before choosing.
2. When the status of one of the customer's orders changes, create a notification for that order's owner.
3. When an order is approved/accepted, notify the customer explicitly. If approval is itself represented by an order-status transition, do not create duplicate notifications for the same event.
4. Show order-related notifications within the existing **My Orders (طلباتي)** page, associated with the relevant order and clearly displaying the updated status or approval message. If a suitable existing notification area is available, reuse it; do not create an unrelated new page without need.
5. Where practical, clicking an order notification should open the relevant existing order details or My Orders destination.

### 10.2 In-App Notification UI

- Use a consistent bell/notification icon and an unread badge with a readable count.
- Show an appropriate empty state when there are no notifications.
- Visually distinguish read and unread items.
- Include a timestamp and concise Arabic message; include the related order reference where appropriate.
- Provide a clear way to mark notifications as read. If a “mark all as read” action is added, it must work reliably.
- Keep notification lists responsive, accessible, and correctly aligned for Arabic RTL.
- Do not expose one customer's notifications to another customer.
- Admin notifications must be visible only to authorized administrators.
- Do not expose private customer information in browser notification text beyond what is necessary.

### 10.3 Browser Notifications

Use the browser's standard Notification API for desktop/browser pop-up notifications when:

- The user is signed in and authorized to receive the event.
- The relevant notification preference is enabled.
- Browser notification permission has been granted.
- The browser is open and the site can receive the event.

Request permission only after a clear user action or through a polite, contextual explanation; do not repeatedly prompt or assume permission is granted. Handle denied, default, unsupported, and granted permission states gracefully. The in-app notification must still work when browser notifications are denied or unsupported.

**Important scope distinction:** Browser Notification API pop-ups while the website is open are not the same as push notifications delivered while the browser/site is closed. Do not claim closed-browser delivery unless the project already has, or the user separately approves, the required service worker, push subscription, and server-side push infrastructure. If true background push is required to meet the request, explain the necessary scope and ask before introducing that infrastructure.

### 10.4 Notification Preferences

Add notification controls to the existing customer settings page, and to administrator settings if a suitable existing settings area exists.

At minimum, allow the user to enable or disable browser pop-up notifications. Clearly distinguish:

- **In-app notifications** (stored and visible in the website, including order updates in My Orders).
- **Browser pop-up notifications** (dependent on the user's preference and browser permission).

Disabling browser pop-ups must not silently disable or delete in-app order notifications. If a global “disable all notifications” option is considered, clarify its effect and ask before implementing it. Store the user's preference using the existing settings approach or a minimal, maintainable addition appropriate to the current architecture. Respect browser permission independently; a site preference cannot override a browser-level denial.

### 10.5 Reliability and Data Integrity

- Inspect Laravel's current version and notification facilities before choosing an implementation.
- Prefer Laravel's established notification mechanisms and the project's existing database conventions where appropriate.
- Persist notifications so they remain available after navigation or signing out and back in.
- Trigger notifications from the real successful registration, login, and order-status/approval events—not merely from front-end clicks.
- Ensure a status update does not create duplicate notifications due to repeated saves, refreshes, or duplicate event handling.
- Ensure the correct recipient is selected for every event.
- Do not send a notification until the corresponding action has succeeded.
- Follow existing authorization rules for viewing, reading, and marking notifications.
- Use a simple, maintainable polling or refresh approach for updating badges if real-time infrastructure is not already installed. Do not add WebSockets or a third-party service without explaining the need and obtaining approval.
- If migrations are required, inspect the database connection and existing migration workflow first. Do not run destructive commands or reset existing data.
- Keep notification messages in Arabic and consistent with the site's RTL design.

### 10.6 Required Notification Tests

Test, as far as the environment permits:

1. A newly registered customer creates the expected admin notification.
2. The admin badge displays the correct unread count and changes after reading notifications.
3. The admin Orders item displays the intended new/unread-order count.
4. A customer receives the intended welcome/login notification without refresh-generated duplicates.
5. An order status change notifies only the customer who owns that order.
6. An order approval notifies the customer exactly once.
7. The My Orders page displays the relevant notification/status update.
8. Browser pop-ups appear only when the site preference is enabled and browser permission is granted.
9. Denied or unsupported browser notifications do not break in-app notifications.
10. Disabling browser pop-ups does not remove in-app order notifications.
11. Customers cannot access each other's notifications, and non-admin users cannot access admin notifications.
12. Badges, notification lists, settings controls, and My Orders notification UI work at the required mobile viewport sizes.

Document test data, commands, actual results, and any environment limitations. Never report unexecuted tests as passed.

## 11. Footer Attribution Update

Update the existing footer copyright/developer attribution.

The current footer text is:

`© 2026 Matrix Wave. جميع الحقوق محفوظة.`

Replace it with the following exact Arabic developer attribution:

**`تم التطوير بواسطة م/جمال الدين 770569805`**

The final footer should display the developer attribution instead of the current copyright sentence, not append it while leaving the old sentence in place.

Requirements:

- Use the exact wording and phone number shown above.
- Preserve the existing footer layout and make the updated text responsive.
- Ensure correct Arabic RTL alignment.
- Prevent the text and phone number from overflowing on narrow smartphones.
- Do not add extra claims, links, or contact behavior unless already supported and separately approved.
- Search for duplicate footer text across shared layouts and page-specific templates so the old attribution is not unintentionally left on some pages.
- Do not alter unrelated footer functionality or content.

## 12. Responsive and Cross-Device Quality

Validate the design at smartphone, tablet, and desktop sizes. Prioritize smartphones for all customer and admin pages.

Address:

- Horizontal overflow.
- Clipped content.
- Navigation overlap.
- Tiny controls.
- Wide tables.
- Incorrect RTL alignment.
- Excessive empty space.
- Banner controls obscuring content.
- Fixed elements covering actions or forms.
- Layout instability during image loading.

The website should feel like one coherent application across device sizes.

## 13. Code Organization and Maintainability

During implementation:

- Follow the existing Laravel and frontend architecture.
- Use clear names and focused components.
- Reuse shared layouts and components appropriately.
- Avoid duplicated markup and CSS.
- Avoid unnecessary abstractions and dependencies.
- Do not put new business logic into presentation templates.
- Limit changes to files necessary for the approved redesign.
- Do not perform unrelated refactoring.
- Do not change backend files or project configuration unless a genuine need is identified; if so, stop and request approval first.

## 14. Implementation Stages

Inspect the real project and propose an implementation order based on its dependencies. Potential stages include:

1. Page inventory and current design audit, including authentication, settings, order lifecycle, admin navigation, and current database/migration state.
2. Shared design tokens, typography, buttons, and icon patterns.
3. Shared customer layout and responsive foundation.
4. Customer mobile bottom navigation.
5. Product catalog, category, search, and product-detail pages.
6. Customer account, order, and form pages.
7. Notification data model and event-flow plan, identifying the minimal required backend/database changes before implementing them.
8. In-app notifications, unread badges, admin order count, and customer My Orders notifications.
9. Browser Notification API and notification preferences.
10. Admin drawer and dashboard.
11. Admin product/order management pages.
12. Remaining existing public and admin pages.
13. Mobile-first regression testing, notification-flow testing, and responsive refinement.
14. Footer attribution update and final cross-site review.

Adapt these stages to the actual project. Identify dependencies, exact files likely to change, and acceptance criteria for each stage.

## 14. Acceptance Criteria

The redesign is complete only when:

1. All existing customer pages in scope have been redesigned.
2. All existing admin pages in scope have been redesigned.
3. Smartphone usability has been prioritized throughout the site.
4. Customer bottom navigation works using existing destinations.
5. The admin drawer remains functional and has improved visual design and icons.
6. Relevant action buttons have clear, consistent icons.
7. Arabic RTL behavior works wherever required.
8. The customer and admin interfaces have been checked at the specified smartphone viewport sizes.
9. Tablet and desktop layouts remain usable.
10. Existing application behavior remains intact.
11. The footer displays `تم التطوير بواسطة م/جمال الدين 770569805` instead of the old copyright sentence.
12. Admins receive persistent notifications for new customer registrations and see the correct unread badge.
13. The admin Orders item shows a clearly defined and accurate new/unread-order count.
14. Customers receive the specified welcome/login and order-status/approval notifications without duplicate spam.
15. Relevant order notifications are visible on My Orders and are accessible only to the correct customer.
16. Browser pop-ups respect both the site's notification preference and browser permission; in-app notifications continue to work when pop-ups are disabled.
17. Notification preferences and notification flows have been tested and documented honestly.
18. Code is maintainable and consistent with the current project.
19. Actual testing results and limitations are documented.

## 16. Workflow and Communication

Communicate with me in Arabic.

Before implementation, read this specification and inspect the current project. Summarize the proposed stages, expected file changes, and important risks in Arabic.

Then proceed with the redesign in logical stages, staying strictly within the visual/UI/UX scope.

After each stage, report:

- Files actually changed.
- Visual improvements completed.
- Customer and admin mobile viewport sizes and pages actually tested.
- Test commands and actual results.
- Any unresolved issues or limitations.
- The next stage.

If any requirement is ambiguous or requires changing backend functionality, stop and ask me rather than guessing.

**Final goal:** A polished, mobile-first Matrix Wave website for both customers and administrators, with consistent icons, an improved admin drawer, customer bottom navigation on smartphones, responsive layouts across the entire site, and the exact developer attribution specified above—without breaking existing functionality.