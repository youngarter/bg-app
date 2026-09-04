---
glob: app/Http/Requests/**
---

# Form Request validation rules

Shared validation rules live in `App\Concerns\*ValidationRules` traits (e.g. `ProfileValidationRules`, `PasswordValidationRules`), used by the `FormRequest` via `rules()`. Do not write validation rules inline in the `FormRequest` when they are shared across requests.
