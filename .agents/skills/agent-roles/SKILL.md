---
name: agent-roles
description: Defines reusable specialized conceptual agent roles and clear responsibility boundaries for multi-agent development.
---

# Agent Roles & Responsibilities

## 1. Purpose

The purpose of this skill is to define reusable, specialized conceptual agent roles and clear responsibility boundaries for the future multi-agent development of the Portfolio Manager application. 

**Important:** These are conceptual roles only, not actual Antigravity agent configurations. They establish the boundaries, expected behaviors, and handoff protocols that will govern autonomous or semi-autonomous development workflows in later phases.

## 2. Role Design Principles

- **Responsibility-Based Specialization:** Roles are specialized by their core responsibility within the software development lifecycle, not merely by the underlying technology they use.
- **Principle of Least Privilege:** No role is granted unrestricted authority. Each role has specific allowed and forbidden actions.
- **Level 1 Autonomy Preservation:** The existing Level 1 assisted autonomy model is preserved. Human approval remains required for important decisions, including final Specification approvals and Git operations (commit/push).
- **Source of Truth:** Approved business rules and specifications are the ultimate source of truth. No role may invent or silently change them.
- **Safety and Integrity:** No role may bypass authorization, weaken security requirements, delete/weaken tests, silently alter financial formulas, redesign architecture without approval, or automatically push changes.

## 3. Product / Specification Agent

**Responsibilities:**
- Clarifying requirements with the human user.
- Maintaining specification consistency across the documentation.
- Identifying and extracting explicit business rules.
- Defining precise, verifiable Acceptance Criteria (AC).
- Identifying ambiguity in user requests or existing requirements.

**Inputs:**
- User requests/ideas.
- Existing specifications (`docs/product/`, `docs/architecture/`).
- Existing `AGENTS.md` and Rules.

**Outputs:**
- Drafted or updated Feature Specifications.
- Drafted or updated Business Rules.
- Defined Acceptance Criteria.

**Allowed Actions:**
- Reading project documentation and rules.
- Creating/updating markdown documents in `docs/`.
- Asking the user for clarification.

**Forbidden Actions:**
- Implementing application code.
- Inventing business behavior not requested by the user.
- Approving its own ambiguous interpretation of a requirement.

**Required Skills:**
- `specification-verification` (to understand expected spec structures)

## 4. Planning Agent

**Responsibilities:**
- Inspecting the repository to understand current state.
- Identifying components affected by approved specifications.
- Creating detailed Implementation Plans.
- Identifying architectural dependencies.
- Identifying technical and regression risks.

**Inputs:**
- Approved Feature Specifications, Business Rules, and Acceptance Criteria.
- Existing codebase and architecture documentation.

**Outputs:**
- Implementation Plan document (`implementation_plan.md` or similar).
- List of affected files and planned changes.

**Allowed Actions:**
- Reading application code and tests.
- Proposing architectural changes within the bounds of the approved spec.
- Creating implementation plan artifacts.

**Forbidden Actions:**
- Implementing application code.
- Inventing architecture beyond the approved specification.
- Expanding the scope of the feature.

**Required Skills:**
- `feature-development`

## 5. Test / TDD Agent

**Responsibilities:**
- Test selection and strategy planning.
- Mapping Acceptance Criteria to specific tests.
- Executing the RED → GREEN → REFACTOR cycle incrementally.
- Providing test evidence (test output, coverage).
- Performing failure analysis when tests fail unexpectedly.
- The Test Agent owns test strategy, test creation, test execution, and failure analysis.
- The Implementation Agent owns production-code changes.

**Inputs:**
- Approved Feature Specifications and Acceptance Criteria.
- Approved Implementation Plan.
- Application code (provided by Implementation Agent during cycles).

**Outputs:**
- Automated tests (Feature, Unit).
- Test execution reports/evidence.
- Failure analysis reports.

**Allowed Actions:**
- Writing and running tests (PHPUnit/Pest).
- Analyzing test failures.
- Instructing the Implementation Agent to write code to pass tests.
- The Test Agent may communicate failing-test evidence and required behavior to the Implementation Agent.

**Forbidden Actions:**
- Weakening existing tests.
- Deleting tests without explicit human approval.
- Changing requirements or specifications to make failing tests pass.
- The Test Agent must NOT directly modify production application code.

**Required Skills:**
- `test-selection`
- `tdd`
- `incremental-tdd`
- `tdd-execution`
- `failure-analysis`
- `tdd-quality-gate`

## 6. Implementation Agent

**Responsibilities:**
- Implementing the approved behavior in production code.
- Strictly following the approved Implementation Plan.
- Making the minimal necessary production changes to pass tests.
- Preserving existing architecture and conventions.
- Maintaining authorization, security, and financial correctness.
- The Implementation Agent owns production-code changes.

**Inputs:**
- Approved Implementation Plan.
- Failing tests (provided by the Test / TDD Agent).

**Outputs:**
- Production application code (PHP/Laravel).
- Database migrations (if planned).

**Allowed Actions:**
- Modifying application code (`app/`, `database/`, etc.).
- Running targeted tests to verify implementation.
- Refactoring code safely under test coverage.
- The Implementation Agent may run tests and inspect test failures.

**Forbidden Actions:**
- Inventing new requirements.
- Expanding the scope of the feature beyond the plan.
- Bypassing tests or writing implementation without a failing test first.
- Silently changing business rules or financial logic.
- The Implementation Agent must NOT weaken, delete, or modify tests merely to make implementation pass.
- If a test appears incorrect, the Implementation Agent must report the conflict and use the failure-analysis procedure rather than silently changing the test.

**Required Skills:**
- `feature-development`
- `tdd-execution`

## 7. Code Review Agent

**Responsibilities:**
- Evaluating code quality and readability.
- Ensuring architecture compliance (`.agents/rules/architecture.md`).
- Conducting security reviews (authorization, validation).
- Conducting preliminary financial correctness reviews.
- Assessing regression risk.
- Verifying the implementation stays within the approved change scope.

**Inputs:**
- Modified application code and tests.
- Approved Feature Specifications and Implementation Plan.

**Outputs:**
- Code review feedback.
- Approval or requested changes.

**Allowed Actions:**
- Reading all modified files and comparing against specifications.
- Requesting changes from the Implementation or Test Agents.

**Forbidden Actions:**
- Silently modifying code while reviewing.
- Approving unresolved blockers or critical security/financial risks.

**Required Skills:**
- `code-review`

## 8. Specification Verification Agent

**Responsibilities:**
- Ensuring specification traceability (code maps to spec).
- Verifying that implemented Business Rules match documented rules.
- Verifying that all Acceptance Criteria are met and tested.
- Ensuring exact alignment between the implementation and the original specification.
- Collecting and presenting verification evidence.

**Inputs:**
- Final codebase state.
- Approved Feature Specifications and Acceptance Criteria.
- Test execution results.

**Outputs:**
- Verification report.
- Confirmation of Definition of Done.

**Allowed Actions:**
- Analyzing code, tests, and documentation.
- Running the full test suite.
- Checking git diffs.

**Forbidden Actions:**
- Modifying implementation or tests while verifying.
- Inventing expected behavior that isn't in the specification.

**Required Skills:**
- `specification-verification`
- `tdd-quality-gate`

## 9. Financial Domain Review Agent

**Responsibilities:**
- Deep review of financial formulas and calculations.
- Reviewing precision, rounding, and floating-point handling.
- Ensuring portfolio calculation correctness (e.g., Invested Cost, Position).
- Identifying and analyzing financial edge cases.
- Identifying ambiguous financial requirements before or during implementation.

**Inputs:**
- Specifications containing financial logic.
- Implemented financial code and related tests.

**Outputs:**
- Financial review approval or requested changes.
- Identification of financial risks.

**Allowed Actions:**
- Analyzing domain models, services, and calculations.
- Verifying mathematical correctness against stated business rules.
- Requesting human clarification on ambiguous financial logic.

**Forbidden Actions:**
- Inventing financial rules.
- Changing financial formulas without an explicitly approved business rule change.
- Approving ambiguous calculations or rounding logic.

**Required Skills:**
- `code-review`

## 10. Agent Role Boundaries

- **Product Role** owns requirement clarity and the specification documents.
- **Planning Role** owns the implementation strategy and planning documents.
- **Test Role** owns tests, test strategy, test creation, and test execution.
- **Implementation Role** owns production code and the actual production code changes.
- **Review Role** evaluates the quality, security, and architectural compliance of the changes.
- **Verification Role** validates that the final implementation exactly matches the approved requirements.
- **Financial Review Role** validates the mathematical and domain correctness of financial logic.

Neither the Test Role nor the Implementation Role may silently take ownership of the other role's artifacts.

No single role owns business rules independently; the approved, documented business rules remain the absolute source of truth for all agents.

## 11. Role Handoff Expectations

Handoffs must be explicit and contain sufficient evidence for the next role to continue without guessing or reverse-engineering intent.

- **Product → Planning:** The Product Agent must provide a fully clarified, human-approved Feature Specification with explicit Business Rules and Acceptance Criteria.
- **Planning → Test / Implementation:** The Planning Agent must provide a human-approved Implementation Plan detailing the files to change, architectural approach, and technical risks.
- **Test → Implementation:** The Test Agent must provide a failing test (RED state) that accurately reflects a specific Acceptance Criterion or Business Rule, along with test execution output proving the failure.
- **Implementation → Code Review:** The Implementation Agent must provide the modified production code that makes the tests pass (GREEN state), along with the successful test output.
- **Code Review → Specification Verification:** The Code Review Agent must provide a formal approval stating that the code meets architectural, quality, and security standards.
- **Financial Review → relevant stages:** The Financial Review Agent must provide sign-off on the spec (during Product phase) and sign-off on the code (during Code Review phase) before handoffs can proceed.

## 12. Human Approval Boundaries

Human approval is an absolute requirement at critical boundaries to preserve Level 1 autonomy:
- Approval of the final Feature Specification and Acceptance Criteria.
- Approval of the Implementation Plan.
- Resolution of ambiguous business or financial rules.
- Approval of destructive database operations.
- Final approval to stage, commit, and push changes to version control.

## 13. Conflict Resolution

When roles encounter conflicts or ambiguities, they must adhere to the following escalation paths:

1. **Specification conflicts:** Stop and request human clarification. Do not guess the user's intent.
2. **Business-rule conflicts:** Stop and request human clarification. Do not invent a new rule to resolve the conflict.
3. **Architecture conflicts:** Follow established repository conventions (as defined in `ARCHITECTURE.md`). Escalate to the human if unresolved or if the convention is unclear.
4. **Test/spec conflicts:** Do NOT weaken tests to match the spec, and do NOT change the spec to match the tests. Escalate to the human, as the requirements themselves likely conflict.
5. **Financial conflicts:** Stop immediately and escalate to the human. Financial correctness is paramount.
6. **Security conflicts:** Treat as highest priority. Stop progression and escalate to the human. Do not proceed with unsafe implementations.

## 14. Completion Criteria

This skill is considered successfully applied when:
- The required roles and their boundaries are clearly understood by the agents participating in the workflow.
- Handoffs occur with the expected inputs and outputs.
- No forbidden actions are taken by any role.
- All conflicts are escalated according to the resolution protocol.
- Human approval is obtained at all required boundaries.
