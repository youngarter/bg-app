# 05 — Budget

← [Sommaire](README.md)

---

## 1. Le budget appartient à l'exercice

Périmètre V1 : **budget de fonctionnement uniquement**. Le budget d'investissement et
le fonds travaux sont en V2 (voir [14](14-hors-perimetre.md)).

```
Exercice 2026
   └── Budget de fonctionnement
          ├── Ligne « Nettoyage »        80 000,00
          ├── Ligne « Gardiennage »     120 000,00
          └── Ligne « Électricité »      50 000,00
          ───────────────────────────────────────
                            total       250 000,00
```

Un exercice porte **au plus un budget de fonctionnement**.

---

## 2. Ligne budgétaire

| Champ | Rôle |
|---|---|
| `account_id` | compte de charge du plan (`6111`, `6131`…) — prépare le budget vs réalisé de la V2 |
| `libelle` | intitulé affiché |
| `montant_prevu` | en centimes |
| `ordre` | affichage |

**Une ligne budgétaire ne produit aucune écriture comptable.** Le budget est une
prévision : il ne crée ni charge ni produit. Seul l'appel de fonds produit des écritures.

---

## 3. Cycle de validation

```
draft ──▶ submitted ──┬──▶ approved   → devient la base des appels
                      └──▶ rejected   → retour en draft, commentaire obligatoire
```

Mécanisme générique : voir [10 — Validation conseil](10-validation-conseil.md).

### Règles

- Un budget `approved` est **immuable**. Une révision est un **nouveau budget** qui
  remplace le précédent après approbation ; l'ancien reste consultable.
- **Aucun appel de fonds ne peut être émis sans budget `approved`** sur l'exercice.
  Invariant bloquant, testé.
- Un budget ne peut être soumis que sur un exercice `open`.

---

## 4. Chaîne de la cotisation

> **Ne jamais écrire `coproprietaire.cotisation = 500 DH`.**

La chaîne complète, sans raccourci :

```
Budget approuvé
      │  répartition par tantièmes
      ▼
Quote-part théorique du lot
      │
      ▼
Appel de fonds  ← l'événement qui crée l'obligation
      │
      ▼
Ligne d'appel (obligation du lot, débiteur et tantième gelés)
      │
      ▼
Paiement
      │
      ▼
Imputation
      │
      ▼
Solde  ← projection du ledger, jamais stockée comme vérité
```

Chaque flèche est une Action, testée isolément. Aucun raccourci n'est autorisé entre
deux niveaux non adjacents.

---

## 5. Écart budget / réalisé

L'appel de fonds est **définitif, pas provisionnel** — voir
[06](06-appels-de-fonds.md) §2. Il en découle :

- l'écart budget / charges réelles est un **rapport informatif**, sans aucun impact
  sur les comptes des lots ;
- ce rapport nécessite les dépenses, donc arrive en **V2**.

En V1, le budget sert exclusivement de **base de calcul et de justification** des
appels de fonds soumis au conseil syndical.
