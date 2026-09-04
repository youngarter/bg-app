# 08 — Le ledger comptable

← [Sommaire](README.md)

## 🔒 Verrous n°1 et n°2

> **Il n'existe qu'un seul ledger.** Tous les soldes de Bayan — lots, trésorerie,
> produits — sont des **projections** de `journal_entry_lines`.
>
> **Il n'existe pas de sous-ledger copropriétaire distinct.** Le compte d'un lot est
> la lecture `(compte 342x, auxiliaire = lot_account)` du grand livre.

### Pourquoi ce refus est structurant

La revue d'architecture proposait deux niveaux : un sous-ledger copropriétaire et un
grand livre comptable, reliés par des références explicites. **C'est rejeté.**

```
❌ ARCHITECTURE REJETÉE            ✅ ARCHITECTURE RETENUE

 Receivables ledger                  journal_entries
        +                                  +
 Accounting ledger                  journal_entry_lines
        ║                                  │
   deux moteurs qui              ┌─────────┴─────────┐
   recalculent chacun            ▼                   ▼
   la réalité                LIVRE-JOURNAL      GRAND LIVRE
        ║                    trié par date    groupé par compte
   → divergence                                et auxiliaire
     à 6 mois                        │
                                     ▼
                             Compte de lot, solde,
                             balance âgée, relevé
```

Deux stockages = deux vérités à réconcilier. C'est exactement l'erreur que le principe
P4 interdit. Le journal et le grand livre ne sont pas deux stockages : ce sont **deux
lectures du même jeu d'écritures**.

**Le grand livre est une projection, jamais une table.**

---

## 1. Structure

```
journal_entry                     la pièce
  · residence_id, mandate_id, exercise_id
  · journal (code)
  · reference       AC-2026-000042
  · libelle
  · effective_date · posted_at
  · source_type · source_id       ← obligatoire
  · reverses_entry_id             ← nullable, extourne
  · created_by_user_id
      │
      ├──< journal_entry_line     compte · auxiliaire · debit · credit
      └──< attachments            pièces justificatives (polymorphe)
```

### Contraintes structurelles

| Règle | Application |
|---|---|
| `Σ debit = Σ credit` sur chaque pièce | vérifiée en **un point unique** du code, au posting |
| Une ligne porte soit un débit, soit un crédit, jamais les deux | contrainte base |
| Aucun `UPDATE`, aucun `DELETE` sur les deux tables | test d'architecture + révocation des droits SQL |
| `source_type` + `source_id` obligatoires | contrainte `not null` |
| Écriture réservée aux Actions | test d'architecture |

---

## 2. Sources autorisées en V1

```
journal_entries.source_type
  ├── FundCall        → clic : l'appel, ses lignes, ce qui reste dû
  ├── Payment         → clic : le paiement, son moyen, sa pièce
  ├── Adjustment      → clic : le motif, l'auteur, la pièce
  ├── OpeningBalance  → clic : « solde repris au JJ/MM/AAAA », import, PJ
  └── Closing         → clic : la clôture d'origine et son exercice
```

Une écriture sans `source_type` résolvable est **interdite**.
V2 ajoutera `Expense`, `Transfer`, `Handover` — sans changer la structure.

---

## 3. Plan comptable — périmètre V1

### 3.1 Le plan est réglementaire, pas générique

Le plan applicable est celui du **Ministère, spécifique aux copropriétés**.

> ⚠️ **Le plan comptable général des entreprises marocaines ne doit pas être utilisé**,
> ni pour remplacer ce plan, ni pour détailler ses comptes. Aucun compte du PCG ne doit
> apparaître dans le seed ni pouvoir être créé par un utilisateur.

Conséquences sur `accounts` :
- structure **fournie et verrouillée** par seed ;
- une résidence ne peut **pas inventer** de compte ;
- seule extension autorisée : un **sous-compte d'un compte existant, de même nature** —
  typiquement un `512x` par compte bancaire réel ;
- le plan est **de la donnée** : une évolution réglementaire est un nouveau seed,
  jamais une migration ;
- **test :** aucun compte hors plan de référence n'existe en base.

### 3.2 Comptes mouvementés par la V1

| Compte | Intitulé | Auxiliaire | Sens |
|---|---|---|---|
| `3421` | Copropriétaire individualisé — avances | **lot** | actif |
| `3422` | Copropriétaire — budget prévisionnel | **lot** | actif |
| `3423` | Copropriétaire — travaux et opérations non courantes | **lot** | actif |
| `3424` | Copropriétaire — créances douteuses | **lot** | actif |
| `512x` | Banque | — | actif |
| `516x` | Caisse | — | actif |
| `7111` | Provisions sur opérations courantes | — | produit |
| `7112` | Provisions sur travaux | — | produit |
| `7113` | Avances | — | produit |
| `119x` | Report à nouveau | — | passif |

Le plan complet est chargé dès la V1 ; seuls ces comptes sont mouvementés.

> ⚠️ La numérotation exacte de `3421`, `512x`, `516x`, `119x` reste **à confirmer sur
> le document officiel avant le seed**. Voir [14](14-hors-perimetre.md) q.3.
> Le schéma est indépendant de la réponse : le développement peut démarrer avec un
> plan provisoire.

### 3.3 Comptes collectifs et auxiliaires

Le grand livre exige des comptes **individuels et collectifs**. C'est le mécanisme
collectif + auxiliaire :

| Compte | Auxiliaire | Solde lu |
|---|---|---|
| `342x` | un par **lot** | ce que le lot doit, par nature |
| `512x` / `516x` | aucun | solde du compte de trésorerie |
| `711x` | aucun | produit appelé de l'exercice |

Le plan reste court (quelques dizaines de comptes) même avec 300 lots, et les soldes
individuels restent des requêtes simples.

---

## 4. Écritures types — V1

**Contrat testable.** Chaque ligne correspond à un test : telle Action produit
exactement cette pièce, sur ces comptes, avec cet auxiliaire.

| Événement métier | Débit | Crédit |
|---|---|---|
| Appel — opérations courantes | `3422` *(aux : lot)* | `7111` |
| Appel — travaux | `3423` *(aux : lot)* | `7112` |
| Appel — avance | `3421` *(aux : lot)* | `7113` |
| Encaissement d'un paiement | `512x` / `516x` | `342x` *(aux : lot)* |
| Déclassement en créance douteuse | `3424` *(aux : lot)* | `342x` d'origine *(aux : lot)* |
| Solde d'ouverture (import) | compte concerné | `119x` Report à nouveau |
| Ajustement motivé | selon le motif | selon le motif |
| Clôture — solde des produits | `711x` | `119x` |
| À-nouveau — réouverture de bilan | compte de bilan | `119x` |
| **Extourne** | inverse exact de la pièce annulée | |

---

## 5. Pièces justificatives

Le règlement impose que **chaque écriture soit rattachable à sa pièce**.
Deux chemins coexistent, et les deux doivent fonctionner :

1. **Par la source** — la pièce est portée par l'objet métier
   (`payments.piece_document_path`). L'écriture y accède via `source_type`/`source_id`.
2. **Directement** — table `attachments` polymorphe, pour les écritures **sans objet
   métier porteur** : ajustements, soldes d'ouverture, à-nouveaux.

**Invariant :** toute pièce comptable résout au moins une pièce justificative, par
l'un ou l'autre chemin. Seule exception autorisée et tracée : les écritures de clôture
générées par le système.

---

## 6. Ajustement — objet de première classe

Puisque l'appel est immuable, **l'ajustement est le seul moyen de corriger un compte**.
Il n'est donc pas un cas particulier, mais une entité à part entière.

```
adjustments
  · lot_account_id (nullable — un ajustement peut ne pas concerner un lot)
  · debit_account_id · credit_account_id
  · montant · effective_date
  · motif            ← OBLIGATOIRE, texte libre significatif
  · document_path    ← OBLIGATOIRE
  · created_by_user_id
  · state : draft → posted → (reversed)
```

Réservé au rôle `comptable` (voir [02](02-identite-roles.md)).
Un ajustement `posted` suit la même règle que tout le reste : extourne, jamais retouche.

---

## 7. Extourne

Seul mécanisme d'annulation.

```
Pièce d'origine  AC-2026-000042    DÉBIT 3422 (lot A102)  1 000
                                   CRÉDIT 7111            1 000

Extourne         AC-2026-000078    DÉBIT 7111             1 000
                 reverses_entry_id CRÉDIT 3422 (lot A102) 1 000
                   = 42
```

- Inverse **exact** : mêmes comptes, mêmes auxiliaires, mêmes montants, sens opposé.
- `effective_date` de l'extourne = date de la décision, **pas** celle de l'origine.
  Si l'exercice d'origine est clos, l'extourne s'inscrit dans l'exercice ouvert.
- Une pièce ne peut être extournée **qu'une seule fois**. Contrainte d'unicité sur
  `reverses_entry_id`.
- Une extourne ne peut pas être extournée : on repost une nouvelle pièce.
