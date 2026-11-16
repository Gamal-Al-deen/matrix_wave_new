# Matrix Wave — Caching Strategy and Implementation

## 1. Purpose

Implement a safe, measurable caching strategy for the Matrix Wave Laravel
application. Reduce repeated database work and improve response times
without introducing stale, cross-user, or inconsistent data.

This task is limited to application-level caching of suitable read data.

## 2. Known Project Context

Treat these as initial context and verify them against the repository:

- Application: Matrix Wave
- Laravel 13 and PHP 8.3
- Blade, Tailwind CSS 4, and vanilla JavaScript
- Supabase PostgreSQL and Supabase Storage
- Hosting: Manus
- Redis is not currently approved or in use.

Do not assume a package, cache backend, environment variable, or service
exists until verified.

## 3. Mandatory Rules

1. Inspect the repository before changing code. Base decisions on actual
   implementation, not assumptions.
2. Do not introduce Redis.
3. Do not add Composer or NPM dependencies unless demonstrably necessary
   and separately approved.
4. Do not modify database schema, migrations, indexes, foreign keys,
   Supabase policies, hosting configuration, or deployment infrastructure.
5. Do not run destructive production commands, clear production caches,
   or perform production data operations.
6. Preserve existing behavior, authorization, and response formats.
7. Do not cache data merely because it is frequently requested. Prove that
   it is suitable and define its invalidation strategy.
8. Never expose secrets, credentials, tokens, or private environment values.
9. Keep changes within this task; avoid unrelated refactoring.
10. If a required change conflicts with these restrictions, stop and report
    the blocker rather than expanding scope silently.

## 4. Phase A — Audit Before Editing

Read the relevant code and configuration, including:

- `config/cache.php`
- `.env.example` and relevant environment variable names
- `bootstrap/app.php` and service providers where relevant
- Existing cache calls and abstractions
- Relevant Composer packages
- Tracked deployment/runtime configuration
- Existing tests for affected features

Do not print actual `.env` secret values. Report variable names and
configuration status only.

Determine the actual cache store used locally and in the deployed
environment. Do not assume local filesystem behavior matches Manus.

### Data and query candidates

Identify repeated or expensive reads. Investigate candidates such as:

- Public site settings and stable configuration
- Categories, brands, and product classification data
- Product listing metadata and filter options
- Other stable reference data actually found in the codebase

These are candidates, not automatic approval to cache.

For each candidate, document:

- Page, endpoint, job, or component that reads it
- Relevant controller, service, repository, model, or query
- Evidence of repeated reads or query cost, if measurable
- Whether data is public, user-specific, role-specific, or sensitive
- Update frequency and all write/delete paths
- Dependencies on locale, currency, tenant, user, role, or permissions
- Relationships or computed values included in the result
- Proposed cache key and TTL
- Exact invalidation strategy
- Risks of stale data or cross-user cache sharing

Do not cache data with unclear authorization or ownership boundaries.

### Baseline measurements

Where practical, record before-change measurements:

- Relevant endpoint/page response time
- Database query count
- Query duration or database time, where available
- Existing cache hit/miss behavior
- Existing automated test results

Use repeatable local or approved non-production measurements.
Do not claim improvements without comparable measurements. If reliable
timings are unavailable, say so and use testable query-count or behavior
assertions instead.

## 5. Phase B — Design

Prepare a concise design based on the audit.

### Cache suitability

Prioritize data that is frequently read, relatively stable, expensive to
retrieve, safe to share within a defined audience, and reliably invalidated.

Do not cache:

- Passwords, tokens, credentials, or secrets
- Payment details or sensitive financial records
- Private messages or private user content
- Authorization or permission-sensitive results unless every relevant
  authorization dimension is safely represented in the key
- Shopping carts, checkout state, or rapidly changing transactional state
  unless explicitly justified and safely invalidated
- Time-sensitive values where staleness could cause incorrect decisions
- Entire authenticated responses containing mixed public/private data

Do not cache Eloquent models with loaded relationships by default.
Prefer deliberate, minimal data structures where appropriate. Preserve the
types and behavior expected by existing callers.

### Cache keys

Keys must be stable, descriptive, namespaced, and versioned when the data
shape or meaning may change.

Include every dimension that changes the result, such as locale, currency,
tenant, user, role, permissions, filters, or pagination.

Never allow one user's private result to be returned to another user.
Do not use generic keys for data that varies by user or permissions.

### TTL policy

Choose TTLs based on observed update frequency and business impact.
Document the reason for each TTL. Do not apply one global TTL to unrelated
data. Prefer short TTLs where stale data could mislead users. Do not cache
critical data unless correct invalidation can be demonstrated.

### Invalidation

For every cached item, identify all relevant create, update, delete,
restore, and status-change paths.

Invalidate or refresh affected keys only after a successful database
transaction. Do not invalidate before a write succeeds.

Use the Laravel cache APIs and the cache store actually supported by the
project. Do not assume cache tags are supported. If tags are unavailable,
use a safe alternative such as explicit keys or versioned namespaces.

Consider external or scheduled data changes only if they exist in the
current architecture. Do not invent a scheduler or background service.

### Failure behavior

Caching is an optimization, not a required dependency. Handle cache
read/write failures safely where appropriate. A cache failure must not
return incorrect or unauthorized data. Follow project logging conventions
without logging secrets.

## 6. Phase C — Implementation

Implement only evidence-supported caching opportunities.

- Use Laravel's existing cache facilities.
- Follow existing service/controller/repository conventions.
- Centralize cache logic when it improves maintainability.
- Avoid duplicating key construction or invalidation logic.
- Keep database writes authoritative.
- Invalidate only after successful writes.
- Preserve pagination, filtering, sorting, localization, and authorization.
- Avoid full-page/response caching unless the existing architecture
  explicitly supports safe variation and invalidation.
- Do not introduce Redis, queues, new infrastructure, or dependencies.
- Do not modify unrelated frontend or database code.

If the configured cache backend is unsuitable or cannot be verified for
deployment, do not silently switch drivers. Report the limitation and
propose the smallest safe next step.

## 7. Phase D — Mandatory Safe Legacy-Code Cleanup

Cleanup is required but must be conservative and limited to code made
obsolete by this caching implementation.

Before deleting or changing existing code:

1. Search all direct references.
2. Search indirect references: routes, dependency injection, service
   providers, Blade components, events, listeners, jobs, scheduled tasks,
   dynamic calls, configuration, and tests.
3. Check whether the code is shared by other features.
4. Confirm the replacement fully covers the old behavior.
5. Remove only code proven unused and made obsolete by this task.
6. Preserve shared utilities and code used outside this scope.
7. Never delete historical migrations or unrelated files.

If usage cannot be established confidently, retain the code and explain why.
After cleanup, rerun relevant tests and build checks.

## 8. Phase E — Tests and Verification

Add or update tests using the existing testing framework and conventions.

Verify at minimum:

### Cache behavior
- First read populates cache where expected.
- Repeated reads use cached data.
- Expired entries are refreshed.
- Cache misses return correct database-backed results.
- Cache backend failure does not break normal behavior where fallback
  is part of the implementation.

### Invalidation
- Create, update, delete, and status-change operations invalidate or
  refresh affected entries.
- Failed database writes do not cause premature invalidation.
- Transactions do not leave stale cache entries.

### Isolation and correctness
- Different users cannot receive each other's private cached data.
- Role, permission, locale, currency, tenant, filter, and pagination
  variations do not collide where they affect results.
- Existing authorization remains enforced.
- Cached and uncached results are equivalent for the same valid request.
- Existing pages, APIs, and workflows remain unchanged.

### Regression and quality checks

Run relevant project tests and available quality checks, including as
supported by the repository:

- Targeted feature tests and relevant unit tests
- Full test suite, if practical
- PHP syntax checks for changed PHP files
- Pint or configured formatter/linter
- Frontend build if frontend files changed

Do not claim a check passed unless it actually ran successfully.
Report skipped checks and reasons.

## 9. Phase F — Performance Comparison

Repeat baseline measurements using the same environment and method.
Report, where measurable:

- Before/after response time
- Before/after database query count
- Before/after query or database time
- Cache hit/miss observations
- Measurable memory or storage overhead
- Whether results are repeatable

Separate measurements from estimates. Do not manufacture metrics or
attribute unrelated improvements to this task.

## 10. Changes Requiring Separate Approval

Stop and request approval before:

- Redis or another new infrastructure service
- New Composer or NPM dependencies
- Database schema or migration changes
- Supabase policy, trigger, index, or database function changes
- Hosting or deployment configuration changes
- Production cache clearing or destructive production operations
- Authentication, authorization, or financial workflow changes
- Broad refactoring outside the caching scope

## 11. Required Final Report

Provide:

1. Executive summary
2. Audit findings and actual cache backend
3. Data candidates evaluated and why each was or was not cached
4. Files created, modified, and deleted
5. Cache keys, TTLs, and invalidation rules
6. Security and user-isolation safeguards
7. Tests and commands executed, with actual results
8. Before/after measurements, or why metrics were unavailable
9. Cleanup report:
   - Removed code and why removal was safe
   - Retained code and why it remains necessary or uncertain
10. Known limitations and follow-up recommendations

Do not mark the task complete if important tests fail, cache isolation is
uncertain, or invalidation is incomplete. Identify blockers clearly.

## 12. Execution Workflow

1. Read this entire document before making changes.
2. Inspect the repository and establish the current implementation.
3. Produce an audit and implementation plan with file paths and evidence.
4. Check the plan against every restriction in this document.
5. Implement the smallest safe set of improvements supported by the audit.
   Do not pause for another approval unless a restriction or blocker applies.
6. Perform mandatory safe legacy-code cleanup.
7. Run tests, quality checks, and performance comparisons.
8. Inspect the final diff for unrelated or accidental changes.
9. Deliver the required final report.

Preserve existing user changes in the working tree. Do not reset, overwrite,
stash, or discard user work. Never use destructive Git commands.
