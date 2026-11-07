# Matrix Wave --- Software Requirements Specification (SRS)

## Mobile UX, Navigation, Notifications, Orders, and Administration Enhancements

**Document ID:** SRS-MW-UX-ENH-001\
**Version:** 1.0\
**Status:** Draft for technical review and user approval\
**Prepared for:** Matrix Wave\
**Target implementation agent:** OpenCode\
**Document language:** English\
**Application interface language:** Arabic\
**Direction:** Right-to-left (RTL) for Arabic interfaces\
**Date:** 2026-11-07

------------------------------------------------------------------------

## 1. Purpose

This document defines the functional, user-interface, technical,
security, and acceptance requirements for a limited set of enhancements
to the existing Matrix Wave application.

The purpose is to provide OpenCode with an implementation-ready
specification while protecting the existing application from unrelated
changes. The requirements must be interpreted in the context of the
actual repository. Existing implementation details must be inspected and
verified; this document does not authorize assumptions about files,
routes, database structures, or integrations that have not been
confirmed.

**This document is a specification, not permission to start
implementation.** OpenCode must inspect the project, prepare a detailed
implementation plan in Arabic, and stop for the user's explicit approval
before changing any file.

## 2. Project Context

Matrix Wave is an existing Arabic product catalog and ordering website.
The current project context provided for this specification is:

-   Framework: Laravel 13.38.0.
-   Runtime: PHP 8.3.36.
-   Local development environment: Windows with XAMPP.
-   Required PHP executable for project commands:
    `C:\xampp\php83\php.exe`.
-   Front-end stack reported for the project: Laravel Blade, Vite 8,
    Tailwind CSS 4, and Vanilla JavaScript.
-   Database: Supabase PostgreSQL.
-   Primary audience: Arabic-speaking customers and administrators.
-   Primary interface: Arabic, RTL, responsive, with particular
    attention to mobile devices.

These details are context supplied for the task. OpenCode must verify
the actual versions, configuration, architecture, and current
implementation from the repository before planning changes. Do not alter
environment configuration or database connectivity as part of these
requirements.

## 3. Scope

### 3.1 In Scope

This specification covers only the following eight enhancement areas:

1.  Improve the mobile home-page UI/UX, especially the category
    presentation.
2.  Use a compact two-column product grid on mobile.
3.  Reorganize customer navigation: cart in the bottom navigation and
    account access in the upper header beside notifications.
4.  Move notification display settings and browser-notification
    permission controls to the Notifications page.
5.  Add a fixed WhatsApp contact button to the home page.
6.  Simplify and improve the mobile My Orders experience, including
    post-order confirmation and WhatsApp handoff.
7.  Improve administrator navigation, show a count of new/unopened
    orders when reliably supported, and add an administrator account
    page.
8.  Make the developer phone number in the footer a WhatsApp link with a
    prepared message.

### 3.2 Out of Scope

Unless the user separately approves a change, do not:

-   Rebuild or redesign the entire application.
-   Change the brand identity, logo, established color palette, or
    typography.
-   Change product pricing, currency rules, cart calculations, checkout
    business rules, payment methods, or order lifecycle rules beyond
    what is explicitly required here.
-   Change Supabase settings, database credentials, environment files,
    database provider, schema, migrations, indexes, or existing data.
-   Replace or redesign the existing advertisement/banner area.
-   Change persistent notifications or the notification center's
    existing data/history behavior, except for the settings relocation
    explicitly required here.
-   Perform broad refactoring, introduce unnecessary dependencies, or
    modify unrelated pages.
-   Change authentication architecture or administrator roles.
-   Invent WhatsApp phone numbers, country codes, routes, fields, or
    configuration keys.

## 4. Stakeholders and User Roles

-   **Customer:** Browses products, uses the cart, places orders, views
    orders, manages the relevant notification preferences, and contacts
    the business.
-   **Administrator:** Manages the website and orders, accesses
    administration functions, and manages their own account credentials.
-   **Developer / project owner:** Reviews the proposed plan, approves
    scope, and accepts or rejects implementation.

## 5. Functional Requirements

### FR-01 --- Mobile Home Page and Category Presentation

**Objective:** Improve the home page on mobile devices and present the
existing product categories in a modern, compact, and unobtrusive way.

Requirements:

1.  Inspect the current home-page layout and the existing category data
    and components before proposing a design.
2.  Improve spacing, hierarchy, alignment, touch targets, and responsive
    behavior without changing the site's established visual identity.
3.  Present categories in a contemporary, visually clear format that
    does not dominate the page or consume excessive vertical space.
4.  Keep category names readable in Arabic and preserve correct RTL
    ordering and alignment.
5.  Ensure the category presentation works with the actual number and
    length of category names and any existing category images/icons.
6.  Do not invent category content or modify category records.
7.  Preserve existing category navigation and filtering behavior.

**Acceptance criteria:**

-   Categories are visually organized and do not overwhelm the mobile
    home page.
-   Arabic text and RTL ordering are correct.
-   Existing category links and filtering continue to work.
-   The page remains usable at narrow mobile widths without horizontal
    overflow.

### FR-02 --- Two-Column Product Grid on Mobile

**Objective:** Display two compact product cards per row on mobile
instead of one oversized card per row.

Requirements:

1.  Use a responsive two-column product grid on supported mobile widths.
2.  Reduce card dimensions and internal spacing proportionally while
    keeping essential information legible.
3.  Preserve the product image, product name, price, availability state,
    and existing product actions that are currently part of the card.
4.  Avoid clipping, overlapping text, distorted images, or inaccessible
    controls.
5.  Use an appropriate image aspect ratio and consistent card heights
    where practical.
6.  Keep tablet and desktop layouts responsive and consistent with the
    existing design.
7.  Do not remove product information or business behavior merely to fit
    two columns.

**Acceptance criteria:**

-   At common mobile widths, two product cards appear in each row.
-   Product images retain their proportions and do not stretch.
-   Names and prices remain readable in Arabic.
-   Buttons and links remain usable by touch.
-   No horizontal page overflow or card overlap occurs.

### FR-03 --- Customer Navigation Reorganization

**Objective:** Make the cart easier to reach from the bottom navigation
and move the account entry point to the upper header.

Requirements:

1.  Inspect the current customer header and bottom navigation, including
    responsive variants and route destinations.
2.  Add or move the **Cart** entry to the bottom navigation alongside
    **Home**, **Products**, and **Orders**.
3.  Move the **My Account** entry to the upper header, beside the
    notifications entry.
4.  Keep the navigation labels and accessible names in Arabic in the
    customer-facing interface.
5.  Preserve existing route destinations, active states, cart
    count/badge behavior, and notification badge behavior.
6.  Prevent duplicate or confusing navigation entries unless the current
    design requires a clearly justified responsive variant.
7.  Ensure the layout remains usable on narrow screens and in RTL.

**Acceptance criteria:**

-   The bottom navigation provides Home, Products, Cart, and Orders.
-   My Account is available in the upper header beside Notifications.
-   All entries navigate to their existing correct destinations.
-   Active states and relevant badges continue to work.
-   No navigation item is clipped or obscured on mobile.

### FR-04 --- Notification Settings and Browser Permission

**Objective:** Consolidate notification-related settings on the
Notifications page and provide a correct browser-permission flow.

Requirements:

1.  Inspect the existing account page and notifications page to
    identify:
    -   Notification display/preferences settings.
    -   Browser notification permission controls.
    -   Any existing notification subscription or registration logic.
2.  Move the existing notification display settings and
    browser-notification permission controls from **My Account** to the
    **Notifications** page.
3.  Avoid duplicating settings. After relocation, remove the old
    controls from My Account while preserving unrelated account
    settings.
4.  When the Notifications page is opened, check the browser's current
    notification permission state.
5.  If permission is already granted, show the enabled state without
    prompting again.
6.  If permission is denied, explain that the user must change the
    permission in browser/site settings where applicable. Do not
    repeatedly invoke the permission prompt.
7.  If permission is still `default`, provide a clear activation flow.
    The implementation may attempt to request permission on page entry
    only if the browser allows it in that context. Because many browsers
    require a direct user gesture, provide a clear **Enable
    notifications** action and request permission from that action when
    required.
8.  Do not repeatedly prompt users who have already made a permission
    decision.
9.  Handle unsupported browsers and unavailable APIs gracefully.
10. Distinguish browser permission from any application-level
    notification preference or server-side subscription. Do not claim
    notifications are enabled unless the relevant underlying state
    confirms it.
11. Preserve existing notification history, read/unread state, and
    persistent notification behavior.

**Acceptance criteria:**

-   Notification-related settings are available on the Notifications
    page and no longer appear redundantly on My Account.
-   Current browser permission is detected and represented accurately.
-   Permission is requested only through a browser-supported flow.
-   Denied, granted, default, and unsupported states have clear Arabic
    UI messages.
-   No repeated or misleading permission prompts occur.
-   Existing notification history and behavior remain intact.

### FR-05 --- Fixed WhatsApp Contact Button on Home Page

**Objective:** Provide a persistent, easily accessible WhatsApp contact
action on the home page.

Requirements:

1.  Add a fixed WhatsApp contact button near the bottom of the home
    page.
2.  Ensure it does not cover the bottom navigation, important content,
    checkout controls, or other fixed elements. Account for mobile safe
    areas and existing layout offsets.
3.  Use the official WhatsApp number already configured in the project,
    if one exists and is verified.
4.  Inspect existing settings, configuration, and current WhatsApp
    integration to find the authoritative number. Do not invent or infer
    a phone number or country code.
5.  Open WhatsApp using a supported `wa.me` or equivalent WhatsApp deep
    link, with a URL-encoded Arabic prefilled message.
6.  The prepared message must clearly communicate that the visitor came
    from the **Matrix Wave website** and wants to contact the business.
7.  If no verified official number can be found, do not implement a
    guessed link. Record the missing value as a blocking clarification
    for the project owner.
8.  Use an accessible label and a suitable WhatsApp visual treatment
    without changing the site's brand identity.

**Acceptance criteria:**

-   The button is visible and usable on the home page at supported
    mobile widths.
-   It opens the correct WhatsApp destination when the verified number
    is configured.
-   The Arabic message is prefilled and identifies Matrix Wave as the
    referral source.
-   The button does not obscure other fixed controls or content.
-   No phone number is fabricated.

### FR-06 --- Mobile My Orders and Successful Order Handoff

**Objective:** Simplify the My Orders/ordering experience on mobile and
provide a clear successful-order message followed by a WhatsApp handoff.

#### A. Mobile presentation and customer details

1.  Inspect the current My Orders page, order form, customer profile
    fields, and the source of each displayed customer detail.
2.  On the mobile My Orders experience, hide the customer's name and
    email fields/details as requested.
3.  Display the customer's phone number as the relevant customer contact
    detail.
4.  Make the phone number editable through the existing safe and
    appropriate account/order flow.
5.  Preserve validation and authorization. Do not allow a customer to
    edit another customer's details.
6.  Simplify the layout, reduce unnecessary vertical space, and remove
    elements that do not contribute to viewing or placing orders.
7.  Preserve essential order information, order status, product details,
    totals, and actions.
8.  Remove this exact notice from the relevant My Orders/order
    interface: \> الأسعار المعروضة حالية. راجعها قبل تأكيد الطلب؛ قد
    تتغير الأسعار قبل إنشاء الطلب.
9.  Do not remove other price disclosures or legal notices unless they
    are the exact notice above or separately approved.
10. Do not alter desktop behavior unless a shared component must change;
    if so, document the impact and preserve desktop usability.

#### B. Successful order submission

1.  After the server has successfully created and persisted the order,
    show a temporary success Toast in Arabic stating that the order was
    sent successfully through the website.
2.  The success message must not appear before the order is actually
    saved.
3.  After showing the success confirmation, automatically attempt to
    open WhatsApp with a prepared Arabic message containing the relevant
    order details and clearly indicating that the order originated from
    the Matrix Wave website.
4.  Inspect the existing WhatsApp order flow and reuse its verified
    destination and message conventions where possible.
5.  Use the official WhatsApp number already configured in the project.
    If no verified number exists, stop and request clarification rather
    than inventing one.
6.  URL-encode the WhatsApp message correctly and avoid exposing
    unnecessary sensitive customer information.
7.  Ensure the order is created only once. Prevent duplicate submissions
    caused by repeated clicks, retries, redirects, or the WhatsApp
    handoff.
8.  WhatsApp opening is a client-side handoff and may be blocked by
    browser behavior or popup restrictions. Handle failure gracefully
    and retain the saved order.
9.  If WhatsApp cannot be opened, the order must remain successfully
    saved and visible in My Orders. Provide a clear way for the customer
    to retry/contact WhatsApp without creating a second order.
10. Do not mark an order as successfully submitted based solely on
    opening WhatsApp.
11. Do not change order status or payment status merely because the
    WhatsApp link was opened.

**Acceptance criteria:**

-   On mobile, the requested name and email details are hidden and the
    phone number is displayed and editable through an authorized flow.
-   The exact specified notice is removed from the relevant interface.
-   A success Toast appears only after confirmed order persistence.
-   WhatsApp is opened or attempted with a correctly encoded prepared
    message.
-   Failure to open WhatsApp does not delete, duplicate, or invalidate
    the saved order.
-   Repeated taps or reloads do not create duplicate orders.
-   The customer can still find the order in My Orders.

### FR-07 --- Administrator Navigation, New-Order Indicator, and Account Page

#### A. Administrator header

**Objective:** Replace the administrator's top-header account shortcut
with a clear administration entry point.

Requirements:

1.  Inspect the administrator header and its existing route structure.
2.  Replace the top-header **My Account** button with **General
    Administration** (Arabic UI label: **الإدارة العامة**).
3.  Keep the notifications entry available beside it, preserving
    existing behavior.
4.  Ensure the new entry points to the existing administration landing
    page or the correct existing admin destination. Do not invent a
    route.
5.  Preserve responsive behavior and access control.

#### B. New/unopened order count

**Objective:** Show administrators how many newly received orders have
not yet been opened/reviewed, if the existing data model can support
that meaning reliably.

Requirements:

1.  Inspect the current order statuses, order timestamps, admin order
    list, and any existing viewed/read tracking.
2.  Determine whether the application currently tracks whether an
    administrator has opened/reviewed an order.
3.  Do not equate **unopened/unreviewed** with:
    -   Unconfirmed.
    -   Pending.
    -   Unpaid.
    -   Unfulfilled.
    -   Any other order status.
4.  If a reliable existing tracking mechanism exists, show a count of
    new/unopened orders in the administrator interface.
5.  Update or clear the indicator according to the actual existing
    review/opening behavior when the administrator visits the
    order-management page.
6.  If no reliable tracking mechanism exists, report that limitation and
    propose the smallest appropriate solution. Do not add a database
    column, table, migration, or schema change without the user's
    explicit approval.
7.  Do not silently approximate the count using order status or creation
    date.
8.  Avoid excessive database queries; use the existing query patterns
    and appropriate aggregation strategy after inspection.

**Acceptance criteria:**

-   The indicator represents only orders that are genuinely
    unopened/unreviewed according to a verified mechanism.
-   It is updated when the order list is opened/reviewed according to
    the agreed behavior.
-   It is not incorrectly calculated from pending, unconfirmed, or
    unpaid status.
-   If the current system cannot track this state, the implementation
    plan identifies the required decision and waits for approval before
    schema changes.

#### C. Administrator account page

**Objective:** Add a dedicated account-management entry in the
administrator sidebar.

Requirements:

1.  Add **My Account** (Arabic UI label: **حسابي**) to the administrator
    sidebar directly below **Settings**.
2.  The page must allow the authenticated administrator to change their
    own email address and password.
3.  Inspect the existing authentication provider, administrator model,
    authorization rules, validation conventions, and
    password-reset/update flow before designing the implementation.
4.  Do not expose or display the current password.
5.  Require appropriate verification of the current password before
    changing credentials if supported by the existing authentication
    design and security policy.
6.  Validate email format, uniqueness, and any existing account
    constraints.
7.  Apply the framework's secure password hashing mechanism. Never store
    plaintext passwords or reversible password values.
8.  Protect the page and update actions with existing administrator
    authentication and authorization middleware/policies.
9.  Provide clear Arabic success and validation/error feedback.
10. Do not change role assignments, create new administrator accounts,
    or alter authentication architecture.

**Acceptance criteria:**

-   The administrator sidebar contains My Account immediately below
    Settings.
-   The authenticated administrator can update their own email and
    password through secure validated actions.
-   The current password is never displayed.
-   Passwords are securely hashed.
-   Unauthorized users cannot access or submit the update actions.
-   Existing authentication and administrator access continue to work.

### FR-08 --- Footer Developer WhatsApp Link

**Objective:** Make the developer phone number in the footer a
functional WhatsApp contact link.

Requirements:

1.  Locate the existing footer text: \> تم التطوير بواسطة م/جمال الدين
    770569805
2.  Keep the wording and visual presentation unless a minor adjustment
    is necessary to make the phone number a usable link.
3.  Make the displayed phone number clickable and open WhatsApp with a
    prepared Arabic message.
4.  The message must identify that the visitor came from the Matrix Wave
    website.
5.  Use a verified international WhatsApp number already present in
    project configuration or settings.
6.  Do not assume that the displayed local-format number is a valid
    international number. Do not invent a country code or alter the
    number based on guesswork.
7.  If a verified international number is not available, report the
    missing value and request confirmation from the project owner before
    implementation.
8.  URL-encode the message and ensure the link works on mobile and
    desktop.

**Acceptance criteria:**

-   The footer wording remains intact.
-   The phone number is visibly and semantically clickable.
-   The WhatsApp link uses the verified international number.
-   The prepared message identifies Matrix Wave as the referral source.
-   No country code or number is fabricated.

## 6. Global UI/UX Requirements

1.  Preserve the existing Matrix Wave brand identity, logo, colors,
    typography, and overall visual language.
2.  All customer- and administrator-facing UI text introduced or changed
    by this scope must be in Arabic unless the existing application
    explicitly supports a language switch and the corresponding
    localization is already implemented.
3.  Preserve correct Arabic RTL layout, reading order, icon placement,
    and text alignment.
4.  Use mobile-first responsive design, with particular attention to
    widths of 320, 360, 390, and 414 CSS pixels, then tablet and desktop
    widths.
5.  Maintain accessible contrast, visible focus states, meaningful
    accessible labels, and usable touch targets.
6.  Fixed buttons and bottom navigation must respect device safe areas
    and must not overlap each other or essential content.
7.  Reuse existing components and styles where appropriate. Do not
    introduce a new design system without approval.
8.  Keep loading, empty, success, error, and permission-denied states
    clear and consistent.
9.  Avoid visual clutter, unnecessary animation, and excessive vertical
    spacing.

## 7. Technical and Repository Analysis Requirements

Before preparing an implementation plan, OpenCode must inspect the
existing repository and identify the actual implementation of the
affected features.

At minimum, inspect:

-   Route definitions and route names for customer, order, notification,
    and administrator pages.
-   Controllers, request validation, middleware, policies, and
    authorization.
-   Blade layouts, page templates, reusable components, and navigation
    partials.
-   JavaScript modules and event handlers for cart, order submission,
    notifications, browser permissions, and WhatsApp.
-   CSS/Tailwind configuration and responsive breakpoints.
-   Models, relationships, scopes, casts, and order lifecycle/status
    definitions.
-   Existing notification persistence, read/unread behavior, and any
    browser push subscription logic.
-   Existing WhatsApp number/configuration and message generation.
-   Authentication and administrator account update mechanisms.
-   Existing tests and test execution commands.

For each affected feature, report the actual relevant file paths and
explain the current behavior. Do not invent file names, routes, model
fields, or architecture. If a detail cannot be verified, mark it as
**Unknown --- requires clarification**.

## 8. Data, Database, and Configuration Constraints

1.  Treat the current database and Supabase configuration as protected.
2.  Do not edit `.env`, database connection settings, Supabase settings,
    credentials, or deployment configuration.
3.  Do not execute migrations, alter tables, add columns, add indexes,
    change constraints, or modify database records as part of
    inspection.
4.  Do not create a new database field for administrator order-read
    tracking without explicit user approval.
5.  If a requirement cannot be implemented safely with the existing data
    model, explain the gap and present the smallest proposed schema
    change separately for approval.
6.  Do not run destructive commands, database resets, seeders, or
    data-cleanup scripts.
7.  Do not expose secrets, tokens, credentials, or private customer data
    in reports.

## 9. Security and Privacy Requirements

-   Enforce authentication and authorization on administrator-only
    functionality.
-   Validate and authorize customer phone-number changes.
-   Use Laravel's secure password hashing facilities.
-   Never display or log plaintext passwords.
-   Do not include unnecessary personal information in WhatsApp
    messages.
-   Protect order submission against duplicate requests.
-   Treat WhatsApp navigation as an external handoff, not proof of order
    creation or payment.
-   Respect browser notification permission states and user decisions.
-   Do not disclose environment variables, API keys, database
    credentials, or private configuration values in generated
    documentation or logs.

## 10. Integration and Compatibility Requirements

### 10.1 WhatsApp

-   Reuse the existing verified WhatsApp integration and configured
    official number where possible.
-   Use a supported WhatsApp deep-link format.
-   URL-encode all prefilled message content.
-   Validate number formatting based on the verified configured value.
-   Do not invent a destination number or country code.
-   Handle cases where WhatsApp is unavailable or the browser blocks
    automatic opening.

### 10.2 Browser Notifications

-   Feature-detect the Notification API and any existing
    push/subscription APIs.
-   Distinguish permission state from application preference and server
    subscription state.
-   Do not repeatedly prompt after a decision.
-   Provide an explicit user action when browser gesture requirements
    apply.

### 10.3 Existing Application Behavior

-   Preserve current authentication, product availability, cart, order,
    payment, and notification-history behavior except where this
    specification explicitly changes the UI or flow.
-   Do not silently change the meaning of order statuses or notification
    states.

## 11. Non-Functional Requirements

  -----------------------------------------------------------------------
  ID                                  Requirement
  ----------------------------------- -----------------------------------
  NFR-01                              Responsive behavior must work on
                                      supported mobile, tablet, and
                                      desktop widths.

  NFR-02                              Arabic text, RTL layout, and mixed
                                      Arabic/number content must render
                                      correctly.

  NFR-03                              Existing core customer and
                                      administrator workflows must not
                                      regress.

  NFR-04                              Changes must follow the existing
                                      Laravel project conventions and
                                      remain maintainable.

  NFR-05                              Avoid unnecessary dependencies and
                                      broad refactoring.

  NFR-06                              Do not introduce avoidable repeated
                                      queries or client-side event
                                      duplication.

  NFR-07                              User feedback must be accurate and
                                      must reflect confirmed server-side
                                      outcomes.

  NFR-08                              Sensitive account and customer
                                      information must be protected.

  NFR-09                              WhatsApp and browser-notification
                                      integrations must degrade
                                      gracefully when unavailable.

  NFR-10                              Existing brand identity and visual
                                      consistency must be preserved.
  -----------------------------------------------------------------------

## 12. Error and Edge-Case Handling

OpenCode must account for at least the following cases in its plan and
tests:

-   Very narrow mobile viewport.
-   Long Arabic category or product names.
-   Product image missing or slow to load.
-   Empty category/product/order results.
-   Cart badge or notification badge count unavailable.
-   Browser notifications unsupported.
-   Browser permission already granted, denied, or left at default.
-   Browser requires a user gesture before requesting notification
    permission.
-   WhatsApp application or web endpoint unavailable.
-   Popup/deep-link opening blocked by the browser.
-   Missing or unverified WhatsApp number.
-   Order request submitted more than once.
-   Network/server failure before order persistence.
-   Network/client failure after order persistence but before WhatsApp
    opens.
-   Customer attempts to edit another user's phone number.
-   Invalid or duplicate administrator email.
-   Incorrect current password during credential update.
-   Administrator order-read tracking is not available in the current
    schema.

## 13. Implementation Phases --- Proposed Planning Structure

OpenCode must review dependencies and may recommend a different
sequence, but must explain the reason. No phase may begin before
approval of the overall plan and the relevant implementation stage.

A possible grouping is:

-   **Phase 0 --- Repository inspection and baseline:** Read-only
    analysis, current behavior map, affected files, risks, and existing
    test baseline.
-   **Phase 1 --- Customer mobile UI:** Home page, category
    presentation, product grid, and customer navigation.
-   **Phase 2 --- Notifications and contact entry points:** Notification
    settings relocation, browser permission flow, home WhatsApp button,
    and footer WhatsApp link.
-   **Phase 3 --- My Orders and order handoff:** Mobile simplification,
    phone-number editing, exact notice removal, success Toast, duplicate
    prevention, and WhatsApp handoff.
-   **Phase 4 --- Administrator interface:** Header navigation,
    new/unopened-order indicator subject to data-model approval, and
    administrator account page.
-   **Phase 5 --- Verification and regression:** Responsive, functional,
    security, and regression tests; final report.

The phases are a planning proposal, not authorization to execute them.

## 14. Testing and Verification Requirements

OpenCode must identify the available test framework and existing test
coverage before proposing test commands.

The verification plan must include:

1.  Route and page rendering checks for every affected page.
2.  Responsive checks at 320, 360, 390, 414, 768, 1024 pixels and a
    desktop viewport.
3.  Two-column product grid checks on mobile.
4.  RTL and Arabic text checks.
5.  Navigation destination and active-state checks.
6.  Cart and notification badge regression checks.
7.  Browser notification permission-state tests, including unsupported
    and denied states.
8.  WhatsApp URL and message encoding checks without sending real
    messages.
9.  Order persistence verification before success feedback.
10. Duplicate order submission tests.
11. WhatsApp handoff failure tests proving that the saved order remains
    available.
12. Customer authorization tests for phone-number changes.
13. Administrator credential validation and authorization tests.
14. New/unopened-order count tests only if reliable tracking exists and
    its semantics are approved.
15. Regression checks for existing product browsing, cart, checkout,
    orders, authentication, admin functions, and notification history.

Do not use production customer data or send real WhatsApp messages
during automated tests.

## 15. Acceptance and Definition of Done

The work is complete only when:

-   All approved requirements have been implemented within the agreed
    scope.
-   Each acceptance criterion has been verified.
-   Existing customer and administrator workflows continue to work.
-   No unapproved database/schema/configuration changes have been made.
-   No unrelated files or features have been changed unnecessarily.
-   The application runs successfully using the project's verified
    environment.
-   Tests and manual checks are reported accurately, including any tests
    that could not be run.
-   Any unresolved WhatsApp-number or order-read-tracking dependency is
    clearly reported and has not been guessed around.
-   The final report lists changed files, behavior changes, tests run,
    results, and remaining limitations.

## 16. OpenCode Mandatory Workflow and Approval Gates

This workflow is mandatory.

### Step 1 --- Read-Only Inspection

-   Read the project documentation and relevant source files.
-   Inspect the Git working tree and identify pre-existing user
    modifications.
-   Verify framework/runtime versions and relevant commands.
-   Trace the current implementation of all eight requirement areas.
-   Do not edit, create, delete, rename, or format project files.
-   Do not run migrations, seeders, destructive commands, or database
    writes.
-   Do not change `.env`, Supabase, or any configuration.

### Step 2 --- Arabic Implementation Plan

Provide a detailed plan in Arabic containing:

1.  Summary of the current implementation for each requirement.
2.  Exact files expected to change and why.
3.  Proposed UI behavior and responsive behavior.
4.  Proposed backend and frontend changes.
5.  Any required route, validation, or authorization changes.
6.  WhatsApp number source and whether it is verified.
7.  Browser notification permission approach and compatibility
    constraints.
8.  Whether unopened-order tracking exists and how it works.
9.  Whether any database/schema change appears necessary. Describe it
    separately; do not perform it.
10. Risks, dependencies, assumptions, and unresolved questions.
11. Implementation stages and verification plan.
12. Explicit list of items that require the user's decision.

### Step 3 --- Stop for Explicit Approval

After presenting the plan, stop and wait.

-   Do not begin implementation until the user explicitly approves the
    plan.
-   Silence, vague comments, or requests for explanation are not
    approval.
-   If the user requests changes, revise the plan and wait again.
-   If any requirement needs a new database field, migration, schema
    change, new dependency, or change outside the stated scope, obtain
    separate explicit approval before that action.

### Step 4 --- Controlled Implementation After Approval

Only after approval:

-   Re-check the Git working tree.
-   Preserve all pre-existing user changes.
-   Create an appropriate backup or use the project's approved
    version-control workflow before editing.
-   Implement only the approved scope, stage by stage.
-   Keep changes minimal and consistent with existing Laravel
    conventions.
-   Do not opportunistically refactor unrelated code.
-   Stop and ask if implementation reveals a new requirement, a
    conflict, or a necessary unapproved change.

### Step 5 --- Verification

-   Run the approved tests and checks.
-   Verify the actual behavior, not merely that the code compiles.
-   Report failures honestly; do not claim success for tests that were
    not run.
-   Do not send real WhatsApp messages or alter production data.

### Step 6 --- Final Arabic Report

Provide a concise but complete Arabic report with:

-   Implemented requirements and any exclusions.
-   Files created, modified, or deleted.
-   Database/configuration impact (explicitly state whether there was
    none).
-   Tests/checks run and their actual results.
-   Manual verification performed.
-   Known limitations and unresolved items.
-   Any follow-up approval needed.

## 17. Required SRS Review Questions

Before implementation, OpenCode must explicitly identify and ask about
any unresolved items, including:

1.  What exact configured value is the official WhatsApp number, and is
    it already in international format?
2.  If no verified WhatsApp number is found, what international number
    should be used?
3.  Does the existing system track whether an administrator has
    opened/reviewed each order?
4.  If it does not, should a minimal tracking mechanism be proposed as a
    separate database change for approval?
5.  What exact existing page/route should the General Administration
    header button open, if the current route structure is ambiguous?
6.  Which phone-number update flow is intended for customers if the
    existing profile flow is not suitable?

Do not block unrelated read-only inspection while gathering these
questions. Do not guess answers.

## 18. Final Instruction to OpenCode

**INSPECT FIRST → DOCUMENT CURRENT BEHAVIOR → PREPARE THE ARABIC PLAN →
WAIT FOR EXPLICIT USER APPROVAL → IMPLEMENT ONLY THE APPROVED SCOPE →
TEST → REPORT IN ARABIC.**

This SRS defines the requested scope and acceptance criteria. It does
not grant permission to modify the repository, database, environment, or
configuration before approval.
