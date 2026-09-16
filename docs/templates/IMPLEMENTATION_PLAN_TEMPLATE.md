# Implementation Plan: [Feature Name]

## 1. Feature
[Name of the feature being implemented.]

## 2. Specification Reference
[Link to the approved Feature Specification document.]

## 3. Business Rule References
[List of related Business Rule IDs (e.g., BR-PORT-001) that must be enforced by this implementation.]

## 4. Acceptance Criteria References
[List of related Acceptance Criteria IDs (e.g., AC-HLD-002) that this implementation must satisfy.]

## 5. Current Implementation Analysis
[Summary of findings from inspecting the current repository state. What existing components currently handle related responsibilities? What can be reused?]

## 6. Affected Components
[High-level list of architectural components (e.g., Controllers, Models, Policies, Services, Resources) impacted by this change.]

## 7. Files Likely to Change
[Specific file paths that are expected to be modified or created. Base this on actual repository inspection, not guesswork.]

## 8. Database Changes
[Required migrations, new tables, columns, indexes, or relationships. If none, state "None". Do not modify existing applied migrations.]

## 9. API Changes
[Specific controllers, routes, requests, and resource files to be updated or created. Respect the existing API versioning (e.g., V1).]

## 10. Business Logic Changes
[How the implementation will fulfill the business rules. Differentiate confirmed behavior changes from assumptions. Do not invent new rules.]

## 11. Test Plan
[List the specific Feature and Unit tests that will be created or modified to satisfy the Acceptance Criteria.]

## 12. TDD Sequence
[The order in which tests and corresponding implementation will be built during the RED -> GREEN -> REFACTOR cycles.]

## 13. Authorization / Security Considerations
[How the implementation will enforce data ownership and authorization (e.g., updating Policies, ensuring server-side validation). Must verify cross-user isolation.]

## 14. Financial Calculation Considerations
[Where and how financial formulas will be implemented. Note how floating-point precision, rounding, and zero-states will be handled securely.]

## 15. Edge Cases
[List specific boundary conditions or unexpected states and how the code will handle them.]

## 16. Documentation Changes
[Which architectural, domain, or API documents need to be updated after this code is written.]

## 17. Risks / Regression Considerations
[Potential risks to existing functionality and how they will be mitigated through testing or specific implementation choices.]

## 18. Implementation Steps
[A step-by-step checklist for the developer/agent to follow, breaking the work into manageable tasks without writing actual code here.]

## 19. Verification Plan
- [ ] Targeted tests executed and passing (0 failures).
- [ ] Complete application test suite executed and passing (0 failures).
- [ ] Authorization / Security boundaries manually or automatically verified.
- [ ] Financial formulas and precision boundaries verified.

## 20. Open Questions
[Any unresolved implementation details or architectural questions that need answering before execution begins.]

## 21. Approval Status
[Status of this plan. Valid values: Draft | Ready for Review | Approved | Rejected]

**Important:** Do not begin implementation (TDD RED phase) until this plan's status is Approved by a human.
