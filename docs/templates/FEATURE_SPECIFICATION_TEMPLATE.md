# Feature Specification: [Feature Name]

## 1. Feature
[A short, clear name for the feature being specified.]

## 2. Purpose
[A brief explanation of what this feature achieves and why it is being built.]

## 3. Problem / User Need
[The specific problem this feature solves for the user or the business.]

## 4. Scope
[What is explicitly included in the implementation of this feature.]

## 5. Out of Scope
[What is explicitly excluded from this feature to prevent scope creep.]

## 6. Preconditions
[What must be true in the system or for the user before this feature can be used or executed.]

## 7. Business Rules
[Specific rules that govern how the feature behaves. Do not invent rules; document only what is required.]

## 8. Financial Rules / Formulas (when applicable)
[Explicit financial calculations required. Avoid floating-point ambiguity. Do not silently round. Detail formulas exactly as they should be implemented.]

## 9. Acceptance Criteria
[Clear, testable statements that define when the feature works as intended. These should easily translate into automated tests.]
* **Scenario:** [Scenario name]
  * **Given:** [Context]
  * **When:** [Action]
  * **Then:** [Expected result]

## 10. Authorization / Ownership Requirements
[Who can access this feature? How is user data ownership protected? Ensure cross-user data access is explicitly denied.]

## 11. API Requirements (when applicable)
[Expected endpoints, request payloads, and response structures. Mention HTTP verbs and status codes.]

## 12. Database Requirements (when applicable)
[New tables, columns, relationships, or indexes needed. Note if soft deletes are required.]

## 13. Edge Cases
[Boundary conditions, zero states, missing data, and other unusual scenarios the feature must handle securely.]

## 14. Error Handling
[How the system should respond when things go wrong (e.g., validation failures, unauthorized access, calculation errors).]

## 15. Testing Requirements
[Specific testing focus areas (e.g., Feature vs. Unit tests, financial precision testing, authorization boundaries). Must result in 0 failures.]

## 16. Documentation Impact
[Which existing documents (API docs, architecture, domain model) will need updating once this feature is implemented.]

## 17. Open Questions / Ambiguities
[Any unresolved questions or unclear requirements that must be answered before implementation begins.]

## 18. Definition of Done
* [ ] Requirement understood and verified.
* [ ] Specification reviewed and approved.
* [ ] Failing tests written (TDD).
* [ ] Code implemented satisfying all acceptance criteria.
* [ ] Targeted and full test suites pass with 0 failures.
* [ ] Authorization and ownership boundaries verified.
* [ ] Edge cases handled.
* [ ] Code reviewed against `code-review` skill.
* [ ] Relevant project documentation updated.
