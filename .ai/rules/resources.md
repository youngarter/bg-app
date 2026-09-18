---
paths:
  - 'resources/**'
---

# Resources

## Never hardcode a color, font or radius — use the tokens
The visual system is defined once in resources/css/app.css and specified in doc/fondation/initial/V1/23-branding-documents.md. Read the markdown, not systeme-visuel.html (810 KB of base64).

- No raw hex, rgb() or arbitrary Tailwind values in components. Use the semantic classes: bg-background, text-foreground, bg-card, text-muted-foreground, border-border, bg-primary, and the domain colors text-ok / text-warn / text-crit / text-info with their -surface backgrounds.
- Fonts by role: font-display (Poppins) for headings only, font-sans (Inter) for everything read, font-mono (JetBrains Mono) for accounting references — AF-2026-001, QUITT-2026-00042, account codes like 3422.
- The brand gradient (#ff007f -> #ff5500) is for brand surfaces only: logo, page header, footer rule, empty states. Never on a button, badge or any interactive element — it has no coherent hover or disabled state.
- #ff007f is 3.8:1 on white and fails AA for text. It is a graphic color. Anything carrying text or an action uses --primary (#c4005f, 6.0:1). #ff5500 carries text in exactly one place: the "Syndic" line of the document lockup, at 16px/600 where the large-text threshold applies.
- Every color must resolve in both themes through the tokens. Never define a color only inside .dark.
