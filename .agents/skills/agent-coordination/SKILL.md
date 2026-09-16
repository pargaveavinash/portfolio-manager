---
name: agent-coordination
description: D5 - Agent Coordination / Orchestration for coordinating specialized agents safely and efficiently.
---

# Agent Coordination / Orchestration

## 1. Purpose

Orchestration:
- selects the appropriate agents
- establishes execution order
- manages dependencies
- coordinates handoffs
- prevents conflicting work
- collects verification evidence
- ensures the feature reaches the required quality gates

The coordinator must prefer the simplest workflow that safely satisfies the feature requirements.

Not every feature requires every agent.

## 2. Coordinator Role

The Coordinator is responsible for:
- understanding the approved feature scope
- determining which agents are required
- creating the execution sequence
- checking dependencies
- assigning ownership
- coordinating handoffs
- detecting conflicts
- collecting evidence
- ensuring quality gates are completed

The Coordinator must NOT:
- invent requirements
- override approved business rules
- silently change tests
- silently change financial formulas
- bypass review/verification
- make final Git decisions unless explicitly authorized

*The Coordinator may delegate work but remains responsible for workflow coordination.*

## 3. Standard Feature Orchestration

The default workflow for feature development is:

```text
Requirement / Specification
        ↓
Planning
        ↓
Implementation Plan Approval
        ↓
Test Selection / TDD
        ↓
Implementation
        ↓
Code Review
        ↓
Specification Verification
        ↓
Financial Review (when applicable)
        ↓
Human Approval
        ↓
Git / Release decision
```

Some stages may be combined or skipped when the feature genuinely does not require them, but required quality gates must never be bypassed.

## 4. Agent Selection

A lightweight decision model for selecting agents.

### Always consider
- Planning
- Test/TDD
- Implementation
- Code Review
- Specification Verification

### Required when applicable
- Financial Review for financial calculations/rules
- Additional security review for security-sensitive changes
- Database-focused validation for significant schema changes

### Not required
Do not invoke unnecessary specialized agents for trivial changes.

Examples:
- Documentation-only change → may not require Implementation/TDD.
- Simple typo fix → lightweight workflow.
- Financial calculation → full financial-aware workflow.

## 5. Execution Modes

### Sequential
Use when:
- tasks share files
- business rules are related
- database changes overlap
- financial calculations overlap
- dependencies are strong

*Example:* Planning → TDD → Implementation → Review

### Parallel
Use only when D4 determines the tasks are SAFE.

*Example:* 
Agent A → API documentation
Agent B → independent tests
Both → Coordinator → Review

### Hybrid
Use when independent work can happen in parallel but dependent work must remain sequential.

*Example:*
```text
Planning
   ↓
┌───────────────┬───────────────┐
│               │               │
Test preparation  Documentation
│               │
└───────┬───────┘
        ↓
Implementation
        ↓
Review
```

Hybrid should be the preferred model when safe parallelism provides real value.

## 6. Dependency Management

Before execution, identify:
- task dependencies
- artifact dependencies
- code dependencies
- database dependencies
- business-rule dependencies
- test dependencies

Represent dependencies simply:
`Task A → Task B` (Meaning B must not proceed until A produces the required output.)

If a dependency is unresolved, mark the task BLOCKED.

*Never assume an unfinished dependency is complete.*

## 7. Feature Execution State

The lightweight state machine for a feature:

- **DRAFT:** Requirements are being gathered.
- **PLANNING:** Implementation plan is being formulated.
- **PLAN_APPROVED:** Plan is approved and ready for execution.
- **TESTING:** Test Agent is writing/running tests (TDD).
- **IMPLEMENTING:** Implementation Agent is writing production code.
- **REVIEW:** Code Review Agent is reviewing the implementation.
- **VERIFICATION:** Specification Verification Agent is checking against AC.
- **FINANCIAL_REVIEW:** (If applicable) Financial Review Agent checks math/rules.
- **HUMAN_APPROVAL:** Human reviews final state.
- **READY_FOR_GIT:** Approved for commit/push.
- **COMPLETE:** Feature is done.

Also allow:
- **BLOCKED:** Waiting on an external dependency, clarification, or human.
- **RETURNED:** Sent back to a previous stage for rework.
- **REJECTED:** Feature implementation is fundamentally flawed or violates rules.

*A feature must not move forward while a required stage is BLOCKED or REJECTED.*

## 8. Coordination Record

A lightweight coordination record includes:

- Feature / Task
- Current State
- Coordinator
- Active Agents
- Agent Responsibilities
- Dependencies
- Expected Files
- Completed Handoffs
- Pending Handoffs
- Verification Evidence
- Findings
- Blockers
- Risks
- Next Action

*Use the existing Agent Handoff Protocol rather than inventing another handoff structure.*

## 9. Agent Invocation Rules

Before invoking an agent, the Coordinator must verify:
- correct role selected
- required inputs available
- required approvals present
- ownership boundaries understood
- dependencies satisfied
- parallel-safety checked
- task scope defined

After an agent completes:
- validate its output
- check expected artifacts
- check reported evidence
- inspect status/diff when appropriate
- accept, return, reject, or block the handoff

*Do not assume an agent completed work merely because it reports completion.*

## 10. TDD Orchestration

1. Planning produces an approved implementation plan.
2. Test/TDD Agent maps AC/BR to tests.
3. Tests enter RED state.
4. Implementation Agent receives failing-test evidence.
5. Implementation Agent changes production code.
6. Tests become GREEN.
7. Refactoring occurs only under test protection.
8. Targeted tests run.
9. Full suite runs at the appropriate quality gate.
10. TDD Quality Gate is evaluated.

The Coordinator must not allow the Implementation Agent to bypass the required TDD process. Use existing TDD skills for detailed execution.

## 11. Failure Coordination

When an agent reports failure:
- do not immediately reassign randomly
- classify the failure
- use Failure Analysis
- determine responsible role
- route the issue to the correct agent
- update coordination state
- re-run required verification

Examples:
- Test failure → Failure Analysis → Implementation or Test role
- Requirement ambiguity → Product/Human
- Implementation quality issue → Implementation
- Review finding → Implementation
- Specification mismatch → Implementation / Planning / Product depending on root cause
- Financial discrepancy → Financial Review + appropriate owner

## 12. Financial Feature Orchestration

For financial features, define a stricter sequence:

```text
Approved Business Rules
        ↓
Approved Implementation Plan
        ↓
Financial-aware Test Selection
        ↓
TDD
        ↓
Implementation
        ↓
Financial Review
        ↓
Code Review
        ↓
Specification Verification
        ↓
Human Approval
```

Financial Review may happen before Code Review when mathematical correctness needs early validation.

Financial ambiguity is BLOCKING.

*No agent may silently reinterpret financial formulas.*

## 13. Database / Migration Orchestration

For migrations:
- identify schema ownership
- check existing migrations
- prevent parallel conflicting migrations
- serialize overlapping schema changes
- test migration behavior
- verify rollback expectations where applicable
- report destructive operations
- require human approval for destructive DB changes

*The Coordinator must treat shared schema changes as high-conflict work.*

## 14. API Change Orchestration

For API changes:
- identify affected endpoint
- identify request/response contract
- identify authorization requirements
- identify API Resources
- identify tests
- coordinate frontend/backend dependencies when applicable

*Do not allow agents to independently create incompatible API contracts.*

## 15. Parallel Work Coordination

See D4 (Parallel Work Safety) directly.

Before parallel execution:
1. Identify tasks.
2. Identify expected files.
3. Identify shared artifacts.
4. Identify dependencies.
5. Run safety classification.
6. Start only SAFE work in parallel.
7. Coordinate CAUTION work explicitly.
8. Do not run UNSAFE work in parallel.

After parallel work:
- collect each agent's handoff
- check conflicts
- integrate safely
- run combined verification

## 16. Human Intervention Rules

The Coordinator must stop and request human decision for:
- ambiguous requirements
- ambiguous business rules
- disputed financial formulas
- major architecture changes
- scope expansion
- destructive database operations
- unresolved ownership conflicts
- security-critical unresolved findings
- discarding another agent's work
- final commit/push/release decisions

*Agents recommend; humans decide.*

## 17. Minimal-Agent Principle

Use the minimum number of agents required to safely complete the task.

Do NOT create multi-agent workflows simply because multiple agents are available.

Examples:
- Simple documentation: Coordinator → Documentation Agent → Verification
- Normal feature: Planning → Test → Implementation → Review → Verification
- Financial feature: Planning → Test → Implementation → Financial Review → Code Review → Verification → Human

*This principle is important for keeping the system efficient.*

## 18. Orchestration Completion Checklist

- [ ] Feature scope confirmed
- [ ] Required agents selected
- [ ] Dependencies identified
- [ ] Ownership assigned
- [ ] Parallel safety assessed
- [ ] Required approvals present
- [ ] Handoffs accepted
- [ ] TDD completed where required
- [ ] Review completed
- [ ] Specification verification completed
- [ ] Financial review completed when required
- [ ] All blockers resolved
- [ ] Verification evidence recorded
- [ ] Human approval obtained where required
- [ ] Git decision remains with human

## 19. Relationship With D1–D4

- **D1 — Roles** = WHO owns the work
- **D2 — Handoff** = HOW work moves between roles
- **D3 — Ownership** = WHAT each role may modify
- **D4 — Parallel Safety** = WHEN work may run simultaneously
- **D5 — Coordination** = HOW the whole workflow is orchestrated

## 20. Definition of Done

D5 is complete when:
- coordinator responsibilities are defined
- agent selection rules are defined
- sequential/parallel/hybrid execution is defined
- dependencies are managed
- feature states are defined
- failures are routed correctly
- TDD orchestration is defined
- financial orchestration is defined
- database/API coordination is defined
- human escalation is defined
- minimal-agent principle is defined
- no existing project behavior is changed
