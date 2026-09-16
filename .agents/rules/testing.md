# Portfolio Manager — Testing Rules

## Testing Conventions
* Use native PHPUnit.
* Feature tests belong in `tests/Feature`.
* Unit tests belong in `tests/Unit`.
* Follow the existing `Tests\TestCase` convention.
* New behavior must follow RED → GREEN → REFACTOR.
* Run targeted tests first.
* Run the complete test suite before declaring the feature complete.
* 0 failures required.
* Never remove or weaken tests merely to make them pass.
