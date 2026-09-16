# Business Rule: [Rule ID] - [Rule Name]

## 1. Rule ID
[A unique, stable identifier for the rule, e.g., BR-PORT-001. This ID will be referenced in feature specifications and tests.]

## 2. Rule Name
[A concise, descriptive name for the business rule.]

## 3. Domain / Feature
[The specific domain or feature this rule applies to, e.g., Portfolio Performance, Holdings, Transactions.]

## 4. Business Requirement
[The underlying business or user requirement that necessitates this rule.]

## 5. Rule Statement
[An explicit, unambiguous, and testable statement defining the rule. Clearly distinguish confirmed rules from assumptions.]

## 6. Formula / Calculation (when applicable)
[Explicit financial formulas required by the rule. Detail the calculation exactly as it should be applied. Avoid floating-point ambiguity and do not silently round.]

## 7. Preconditions
[What must be true in the system before this rule applies or can be evaluated.]

## 8. Inputs
[The specific data points or inputs required to evaluate this rule.]

## 9. Expected Behavior
[The required system behavior when the rule's conditions are met.]

## 10. Exceptions
[Specific conditions where this rule explicitly does not apply or should behave differently.]

## 11. Edge Cases
[Boundary conditions, zero states, missing data, and other unusual scenarios relevant to this rule.]

## 12. Examples
[Concrete examples of the rule in action, such as input/output pairs or specific scenarios.]

## 13. Related Acceptance Criteria
[References to the acceptance criteria in feature specifications that depend on this rule.]

## 14. Related Tests
[References to the automated tests (e.g., test class or method names) that verify this rule.]

## 15. Related Documentation
[Links or references to other architectural, API, or domain documentation that provides context for this rule.]

## 16. Status
[Status of the rule. Valid values: Proposed | Confirmed | Deprecated]

## 17. Open Questions
[Any unresolved ambiguity, questions, or missing details that need clarification before the rule can be confirmed.]
