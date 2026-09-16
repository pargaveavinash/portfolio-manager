---
name: feature-development
description: Standard workflow for implementing normal features in Portfolio Manager
---

# Feature Development Skill

This skill defines the standard procedure for implementing a normal feature in the Portfolio Manager project. It must be followed strictly to ensure quality, security, and correctness.

## 1. Requirement & Understanding
* **Understand the Requirement:** Read the user request carefully. Do not introduce speculative architecture, unrequested features, or unnecessary dependencies.
* **Ambiguous Rules:** If business rules or financial calculations are ambiguous, STOP and ask for clarification rather than inventing rules or formulas.

## 2. Investigation & Planning
* **Inspect Repository:** Explore the current repository state before making any code changes.
* **Identify Affected Components:** Determine which models, controllers, services, tests, or docs will be impacted.
* **Read Existing Context:** Read relevant existing code, tests, and documentation to understand current patterns.
* **Verify Authorization:** If the feature involves user-owned resources, ensure your plan includes verifying that existing authorization/ownership mechanisms are preserved or implemented.
* **Create Implementation Plan:** Draft a plan detailing the specific changes to be made.

## 3. Test-Driven Development (TDD)
You must follow TDD for all new behavior:
* **RED (Write Failing Tests):** Write the tests first. Confirm they fail for the expected reason (e.g., missing logic or class).
* **GREEN (Implement Minimum Solution):** Implement the minimum code required to make the tests pass. Remember that financial calculations require special care (avoid floating-point issues, explicit formulas, no silent rounding).
* **Targeted Tests:** Run the targeted tests to verify your implementation.
* **Edge Cases:** Add tests for important edge cases (unauthorized access, boundary values, zero states, etc.) and verify they pass.
* **REFACTOR:** Refactor the code for cleanliness and adherence to existing conventions without changing the behavior.

## 4. Verification
* **Run Complete Test Suite:** Execute the full application test suite. 0 failures are required before considering the implementation done. Do not remove or weaken tests simply to make them pass.

## 5. Review & Documentation
* **Review Changes:** Inspect the local changes you've made.
* **Update Documentation:** If the feature alters business rules, architecture, API, database schema, or financial logic, update the relevant project documentation.
* **Git Checks:** Run `git diff --check` to ensure there are no whitespace errors or unresolved conflict markers.

## 6. Completion & Reporting
* **Prepare Completion Report:** Present a detailed report to the user summarizing:
  * The requirement implemented.
  * Files modified or created.
  * Evidence of test execution (e.g., number of tests, assertions, and command outputs) rather than simply saying "Done".
  * Any edge cases handled.
* **Wait for Approval:** Do NOT automatically stage, commit, or push changes. Stop and wait for human approval before proceeding.
