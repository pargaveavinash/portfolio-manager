---
name: parallel-work-safety
description: D4 - Parallel Work Safety rules for coordinating multiple agents working concurrently on the Portfolio Manager.
---

# Parallel Work Safety

## 1. Purpose

Parallel agent execution is allowed only when the work can be safely isolated.

Correctness and repository integrity take priority over speed.

For financial features, prefer serialized work when parallel execution could affect shared financial rules, calculations, migrations, or portfolio state.

## 2. Core Principles

- Never assume parallel work is safe.
- First identify affected files, components, database objects, business rules, and tests.
- Agents must have clearly separated ownership.
- Shared artifacts require coordination.
- The same production file must not be independently modified by multiple agents at the same time.
- The same test file must not be independently modified by multiple agents at the same time.
- Shared migrations require serialization.
- Shared business rules require serialization and approval.
- Agents must inspect current Git state before starting.
- Agents must inspect the repository again before handoff.
- Unexpected changes must be reported rather than overwritten.
- No agent may reset, discard, stash, or overwrite another agent's work without explicit human approval.

## 3. Parallel-Safety Assessment

Before starting parallel work, the coordinating agent must classify the work as:

### SAFE
Parallel execution is allowed when:
- files are clearly disjoint
- ownership is disjoint
- business rules are independent
- database changes do not conflict
- tests are independent
- no shared generated artifacts are involved

### CAUTION
Parallel work may be possible but requires coordination when:
- agents touch related modules
- shared interfaces are involved
- shared services are involved
- tests use shared fixtures/factories
- API contracts may interact
- documentation dependencies exist

### UNSAFE
Parallel work is prohibited when:
- the same production file is being modified
- the same test file is being modified
- the same migration/schema area is being changed
- the same financial formula/business rule is being changed
- one task depends on another unfinished task
- agents may overwrite each other's work
- a destructive database operation is involved
- requirements or business rules are unresolved

## 4. Shared Artifact Rules

### Production Code
One agent owns a production file at a time.

### Tests
One agent owns a test file at a time.

Test ownership remains with the Test Agent even when Implementation runs the tests.

### Database Migrations
Migration creation/modification must be coordinated.

Do not allow two agents to independently create conflicting migrations for the same domain change.

Do not rewrite an existing migration that may already have been executed.

### Database Schema
Schema changes affecting shared tables must be serialized unless the coordinator explicitly determines they are independent.

### API Contracts
Changes to shared endpoints, request formats, response formats, or API Resources require coordination.

### Business Rules
Changes to the same Business Rule require serialization.

No agent may create competing interpretations of the same financial rule.

### Documentation
Documentation may be parallel only when files/sections are clearly separated.

## 5. Financial Feature Safety

For this Portfolio Manager, explicitly identify these as high-risk shared areas:

- portfolio valuation
- holding position calculations
- transaction calculations
- cash balance
- invested cost
- realized/unrealized profit
- portfolio performance
- asset allocation
- rebalancing
- SIP calculations
- mutual fund evaluation/switching rules
- currency conversion
- financial rounding/precision rules

When multiple agents could modify related financial behavior:

- prefer serialized execution
- use the approved Business Rules as the source of truth
- require Financial Review when applicable
- do not allow agents to silently change formulas
- preserve decimal precision
- document any formula impact
- run relevant financial regression tests

## 6. Git Working Tree Safety

Before starting:
- Run `git status` to verify a clean or expected working state.
- Identify the exact branch and active changes.

During execution:
- Do not blindly overwrite modified files if they differ from expectations.
- Report unexpected staged or unstaged changes.

Before handoff:
- Run `git diff --check` to ensure no whitespace/conflict markers.
- Run `git status` to verify exactly what was changed.

## 7. Parallel Safety Checklist

- [ ] Safety assessment completed (SAFE, CAUTION, or UNSAFE).
- [ ] Required file/component isolation verified.
- [ ] No overlapping ownership of the same file.
- [ ] Migrations and schema changes coordinated/serialized.
- [ ] Shared business/financial rules serialized.
- [ ] Git state inspected before starting.
- [ ] Unexpected changes reported, not overwritten.

## 8. Relationship With Other Skills

- **D1 (Agent Roles)** = WHO owns the work.
- **D2 (Handoff Protocol)** = HOW work moves between roles.
- **D3 (Ownership Boundaries)** = WHAT each role may modify.
- **D4 (Parallel Work Safety)** = WHEN multiple agents may execute concurrently.

## 9. Definition of Done

D4 is complete only when:
- safe/caution/unsafe states are defined
- shared artifact rules are explicit
- financial feature safety boundaries are preserved
- Git working tree inspection rules are defined
- no existing project behavior is changed
