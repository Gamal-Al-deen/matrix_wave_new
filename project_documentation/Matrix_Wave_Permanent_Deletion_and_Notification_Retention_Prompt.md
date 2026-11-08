# Matrix Wave — Permanent Deletion Policy & Notification Retention
## Strict Audit, Execution Plan, and Implementation Authorization for OpenCode

---

# 1. Mission and Execution Mode

You are working on the **Matrix Wave** application.

Your task has TWO connected objectives:

1. Audit and correct deletion behavior so that when the application performs a delete operation, the targeted database record is **permanently deleted**, not merely soft-deleted, archived, hidden, or moved to a trash state.
2. Fix notification retention so that **each user keeps only their latest 10 notifications** and all older notifications are permanently deleted from the database.

You must work carefully, follow professional Laravel engineering practices, and preserve data integrity.

## Mandatory execution sequence

Follow this sequence strictly:

1. Inspect the actual codebase and database migrations.
2. Document the current behavior and identify the exact affected components.
3. Write a detailed, actionable implementation plan.
4. Review your plan for data-loss risks, foreign-key constraints, storage consistency, and regression risks.
5. **After completing the plan, begin implementing the plan in the same task. Do not stop to ask for a second approval.**
6. Execute relevant tests and verify the implementation.
7. Perform safe legacy-code cleanup.
8. Produce a final report with evidence.

The user explicitly authorizes code changes and implementation after you have written and internally checked the plan.

This authorization does NOT authorize destructive operations against production data, production deployment, or unrelated infrastructure changes. Do not run a real cleanup against the production database or delete production Storage objects. Implement and test the required behavior safely; clearly identify any production operation that must be run separately.

If you discover a requirement that cannot be implemented safely without a decision from the user, stop only that specific part, explain the exact blocker, and continue with independent safe work where possible.

---

# 2. Project Context

The application uses:

- Laravel 13
- PHP 8.3
- Supabase PostgreSQL
- Supabase Storage
- Blade
- Tailwind CSS 4
- Vanilla JavaScript
- Manus hosting

Use the actual repository as the source of truth. Inspect the installed Laravel version, current architecture, models, migrations, routes, services, and existing behavior.

Do not assume table names, model relationships, foreign-key behavior, Storage bucket names, or notification implementation. Verify them in the codebase.

Do not introduce Redis.

---

# 3. Objective A — Permanent Database Deletion

## 3.1 Required deletion policy

When a user or administrator invokes an existing delete action, the targeted record must be permanently removed from the database.

Do not leave the targeted record behind using:

- Laravel SoftDeletes.
- `deleted_at` as a substitute for deletion.
- Archive flags.
- Hidden/inactive flags used to simulate deletion.
- Trash tables or recoverable deletion states.
- Application-level filtering that merely hides the record.

If the application currently uses soft deletion for an entity that has an explicit delete action, investigate the actual workflow and implement permanent deletion for that action.

A successful delete action must result in the targeted database row no longer existing.

This is a hard requirement.

## 3.2 Scope of investigation

Audit delete actions across the application, including relevant modules such as:

- Products.
- Product images.
- Product drafts and temporary product uploads.
- Categories and other catalog records.
- User-owned records where the application exposes a delete action.
- Notifications.
- Other entities with explicit deletion workflows.

Create an inventory of the delete routes, controllers, models, services, observers, events, and database relationships involved.

Do not assume that every model should be deleted as a side effect of deleting another model. Determine ownership and dependencies first.

## 3.3 Exact deletion versus dependent data

Apply permanent deletion to the record explicitly targeted by the delete action and to dependent records that are exclusively owned by it and are intended to be deleted with it.

Before deleting dependent rows, verify:

- Their ownership.
- Their foreign-key relationships.
- Whether they are shared with other records.
- Whether another module depends on them.
- Whether they represent historical or legally/business-critical data.

Never delete unrelated records merely because they reference the same parent.

Never delete another user's data.

Never delete shared records while they are still referenced elsewhere.

## 3.4 Historical orders and financial integrity

**Critical protection:**

Permanently deleting a product must NOT destroy historical business records, including:

- Orders.
- Order items.
- Payment records.
- Invoices.
- Transaction history.
- Completed sales history.
- Financial/audit records.

Inspect how historical order items store product information.

If an order item references a product that may be deleted, preserve the historical snapshot and financial information. Use the existing schema and relationships safely.

If the current foreign-key structure prevents permanent product deletion, do not silently switch back to soft deletion and do not remove historical records to bypass the constraint.

Instead:

1. Identify the exact constraint.
2. Determine whether the existing schema supports a safe detach or historical snapshot.
3. Propose the smallest safe solution.
4. If a schema change is genuinely necessary, do not apply it without authorization. Complete independent work and report the blocker and required migration separately.

The rule is: **the explicitly deleted target must be permanently removed, while protected historical records remain intact.**

## 3.5 Supabase Storage and file deletion

For entities that own files, inspect and implement safe permanent file cleanup where appropriate.

For product deletion, investigate:

- Product image records.
- Actual Storage object paths.
- Product image ownership.
- Draft and temporary product files.
- Whether any Storage object is shared or referenced by another record.
- Existing Supabase Storage deletion methods.

A database row deletion does not automatically delete an external Supabase Storage object.

Before deleting any Storage object:

1. Resolve its exact stored object path from trusted database data.
2. Verify that it belongs to the entity being deleted.
3. Check whether another record still references the same object.
4. Never construct or guess a path and delete it without verification.
5. Never delete files belonging to another module or user.

Do not delete banners, wallet icons, site logos, or unrelated files as a side effect of product deletion.

## 3.6 Database and Storage consistency

PostgreSQL transactions do not make Supabase Storage operations transactional.

Do not claim that a database transaction can roll back an external Storage deletion.

Design a failure-safe sequence using the existing architecture. Consider:

- Validating all target records and file paths before destructive work.
- Collecting the exact set of exclusively owned Storage objects.
- Preserving database references until the relevant external operation has been handled safely.
- Handling partial Storage failures.
- Idempotent retry behavior.
- Logging failed cleanup operations.
- A recoverable cleanup mechanism if the project architecture supports it.

Do not leave database rows pointing to files that have already been deleted.

Do not leave orphaned database rows after a successful deletion.

If a reliable cross-system deletion cannot be guaranteed synchronously, use a carefully designed pending-cleanup mechanism only if it can be implemented without unauthorized schema or infrastructure changes. Otherwise, report the limitation and propose the smallest safe follow-up.

Do not introduce a new queue backend or Redis.

## 3.7 SoftDeletes review

Search for:

- `SoftDeletes` traits.
- `deleted_at` columns.
- `withTrashed()`.
- `onlyTrashed()`.
- `restore()`.
- `forceDelete()`.
- `forceDeleteQuietly()`.
- Custom archive or trash behavior.
- Delete observers and model events.

Determine where each is used.

Do not globally remove the SoftDeletes trait or delete every `deleted_at` column blindly. Some existing workflows may rely on it, and schema changes are outside this authorization.

For every existing user-facing delete action in scope, ensure that the action performs a true permanent deletion.

If an entity has no delete action and soft deletion serves a separate business purpose, do not change that behavior without a clear requirement.

Document all exceptions.

---

# 4. Objective B — Notification Retention: Latest 10 Per User

The required policy is:

**Each user may retain no more than their 10 newest notifications. Older notifications must be permanently deleted from the database.**

This policy applies to read and unread notifications alike. The newest 10 are retained based on creation time, with a deterministic tie-breaker such as the notification ID.

Do not retain older unread notifications at the expense of newer notifications unless the existing schema makes the required policy impossible; if so, report the conflict rather than silently changing the rule.

## 4.1 Inspect the existing notification implementation

Identify:

- Notification model.
- Notification table and schema.
- Whether Laravel database notifications are used.
- Notification creation paths.
- Controllers and services that create notifications.
- Events and listeners.
- Jobs and queued notifications.
- Read/unread handling.
- Notification listing and pagination.
- Existing cleanup logic.
- Existing indexes.

Find every code path that creates a notification. Do not fix only one controller if notifications are created elsewhere.

## 4.2 Required retention behavior

After a notification is successfully created for a user:

1. Identify that user's notifications.
2. Order them newest first using `created_at` and a deterministic secondary key.
3. Keep the newest 10.
4. Permanently delete every older notification belonging to that same user.
5. Do not affect notifications belonging to other users.

The rule must be enforced consistently regardless of which application component creates the notification.

If Laravel's polymorphic notification table is used, scope the operation by BOTH `notifiable_type` and `notifiable_id`. Do not accidentally combine notifications from different notifiable model types that happen to share the same numeric ID.

Use efficient database operations. Avoid loading every notification model into PHP memory merely to delete older rows.

## 4.3 Existing notification cleanup

The implementation must also provide a safe way to clean up notifications that already exceed the 10-notification limit.

Design an Artisan command or another appropriate Laravel-native mechanism if necessary.

Requirements:

- Support a dry-run/reporting mode where practical.
- Report how many rows would be deleted before actual cleanup.
- Scope cleanup independently per user/notifiable identity.
- Permanently delete only notifications older than the newest 10 for that identity.
- Be safe to run more than once.
- Process large datasets in bounded batches.
- Avoid loading the entire notifications table into memory.
- Do not execute a destructive cleanup against production during development or testing.

Do not rely only on a one-time manual database query.

## 4.4 Performance and concurrency

The retention logic must not introduce excessive queries or slow down notification creation unnecessarily.

Inspect existing indexes and query patterns.

Evaluate whether an index supporting per-user notification ordering and cleanup is useful. Do not add a migration or modify the database schema without explicit authorization.

Handle concurrent notification creation as safely as the existing database and application architecture allow. Avoid race conditions that can leave users with substantially more than 10 notifications.

If strict concurrency guarantees require database-level changes, explain the limitation and propose the smallest safe solution without applying unauthorized schema changes.

Do not introduce Redis.

## 4.5 Notification behavior preservation

Do not break:

- Notification creation.
- Read/unread status.
- Mark-as-read behavior.
- Notification links and payloads.
- Existing UI.
- Pagination.
- Notification authorization.

The only intended behavioral change is the retention policy: each user keeps the latest 10 notifications, and older ones are permanently removed.

---

# 5. Professional Laravel Engineering Standards

All implementation must follow professional Laravel conventions.

Mandatory requirements:

- Follow Laravel directory structure and naming conventions.
- Follow PSR-12.
- Keep controllers thin.
- Put substantial business logic in appropriately designed services or Laravel components.
- Use Eloquent relationships correctly.
- Use transactions for related database operations where appropriate.
- Validate and authorize destructive requests.
- Avoid N+1 queries.
- Avoid unnecessary queries and full-table loading.
- Use dependency injection where appropriate.
- Handle exceptions deliberately.
- Log meaningful failures without exposing secrets.
- Preserve existing application conventions when they are sound.
- Keep classes focused and methods readable.
- Avoid duplicated business logic.
- Do not overengineer or introduce unnecessary repository patterns.
- Do not introduce dependencies just for convenience.

Before creating a class, service, command, observer, event, or listener, search for existing equivalent functionality and determine whether it can be safely reused.

---

# 6. Mandatory Legacy Code Cleanup

After implementing the changes, perform a careful cleanup of code made obsolete by this work.

You must:

- Identify old deletion methods that have been replaced.
- Remove duplicate deletion implementations when safe.
- Remove unused methods, imports, variables, and obsolete branches related to the changed workflows.
- Remove redundant notification cleanup logic if replaced by the new centralized policy.
- Update comments and documentation that no longer describe actual behavior.
- Check direct and indirect references before deleting code.

Before removing anything, inspect:

- Routes.
- Controllers.
- Dependency injection.
- Model relationships.
- Observers.
- Events and listeners.
- Jobs.
- Callbacks.
- Dynamic method references.
- Other modules that may reuse shared methods.

Do not delete shared functionality used by unrelated modules.

Do not perform broad, unrelated refactoring.

Do not delete code merely because it appears unused at first glance.

The final implementation must not leave two competing deletion or notification-retention mechanisms.

Include a clear cleanup summary in the final report.

---

# 7. Database, Dependencies, and Infrastructure Restrictions

Unless a change is strictly necessary and separately authorized, do NOT:

- Install Composer or NPM packages.
- Modify `composer.json` or `composer.lock`.
- Modify `package.json` or lock files.
- Add or execute database migrations.
- Change database schema.
- Change foreign-key constraints.
- Change Supabase Storage policies.
- Change environment variables.
- Modify Manus hosting configuration.
- Introduce Redis.
- Change unrelated caching behavior.
- Deploy to production.
- Run destructive operations against production data.
- Delete real production Storage objects.

If implementation requires any of these actions, do not perform that action. Explain the exact reason, impact, and minimum required follow-up.

You may create code, tests, and a safe Artisan cleanup command as part of the authorized implementation.

---

# 8. Testing and Verification

Create or update focused tests.

Use isolated test data and the project's configured test environment. Never point destructive tests at production.

## 8.1 Permanent deletion tests

Test:

- Deleting a product permanently removes its database row.
- The targeted record is not merely soft-deleted.
- Exclusively owned product image records are removed.
- Correctly owned Storage objects are scheduled for or safely removed according to the implemented workflow.
- Missing Storage objects are handled safely.
- Storage failures do not silently corrupt database references.
- Shared Storage objects are preserved.
- Other products and users remain unaffected.
- Historical orders and financial records remain intact.
- Unauthorized users cannot invoke destructive deletion.
- Repeated deletion attempts are handled safely.
- Existing delete actions in the audited scope do not silently retain their targeted database rows.

Test other entity deletion flows only where they are included in the inspected and approved implementation scope.

## 8.2 Notification retention tests

Test:

1. A user with fewer than 10 notifications retains all of them.
2. A user with exactly 10 retains all 10.
3. A user with more than 10 retains exactly the newest 10.
4. Older notifications are physically removed from the database.
5. Both read and unread notifications follow the same newest-10 rule.
6. Another user's notifications are never affected.
7. Different notifiable types are isolated correctly if the table is polymorphic.
8. Equal timestamps are handled deterministically.
9. Every notification creation path enforces the policy.
10. The cleanup command's dry-run mode does not delete records.
11. Actual cleanup is idempotent.
12. Large cleanup workloads are processed in bounded batches.

## 8.3 Regression tests

Verify that:

- Product creation still works.
- Product editing still works.
- Product image replacement still works.
- Draft workflows still work.
- Order history remains accessible.
- Payment and transaction history remain intact.
- Notification creation and read/unread operations still work.
- Unrelated file workflows remain unchanged.

Run relevant tests first, then the broader test suite if practical.

Report actual command output and results. Never claim a test passed unless it was executed successfully.

If existing tests fail, distinguish pre-existing failures from regressions introduced by your changes using evidence.

---

# 9. Required Execution Plan — Write Before Coding

Before changing any file, produce a concise but technically detailed plan in your working notes/report.

The plan must contain:

1. Current deletion architecture and confirmed behavior.
2. Inventory of delete routes and affected models.
3. Soft-delete and hard-delete findings.
4. Product and dependent-record relationship map.
5. Historical order and financial data protection strategy.
6. Supabase Storage cleanup strategy and failure recovery.
7. Notification creation paths and current retention behavior.
8. Exact latest-10-per-user enforcement design.
9. Existing notification cleanup strategy.
10. Performance, indexing, and concurrency considerations.
11. Exact files to create and modify.
12. Legacy code to remove, with reference checks.
13. Test plan.
14. Risks, limitations, and any actions requiring separate authorization.
15. Ordered implementation steps.

After writing the plan, check it against every mandatory requirement in this prompt.

Then **begin implementing the safe, authorized code changes immediately**. Do not stop for another approval merely to repeat the plan.

Keep the implementation in small, logical steps. Verify each step before proceeding to the next.

---

# 10. Final Report Requirements

When implementation and verification are complete, provide a structured report containing:

## A. Implementation Summary
Explain what was implemented for permanent deletion and notification retention.

## B. Permanent Deletion Results
List the audited delete workflows and state which now perform actual database deletion.

Clearly identify any workflows not changed and explain why.

## C. Data Integrity
Explain how dependent records, historical orders, payments, and financial records are protected.

## D. Supabase Storage
Explain the exact file cleanup behavior, retry/recovery strategy, and any remaining limitations.

## E. Notification Retention
Confirm how the newest 10 notifications per user are retained and how older rows are permanently deleted.

Include the Artisan command name and usage if one was created, including dry-run usage.

## F. Files Changed
List every created, modified, and deleted file with a brief reason.

## G. Legacy Code Cleanup
List obsolete code removed, shared code retained, and the checks performed before removal.

## H. Tests
Report commands executed, passed tests, failed tests, skipped tests, and the actual reasons.

## I. Production Readiness
State what is ready for deployment and what production-only operation remains. Do not claim production cleanup or deployment occurred unless it actually did.

## J. Remaining Risks
List unresolved issues, schema limitations, and any separately authorized actions required.

---

# 11. Final Non-Negotiable Rules

- A delete action must permanently remove its targeted database record.
- Do not substitute soft deletion, archiving, or hiding for a requested delete action.
- Delete only the targeted record and its verified, exclusively owned dependents.
- Preserve historical orders, payments, invoices, and financial records.
- Never delete shared or unrelated Storage objects.
- Each user must retain only their latest 10 notifications.
- Older notifications must be permanently deleted from the database.
- Enforce notification retention across all notification creation paths.
- Do not introduce Redis.
- Follow professional Laravel architecture and directory conventions.
- Clean up obsolete code safely after implementation.
- Write the implementation plan before editing.
- Execute the authorized plan without asking for a second approval.
- Do not deploy or perform destructive production cleanup.

**Start by inspecting the repository, write the execution plan, validate its safety, and then implement the authorized changes.**
