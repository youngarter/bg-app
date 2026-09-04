# 00 — Principes directeurs et conventions

← [Sommaire](README.md)

Ces règles priment sur toute considération de confort d'implémentation.
Elles sont vérifiées par des tests, pas par la discipline.

---

## 1. Les sept principes

### P1 — Immuabilité
Aucune suppression physique, aucune modification d'une écriture postée.
Une correction est une **extourne** (`reverses_entry_id`) suivie d'une nouvelle écriture.

### P2 — Partie double
Chaque événement financier produit une pièce équilibrée `Σ débit = Σ crédit`,
vérifiée en **un point unique** du code. L'équilibre est structurel, pas surveillé.

### P3 — Traçabilité totale
Chaque écriture porte `source_type` + `source_id`. Aucun chiffre affiché sans origine
remontable jusqu'à une pièce justificative ou une décision motivée.

### P4 — Source de vérité unique
Tous les soldes — lots, trésorerie, produits — sont calculés depuis
`journal_entry_lines`. Tout agrégat stocké est un **cache reconstructible**, jamais
l'autorité. Il n'existe **pas** de sous-ledger parallèle.

### P5 — Contexte obligatoire
Chaque opération porte son `mandate_id` et son `exercise_id`. Jamais réécrits après coup.

### P6 — Écriture réservée aux Actions
Aucun contrôleur, job, observer, commande ou seeder n'écrit directement dans
`journal_entry_lines`. Vérifié par un test d'architecture.

### P7 — Le temps est modélisé, pas déduit
Mandats, exercices, détentions de lot, rôles du conseil : tous portent
`started_on` / `ended_on`. Aucune date n'est inférée d'une autre entité.

---

## 2. Le cycle de posting

Concept transversal. **Tout événement financier suit exactement ce chemin**, sans
exception et sans variante par module.

```
       Action métier
            │
            ▼
     ┌─────────────┐
     │ VALIDATION  │  règles métier, invariants, autorisation
     └──────┬──────┘
            │ (échec → exception, rien n'est écrit)
            ▼
     ┌─────────────┐
     │   POSTING   │  traduction en pièce comptable équilibrée
     └──────┬──────┘
            │  point unique du code
            ▼
     ┌─────────────┐
     │  ÉCRITURES  │  immuables, horodatées, sourcées
     └──────┬──────┘
            │
            ▼
       PROJECTIONS   soldes, relevés, balance âgée, dashboard
```

Application aux trois événements de la V1 :

| Événement | Validation | Posting produit |
|---|---|---|
| Émission d'un appel | budget approuvé, exercice `open`, Σ tantièmes | une pièce par appel, une ligne débit par lot + crédit produit |
| Encaissement d'un paiement | lot existant, montant > 0, exercice `open` | débit trésorerie, crédit `342x` auxiliaire lot |
| Ajustement motivé | motif + pièce obligatoires, exercice `open` | selon le motif, toujours équilibrée |

**Conséquence :** aucun module n'invente son propre comportement financier.
Ajouter un événement en V2 = ajouter une Action et une écriture type, rien d'autre.

---

## 3. Convention de dates

Cinq rôles distincts, **jamais confondus**. Chaque entité financière porte celles qui
la concernent.

| Champ | Sens | Exemple |
|---|---|---|
| `effective_date` | date économique réelle de l'événement | le paiement a été reçu le 03/02 |
| `document_date` | date portée par la pièce justificative | le chèque est daté du 28/01 |
| `due_date` | date d'exigibilité de l'obligation | l'appel est exigible le 05/02 |
| `recorded_at` | horodatage de saisie dans Bayan | le gestionnaire a saisi le 07/02 à 14h32 |
| `posted_at` | horodatage de comptabilisation | la pièce est devenue immuable le 07/02 à 14h32 |

**Règles :**
- `effective_date` détermine l'exercice de rattachement, **jamais** `recorded_at`.
- `posted_at` est écrit une seule fois, par le posting, jamais modifiable.
- `recorded_at` et `posted_at` peuvent différer (saisie en brouillon puis validation).
- Une pièce dont `effective_date` tombe hors de l'exercice ouvert est **refusée**.

---

## 4. Convention de montants et de quantités

| Grandeur | Type | Unité |
|---|---|---|
| Montants | `bigint` | **centimes** (entiers). Jamais de flottant, jamais de décimal en PHP. |
| Quotes-parts | `int` | **points de base** : 10 000 = 100 % |
| Tantièmes | `int` | entier, base définie par `residences.total_tantiemes` |
| Devise | `char(3)` | `MAD` par défaut, portée par la résidence |

### Arrondis de répartition

La répartition d'un montant par tantièmes produit des restes. Règle unique :

```
1. montant_lot = floor(montant_total × tantieme_lot / total_tantiemes)
2. reste = montant_total − Σ montant_lot
3. le reste est distribué au centime, lot par lot,
   par tantième décroissant puis par identifiant de lot croissant
```

**Invariant testé :** `Σ lignes d'appel = montant de l'appel`, au centime, toujours.
La règle est déterministe : deux exécutions produisent la même répartition.

---

## 5. Convention de sens comptable

> Le **débit** augmente l'actif et les charges.
> Le **crédit** augmente le passif et les produits.

| | Débit | Crédit |
|---|---|---|
| Copropriétaires `342x` *(actif)* | le lot doit plus (appel émis) | le lot doit moins (paiement reçu) |
| Trésorerie `512` / `516` *(actif)* | l'argent entre | l'argent sort |
| Produits `711x` | annulation | appel émis, produit acquis |

À retenir noir sur blanc : **un paiement crédite le compte du lot et débite le compte
bancaire, du même montant, dans la même pièce.**

---

## 6. Correction : le seul mécanisme autorisé

```
                 Événement posté
                       │
        ┌──────────────┴──────────────┐
        ▼                             ▼
   ERREUR DE SAISIE              FAIT NOUVEAU
   (n'aurait pas dû exister)     (la réalité a changé)
        │                             │
        ▼                             ▼
     EXTOURNE                   NOUVEL ÉVÉNEMENT
  pièce inverse exacte          appel complémentaire,
  + nouvelle pièce si besoin    avoir, ou ajustement motivé
```

**Interdit dans tous les cas :** modifier une pièce postée, supprimer une ligne,
recalculer rétroactivement un montant appelé.

> Reformulation actée du README cible : les appels validés sont **immuables**.
> Toute correction ultérieure est un **nouvel événement financier traçable**.
> Cela n'interdit pas l'appel complémentaire ni l'avoir — cela interdit la retouche.

---

## 7. Nommage et numérotation

| Objet | Format | Portée d'unicité |
|---|---|---|
| Appel de fonds | `AF-{exercice}-{séquence:3}` | résidence |
| Paiement | `PAY-{année}-{séquence:5}` | résidence |
| Pièce comptable | `{journal}-{année}-{séquence:6}` | résidence |

Séquences **sans trou** et attribuées au posting, jamais à la création du brouillon.
Verrou de génération au niveau base (pas de calcul `MAX(...) + 1` applicatif).
