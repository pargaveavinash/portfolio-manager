---
name: tdd
description: Standard test-driven development (TDD) procedure for Portfolio Manager
---

# TDD Skill

This skill defines the exact Test-Driven Development (TDD) procedure for the Portfolio Manager project. It must be used whenever you are implementing new behavior, modifying existing business logic, or fixing bugs. It complements `feature-development/SKILL.md` by providing the detailed, step-by-step TDD loop that fits inside the "TDD" phase of feature development.

## When to use this Skill

Use this skill whenever you are about to write or change application code (e.g., adding a feature, fixing a bug, updating a financial formula). Do not use this skill for purely documentation or infrastructure tasks that do not involve application logic. This skill fits into step 3 of the `feature-development` skill.

## 1. Understand and Inspect
* **Understand the behavior being added or changed:** Read the requirement carefully. Identify if you are adding new logic, changing existing logic, or fixing a bug.
* **Inspect existing related tests:** Before writing new tests, look at existing tests for the affected component. Notice the structure, naming conventions, and setup used.

## 2. Identify the Correct Test Type
Determine where the test belongs based on the existing `Tests\TestCase` convention:
* **Feature test:** If you are testing an API endpoint, a complete user flow, or database interactions, place the test in `backend/tests/Feature`.
* **Unit test:** If you are testing an isolated service, utility function, or complex logic that doesn't require database or framework boot, place it in `backend/tests/Unit`.
* *Note:* All tests must use native PHPUnit.

## 3. The TDD Loop
For every piece of behavior, follow this exact sequence:

1. **Write the smallest failing test:** Write a test that specifically asserts the *new* or *changed* behavior.
   * Do not invent financial formulas or business rules. If the expected behavior is ambiguous, STOP and ASK the user.
   * For authorization-related behavior, include unauthorized/ownership cases where applicable.
2. **Run the targeted test:** Execute only the new or modified test.
   * Do not claim a test passed unless it was actually executed.
3. **Confirm the failure is for the expected reason:** Verify that the test fails exactly because the feature is missing or incorrect, not due to a syntax error or incorrect test setup.
4. **Implement the minimum production code:** Write just enough code in the application to make this single test pass.
5. **Run the targeted test again:** Verify the test now passes.
6. **Add important edge cases:** Write tests for edge cases (e.g., boundary values, zero states, precision issues for financial calculations, missing inputs). Repeat the RED/GREEN cycle for each.
7. **Refactor without changing behavior:** Clean up the production code and test code. Maintain the `Tests\TestCase` convention.
   * *Critical:* Do not weaken or remove tests merely to make them pass.

## 4. Final Verification
* **Run the complete test suite:** Run all tests in the application.
* **Record exact verification commands and results:** Provide the user with the exact commands run (e.g., `php artisan test`) and a summary of the output (number of tests, assertions, and status) to prove the suite passed.
