---
name: code-review
description: Reusable code-review procedure for the Portfolio Manager project
---

# Code Review Skill

This skill defines a structured review process that an agent must execute after implementing a feature, code change, or bug fix. It acts as the final quality gate before completion. 

## Important Principles
* Passing tests alone is NOT sufficient for approval.
* Do not automatically rewrite code during the review.
* Identify problems and explain *why* they matter.
* Classify findings by severity: BLOCKER, HIGH, MEDIUM, LOW, INFO.
* Distinguish real defects from optional improvements.
* Never invent requirements.
* Do not approve a change that violates AGENTS.md or workspace rules.
* If a financial rule is ambiguous, STOP and request clarification.
* Do not commit or push as part of the review.

## Review Checklist

### 1. Requirement Compliance
* Does the implementation actually satisfy the requested behavior?
* Are there any unrequested changes or speculative features?

### 2. Architecture
* Does the change follow the existing Laravel architecture?
* Is existing domain logic reused where appropriate?
* Are unnecessary abstractions avoided?

### 3. Authorization & Ownership
* Are user-owned resources explicitly protected?
* Can one user access another user's portfolio data?
* Are client-supplied IDs trusted incorrectly (they shouldn't be)?

### 4. Business Rules
* Are existing business rules preserved?
* Were any rules invented or changed without an explicit requirement?
* Are edge cases handled appropriately?

### 5. Financial Correctness
* Are formulas explicit and documented in the code?
* Is precision handled safely?
* Is there accidental floating-point behavior affecting financial correctness?
* Is there any silent rounding taking place?
* Are zero, empty, and boundary cases handled properly?

### 6. API
* Does the implementation follow the existing `Api\V1` convention?
* Is the input validation correct and comprehensive?
* Are the HTTP status codes appropriate for the responses?
* Are API Resources and response conventions preserved?

### 7. Database
* Are migrations required for this change?
* Are relationships and foreign keys defined correctly?
* Are indexes justified and present where needed?
* Are existing migrations left unchanged when appropriate? (New schema changes need new migrations)

### 8. Testing
* Are tests present for the new behavior?
* Are tests focused on testing behavior rather than implementation details?
* Are authorization and important edge cases covered by the tests?
* Was the targeted test *actually* executed and verified?
* Was the complete test suite executed?
* Are there exactly 0 failures?

### 9. Regression Risk
* Could the change break existing functionality?
* Are existing tests sufficient to prevent regression?
* Were unrelated components changed?

### 10. Code Quality
* Is the implementation readable and clean?
* Does it follow existing project conventions?
* Is there duplicated logic that should be extracted?
* Is the complexity justified by the requirement?
* Are unnecessary dependencies introduced?

### 11. Documentation
* Does the change require documentation updates?
* If yes, were the relevant docs updated to reflect the new state?

### 12. Git / Change Hygiene
* Review `git status`.
* Review the `git diff`.
* Run `git diff --check`.
* Confirm only intended files changed.
* Confirm no secrets, `.env` files, or unrelated changes are present in the diff.

## Final Review Report Format

When reporting the review results to the user, you must use the following exact format:

### Review Summary
* **Overall result:** [APPROVE / REQUEST CHANGES]
* **Explanation:** [Short explanation of the decision]

### Findings
*(List each finding individually. If none, write "None")*
* **Severity:** [BLOCKER | HIGH | MEDIUM | LOW | INFO]
* **File:** [Filename]
* **Location:** [Line number or method]
* **Problem:** [Description of the issue]
* **Why it matters:** [Impact of the issue]
* **Recommended action:** [What needs to be done to fix it]

### Verification
* **Tests executed:** [List of targeted tests or full suite]
* **Exact commands:** [e.g., `php artisan test`]
* **Test/assertion counts:** [e.g., 45 tests, 120 assertions]
* **Failures:** [Must be 0]
* **git status:** [Output summary]
* **git diff --check:** [Output summary]

### Scope
* **Files changed:** [List of modified files]
* **Unrelated changes:** [Yes/No and explain if Yes]
