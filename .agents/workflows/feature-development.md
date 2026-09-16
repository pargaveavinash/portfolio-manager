# E4 — Agent Orchestration Workflow

## 1. Purpose
This document establishes the practical orchestration workflow for implementing Portfolio Manager features through the configured agents. 

The workflow is designed around the Coordinator agent and strictly adheres to existing governance frameworks without duplicating their detailed procedures:
- Agent Roles
- Agent Handoff Protocol
- Agent Ownership Boundaries
- Parallel Work Safety
- Agent Coordination
- Multi-Agent Quality Gate
- TDD
- Failure Analysis
- Code Review
- Specification Verification

## 2. Workflow Entry
The workflow begins when a human provides a development request.

The **Coordinator** must:
1. Read `AGENTS.md`.
2. Read relevant project rules.
3. Inspect current repository state.
4. Understand the requested change.
5. Determine feature complexity.
6. Determine whether the feature is financial.
7. Select the minimum required agents.
8. Determine dependencies.
9. Determine whether parallel work is safe.
10. Start the appropriate workflow.

*The Coordinator must not begin implementation before required planning and approval conditions are satisfied.*

## 3. Feature Classification

### TRIVIAL
**Examples:** Documentation typo, wording correction, formatting-only documentation change.
- May use a lightweight workflow skipping planning or intensive review.

### NORMAL
**Examples:** API endpoint, CRUD feature, validation change, non-trivial backend behavior.
- **Workflow Sequence:**
  `Coordinator → Planning → TDD → Implementation → Code Review → Specification Verification → Quality Gate → Human Approval`

### FINANCIAL
**Examples:** Portfolio calculations, holdings calculations, transaction calculations, cash management, performance, allocation, rebalancing, SIP, mutual fund evaluation/switching, currency conversion.
- **Workflow Sequence:**
  `Coordinator → Planning → TDD → Implementation → Financial Review → Code Review → Specification Verification → Quality Gate → Human Approval`
- *Note: Financial Review is mandatory when applicable.*

## 4. Planning Stage
Coordinator invokes the **Planner** agent.

The **Planner** must:
- Inspect current implementation, architecture, related tests, specifications, and business rules.
- Identify affected files, database/API changes, security/authorization impact, financial impact, dependencies, and risks.
- Create the Implementation Plan.

**Constraints:**
- The Planner must not implement production code.
- The Implementation Plan must be approved before TDD/Implementation begins.
- If requirements or business rules are ambiguous: **BLOCKED → Human/Product decision.**

## 5. Test / TDD Stage
After plan approval, the Coordinator invokes the **TDD Agent**.

The **TDD Agent**:
1. Maps AC → tests.
2. Maps BR → tests.
3. Identifies Feature vs Unit tests.
4. Identifies authorization/security tests.
5. Identifies edge cases.
6. Identifies financial precision tests where applicable.
7. Creates failing tests.
8. Runs the tests.
9. Records exact RED evidence.
10. Hands off to Implementation.

**Constraints:**
- The TDD Agent must not modify production code.
- Do not weaken tests to make implementation pass.

## 6. Implementation Stage
Coordinator invokes the **Implementation Agent** after TDD handoff is accepted.

The **Implementation Agent**:
1. Reads approved plan, failing tests, and relevant business rules.
2. Implements minimum production changes.
3. Preserves architecture, authorization/security, and financial precision.
4. Runs targeted tests.
5. Performs safe refactoring.
6. Runs required verification.
7. Creates implementation handoff.

**Constraints:**
- If implementation exposes a requirement/test/business-rule conflict: **STOP → Failure Analysis → appropriate owner/human.**
- Implementation Agent must not silently modify tests or requirements.

## 7. Financial Review Stage
*(For financial features only)*
Coordinator invokes the **Financial Review Agent**.

The **Financial Review Agent** validates:
- Formulas, business rules, precision, rounding, currency handling, zero values, boundary cases, financial consistency, and relevant regression behavior.

**Constraints:**
- Financial ambiguity or incorrect calculations are **BLOCKING**.
- Financial Review does not modify production code.
- If findings exist: **RETURN** → Implementation / Planning / Product as appropriate.
- Only continue when the required financial review is acceptable.

## 8. Code Review Stage
Coordinator invokes the **Code Review Agent**.

**Review covers:** Architecture, code quality, security, authorization, API, database, tests, financial concerns when applicable, regression risk, scope, and change hygiene.

**Constraints:**
- Review produces severity-based findings.
- **BLOCKER/HIGH** findings prevent progression. Remediation returns to Implementation.
- Do not modify production code during review.

## 9. Specification Verification Stage
Coordinator invokes the **Specification Verification Agent**.

**Verification checks:** Specification, Acceptance Criteria, Business Rules, scope, authorization, security, API behavior, database behavior, and financial behavior when applicable.

**Possible results:**
- PASS
- PASS WITH FINDINGS
- FAIL
- BLOCKED

*FAIL/BLOCKED prevents Quality Gate progression.*

## 10. Multi-Agent Quality Gate
After all required agents complete, the Coordinator executes the Multi-Agent Quality Gate.

**Verify:** Requirements, business rules, AC, handoffs, ownership, parallel safety, TDD, code review, specification verification, financial review when applicable, security, database, API, change scope, and exact evidence.

*Use the existing Multi-Agent Quality Gate skill. Do not duplicate its detailed checklist.*

## 11. Human Approval
Only after the Quality Gate passes, present the human with:
- Feature summary
- Files changed
- Tests executed & results
- Review & Verification results (including Financial Review when applicable)
- Quality gate result & non-blocking findings
- Git status & recommendation

The human decides whether the work is ready for Git actions. 

**Constraints:**
Agents must **NOT** automatically stage, commit, push, merge, deploy, or release.

## 12. Failure / Return Flow
General loop for failures or blockers:
`Agent → Finding / Failure → Classification → Failure Analysis → Responsible Owner → Correction → Re-verification → Continue workflow`

**Examples:**
- **Test failure** → Failure Analysis → Implementation or Test role
- **Requirement/Business-rule ambiguity** → Product/Human
- **Financial discrepancy** → Financial Review + responsible implementation owner
- **Code Review finding** → Implementation
- **Specification mismatch** → Implementation / Planning / Product depending on root cause

*Do not randomly restart the entire workflow.*

## 13. Parallel Execution
*(Reference D4)*
Before parallel work, identify files, ownership, dependencies, shared artifacts, and financial/business-rule overlap.

- Only **SAFE** work may run concurrently.
- Financial/shared-domain work defaults to sequential.
- If uncertain: **SEQUENTIAL**.
- Do not create parallel work merely to increase agent count.

## 14. Workflow State
Use these tracking states:
- `DRAFT`
- `PLANNING`
- `PLAN_APPROVED`
- `TESTING`
- `IMPLEMENTING`
- `FINANCIAL_REVIEW`
- `CODE_REVIEW`
- `VERIFICATION`
- `QUALITY_GATE`
- `HUMAN_APPROVAL`
- `READY_FOR_GIT`
- `COMPLETE`

Also: `BLOCKED`, `RETURNED`, `REJECTED`. 
*A blocked/rejected required stage prevents progression.*

## 15. Coordination Record
For each non-trivial feature maintain a lightweight coordination record containing:
- Feature, Current State, Coordinator, Active Agent, Required Agents, Dependencies, Handoffs, Findings, Blockers, Risks, Verification Evidence, Next Action.

*Use the existing Handoff Protocol format where applicable. Do not create unnecessary tracking infrastructure.*

## 16. Real Project Example
**"Add Portfolio Summary API"**

1. **Coordinator**: Evaluates task, sets state to `PLANNING`, invokes Planner.
2. **Planner**: Creates Implementation Plan for the summary API.
3. **TDD**: Writes failing feature tests for the new endpoint.
4. **Implementation**: Implements controller and logic to satisfy tests.
5. **Financial Review**: *(Only if new financial calculations are introduced)*
6. **Code Review**: Reviews code quality and architecture.
7. **Specification Verification**: Verifies response matches the API AC.
8. **Quality Gate**: Coordinator runs the final check.
9. **Human Approval**: Human reviews evidence and handles Git commits.

*Explanation: The planner produced a design. The TDD agent created tests. The Implementation agent wrote the code. The reviewers validated the quality and specification constraints. The Coordinator gated the process before giving it to the Human for final action.*

## 17. Financial Project Example
**"Add portfolio cash balance calculation"**

1. **Coordinator**: Evaluates task as a financial calculation.
2. **Planner**: Creates plan detailing cash math and business rules.
3. **TDD**: Writes failing unit tests for calculations and bounds.
4. **Implementation**: Implements calculation logic.
5. **Financial Review**: **(Mandatory)** Explicitly reviews rounding, zero values, precision, and business logic for the cash math.
6. **Code Review**: Audits code conventions.
7. **Specification Verification**: Verifies cash formulas against approved BRs/ACs.
8. **Quality Gate**: Coordinator validates all handoffs.
9. **Human Approval**: Human authorizes completion.

*Explicit statement: Financial Review is mandatory because the feature alters fundamental financial-domain calculations and balances. Standard Code Review is insufficient for assuring financial calculation precision.*

## 18. Minimal Agent Principle
**Use the minimum number of agents required.**
- Do not invoke Financial Review for non-financial features.
- Do not invoke Planning for trivial changes unless needed.
- Do not invoke unnecessary parallel agents.
- Do not create agents simply because they exist.

## 19. Human Safety Boundary
Human approval remains mandatory for:
- Ambiguous requirements or financial formulas
- Business-rule disputes
- Destructive DB operations
- Major architectural changes
- Scope changes
- Discarding another agent's work
- Final Git actions (commit, push)
- Deployment/release
