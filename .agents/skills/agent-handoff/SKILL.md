---
name: agent-handoff
description: D2 - Agent Handoff Protocol for safe and auditable transfer of work and evidence between specialized agents.
---

# Agent Handoff Protocol

## 1. Purpose

The Agent Handoff Protocol defines a formal, lightweight mechanism for transferring work between specialized agents. Its purposes are to:
- Transfer responsibility safely between agents.
- Preserve context and evidence across role boundaries.
- Prevent assumptions and silent reinterpretations.
- Make the multi-agent workflow fully auditable.
- Preserve human approval boundaries.

## 2. General Handoff Principles

- A handoff must contain enough information for the receiving agent to continue without guessing.
- The sending agent remains responsible for the correctness of its own output until the receiving agent accepts the handoff.
- The receiving agent must validate the handoff before acting.
- Missing, contradictory, or ambiguous information must result in a BLOCKED handoff or clarification request.
- Agents must not silently reinterpret requirements.
- Agents must not silently modify another agent's artifacts outside their ownership.
- Evidence must be based on commands/actions actually performed.
- Financial, authorization, security, and destructive-operation concerns require explicit attention.
- Human approval boundaries remain authoritative.

## 3. Standard Handoff Record

A standard handoff must contain the following information:

- **Handoff ID:** Unique identifier for the handoff (e.g., issue or feature number).
- **From Role:** The agent role sending the handoff.
- **To Role:** The agent role receiving the handoff.
- **Feature / Task:** The specific feature or task being transferred.
- **Objective:** The goal of the next phase of work.
- **Context:** Relevant background information.
- **Approved Inputs:** Links to or summaries of approved specifications, plans, etc.
- **Work Completed:** Summary of what the sending agent has accomplished.
- **Artifacts Changed:** Files created, modified, or deleted by the sending agent.
- **Requirements / AC Covered:** Specific Acceptance Criteria addressed.
- **Business Rules Covered:** Specific Business Rules addressed.
- **Tests and Verification Evidence:** Evidence of execution and validation.
- **Known Issues:** Any outstanding issues or limitations.
- **Open Questions:** Questions requiring clarification from the receiving agent or a human.
- **Risks:** Identified risks for the next phase.
- **Required Action:** What the receiving agent is expected to do.
- **Status:** The current state of the handoff.

### Allowed Statuses:

- **READY:** The handoff is complete and awaiting action by the receiving agent.
- **ACCEPTED:** The receiving agent has validated the inputs and assumed responsibility.
- **BLOCKED:** The receiving agent cannot proceed due to missing information, dependencies, or ambiguity.
- **REJECTED:** The handoff violates protocol or contains critical errors.
- **RETURNED:** The handoff has been sent back to the sending agent for rework or clarification.

## 4. Role-to-Role Handoffs

### Product → Planning
Must include:
- approved requirement/specification
- acceptance criteria
- confirmed business rules
- scope and out-of-scope
- open questions

*Planning must not begin implementation from an unapproved specification.*

### Planning → Test / TDD
Must include:
- approved implementation plan
- specification reference
- AC references
- business-rule references
- affected components
- intended TDD sequence
- test strategy
- known risks/open questions

### Planning → Implementation
Must include:
- approved implementation plan
- specification/AC references
- implementation boundaries
- affected components
- constraints
- known risks

*Implementation must not proceed when required approval is missing.*

### Test / TDD → Implementation
Must include:
- failing test(s)
- exact test command(s)
- exact failure evidence
- AC/BR mapped to the test
- expected behavior
- relevant edge cases
- any failure-analysis findings

*The Test Agent owns the tests. The Implementation Agent owns production-code changes.*

### Implementation → Code Review
Must include:
- production changes completed
- files changed
- AC/BR addressed
- targeted test results
- full test results when available
- migrations/database changes
- API changes
- known limitations
- unresolved issues

### Code Review → Specification Verification
Must include:
- review result
- findings
- severity
- verification evidence
- scope of reviewed changes
- required remediation, if any

### Specification Verification → Human Approval
Must include:
- verification result
- AC traceability
- business-rule traceability
- test evidence
- remaining findings
- blocked items
- explicit recommendation

### Financial Review
For financial features, Financial Review may receive the implementation and verification evidence and must report:
- formulas reviewed
- business rules reviewed
- precision/rounding concerns
- edge cases
- discrepancies
- approval/findings

*Do not require Financial Review for unrelated non-financial features.*

## 5. Handoff Acceptance Protocol

1. Sending agent prepares handoff.
2. Sending agent validates completeness.
3. Receiving agent reads and validates inputs.
4. Receiving agent checks required approvals.
5. Receiving agent assigns status:
   - ACCEPTED
   - BLOCKED
   - REJECTED
   - RETURNED
6. Only after ACCEPTED may the receiving agent proceed.

*A receiving agent must not silently fill missing requirements.*

## 6. Handoff Failure Handling

How to handle issues during handoff or execution:
- **missing information / contradictory requirements:** Mark as BLOCKED or RETURNED. Do not invent requirements.
- **failed tests:** Route through the existing Failure Analysis skill.
- **unexpected implementation behavior:** RETURNED to Implementation, or consult Planning.
- **specification/business-rule conflicts:** BLOCKED. Escalate to Product/Human.
- **authorization/security concerns:** BLOCKED. Escalate for human approval.
- **financial calculation discrepancies:** RETURNED. Must be resolved by Financial Review and human approval.
- **infrastructure failures:** BLOCKED. Await resolution.

*Route test failures through the existing Failure Analysis skill. Do not duplicate its detailed procedure.*

## 7. Evidence Requirements

Evidence must contain:
- exact command executed
- relevant result
- pass/fail status
- affected scope

Examples:
- `php artisan test --filter CurrentInvestedTest`
- `git diff --check`
- `git status`

*Never state that something passed unless it was actually executed.*

## 8. Ownership Rules

- **Product** owns requirements/specification.
- **Planning** owns implementation plans.
- **Test** owns tests and test strategy.
- **Implementation** owns production code.
- **Review** owns review findings.
- **Verification** owns specification traceability.
- **Financial Review** owns financial correctness findings.

*No agent may silently modify another role's artifacts.*

## 9. Human Approval Boundaries

Agents must not bypass human approval for:
- ambiguous business rules
- ambiguous financial formulas
- destructive database operations
- major architectural changes
- scope expansion
- final commit/push decisions

## 10. Handoff Quality Checklist

- [ ] Correct receiving role identified
- [ ] Required approvals present
- [ ] Context included
- [ ] AC/BR references included
- [ ] Artifacts identified
- [ ] Exact evidence included
- [ ] Known issues documented
- [ ] Open questions documented
- [ ] Risks documented
- [ ] Required next action clear
- [ ] Ownership boundaries preserved
- [ ] No unsupported assumptions

## 11. Handoff Example

*Example: Portfolio Summary - Current Invested Cost Calculation*

- **Handoff ID:** Feature-6.1
- **From Role:** Test / TDD
- **To Role:** Implementation
- **Feature / Task:** Current Invested Cost Calculation
- **Objective:** Implement the calculation logic to make the failing test pass.
- **Context:** Implementing Phase 6.1 of the roadmap.
- **Approved Inputs:** Phase 6.1 Specification, AC-1, BR-Invested-Cost-01.
- **Work Completed:** Wrote failing feature test for Current Invested Cost calculation.
- **Artifacts Changed:** `tests/Feature/Holding/InvestedCostTest.php`
- **Requirements / AC Covered:** AC-1 (Calculation logic).
- **Business Rules Covered:** BR-Invested-Cost-01 (Qty * Avg Cost).
- **Tests and Verification Evidence:** 
  - Command: `php artisan test tests/Feature/Holding/InvestedCostTest.php`
  - Result: FAIL (Expected 5000.00, got null).
- **Known Issues:** None.
- **Open Questions:** None.
- **Risks:** Precision loss during multiplication; ensure appropriate decimal casting.
- **Required Action:** Implement calculation in `Holding` model to satisfy the test.
- **Status:** READY

## 12. Relationship With Other Skills

- **Agent Roles** defines WHO owns the work.
- **Handoff Protocol** defines HOW work moves between roles.
- **TDD / TDD Execution** defines HOW tests are developed.
- **Failure Analysis** defines HOW failures are classified and handled.
- **Code Review** defines HOW implementation quality is reviewed.
- **Specification Verification** defines HOW final behavior is checked against approved requirements.

## 13. Safety Rules

- No requirement invention.
- No silent scope expansion.
- No weakening/deleting tests to satisfy implementation.
- No unsupported claims of verification.
- No silent financial-rule changes.
- No bypassing authorization/security requirements.
- No destructive operation without approval.
- No automatic commit/push.
