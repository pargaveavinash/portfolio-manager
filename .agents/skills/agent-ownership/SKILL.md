---
name: agent-ownership
description: D3 - Agent Ownership Boundaries
---

# Agent Ownership Boundaries

## 1. Purpose

Ownership boundaries prevent:
- conflicting edits
- accidental scope expansion
- agents modifying artifacts owned by another role
- hidden changes
- unclear responsibility
- unsafe autonomous behavior

Ownership is about responsibility and modification authority, not unrestricted filesystem access.

## 2. Ownership Principles

- Each agent has a primary artifact ownership area.
- Agents may read other artifacts when required for their task.
- Read access does not imply modification ownership.
- An agent must not modify another role's owned artifact without an explicit handoff/approval.
- If another artifact must change, the agent must hand the work to its owning role.
- Existing repository conventions and AGENTS.md remain authoritative.
- Human approval overrides all agent ownership permissions.
- No agent may use ownership boundaries to bypass security, testing, financial, or approval requirements.

## 3. Product / Requirements Role

### Owns
- feature requirements
- product intent
- scope
- out-of-scope decisions
- Feature Specification
- Acceptance Criteria
- Business Rule proposals/clarifications

### May read
- architecture
- implementation plans
- tests
- review findings
- verification findings

### Must not modify
- production application code
- tests
- migrations
- implementation plans after approval
- code-review results

### Escalate when
- requirement is ambiguous
- business rule is unclear
- scope changes
- financial behavior is unclear

## 4. Planning Role

### Owns
- Implementation Plan
- implementation sequencing
- affected-component analysis
- technical implementation strategy

### May read
- specifications
- business rules
- acceptance criteria
- repository code
- tests
- architecture
- existing skills/rules

### Must not modify
- production code as part of planning
- tests as part of planning
- approved specifications
- review findings

### Escalate when
- implementation requires scope expansion
- architecture conflict exists
- required behavior is unspecified
- destructive DB operation is proposed

## 5. Test / TDD Role

### Owns
- Feature tests
- Unit tests
- test scenarios
- test mapping
- test evidence
- failure-analysis outputs

### May read
- production code
- specifications
- implementation plans
- business rules
- migrations
- API resources/controllers
- existing tests

### Must not modify
- production application code
- business rules
- approved specifications
- implementation plans
- review findings

*The Test Agent may identify the production behavior required for a failing test, but production implementation belongs to the Implementation Agent.*

## 6. Implementation Role

### Owns
- production application code
- production business logic
- controllers
- services
- models
- policies
- API resources
- migrations when included in the approved plan
- production configuration changes when included in the approved plan

### May read
- tests
- specifications
- business rules
- implementation plans
- architecture
- review findings

### Must not modify
- tests merely to make implementation pass
- approved requirements
- business rules
- acceptance criteria
- review findings to hide issues

*If implementation reveals that a test, requirement, or business rule appears incorrect, the Implementation Agent must stop and use the established failure-analysis/handoff process rather than silently changing the owned artifact of another role.*

## 7. Code Review Role

### Owns
- review findings
- severity classifications
- review report
- remediation recommendations

### May read
- all relevant code
- tests
- specifications
- business rules
- implementation plan
- Git diff
- test evidence

### Must not modify
- production code during review
- tests during review
- requirements
- business rules

*The Review Agent reports problems; the Implementation Agent performs approved remediation.*

## 8. Specification Verification Role

### Owns
- specification traceability
- AC verification
- BR verification
- final verification report

### May read
- specification
- AC
- business rules
- implementation plan
- production code
- tests
- review findings
- verification evidence

### Must not modify
- production code
- tests
- approved specification
- implementation plan

*If verification fails, return the work with findings rather than modifying implementation.*

## 9. Financial Review Role

### Owns
- financial correctness findings
- formula validation findings
- precision/rounding findings
- financial risk assessment

### May read
- business rules
- financial formulas
- production code
- tests
- database schema
- API behavior
- verification evidence

### Must not modify
- financial production logic
- financial tests
- business rules
- specifications

*If a financial rule is ambiguous or mathematically disputed, escalate to the human/product authority. Do not require this role for features with no financial impact.*

## 10. Human Ownership / Authority

The human developer remains the final authority for:
- approving requirements
- approving business rules
- approving implementation plans
- resolving ambiguous requirements
- resolving disputed financial rules
- approving destructive database operations
- approving major architectural changes
- approving scope changes
- final commit
- final push
- release/deployment decisions

*Agents may recommend; they do not override these decisions.*

## 11. Artifact Ownership Matrix

| Artifact | Primary Owner | Other Roles May Read | Other Roles May Modify |
|----------|---------------|----------------------|-------------------------|
| Feature Specification | Product | Yes | Owning role only / Human approval required |
| Business Rules | Product | Yes | Owning role only / Human approval required |
| Acceptance Criteria | Product | Yes | Owning role only / Human approval required |
| Implementation Plan | Planning | Yes | Owning role only / Human approval required |
| Feature Tests | Test / TDD | Yes | Owning role only |
| Unit Tests | Test / TDD | Yes | Owning role only |
| Production Code | Implementation | Yes | Owning role only |
| Migrations | Implementation | Yes | Owning role only / Human approval required |
| API Resources | Implementation | Yes | Owning role only |
| Review Report | Code Review | Yes | Owning role only |
| Verification Report | Specification Verification | Yes | Owning role only |
| Financial Review Report | Financial Review | Yes | Owning role only |
| Git commits | Human | Yes | Human approval required |

## 12. Cross-Role Change Requests

When an agent discovers that another role's artifact must change:
1. Identify the required change.
2. Stop modification of the foreign artifact.
3. Record the reason.
4. Create a handoff/request to the owning role.
5. Owning role evaluates the request.
6. If approval is needed, obtain human approval.
7. Owning role modifies its artifact.
8. Receiving agent revalidates the updated artifact.
9. Continue only after the handoff is accepted.

## 13. Ownership Conflict Resolution

- Never resolve ownership conflicts by silently editing both artifacts.
- Prefer the role that owns the artifact.
- Requirements/business-rule conflicts go to Product/Human authority.
- Test-vs-implementation conflicts use Failure Analysis.
- Financial-rule conflicts require Financial Review and, when ambiguous, human decision.
- Architecture conflicts require Planning/Review and human approval when material.
- Security/authorization conflicts must be treated as blocking until resolved.

## 14. Git Ownership and Change Scope

- Agents may inspect Git status/diff/log.
- Agents must only change files within their approved task scope.
- A role's ownership does not automatically authorize Git staging.
- No agent may automatically commit or push unless explicitly authorized by the human workflow.
- Before handoff, the sending agent must identify changed files.
- Unexpected changed files must be reported.

## 15. Ownership Safety Checklist

- [ ] I know my role.
- [ ] I know which artifacts I own.
- [ ] I am not modifying another role's artifact.
- [ ] Required inputs are approved.
- [ ] My changes are within approved scope.
- [ ] Tests/business rules/specifications are not silently altered.
- [ ] Financial rules are not silently changed.
- [ ] Authorization/security boundaries are preserved.
- [ ] Unexpected changes are reported.
- [ ] Required handoff is prepared.
- [ ] Human approval is requested where required.

## 16. Relationship With D1 and D2

- **D1 (Agent Roles)** = WHO owns the responsibility.
- **D2 (Handoff Protocol)** = HOW work moves between roles.
- **D3 (Ownership Boundaries)** = WHAT each role may and may not modify.

## 17. Definition of Done

D3 is complete only when:
- every defined agent has an ownership boundary
- read vs modify authority is explicit
- cross-role changes have a defined process
- ownership conflicts have a defined escalation path
- Git boundaries are explicit
- human authority is explicit
- financial and security boundaries are preserved
- no existing project behavior is changed
