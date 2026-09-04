# 06 — Appels de fonds

← [Sommaire](README.md)

**C'est l'événement central de la V1 : celui qui crée l'obligation de payer.**

---

## 1. Anatomie

```
fund_calls                        l'événement
  · référence AF-2026-001
  · exercice, mandat
  · nature
  · montant total
  · effective_date · due_date
  · état
      │
      └──< fund_call_lines        l'obligation, une par lot
             · lot_account
             · montant
             · tantiemes_used / total_tantiemes_used   ← GELÉS
             · debtor_owner_id / debtor_snapshot       ← GELÉS
             · due_date
             · payment_status
             · recovery_status
```

**`fund_call_lines` EST la créance.** Il n'existe pas de table `receivables`
parallèle : ce serait une seconde source de vérité.

---

## 2. L'appel est définitif, pas provisionnel

### Décision actée

L'appel de fonds n'est **pas une provision** : c'est la charge définitive.
Ce que le copropriétaire doit = ce qui a été appelé.

### Reformulation actée (revue du 03/09/2026)

> Les appels validés sont **immuables**. Toute correction ultérieure est effectuée par
> un **nouvel événement financier traçable** : appel complémentaire, avoir, ou
> ajustement motivé.

```
❌ INTERDIT                        ✅ AUTORISÉ

Appel janvier   1 000              Appel janvier          1 000  (intact)
      ↓ modifier                   Appel complémentaire     200
Appel janvier   1 200              ou Avoir                −100
                                   ou Ajustement motivé    ±  x
```

Cela n'interdit pas la correction — cela interdit la **retouche**.

### Conséquences assumées

- L'écart budget / réalisé est **informatif**, sans impact sur les comptes des lots.
- L'**ajustement devient un objet de première classe** : motivé, tracé, avec pièce.
- La clôture d'exercice se réduit à « geler + reporter ».

---

## 3. Nature de l'appel

Détermine le couple de comptes mouvementés. **Sans elle, la ventilation réglementaire
des créances est impossible à produire.**

| Nature | Débit *(aux : lot)* | Crédit |
|---|---|---|
| `courant` | `3422` Copropriétaire – budget prévisionnel | `7111` Provisions sur opérations courantes |
| `travaux` | `3423` Copropriétaire – travaux et opérations non courantes | `7112` Provisions sur travaux |
| `avance` | `3421` Copropriétaire individualisé | `7113` Avances |

En V1, la nature `courant` est le cas nominal. `travaux` et `avance` sont supportées par
le modèle et le posting, mais sans budget d'investissement dédié (V2).

---

## 4. Cycle de vie

```
draft ──▶ submitted ──┬──▶ rejected ──▶ draft
                      │
                      └──▶ approved ──▶ issued ──▶ [payment_status des lignes]
                                          │
                                          └──▶ reversed  (extourne intégrale)
```

| État | Ce qui existe | Modifiable |
|---|---|---|
| `draft` | brouillon, lignes recalculables à volonté | ✅ tout |
| `submitted` | soumis au conseil | ❌ |
| `approved` | validé, pas encore émis | ❌ |
| `issued` | **posté** : écritures produites, obligations exigibles | ❌ **jamais** |
| `reversed` | extourné intégralement, pièce inverse produite | ❌ |

**`issued` est le point de non-retour.** Voir [11](11-machines-etats.md).

---

## 5. Émission (`issue`) — l'Action critique

### Contrôles bloquants avant posting

```
1. exercice.state = open
2. un budget approved existe sur l'exercice
3. Σ lots.tantiemes = residences.total_tantiemes
4. l'appel est approved
5. effective_date ∈ [exercice.started_on, exercice.ended_on]
6. montant total > 0
7. chaque lot a un lot_account actif
8. chaque lot a au moins une détention active à effective_date
```

Le contrôle 8 est celui qui garantit qu'un `debtor_owner_id` est déterminable.
Un lot sans détenteur connu **bloque l'émission** et remonte une anomalie nommée.

### Ce que le posting produit

```
Pour chaque lot :
  montant_lot = floor(montant_total × tantieme / total_tantiemes)
  puis distribution déterministe du reste au centime (voir 00 §4)

Gel sur chaque ligne :
  tantiemes_used, total_tantiemes_used
  debtor_owner_id, debtor_snapshot

Pièce comptable unique, équilibrée :
  N lignes   DÉBIT   342x  (auxiliaire = lot_account)   montant_lot
  1 ligne    CRÉDIT  711x                                montant_total
```

**Invariant :** `Σ fund_call_lines.montant = fund_calls.montant_total`, au centime.

---

## 6. Échéance et exigibilité

- `due_date` est portée par l'appel et **recopiée sur chaque ligne** — une ligne peut
  ainsi porter une échéance dérogatoire (échéancier accordé à un lot), sans toucher
  l'appel.
- Une ligne devient `overdue` quand `due_date < aujourd'hui` **et** reste due.
  C'est un **calcul**, pas un état stocké mis à jour par un job.

---

## 7. Extourne d'un appel

Réservée à l'erreur de saisie manifeste (mauvais exercice, mauvais montant global,
mauvaise nature). Exige un motif.

```
1. contrôle : aucune imputation ne pointe une ligne de cet appel
   (sinon → désimputer d'abord, explicitement)
2. pièce inverse exacte, reverses_entry_id renseigné
3. fund_calls.state → reversed
4. les lignes passent en payment_status = cancelled
5. l'appel et ses lignes restent visibles au relevé, barrés, avec leur motif
```

**Aucune suppression.** L'appel extourné reste dans l'historique du lot.
