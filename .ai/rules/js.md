---
paths:
  - 'resources/js/**'
---

# Js

## Money, state and permissions have fixed UI rules
This is an accounting product. These are correctness rules, not preferences.

- Amounts are bigint centimes in the backend. Format once, in a shared helper, as "1 234,56 DH" — French spacing, comma decimal. Right-align, tabular-nums (already applied to table by app.css).
- Never color a debit or credit column green/red. In accounting a debit is neither good nor bad; coloring it teaches a false reading. Only the state of a balance — a jour, en retard, douteuse — gets color.
- State is never carried by color alone: always a pill with a written label, so it survives colorblindness and print.
- Never render a computed money figure without a path back to its origin. Every balance drills down to journal entries via source_type/source_id (spec 09 §4).
- Licence read_only: keep write controls visible but disabled, with the reason stated. Never hide them — a syndic must understand why they cannot save, and consultation, export and printing stay available.
- Irreversible actions (issue a fund call, revoke access, finalize a handover, reverse an entry) need a confirmation naming what becomes immutable. Where the spec requires a motif it is >= 10 characters, enforced in the Action too.
- Wide tables go in their own overflow-x container; the page body never scrolls sideways.
- UI copy is French. Dates dd/mm/yyyy, currency DH.
