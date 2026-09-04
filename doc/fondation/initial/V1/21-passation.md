# 21 — Passation entre mandats

← [Sommaire](README.md)

La passation est un **vrai processus métier**, avec états, rapprochement et journal
d'audit — pas un recopiage de soldes.

C'est potentiellement l'**avantage concurrentiel** de Bayan : la situation réelle du
marché est « le nouveau syndic n'arrive pas à récupérer une situation propre de
l'ancien ».

---

## 🔒 Décision actée — deux actes distincts

> Le **snapshot de passation** constate et rapproche.
> La **génération des à-nouveaux** est un acte comptable **séparé et explicite**,
> décidé dans l'exercice du nouveau mandat.

```
        HANDOVER SNAPSHOT                    ÉCRITURES D'OUVERTURE
   constatation, rapprochement,       ≠      pièces comptables dans
   justification des écarts                  l'exercice du mandat entrant

   « voici la situation au 15/01 »           « je l'inscris dans mes comptes »
```

### Pourquoi les séparer

Les coupler rendrait impossible le cas le plus fréquent : une passation **constatée**
mais dont l'ouverture comptable attend la validation du conseil syndical, ou attend
simplement que le nouvel exercice soit ouvert.

C'est la même logique que « mandat ≠ accès » ([01](01-plateforme-licence.md)) et
« AG ≠ budget » ([16](16-assemblees-generales.md)) : un fait constaté et son effet
applicatif sont deux choses distinctes.

---

## 1. Modèle

```
handovers
  id, residence_id
  outgoing_mandate_id, incoming_mandate_id
  effective_date
  state : draft | reviewed | finalized
  reviewed_at, finalized_at
  finalized_by_user_id
  approval_request_id
  timestamps

handover_items
  id, handover_id
  categorie : creances | tresorerie | dettes_fournisseurs
            | actifs | documents | autres_soldes
  libelle
  montant_calcule (nullable)      ← ce que Bayan lit dans le ledger
  montant_declare (nullable)      ← ce que le syndic sortant déclare
  ecart                           ← généré
  justification_ecart             ← OBLIGATOIRE si ecart ≠ 0
  source_type, source_id (nullable)   ← référence polymorphe
  document_path
```

> **Une table `handover_items` polymorphe, pas cinq tables quasi identiques**
> (`HandoverReceivable`, `HandoverCash`, `HandoverLiability`…). Ce serait cinq fois le
> même schéma à maintenir.

---

## 2. Catégories transférées

Le suivi de trésorerie de [15](15-tresorerie-depenses.md) transforme cette section :
**trois catégories sur six deviennent calculées** au lieu d'être saisies à la main.

| Catégorie | Origine |
|---|---|
| Créances des copropriétaires | **calculée** — comptes `342x` par auxiliaire |
| Soldes de trésorerie (banque, caisse, fonds travaux) | **calculée** — `512x` / `516x` |
| Dettes fournisseurs | **calculée** — dépenses `recorded` non `paid` |
| Actifs (matériel, contrats en cours) | saisie |
| Documents (archives, plans, PV) | saisie |
| Autres soldes d'ouverture | saisie |

C'est le principal bénéfice de l'intégration de la trésorerie : **la passation cesse
d'être un formulaire déclaratif pour devenir un état rapproché.**

---

## 3. Le rapprochement

À la date d'effet, Bayan compare **ses soldes calculés** aux **soldes déclarés** par le
syndic sortant.

```
PASSATION TR-2026-001 · M12 → M13 · effet 15/01/2026

  Catégorie                  Calculé Bayan    Déclaré sortant     Écart
  ──────────────────────────────────────────────────────────────────────
  Créances copropriétaires      198 400,00       198 400,00        0,00  ✅
  Banque BMCE                   142 350,00       142 350,00        0,00  ✅
  Caisse                          3 200,00         2 850,00      350,00  ⚠️
     └── justification : espèces remises le 14/01, non saisies
  Fonds travaux                 380 000,00       380 000,00        0,00  ✅
  Dettes fournisseurs            47 800,00        52 300,00   − 4 500,00 ⚠️
     └── justification : facture ascenseur reçue après arrêté
```

**Tout écart doit être justifié avant le passage à `finalized`.** Invariant bloquant.

---

## 4. Cycle de vie

| De → Vers | Acteur | Conditions | Irréversible |
|---|---|---|---|
| — → `draft` | gérant / admin | deux mandats identifiés, date d'effet | non |
| `draft` → `reviewed` | gérant | tous les items renseignés | non |
| `reviewed` → `draft` | gérant | correction nécessaire | non |
| `reviewed` → `finalized` | gérant | **tout écart justifié** + approbation du conseil | **oui** |

Une passation `finalized` est **immuable**. Une correction est un ajustement motivé
dans les comptes, jamais une retouche du document.

---

## 5. Génération des à-nouveaux — l'acte séparé

Action **distincte**, déclenchée explicitement après finalisation.

```
Contrôles bloquants
  1. la passation est finalized
  2. un exercice OPEN existe sur le mandat entrant
  3. les à-nouveaux n'ont pas déjà été générés (unicité)

Posting
  une pièce source_type = Handover, pointant le handover_id
  réouvre les comptes de BILAN uniquement :
     342x par auxiliaire · 512x / 516x · 441x par auxiliaire · 13x · 119x

Effet
  handovers.opening_entry_id renseigné
```

- Les à-nouveaux **ne recréent pas la dette** : ils rouvrent les comptes de bilan avec
  leur solde constaté. La dette est **continuée**, jamais recréée ni déplacée
  ([04](04-mandats-exercices.md) §5).
- Une passation peut rester `finalized` **sans** à-nouveaux générés. C'est un état
  valide et visible, pas une anomalie.

---

## 6. Export de sortie

Distinct de la passation, et **obligatoire à la révocation d'accès**
([01](01-plateforme-licence.md) §5).

| | Export de sortie | Passation |
|---|---|---|
| Déclencheur | révocation de l'accès par l'admin | fin de mandat |
| Destinataire | la société sortante | le mandat entrant |
| Contenu | tout ce que la société a produit | la situation à la date d'effet |
| Bloquant | **oui** — sans lui, litige certain | non |

Les deux peuvent exister séparément : un accès révoqué sans passation formalisée, ou
une passation sans révocation immédiate.
