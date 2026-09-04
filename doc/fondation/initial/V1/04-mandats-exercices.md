# 04 — Mandats et exercices

← [Sommaire](README.md)

---

## 1. Deux entités indépendantes

> **L'exercice est une entité autonome. Ne jamais coder
> `exercice.annee = mandat.annee + 1`.**

| | Mandat | Exercice |
|---|---|---|
| Nature | fait juridique | période comptable |
| Décidé par | l'AG | le syndic / les statuts |
| Durée de référence | 2 ans (défaut de saisie) | 12 mois |
| Interruptible | **oui, à tout moment** | oui, clôture anticipée |
| Confère un accès | **non** (voir [01](01-plateforme-licence.md)) | non |

---

## 2. Le mandat

Lie une résidence à une société de syndic sur une période datée.

```
draft ──▶ active ──┬──▶ terminated   (clôture anticipée, motivée)
            │      ├──▶ expired      (ends_on atteint)
            │      │
            ├─▶ suspended ─▶ active
            │
            └──────────────▶ closed  (après passation)
```

- La durée de 2 ans est un **défaut de saisie, jamais une règle codée en dur**.
- Un mandat clôturé n'est **jamais supprimé**. Consultable, auditable, exportable.
- `terminated` exige un motif et une pièce.

Un mandat porte : résidence, société de syndic, `started_on`, `ended_on`, état,
référence du PV d'AG l'ayant élu, honoraires convenus.

---

## 3. L'exercice

Un mandat contient un ou plusieurs exercices :

```
Mandat #12 (01/01/2024 → 31/12/2025)
   ├── Exercice 2024
   └── Exercice 2025
```

### 3.1 Interruption en cours d'année

```
Mandat #12 (01/01/2024 → 15/08/2025)
   ├── Exercice 2024
   └── Exercice 2025-A (01/01 → 15/08) — clôture anticipée

Mandat #13 (16/08/2025 → …)
   └── Exercice 2025-B (16/08 → 31/12)
```

> **Deux mandats peuvent porter des données sur la même année civile.**
> C'est normal, et le modèle l'accepte nativement.

Conséquence : `exercises.label` est une **chaîne** (`2025-A`), pas un entier.
Aucune contrainte d'unicité sur une « année ».

### 3.2 Invariants

- Les exercices d'une résidence **ne se chevauchent jamais** dans le temps. Testé.
- Chaque exercice appartient à **exactement un** mandat.
- `[exercise.started_on, exercise.ended_on]` ⊆ `[mandate.started_on, mandate.ended_on]`.
- **Un seul exercice `open`** par résidence à la fois.

### 3.3 États

```
open ──▶ closing ──▶ closed
  ▲          │
  └──────────┘   (échec des contrôles → retour en open)
```

Une fois `closed` :

| | |
|---|---|
| ❌ | modifier un appel, supprimer un paiement, ajouter une écriture datée dans l'exercice |
| ✅ | consulter, exporter, auditer |
| ✅ | corriger par **écriture d'ajustement** sur l'exercice suivant (extourne + nouvelle écriture) |

**Aucune suppression physique dans le ledger, jamais.**

---

## 4. Clôture d'exercice — V1

Périmètre réduit : la clôture V1 **gèle et reporte**. Les états financiers complets
sont en V2.

```
Exercice OPEN
     ↓  le syndic lance la clôture
Exercice CLOSING
     ↓  contrôles automatiques bloquants
         · aucun appel en état draft ou submitted
         · aucun paiement non confirmé
         · aucune imputation en attente
         · Σ des lignes du ledger équilibrée sur chaque pièce
         · cohérence : solde de chaque compte de lot = projection du ledger
     ↓  écritures de clôture
         · solder les comptes de PRODUITS (711x)
         · résultat → report à nouveau (119x)
     ↓  production et archivage de la balance et du grand livre (figés)
     ↓  approbation du conseil syndical
Exercice CLOSED
     ↓
À-nouveaux : réouverture des comptes de BILAN uniquement
             (342x par auxiliaire, 512/516, 119x) sur le nouvel exercice
```

### Points de conception

- **Le résultat n'est pas ventilé sur les comptes des lots.** Il reste au report à
  nouveau de la copropriété. La partie double fournit le résultat comptable ; le choix
  de ne pas le répartir reste entier et documenté.
- Les à-nouveaux **ne recréent pas la dette** : ils rouvrent les comptes de bilan avec
  leur solde de clôture, dans une pièce `source_type = Closing` qui **pointe l'exercice
  d'origine**.
- Un échec de contrôle ramène l'exercice en `open` avec la liste des anomalies. Aucune
  écriture n'est produite.

---

## 5. Règle d'or — un appel ne migre jamais

> **Un appel de fonds n'est jamais déplacé d'un mandat ou d'un exercice à un autre.**

Si `AF001` (Mandat 12, Exercice 2025, 1 000 DH) est payé à hauteur de 600 DH et que le
mandat se clôture, on ne réaffecte **pas** `AF001` au Mandat 13. On conserve :

```
AF001 · Mandat 12 · Exercice 2025
  Appelé  1 000
  Payé      600
  Solde     400
```

et la clôture crée un **à-nouveau de 400 DH** sur l'exercice suivant.

> **La dette est continuée, jamais recréée ni déplacée.**
