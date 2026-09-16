# E1 — Agent Configuration Architecture

## 1. Overview
This document defines the agent configuration architecture for the Portfolio Manager project. This configuration provides a practical and minimal set of specialized agents based on the D1–D6 governance framework.

**Note:** This document represents the architecture and design layer. Actual Antigravity runtime configuration files should only be created after this design is reviewed and approved.

## 2. Initial Agent Set
The following agents make up the core workflow. The Coordinator Agent decides the actual sequence based on the task requirements.

### 2.1 Coordinator / Orchestrator Agent
- **Primary Purpose:** Orchestrates the workflow and determines the necessary agent sequence based on the Minimal Agent Principle.
- **Responsibilities:** Evaluates tasks, selects required agents, initiates handoffs, and ensures the Multi-Agent Quality Gate is satisfied before seeking human approval.
- **Inputs:** Human request, project context.
- **Outputs:** Agent invocation sequence, initial task context for downstream agents.
- **Skills Used:** `agent-coordination`, `multi-agent-quality-gate`, `agent-roles`, `agent-handoff`, `agent-ownership`, `parallel-work-safety`
- **Rules Followed:** All project rules.
- **Artifacts Owned:** None. The Coordinator does not own production implementation.
- **Artifacts Read:** All project files and documentation.
- **Artifacts Must Not Modify:** Production code, tests.
- **Handoff Relationships:** Hands off to the Planning Agent (or other agents if Planning is skipped).
- **When Invoked:** First agent invoked for any new request. Determines the workflow.
- **When NOT Invoked:** Never skipped (always the entry point).

### 2.2 Planning Agent
- **Primary Purpose:** Creates detailed implementation plans for approved features.
- **Responsibilities:** Researches the codebase, identifies required changes, and produces an actionable plan.
- **Inputs:** Feature requirements, Coordinator context, codebase.
- **Outputs:** Implementation plan artifact.
- **Skills Used:** `feature-development`
- **Rules Followed:** All project rules.
- **Artifacts Owned:** Implementation plan documents.
- **Artifacts Read:** Source code, docs, tests.
- **Artifacts Must Not Modify:** Production code, tests.
- **Handoff Relationships:** Receives from Coordinator. Hands off to Test / TDD Agent or Code Review Agent.
- **When Invoked:** Required for non-trivial features.
- **When NOT Invoked:** Not required for tiny documentation changes or trivial tasks.

### 2.3 Test / TDD Agent
- **Primary Purpose:** Implements tests following the TDD methodology.
- **Responsibilities:**
  - Test Agent creates and maintains tests according to approved AC/BR.
  - Test Agent runs tests before and after implementation.
  - Test Agent may modify tests only when the approved specification, AC, or test design legitimately requires the change.
  - Test Agent must NOT modify tests merely to make implementation pass.
- **Inputs:** Implementation plan, feature requirements.
- **Outputs:** Test code, test execution reports.
- **Skills Used:** `tdd`, `tdd-execution`, `test-selection`, `incremental-tdd`, `tdd-quality-gate`
- **Rules Followed:** TDD rules, Testing rules.
- **Artifacts Owned:** Test files (`tests/`).
- **Artifacts Read:** Implementation plan, production code, specifications.
- **Artifacts Must Not Modify:** Production code.
- **Handoff Relationships:** Receives from Planning Agent. Hands off to Implementation Agent.
- **When Invoked:** Required for behavior/code changes.
- **When NOT Invoked:** Not required for pure documentation changes.

### 2.4 Implementation Agent
- **Primary Purpose:** Writes production code to satisfy failing tests.
- **Responsibilities:** Implements the minimum required logic to make tests pass, refactors code.
- **Inputs:** Failing tests, implementation plan.
- **Outputs:** Production code.
- **Skills Used:** `feature-development`, `failure-analysis`
- **Rules Followed:** Architecture rules, Laravel rules, Database rules, API rules.
- **Artifacts Owned:** Production code (`app/`, `database/`, `routes/`, etc.).
- **Artifacts Read:** Tests, implementation plan, existing codebase.
- **Artifacts Must Not Modify:** Tests, specifications.
- **Handoff Relationships:** Receives from Test / TDD Agent. Hands off to Code Review Agent (or Financial Review Agent).
- **When Invoked:** Required for production-code changes.
- **When NOT Invoked:** Not required for documentation or test-only changes.

### 2.5 Code Review Agent
- **Primary Purpose:** Reviews implemented code against project standards.
- **Responsibilities:** Checks for code quality, security, performance, and adherence to rules.
- **Inputs:** Implemented code, tests.
- **Outputs:** Review findings, approval or rejection.
- **Skills Used:** `code-review`
- **Rules Followed:** All project rules.
- **Artifacts Owned:** Review findings/reports.
- **Artifacts Read:** Production code, tests, implementation plan.
- **Artifacts Must Not Modify:** Production code, tests.
- **Handoff Relationships:** Receives from Implementation Agent (or Financial Review Agent). Hands off to Specification Verification Agent or back to Implementation.
- **When Invoked:** Required for meaningful production-code changes.
- **When NOT Invoked:** May be skipped for trivial documentation changes.

### 2.6 Specification Verification Agent
- **Primary Purpose:** Verifies that the implementation meets the original specification and business rules.
- **Responsibilities:** Validates the final state against Acceptance Criteria.
- **Inputs:** Implementation, tests, feature specification, business rules.
- **Outputs:** Verification findings.
- **Skills Used:** `specification-verification`
- **Rules Followed:** All project rules.
- **Artifacts Owned:** Verification findings/reports.
- **Artifacts Read:** Specifications, business rules, code, tests.
- **Artifacts Must Not Modify:** Production code, tests, specifications.
- **Handoff Relationships:** Receives from Code Review Agent. Hands off to Coordinator / Quality Gate.
- **When Invoked:** Required for features with formal specifications/AC.
- **When NOT Invoked:** May be lightweight/skipped for trivial changes.

### 2.7 Financial Review Agent
- **Primary Purpose:** Conducts specialized review for financial domain accuracy.
- **Responsibilities:** Verifies calculations, rounding, currency handling, and financial formulas.
- **Inputs:** Implementation involving financial logic, financial business rules.
- **Outputs:** Financial review findings.
- **Skills Used:** `code-review`, `multi-agent-quality-gate`, `agent-handoff`, `agent-ownership`
- **Rules Followed:** Financial Calculation Rules, relevant financial-domain project rules.
- **Artifacts Owned:** Financial review findings.
- **Artifacts Read:** Financial code, tests, business rules.
- **Artifacts Must Not Modify:** Production code, tests.
- **Handoff Relationships:** Receives from Implementation Agent. Hands off to Code Review Agent.
- **When Invoked:** Required when financial formulas, financial business rules, portfolio calculations, transactions, allocations, rebalancing, cash, SIP, or fund evaluation logic changes.
- **When NOT Invoked:** Conditional; skipped for all non-financial changes.

## 3. Agent Architecture Workflow
The logical high-level workflow is as follows:

```text
Human
  ↓
Coordinator
  ↓
Planning
  ↓
Test / TDD
  ↓
Implementation
  ↓
Financial Review (when applicable)
  ↓
Code Review
  ↓
Specification Verification
  ↓
Multi-Agent Quality Gate
  ↓
Human Approval
```
*Note: This is a logical workflow. For non-financial features, skip Financial Review. Not every task requires every agent.*

## 4. Minimal Agent Principle
Use the minimum number of agents necessary for the task. 

**Examples:**

*   **Simple documentation:**
    `Coordinator → relevant agent → verification`
*   **Normal backend feature:**
    `Coordinator → Planning → Test → Implementation → Review → Verification`
*   **Financial feature:**
    `Coordinator → Planning → Test → Implementation → Financial Review → Review → Verification → Quality Gate`

Do not invoke all agents automatically.

## 5. Agent Communication
Agents communicate using the Agent Handoff Protocol (`agent-handoff`). Handoffs must include:
- Explicit task context
- AC/BR (Acceptance Criteria / Business Rules) references
- Evidence of work completed
- Blockers (if any)
- Risks identified
- Expected next action

No free-form assumptions should be treated as requirements.

## 6. Ownership Boundaries
As defined in D3 (Agent Ownership Boundaries):
- **Coordinator** does not own production implementation.
- **Test Agent** owns tests.
- **Implementation Agent** owns production code.
- **Review Agent** owns review findings.
- **Verification Agent** owns verification findings.
- **Financial Review** owns financial review findings.

## 7. Parallel Execution
As defined in D4 (Parallel Work Safety), parallel execution must be assessed before starting.

**Default rules:**
- **Financial/shared-domain work:** Sequential execution required.
- **Independent work:** Parallel execution when safe.
- **Uncertain overlap:** Sequential execution required.

## 8. Human Approval
The human remains responsible for key decisions and approvals. Agents may recommend but cannot override human authority on:
- Requirement approval
- Business-rule approval
- Implementation-plan approval
- Ambiguous decisions
- Destructive DB changes
- Major architecture changes
- Scope changes
- Final commit
- Push
- Deployment/release

## 9. Configuration vs. Skills
It is critical to understand the distinction between the different organizational layers:
- **Skills** define HOW an agent performs a task.
- **Agent Configuration** defines WHICH agent uses those skills and WHEN.
- **Rules** define repository-wide constraints.
- **Handoff** defines HOW agents transfer work.
- **Ownership** defines WHAT artifacts agents may modify.
- **Quality Gate** defines WHETHER the workflow is ready.

This distinction must be explicit.

## 10. Agent Invocation Example
**Feature:** "Add Portfolio Summary API"

Since this feature likely aggregates values but may involve financial calculations, the sequence would be:

1.  **Coordinator:** Analyzes the request and plans the agent workflow.
2.  **Planning:** Designs the API endpoints, resources, and database queries.
3.  **Test/TDD:** Writes feature tests for the new API endpoints (failing).
4.  **Implementation:** Writes the controller and resource logic to make tests pass.
5.  **Financial Review (Conditional):** If the summary involves new calculations, this agent reviews the formulas. It would be inserted here before Code Review.
6.  **Code Review:** Reviews the overall code quality and architecture.
7.  **Specification Verification:** Confirms the API matches the required structure and data.
8.  **Quality Gate:** Coordinator runs the final quality gate.
9.  **Human Approval:** Human reviews the findings and approves the completion.
