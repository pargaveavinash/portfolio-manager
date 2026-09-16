# Portfolio Manager — Git Workflow Rules

## Branching Strategy
Do not develop new features directly on `main`.

Use a feature-branch workflow:
```text
main
  ↓
feature/<feature-name>
  ↓
TDD -> Implementation -> Tests -> Full test suite
  ↓
Review
  ↓
Commit
  ↓
Push
```

## Pre-commit Checks
Before committing, always run:
```bash
git status
git diff --check
```
Review the diff to ensure no unrelated changes are included, and no secrets, credentials, or `.env` files are accidentally committed.

## Commit Messages
* Do not commit unrelated changes.
* Use clear conventional commit messages where appropriate.
  * Example: `feat: add holding invested cost calculation`
* Do not push destructive changes without explicit approval.
