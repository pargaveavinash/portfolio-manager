# Portfolio Manager — Project Rules

## Project Overview
Portfolio Manager is a personal investment portfolio management application being developed incrementally.
The long-term goal is to evolve the application into a production-ready portfolio management SaaS platform.

The application has a strong focus on:
* Correct financial calculations
* Secure multi-user data ownership
* Test-driven development (TDD)
* Clean architecture
* Maintainable Laravel code
* Explicit business rules
* Reliable automated verification

## Implementation Status
* The actual repository state is authoritative for implementation status.
* Current-state/project documentation should be consulted before deciding what feature is next.
* Do not assume an older roadmap reflects the current implementation state.
* Follow the repository's existing implementation rather than reverting to an older roadmap phase.

## Core Development Philosophy
1. Understand before modifying.
2. Inspect existing code before creating new code.
3. Reuse existing domain logic where appropriate.
4. Prefer the smallest correct implementation.
5. Avoid speculative abstractions.
6. Do not introduce unnecessary dependencies.
7. Keep business logic explicit and testable.
8. Preserve existing behavior unless the requirement explicitly changes it.
9. Never weaken existing authorization or validation.
10. Every feature must have automated tests.

## Agent Workflow
For every non-trivial feature, follow this sequence:
1. **Understand** the requirement.
2. **Inspect** existing code and check relevant documentation.
3. **Plan:** Identify affected components and create an implementation plan.
4. **Test:** Write failing tests.
5. **Implement** the minimum solution.
6. **Verify:** Run targeted tests and the complete test suite.
7. **Refactor & Review:** Refactor if necessary, review changes, update documentation, check git diff, and prepare the commit.

## Agent Safety Rules
* Do not delete production data or drop databases without explicit approval.
* Do not reset databases without explicit approval.
* Do not remove tests to make the suite pass.
* Do not disable authorization or bypass validation.
* Do not modify unrelated features.
* Do not introduce dependencies without justification.
* Do not rewrite large portions of the application unnecessarily.
* Do not commit secrets, credentials, or `.env` files.
* For destructive or potentially irreversible operations, ask for confirmation first.

## Planning & Documentation Rules
* For non-trivial features, produce a plan identifying: requirement, existing code involved, files likely to change, database changes, business rules, tests required, risks, and verification steps.
* Prefer modifying the smallest number of files necessary.
* Documentation is part of the implementation. Update it when a feature introduces or changes business rules, architecture, API behavior, database structure, financial formulas, or development workflows.
* Documentation must describe the actual implemented behavior.

## Golden Rule
When uncertain:
Do not guess. Inspect. Understand. Plan. Test. Implement. Verify.
Correctness is more important than speed.
