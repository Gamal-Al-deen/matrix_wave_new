# OpenCode --- Strict WebP Image Optimization: Investigation and Implementation

## Project Context

You are working on the Matrix Wave application: - Laravel 13, PHP 8.3 -
Blade, Tailwind CSS 4, vanilla JavaScript - Supabase PostgreSQL -
Supabase Storage for product images - Currently hosted on Manus
hosting - Primarily used through mobile screens - Redis is not wanted
and must not be introduced in this task.

Do not assume that production hosting supports any image library, PHP
extension, WebP encoder, filesystem operation, or runtime capability
merely because it works locally.

## Primary Objective

Investigate the existing product-image lifecycle and prepare a safe
implementation plan for optimizing product images using **one WebP image
per product image**.

Approved direction: - Convert new product image uploads to WebP. - Keep
only the optimized WebP version after processing, upload, verification,
and database update have all succeeded. - Do not retain the original
uploaded file after successful completion. - Do not create separate
medium, thumbnail, desktop, or multiple responsive variants. - Images
must remain sufficiently sharp and detailed for customers to inspect
products on mobile. - Preserve the existing design, image presentation,
workflows, and user experience.

**Phase 1 is investigation and planning only. Do not implement until the
user reviews and explicitly approves the written plan.**

------------------------------------------------------------------------

# PHASE 1 --- INVESTIGATION AND PLAN ONLY

## 1. Strict No-Modification Rule

During Phase 1, you MUST NOT: - Edit, create, delete, rename, or move
project files. - Install, remove, or update Composer/npm packages. -
Modify composer files, package files, or configuration. - Run migrations
or modify database schema/data. - Upload, overwrite, move, or delete
Supabase Storage objects. - Convert, recompress, rename, or delete
existing images. - Run bulk operations or data migrations. - Change
environment variables or deployment settings. - Make any code changes,
including temporary changes.

You may read project files and perform safe, read-only inspection. Do
not run commands that may alter project files, dependencies, databases,
storage, or deployment state.

## 2. Map the Existing Image Lifecycle

Trace actual source code; do not infer behavior from filenames or
assumptions. Inspect and document:

1.  Upload routes and entry points.
2.  Controllers, request validators, services, models, and actions
    involved.
3.  Current server-side validation: MIME, extensions, size, dimensions.
4.  Whether GD, Imagick, Intervention Image, another library, or no
    processor is used.
5.  File naming conventions.
6.  PostgreSQL tables and columns storing image paths/URLs.
7.  Supabase Storage upload implementation.
8.  Bucket name, access mode, and folder conventions.
9.  How URLs are generated and images displayed.
10. Product image replacement/update behavior.
11. Product/image deletion behavior.
12. Whether image paths can be shared by multiple records/products.
13. Fallback images, placeholders, and special handling.
14. Other upload workflows that share image code.

Give exact file paths and class/function/method names. Mark anything
that cannot be confirmed.

## 3. Confirm Manus Hosting and Runtime Compatibility

Determine what can be verified from repository/configuration and
identify what requires a production runtime check:

-   PHP GD extension and WebP support.
-   Imagick extension and ImageMagick WebP support, if relevant.
-   PHP 8.3 and Laravel 13 compatibility.
-   Composer package compatibility, if a package may be needed.
-   Temporary filesystem write permissions.
-   PHP memory limit.
-   Upload size limits.
-   Execution time limits.
-   Hosting restrictions affecting image processing.

Do not claim Manus supports a capability without reliable evidence.
Categorize findings as: - Confirmed from repository/configuration. -
Confirmed from reliable hosting documentation. - Not verifiable from
available evidence. - Requires a production runtime check.

Do not install packages or change hosting settings in Phase 1.

## 4. Recommend Image Dimensions and Quality

The approved direction is one WebP image only, with no original retained
after successful processing and no separate variants.

Initial proposal: - Maximum longest dimension: 1200 pixels. - WebP
quality: approximately 80%. - Preserve aspect ratio. - Never upscale
smaller images. - Do not crop unless the current interface demonstrably
requires it.

Inspect actual image display sizes and use cases, especially on mobile.
Explain whether 1200px and quality 80 are appropriate, and assess: -
Product listing and detail image dimensions. - `object-fit: cover`,
`contain`, fixed aspect ratios, and cropping. - Risk of blurring or
losing product details. - Final recommended dimensions and quality.

Do not blindly apply these values. If the UI evidence is insufficient or
your recommendation materially differs, ask the user before
implementation.

## 5. Design a Safe Upload and Conversion Workflow

Plan the complete new-upload sequence:

1.  Receive uploaded file.
2.  Apply server-side validation.
3.  Verify actual file content; do not trust only filename or browser
    MIME.
4.  Decode and process safely.
5.  Convert to WebP using approved dimensions/quality.
6.  Write to a temporary location if needed.
7.  Verify output exists, is non-empty, and is valid/readable WebP.
8.  Upload WebP to the correct Supabase Storage bucket.
9.  Verify successful upload and obtain final path/URL.
10. Update the database reference only after the new file is safely
    uploaded and verified.
11. Clean temporary files safely.
12. Ensure failures never leave the database pointing to a missing
    image.

The original must not be discarded before conversion, upload,
verification, and database update have succeeded.

Explain handling of: - Decode failure. - Conversion failure. - Temporary
file creation/write failure. - Storage upload or verification failure. -
Database update failure after upload. - Temporary cleanup failure.

A failed new upload must not destroy or break the currently displayed
image. Do not claim Storage and PostgreSQL share a transaction. Explain
safe coordination across these two systems.

## 6. Design a Safe Replacement Workflow

Required safety principles: - Never delete the old referenced image
before the new WebP has been generated and uploaded successfully. -
Update the database reference only after the new image is confirmed in
Storage. - Delete the old image only after the database update
succeeds. - If shared references are possible, check that the old path
is not still used elsewhere before deletion. - If old-image deletion
fails, preserve the working new image and log/report cleanup failure. -
If the database update fails, keep the old image. Clean up any newly
uploaded orphan only if safe and explicitly handled.

Describe exact operation order and recovery behavior.

## 7. Database, Storage, and URL Compatibility

Inspect actual schema and conventions. Document: - Tables and columns
storing image paths/URLs. - Whether full public URLs or object paths are
stored. - Bucket name and folder conventions. - Whether `.webp`
paths/extensions affect any code. - Where URL generation occurs. -
Legacy path formats or extensions in existing records. - Any API,
validation, frontend, or integration assumptions about extensions.

Do not propose schema changes unless demonstrably necessary. If
necessary, explain and request approval. Do not change bucket privacy or
access policies.

## 8. Existing Images and Migration Scope

This task initially covers **new uploads and replacements only**, unless
the user separately approves a migration.

You MUST NOT: - Bulk-convert existing product images. - Delete existing
originals or legacy images. - Rename existing Storage objects. - Rewrite
existing database paths. - Run scripts across all products/images. -
Perform one-time migrations.

You may describe a separate optional future migration, clearly marked as
outside this task and requiring separate approval. Explain how old and
new image formats can coexist during transition.

## 9. Frontend Compatibility and Visual Regression

Inspect relevant Blade templates, components, JavaScript, and CSS.
Determine whether `.webp` URLs work without frontend changes.

Preserve: - Existing page layouts and product cards. - Product detail
image display. - Mobile responsiveness and aspect ratios. - Placeholders
and fallback behavior. - Loading behavior and lazy loading. - Existing
visual styling.

Do not redesign the interface or make unrelated CSS/JS changes. List
exact frontend files only if changes are genuinely required.

## 10. Security and Reliability

Review only risks relevant to product image handling: - Validate actual
MIME/content, not just extension. - File size and dimension limits. -
Decompression/resource-exhaustion risks. - Corrupt or malformed
images. - Unsupported formats. - Safe temporary names and paths. - No
user-controlled filesystem paths/path traversal. - Avoid accidental
object overwrites. - Correct Storage error handling. - Useful logs
without exposing secrets. - Temporary-file cleanup. - Never delete
objects still referenced in the database.

Do not expand this into a general application security audit.

## 11. Testing Plan

Include successful cases: - Valid JPEG and PNG uploads. - Other
currently supported formats, if any. - Large image resized
appropriately. - Small image not upscaled. - Existing image
replacement. - WebP display on mobile listing/detail pages.

Include failure cases: - Unsupported type. - Spoofed extension/MIME. -
Corrupt image. - Oversized image. - Conversion failure. - Storage upload
failure. - Database update failure. - Old-image deletion failure. -
Temporary cleanup failure.

Include regression checks: - Product creation and editing still work. -
Legacy images still display. - No unrelated layout or behavior
changes. - Existing image is preserved when upload fails.

State which tests are automatable and which require a safe
staging/production-like environment. Never run destructive tests against
production data.

## 12. Exact Scope and File Change Proposal

List every file expected to be created or modified in a table: - Exact
path. - Current responsibility. - Proposed change. - Why necessary. -
Required or optional.

List possible dependency changes separately; do not install anything.
Keep changes minimal and consistent with existing architecture. No
unrelated refactoring.

## 13. Explicitly Out of Scope

Do not implement, modify, or refactor: - Redis. - Application-wide
caching. - Product deletion cleanup beyond what is strictly required for
this upload/replacement lifecycle. - Notification retention/deletion
policies. - Database indexing/query optimization. - Search. - Cart
behavior. - Authentication/authorization. - Orders, checkout, or
payments. - Product pagination/eager loading. - General UI redesign. -
Unrelated cleanup/refactoring. - Bulk conversion of existing images. -
Storage bucket policy changes. - Unrelated database schema changes.

Report any discovered out-of-scope issue separately; do not fix it.

------------------------------------------------------------------------

# REQUIRED PHASE 1 DELIVERABLE

Return a detailed implementation plan using exactly these sections:

1.  **Executive Summary** --- current workflow, feasibility, major
    risks.
2.  **Current Image Lifecycle** --- exact paths, classes, methods,
    routes, DB fields; upload/display/replace/delete.
3.  **Hosting and Runtime Compatibility** --- confirmed capabilities,
    unknowns, required Manus checks.
4.  **Recommended WebP Specifications** --- dimensions, quality, aspect
    ratio, cropping, evidence from UI.
5.  **Proposed Upload Workflow** --- exact sequence and failure
    handling.
6.  **Proposed Replacement Workflow** --- exact sequence, safe deletion,
    recovery.
7.  **Database and Supabase Storage Impact** --- tables, columns,
    bucket, paths, URLs, compatibility; identify schema/policy
    implications.
8.  **Frontend Impact** --- exact templates/components/scripts and
    legacy-image compatibility.
9.  **Security and Reliability** --- relevant risks and mitigations.
10. **Testing Plan** --- success, failure, and regression tests.
11. **Implementation File List and Approval Questions** --- exact
    proposed files, dependencies, unresolved decisions, and anything
    unverified.

## Mandatory Stop Condition

After presenting the Phase 1 plan: - STOP. - Do not implement
anything. - Do not edit files or install dependencies. - Do not run
migrations. - Do not access or modify production database records. - Do
not upload, convert, or delete Storage objects. - Do not begin Phase 2
without the user's explicit approval.

If any requirement, hosting capability, existing behavior, or
implementation detail is unclear, ask a specific question and wait. **Do
not guess, invent requirements, or decide on the user's behalf.**

------------------------------------------------------------------------

# PHASE 2 --- IMPLEMENTATION (ONLY AFTER EXPLICIT APPROVAL)

Do not enter this phase until the user has reviewed the plan and
explicitly approved it.

After approval: 1. Re-read the approved plan and treat it as the
implementation contract. 2. Implement only approved requirements and
files. 3. If an unapproved dependency, schema change, hosting change,
bucket policy change, or extra file is required, STOP and ask first. 4.
Do not silently change approved dimensions or quality. 5. Do not
bulk-convert existing images. 6. Do not change unrelated functionality
or refactor unrelated code. 7. Preserve the current image if replacement
fails at any stage. 8. Verify the new WebP in Storage before updating
the database. 9. Delete the old image only after DB update succeeds and
only if not referenced elsewhere. 10. Clean temporary files safely. 11.
Add/update only tests required for the approved workflow. 12. Never
claim a test passed unless you actually ran it and can report its
result.

## Phase 2 Final Report

After implementation, report: 1. What was implemented. 2. Exact files
created/modified. 3. Dependencies added/changed and justification. 4.
Final WebP dimensions and quality. 5. Actual upload/replacement
sequence. 6. Failure handling and image-preservation behavior. 7. Tests
executed and actual results. 8. Tests not executed and why. 9. Required
Manus production configuration/runtime checks. 10. Remaining risks or
follow-up actions.

If implementation requires changing the approved plan, stop and ask
before making that change.

**Final instruction: Inspect before acting, work carefully, stay
strictly within scope, and never assume where project evidence is
insufficient. Phase 1 is planning only.**
