# 07 — Paiements et imputation

← [Sommaire](README.md)

---

## 1. Le paiement

Enregistre **l'argent réellement reçu**.

| Champ | Contenu |
|---|---|
| `lot_account_id` | le compte crédité |
| `paid_by_owner_id` | **qui a payé** — traçabilité, sans effet sur le solde |
| `treasury_account_id` | où l'argent est entré (banque ou caisse) |
| `montant` | centimes |
| `moyen` | `especes` · `cheque` · `virement` · `tpe` · `en_ligne` |
| `reference` | n° de chèque, référence de virement |
| `effective_date` | date de réception réelle → détermine l'exercice |
| `document_date` | date portée par la pièce (ex. date du chèque) |
| `piece_document_path` | reçu, avis, bordereau |
| `state` | `draft` → `confirmed` → (`reversed`) |

> Le paiement est **au lot**, pas au copropriétaire. En indivision, peu importe lequel
> des indivisaires paie : c'est le compte du lot qui est crédité. `paid_by_owner_id`
> répond à « qui a payé », jamais à « à qui l'imputer ».

### Posting à la confirmation

```
DÉBIT   512x / 516x   trésorerie              montant
CRÉDIT  342x          (auxiliaire = lot)      montant
```

Une seule pièce, équilibrée. L'imputation aux lignes d'appel est un **acte distinct**
qui ne produit **aucune écriture** — voir §3.

---

## 2. Comptes de trésorerie — périmètre V1

La V1 a besoin d'un compte de contrepartie pour encaisser. Elle inclut donc
`treasury_accounts` dans sa forme minimale :

```
treasury_accounts
  type          banque | caisse
  libelle, banque, rib
  account_id    le compte du plan (512x ou 516x)
  solde_initial + date_solde_initial
```

**Invariant :** une caisse ne peut jamais être négative. Bloquant.
Une banque peut l'être : alerte, non bloquante.

Le compte appartient à la **résidence**, jamais au syndic.

Le traitement complet — virements, rapprochement bancaire, affectation du fonds
travaux, situation de trésorerie consolidée — est décrit en
[15](15-tresorerie-depenses.md).

---

## 3. L'imputation est explicite

> L'affectation d'un paiement aux lignes d'appel est **matérialisée** dans
> `payment_allocations`, jamais calculée implicitement à la volée.

```
payments ──< payment_allocations >── fund_call_lines
                  · montant
                  · methode : fifo | manuelle
                  · created_by_user_id
                  · cancelled_at / cancelled_by / cancel_motif
```

### Pourquoi l'imputation ne produit pas d'écriture

Le paiement a **déjà** crédité `342x` pour le lot. L'imputation ne fait que dire
**quelle obligation** ce crédit éteint. Elle affine la lecture, elle ne déplace pas
d'argent.

```
Solde du lot      = projection du ledger        (ne dépend pas de l'imputation)
Détail par appel  = lignes − imputations        (dépend de l'imputation)
```

Cette séparation est ce qui permet d'avoir un **paiement non imputé** : l'argent est
encaissé, le solde du lot est juste, et l'affectation reste à décider.

---

## 4. Règle FIFO — un défaut, pas une loi

**Défaut : FIFO par date d'échéance**, la dette la plus ancienne d'abord.
À échéance égale, par identifiant de ligne croissant (déterministe).

Mais :
- le payeur peut **désigner** l'appel réglé ;
- le syndic doit pouvoir **réaffecter** ;
- une imputation est **annulable**, et l'annulation est tracée (auteur, date, motif).

### Exemple

```
Appel AF-2026-005    1 000,00   échéance 05/02/2026
Appel AF-2026-006    1 000,00   échéance 05/05/2026

Paiement PAY-00042   1 500,00   reçu le 10/05/2026

Imputation automatique (FIFO) :
   → AF-2026-005 : 1 000,00   ligne soldée      payment_status = paid
   → AF-2026-006 :   500,00   partiel, reste 500 payment_status = partial
```

---

## 5. Invariants d'imputation

```
1. Σ allocations actives d'un paiement  ≤  paiement.montant
2. Σ allocations actives d'une ligne    ≤  ligne.montant
3. une allocation ne peut pointer une ligne d'un appel reversed
4. une allocation ne peut pointer un paiement draft ou reversed
5. le paiement et la ligne appartiennent au MÊME lot_account
6. annuler une allocation ne supprime rien : cancelled_at + motif
```

L'invariant 5 est bloquant et testé : on n'impute jamais le paiement d'un lot sur la
dette d'un autre. Un transfert entre lots est un **ajustement motivé**, pas une
imputation.

---

## 6. Reliquat et avance

Un paiement dont `Σ allocations < montant` laisse un **reliquat non imputé**.

```
Paiement 1 500,00
  imputé  1 000,00
  ─────────────────
  reliquat  500,00   ← visible sur l'écran du lot, à imputer
```

Le reliquat **crédite déjà le compte du lot** : le solde affiché est juste dès
l'encaissement. Il apparaît comme « avance / non affecté » sur le relevé, et sera
imputé automatiquement au prochain appel émis (proposition FIFO, confirmée par le
gestionnaire).

---

## 7. Extourne d'un paiement

Cas réels : chèque impayé, virement rejeté, double saisie.

```
1. annulation de toutes les allocations actives (tracée, motivée)
2. pièce inverse exacte, reverses_entry_id renseigné
3. payments.state → reversed, motif obligatoire
4. le paiement reste visible au relevé, barré, avec son motif
```

Un chèque impayé n'est pas une suppression : c'est un **événement** qui doit rester
lisible dans l'historique du lot.
