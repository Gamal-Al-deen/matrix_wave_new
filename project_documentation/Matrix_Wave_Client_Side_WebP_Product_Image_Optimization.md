# Matrix Wave --- Client-Side Product Image Optimization (WebP)

## OpenCode execution instructions

**Target file:**
`project_documentation/Matrix_Wave_Client_Side_WebP_Product_Image_Optimization.md`\
**Task:** Optimize product images in the browser with JavaScript before
uploading them to Supabase Storage.

## 1. Objective and scope

Implement reusable client-side image resizing and WebP compression for
**all existing product-image upload and replacement workflows**. The
optimized file, not the original selection, must be uploaded.

This task supersedes the earlier proposal to perform product-image
conversion on the Laravel server. Prefer browser-native APIs
(`createImageBitmap`, `Canvas`, `canvas.toBlob`) and do not add a
dependency unless repository evidence establishes a specific need.

The previous permanent-deletion and notification-retention task is
considered complete. Do not reopen or modify it unless a regression
directly caused by this task is demonstrated.

## 2. Mandatory inspection before implementation

1.  Confirm the project root and locate `project_documentation/`.
2.  Read this entire instruction file.
3.  Inspect the existing image upload flow end to end: Blade
    views/components, JavaScript entry points, Laravel
    routes/controllers/Form Requests/services, Supabase Storage
    integration, database image references, and tests.
4.  Identify every product-image path: creation, editing, replacement,
    drafts/pending submissions, admin forms, dynamically rendered forms,
    and any alternate entry point present in the repository.
5.  Identify non-product image flows and map how they use shared
    helpers.
6.  Record baseline tests/build status and current behavior.
7.  Prepare a concise implementation plan, then begin implementation
    without waiting for another approval for work within this document's
    scope.

Do not assume routes, files, buckets, or paths. Discover them from the
codebase.

## 3. Exact image policy

For every product image selected for upload:

-   Output must be **WebP only** (`image/webp`).
-   Maximum longest dimension: **1200 px**.
-   Encoding quality: **0.80 (80%)**.
-   Preserve aspect ratio.
-   Never upscale.
-   Never crop.
-   If both dimensions are at most 1200 px, retain their dimensions and
    encode as WebP.
-   If either dimension exceeds 1200 px, scale proportionally so the
    longest edge is 1200 px, allowing normal integer rounding.
-   Produce one optimized file per input; do not create responsive
    variants or thumbnails.
-   Give the output a `.webp` filename and correct `image/webp` MIME
    type.
-   Never silently fall back to uploading the original if conversion
    fails.

Validate that `toBlob` returned a non-null Blob and that its MIME type
is exactly `image/webp`. Any failure must stop the upload.

## 4. JavaScript implementation

Create or extend a reusable utility in the project's existing JavaScript
structure. Do not duplicate conversion logic in each form.

The utility must:

1.  Accept a selected `File`.
2.  Enforce the project's existing allowed raster-image types; do not
    broaden accepted formats without justification.
3.  Decode safely and handle decode errors.
4.  Calculate target dimensions using the 1200 px longest-edge rule.
5.  Draw at the target dimensions to a canvas.
6.  Encode with `canvas.toBlob(callback, 'image/webp', 0.8)`.
7.  Reject null output, empty output, or output whose MIME type is not
    `image/webp`.
8.  Return a new `File` with a `.webp` filename and `image/webp` MIME
    type.
9.  Close an `ImageBitmap` after use where supported.
10. Release resources and revoke any object URLs created.
11. Use the project's existing JavaScript/build conventions and present
    clear user-facing errors.

Do not introduce a frontend framework, new dependency, or
image-processing package for convenience when native browser APIs are
sufficient.

## 5. Integrate every product upload path

Ensure the optimized WebP file is the file passed to the existing
Supabase Storage upload call for every discovered product-image
workflow, including creation, edit, replacement, drafts, admin
interfaces, and dynamically generated forms where present.

Preserve existing bucket names, object-path conventions, database
fields, authorization, ownership checks, and UI behavior unless a change
is strictly necessary and within scope.

**Do not change non-product image handling**, including site logos,
banners/promotional artwork, wallet icons, avatars/profile images, or
other unrelated image assets.

If a helper is shared by product and non-product uploads, make
optimization explicitly scoped/opt-in for product images. Do not change
the shared helper's behavior for other asset types.

## 6. Laravel validation and trust boundary

Client-side processing is not a security boundary. Preserve or add
server-side validation using the project's established Laravel patterns:

-   Validate actual uploaded file content/MIME and the existing
    product-image size/type rules.
-   Do not trust the filename or browser-supplied MIME type alone.
-   Ensure valid WebP output is accepted.
-   Do not add server-side re-encoding as a fallback.
-   Do not weaken validation or authorization to make the feature work.
-   Return understandable validation errors.

## 7. Failure-safe upload and replacement

-   Decode, canvas, or WebP encoding failure: stop; show a useful error;
    do not upload the original.
-   Network or Supabase upload failure: preserve the existing database
    reference and stored image.
-   On replacement, upload the new WebP object first, then update the
    database. Delete the old object only after the database update
    succeeds, and only if ownership and non-shared status are verified
    under the existing storage model.
-   Never guess storage paths or delete shared objects.
-   If the database update fails, do not delete the old image; clean up
    the newly uploaded object only when safe and ownership is certain.
-   Prevent duplicate submissions while processing and restore the
    form's usable state after success or failure.
-   Preserve existing previews and update them to reflect the optimized
    image where appropriate.

Do not silently fall back to the original image under any failure
condition.

## 8. Performance and user experience

Keep the UI responsive; use existing progress/busy-state patterns where
available. Avoid unnecessary memory copies and excessive main-thread
work. Handle large images within reasonable browser memory limits, but
do not silently lower the 1200 px or 80% requirements. If the browser
cannot process the image reliably, fail safely with a clear message.

Do not promise a fixed compression ratio; WebP output size varies by
image content. Verify that the upload request carries the generated
`.webp` file and correct MIME type.

## 9. Explicit restrictions

Unless repository evidence shows a necessary change and the user
separately approves it, do not:

-   Modify database schema or create migrations.
-   Change foreign keys or database relationships.
-   Change Supabase buckets, Storage policies, or hosting configuration.
-   Add dependencies or change package versions.
-   Introduce Redis or another cache/queue service.
-   Bulk-convert existing stored images or modify existing objects.
-   Change logos, banners, wallet icons, avatars, or other non-product
    image flows.
-   Change unrelated business logic or UI.
-   Deploy to production or run destructive production operations.

This task applies to new product-image uploads and replacements only.
Existing stored images remain unchanged. If a part cannot be implemented
within these boundaries, document the blocker and continue with
independent safe work.

## 10. Laravel and JavaScript quality

Follow the installed Laravel version and existing architecture. Keep
controllers thin, use existing Form Request and validation conventions,
keep JavaScript modular and reusable, follow current
naming/formatting/error-handling patterns, and avoid duplicated logic or
overengineering. Preserve authorization and Supabase integration
conventions.

## 11. Mandatory safe legacy-code cleanup

After implementation:

1.  Identify old product-image conversion, upload, or preview logic
    superseded by this implementation.
2.  Search for all direct and indirect usages before removing anything.
3.  Distinguish product-only logic from shared logic used by non-product
    assets.
4.  Remove only code proven unused or superseded within this task's
    scope.
5.  Do not remove shared helpers or unrelated legacy code.
6.  Run relevant tests and build checks again after cleanup.
7.  In the report, list removed and retained legacy components and
    explain why.

## 12. Required tests and verification

Add/update tests consistent with the repository's existing testing
approach.

### Image processing

Verify: - Large landscape and portrait images have longest edge no
greater than 1200 px. - Aspect ratio is preserved. - Small images are
not upscaled. - Images already within the limit retain their
dimensions. - Output MIME is `image/webp` and filename ends in
`.webp`. - Encoding quality is 0.80. - Decode errors, null Blob, empty
output, and wrong MIME prevent upload.

### Integration and regression

Verify: - Every product-image upload path sends the optimized file,
never the original. - Non-product image flows are unchanged. - Product
create/edit/replacement behavior still works. - Failed upload does not
change the old database reference or delete the old object. - Failed
database update does not cause premature deletion of the old object. -
Duplicate submission is prevented. - Existing validation and
authorization remain intact.

Run relevant automated tests, frontend build, lint/type checks if
configured, and available focused manual checks. Never claim a check
passed unless it was actually run. If browser-level tests are
unavailable, state this limitation and list the manual verification
required.

## 13. Measurements

Where practical, record representative before/after image dimensions and
file sizes, output MIME type, and browser processing duration. Report
actual results only; do not fabricate savings or performance gains.

## 14. Execution sequence

1.  Map current product and non-product upload paths.
2.  Record baseline behavior and tests/build status.
3.  Present a concise implementation plan.
4.  Implement the reusable JavaScript optimizer.
5.  Integrate it into every product-image upload path.
6.  Verify Laravel validation and failure-safe replacement.
7.  Add/update tests.
8.  Run tests and build checks.
9.  Perform the mandatory safe legacy-code cleanup.
10. Run tests and build checks again.
11. Review the diff for unrelated changes and data-integrity/security
    risks.

Proceed after presenting the plan; do not wait for another approval for
changes authorized here.

## 15. Final report

Report: - Implementation summary. - Every product-image upload path
discovered and updated. - Files created, modified, and deleted. - Exact
dimensions, format, and quality settings. - Confirmation that
non-product image flows were preserved. - Laravel validation and
failure-safe behavior. - Legacy code removed/retained and rationale. -
Tests/build checks actually executed and results. - Measurements, if
collected. - Browser compatibility limitations, manual checks still
required, and blockers.

**Start by reading this file from the project root:**

`project_documentation/Matrix_Wave_Client_Side_WebP_Product_Image_Optimization.md`
