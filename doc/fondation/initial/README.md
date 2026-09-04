# Bayan — Fondation initiale

**Statut : à valider** · Version 0.1 · 25/08/2026

Document de référence issu de la conversation d'architecture initiale. Il fige le
fonctionnel et le technique **avant** l'écriture des migrations. Rien ne doit être
codé tant que la section « Checklist de validation » n'est pas cochée.

---

## Sommaire

**Partie I — Fonctionnel**
1. [Objet et périmètre](#1-objet-et-périmètre)
2. [Vocabulaire](#2-vocabulaire)
3. [Acteurs et rôles](#3-acteurs-et-rôles)
4. [Licence et accès](#4-licence-et-accès)
5. [Cycle de vie de la gestion](#5-cycle-de-vie-de-la-gestion)
6. [Structure de la copropriété](#6-structure-de-la-copropriété)
7. [Budget et appels de fonds](#7-budget-et-appels-de-fonds)
8. [Paiements et imputation](#8-paiements-et-imputation)
9. [Compte de lot, créances, recouvrement](#9-compte-de-lot-créances-recouvrement)
10. [Trésorerie, dépenses et fournisseurs](#10-trésorerie-dépenses-et-fournisseurs)
11. [Clôture d'exercice](#11-clôture-dexercice)
12. [Passation entre mandats](#12-passation-entre-mandats)
13. [Import historique](#13-import-historique)
14. [Portail copropriétaire](#14-portail-copropriétaire)
15. [Validation par le conseil syndical](#15-validation-par-le-conseil-syndical)

**Partie II — Technique**

16. [Principes directeurs](#16-principes-directeurs)
17. [Schéma de données](#17-schéma-de-données)
18. [Invariants](#18-invariants)
19. [Machines à états](#19-machines-à-états)
20. [Autorisation](#20-autorisation)
21. [Montants, arrondis, numérotation](#21-montants-arrondis-numérotation)
22. [Couche applicative (Actions)](#22-couche-applicative-actions)
23. [Stratégie de tests](#23-stratégie-de-tests)

**Annexes**

24. [Décisions actées](#24-décisions-actées)
25. [Hors périmètre v1](#25-hors-périmètre-v1)
26. [Questions ouvertes](#26-questions-ouvertes)
27. [Checklist de validation](#27-checklist-de-validation)

---

# Partie I — Fonctionnel

## 1. Objet et périmètre

Bayan est une application de gestion de copropriété (syndic) destinée au marché
marocain. Elle couvre la structure de l'immeuble, la gestion budgétaire, les appels
de fonds, l'encaissement, le recouvrement, la clôture comptable et la continuité
lors des changements de syndic.

**Le principe directeur de tout le système :**

> Toute opération de gestion est contextualisée par un mandat et un exercice,
> mais les droits, dettes et historiques financiers survivent au mandat.
> Aucun chiffre affiché ne doit apparaître sans qu'on puisse remonter à son origine.

Le modèle commercial est un SaaS **vendu à la copropriété** : la résidence est le
tenant, le syndic est un utilisateur invité dont l'accès est révocable.

## 2. Vocabulaire

| Terme | Définition |
|---|---|
| **Résidence** | L'entité permanente. Existe pendant des décennies, ne disparaît pas quand le syndic change. C'est le **tenant** du SaaS. |
| **Société de syndic** | Personne morale qui exerce la gestion. Invitée sur une résidence, jamais propriétaire de la donnée. |
| **Mandat** | Période pendant laquelle une société de syndic exerce la gestion. Durée statutaire de référence : 2 ans, **interruptible à tout moment**. |
| **Exercice** | Période comptable. Entité autonome, **jamais dérivée du mandat**. |
| **Lot** | Unité de propriété (appartement, magasin, parking, cave…). Porte un tantième. |
| **Tantième** | Quote-part du lot dans les charges communes. Base de toute répartition. |
| **Copropriétaire** | Personne physique ou morale détenant tout ou partie d'un lot, sur une période donnée. |
| **Compte de lot** | Compte financier permanent attaché au **lot**, pas au propriétaire. |
| **Appel de fonds** | Événement qui crée l'obligation de payer. Réparti par tantièmes sur les lots. |
| **Paiement** | Argent réellement reçu. |
| **Imputation (lettrage)** | Affectation explicite d'un paiement à une ou plusieurs lignes d'appel. |
| **Créance** | Solde restant dû. **Grandeur calculée**, jamais stockée comme vérité. |
| **Ledger** | Journal des mouvements financiers du compte de lot. Immuable. |
| **Passation** | Processus métier de transfert de situation entre deux mandats. |
| **Import historique** | Reprise de données provenant d'un système externe (Excel, PDF, ancien logiciel). |

## 3. Acteurs et rôles

### 3.1 Administrateur plateforme

Interne à Bayan. N'est pas un utilisateur de la copropriété. Ses prérogatives :

- créer une résidence, gérer sa licence ;
- **accorder et révoquer l'accès d'une société de syndic à une résidence**, sur pièce
  justificative (lettre d'information de fin de mandat) ;
- consulter en support, exporter, auditer.

Chacune de ses actions est journalisée avec l'acteur, la date, le motif et la pièce.

### 3.2 Société de syndic (et ses collaborateurs)

Accès opérationnel complet à la résidence sur laquelle elle est accréditée :
structure, budgets, appels de fonds, encaissements, dépenses, clôtures, passation.

Un collaborateur appartient à une société de syndic ; ses droits sur une résidence
découlent de l'accréditation de sa société.

### 3.3 Président du conseil syndical

Copropriétaire élu, avec un mandat daté. Rôle **lecture + validation** :

- consulte l'intégralité des comptes de la résidence (tous les lots) ;
- **valide ou refuse** : budget, appel de fonds, clôture d'exercice, passation ;
- ne saisit rien, ne modifie rien.

### 3.4 Copropriétaire

Se connecte au portail. Accès strictement limité à **ses propres lots** :

- son compte, son solde, l'historique de ses appels et paiements ;
- les documents publics de la résidence (budgets approuvés, PV d'AG) ;
- ses coordonnées.

Son accès découle de la détention d'un lot : il s'ouvre à l'acquisition et se ferme
à la mutation. L'historique de la période où il détenait le lot lui reste visible.

## 4. Licence et accès

**Point d'architecture central : le mandat et l'accès sont deux choses distinctes.
Les permissions ne sont jamais dérivées du mandat.**

Le mandat est un fait juridique (l'AG a élu ABC du 01/01/24 au 31/12/25). L'accès est
une décision de la plateforme, prise par l'administrateur sur pièce. Les deux se
désynchronisent en permanence : l'AG vote le nouveau syndic le 10 janvier, la lettre
d'information arrive le 3 février — pendant trois semaines l'ancien syndic doit
conserver l'accès pour achever la passation.

### 4.1 Trois verrous indépendants

Un utilisateur accède à une résidence si et seulement si :

1. la **licence** de la résidence l'autorise (état `active` ou `grace`) ;
2. son rattachement est **actif** (accréditation de sa société, mandat de président,
   ou détention d'un lot) ;
3. la **policy** de l'objet demandé l'autorise.

### 4.2 États de licence

```
active ──(date de fin dépassée)──▶ grace ──(grâce épuisée)──▶ read_only
   ▲                                  │                           │
   └──────── renouvellement ──────────┴───────────────────────────┘

                    suspended  ◀── décision administrateur
```

| État | Lecture | Écriture | Export |
|---|---|---|---|
| `active` | ✅ | ✅ | ✅ |
| `grace` | ✅ | ✅ + bandeau d'alerte | ✅ |
| `read_only` | ✅ | ❌ | ✅ |
| `suspended` | ❌ | ❌ | ❌ (admin seul) |

**Décision actée : pas de blocage sec à l'expiration.** Couper une copropriété de sa
propre comptabilité pour un retard de paiement crée un incident support et un risque
juridique. Période de grâce configurable (défaut 30 jours), puis lecture seule.

### 4.3 Révocation d'un accès syndic

Déclenchée par l'administrateur, sur lettre d'information de l'une ou l'autre partie.

```
Réception de la lettre
        ↓
Admin ouvre la fiche résidence
        ↓
Révocation de l'accès de la société A
  · motif obligatoire
  · pièce justificative obligatoire
  · date d'effet
        ↓
Génération automatique d'un export de passation pour A
  (relevés, appels, paiements, dépenses de ses mandats)
        ↓
Accréditation de la société B
        ↓
Journalisation
```

**Conséquence assumée :** une fois révoquée, la société A ne voit plus rien. L'export
généré à la révocation est donc obligatoire — sans lui, A ne peut plus produire ses
propres justificatifs et un litige est certain.

**Conséquence assumée :** la société B voit l'intégralité de l'historique de la
résidence, y compris les données saisies par A. C'est cohérent avec le choix
« tenant = résidence » : la donnée appartient à la copropriété.

## 5. Cycle de vie de la gestion

### 5.1 Vue d'ensemble

```
Création résidence
        ↓
Licence activée
        ↓
Accréditation du syndic
        ↓
Mandat initial  ──── ou ────  Import historique
        ↓
    Exercice
        ↓
     Budget ──── validation conseil syndical
        ↓
  Appels de fonds ──── validation conseil syndical
        ↓
     Créances
        ↓
    Paiements
        ↓
  Livre financier
        ↓
 Clôture exercice ──── validation conseil syndical
        ↓
 Report des soldes
        ↓
  Nouvel exercice
        ↓
   Fin du mandat
        ↓
    Passation ──── validation conseil syndical + admin
        ↓
  Nouveau mandat
        ↓
 Soldes d'ouverture
        ↓
  Nouvel exercice
```

### 5.2 Le mandat

Un mandat lie une résidence à une société de syndic sur une période.

- Durée statutaire de référence : **2 ans**, mais c'est un défaut de saisie, **jamais
  une règle codée en dur**. Le système doit gérer une clôture anticipée.
- Un mandat clôturé n'est **jamais supprimé**. Il reste consultable, auditable,
  exportable.
- États : `draft` → `active` → (`suspended`) → `terminated` | `expired` → `closed`.

### 5.3 L'exercice

**L'exercice est une entité autonome. Ne jamais coder `exercice.année = mandat.année + 1`.**

Un mandat contient un ou plusieurs exercices :

```
Mandat #12 (01/01/2024 → 31/12/2025)
   ├── Exercice 2024
   └── Exercice 2025
```

En cas d'interruption au 15/08/2025 :

```
Mandat #12 (01/01/2024 → 15/08/2025)
   ├── Exercice 2024
   └── Exercice 2025-A (01/01 → 15/08) — clôture anticipée

Mandat #13 (16/08/2025 → …)
   └── Exercice 2025-B (16/08 → 31/12)
```

**Deux mandats peuvent porter des données sur la même année civile.** C'est normal et
le modèle doit l'accepter nativement.

Les exercices d'une résidence **ne se chevauchent jamais** dans le temps (invariant
testé). Chaque exercice appartient à exactement un mandat.

États : `open` → `closing` → `closed`. Une fois `closed` :

- ❌ modifier un appel de fonds, supprimer un paiement, supprimer une charge ;
- ✅ consulter, exporter, auditer ;
- ✅ corriger **par écriture d'ajustement** (extourne + nouvelle écriture).

**Aucune suppression physique dans le livre financier, jamais.**

## 6. Structure de la copropriété

### 6.1 Lots et tantièmes

Chaque lot porte un **tantième**, unique clé de répartition du système.

**Décision actée : une seule clé de répartition (tantièmes généraux).** Pas de clé
ascenseur, chauffage, cage d'escalier, etc. en v1.

Deux garde-fous peu coûteux qui protègent l'avenir :

1. `residences.total_tantiemes` (typiquement 1000 ou 10 000) avec l'invariant testé
   `Σ lots.tantiemes = residences.total_tantiemes`. Cela attrape les erreurs de saisie
   définitivement.
2. **Le tantième utilisé est figé dans la ligne d'appel de fonds**
   (`tantiemes_used` + `total_tantiemes_used`). Les tantièmes changent (division de
   lot, modificatif du règlement de copropriété) ; sans ce gel, un recalcul futur
   modifierait un appel passé. Deux colonnes suffisent, pas de table de versioning.

*Évolution future :* si une deuxième clé devient nécessaire, la migration consiste à
ajouter une table `repartition_keys` et à basculer l'existant sur une clé « générale ».
Ne pas la construire maintenant.

### 6.2 Copropriétaires et détention

Relation **plusieurs-à-plusieurs, datée** :

- un copropriétaire peut détenir **plusieurs lots** ;
- un lot peut appartenir à **plusieurs copropriétaires** (indivision, SCI,
  usufruit / nue-propriété).

```
lots ──< lot_ownerships >── owners
         · quote_part
         · nature (pleine propriété | indivision | usufruit | nue-propriété)
         · started_on / ended_on
```

C'est le même problème temporel que les mandats, appliqué aux propriétaires.

### 6.3 Le compte est celui du lot

**Décision actée : un seul compte par lot.**

Si A102 est en indivision Ahmed 50 % / Fatima 50 % avec 4 000 DH d'impayé, il y a
**un compte à 4 000 DH**, pas deux comptes à 2 000 DH. La dette est indivisible face
au syndicat et les indivisaires sont solidaires.

Le paiement enregistre **qui a payé** (`paid_by_owner_id`) pour la traçabilité, mais
le solde reste celui du lot.

```
Résidence
   └── Lot A102
          ├── lot_ownerships (Ahmed 50 %, Fatima 50 %)
          └── Compte de lot  ← le solde vit ici, en permanence
                 └── Écritures (ledger)
```

### 6.4 Mutations

La charge suit le lot : l'acquéreur hérite du solde du compte.

Une table `lot_mutations` enregistre l'événement (date, ancien(s) détenteur(s),
nouveau(x), **solde à la date**, pièce justificative). Cela permet au relevé de compte
d'afficher « solde repris de M. Ahmed au 15/03/2026 » au lieu d'un montant sans
origine.

> ⚠️ Le sort juridique exact des arriérés lors d'une mutation (opposition du syndic)
> doit être vérifié dans la **loi 18-00** avant de coder une règle automatique de
> transfert ou de rétention. Le modèle temporel est nécessaire dans tous les cas.

## 7. Budget et appels de fonds

### 7.1 Le budget appartient à l'exercice

```
Exercice
   ├── Budget de fonctionnement
   │      ├── Ligne « Nettoyage »        80 000
   │      ├── Ligne « Gardiennage »     120 000
   │      └── Ligne « Électricité »      50 000
   │                          total     250 000
   │
   └── Budget(s) d'investissement
```

Le budget est soumis par le syndic puis **validé par le conseil syndical**.

### 7.2 Chaîne de la cotisation

Ne jamais écrire `copropriétaire.cotisation = 500 DH`. La chaîne complète est :

```
Budget
   ↓ répartition par tantièmes
Quote-part du lot
   ↓
Appel de fonds
   ↓
Obligation du lot (ligne d'appel)
   ↓
Paiement
   ↓
Imputation
   ↓
Solde
```

### 7.3 L'appel de fonds est définitif, pas provisionnel

**Décision actée : pas de régularisation de fin d'exercice.**

Conséquence à assumer explicitement : l'appel de fonds n'est **pas une provision**,
c'est la charge définitive. Ce que le copropriétaire doit = ce qui a été appelé.

Il en découle que :

- l'écart budget / charges réelles est un **rapport informatif**, sans aucun impact
  sur les comptes des lots ;
- l'**écriture d'ajustement devient le seul moyen de corriger un compte**. Elle est
  donc une entité de première classe : motivée, tracée, jamais une modification d'un
  appel existant ;
- la clôture d'exercice se réduit à « geler + reporter les soldes ».

### 7.4 Règle d'or

**Un appel de fonds n'est jamais déplacé d'un mandat ou d'un exercice à un autre.**

Si l'appel AF001 (Mandat 1, Exercice 2025, 1 000 DH) est payé à hauteur de 600 DH et
que le mandat se clôture, on ne réaffecte **pas** AF001 au Mandat 2. On conserve :

```
AF001 · Mandat 1 · Exercice 2025
  Appelé  1 000
  Payé      600
  Solde     400
```

et la passation crée un **solde d'ouverture de 400 DH** sur le Mandat 2.

> La dette est **continuée**, jamais recréée ni déplacée.

## 8. Paiements et imputation

Un paiement enregistre : le lot, qui a payé, la date, le montant, le moyen
(espèces, chèque, virement, TPE, en ligne), la référence et la pièce.

### 8.1 L'imputation est explicite

L'affectation d'un paiement aux lignes d'appel est **matérialisée** dans
`payment_allocations`, jamais calculée implicitement à la volée.

- Règle par défaut : **FIFO par date d'échéance** — la dette la plus ancienne d'abord.
- Cette règle est un **défaut, pas une loi** : le payeur peut désigner l'appel réglé,
  et le syndic doit pouvoir réaffecter.
- Une imputation est **annulable** et l'annulation est tracée.

### 8.2 Exemple

```
Appel AF-2025-005    1 000 DH   échéance 05/02/2025
Appel AF-2025-006    1 000 DH   échéance 05/05/2025

Paiement PAY-001     1 500 DH   le 10/05/2025

Imputation automatique (FIFO) :
   → AF-2025-005 : 1 000 DH  (soldé)
   → AF-2025-006 :   500 DH  (partiel, reste 500)
```

## 9. Compte de lot, créances, recouvrement

### 9.1 La créance est calculée, pas stockée

C'est le point où un système comme celui-ci se casse habituellement. La créance est
par définition `montant appelé − montant imputé`. La **stocker** en parallèle du
livre financier crée deux sources de vérité qui divergent — le bug se manifeste six
mois après la mise en production et il est très coûteux à réparer.

```
Créance d'un lot = Σ débits du ledger − Σ crédits du ledger
```

L'écran « Créances » est une **requête d'agrégation**, pas une table à maintenir.

Si une page devient réellement lente, on ajoute un **cache** `lot_account_balances`
avec `recomputed_at` et une commande de reconstruction. Le cache n'est jamais
l'autorité. On ne l'ajoute pas avant d'avoir mesuré un problème.

### 9.2 Les deux natures de créance, résolues par une seule mécanique

Le besoin exprimé : l'écran affiche « Lot A102 — M. Ahmed — 4 000 DH » et un clic
descend dans le détail. Mais pour une résidence dont les données ont été importées,
**ce détail n'existe pas**.

Ce n'est pas un cas particulier : c'est le même ledger avec un `source_type`
différent.

```
journal_entries.source_type
  ├── FundCallLine    → clic : l'appel de fonds, ce qui reste dû
  ├── Payment         → clic : le paiement, sa pièce
  ├── OpeningBalance  → clic : « Solde repris au 01/01/2026,
  │                             import du relevé du syndic ABC, PJ jointe »
  ├── Adjustment      → clic : le motif, l'auteur, la pièce
  └── Handover        → clic : la passation d'origine
```

**Même table, même écran, transparence honnête sur le niveau de détail disponible.**
On obtient l'écran voulu sans table `creances` parallèle, donc sans risque de
divergence.

### 9.3 Balance âgée

La ventilation de la dette par exercice est un simple `GROUP BY exercise_id` :

```
Lot A102 — dette totale 7 800 DH
   2023      1 000 DH     Mandat M8
   2024      2 300 DH     Mandat M9
   2025      3 500 DH     Mandat M12
   2026      1 000 DH     Mandat M13
```

Les soldes importés en bloc sont marqués **« antérieur à la reprise »** plutôt que
rattachés artificiellement à une année qu'on ne connaît pas.

### 9.4 Traçabilité attendue

À la question « pourquoi le lot A102 doit-il 4 500 DH ? », Bayan doit répondre :

```
4 500 DH
  ├── 1 000 DH  Appel AF-2024-003 · Mandat M12 · Exercice 2024
  │                appelé 2 000 · payé 1 000
  ├── 2 000 DH  Appel AF-2025-001 · Mandat M12 · Exercice 2025
  └── 1 500 DH  Solde d'ouverture · Passation M11 → M12
                   └── origine : créance historique importée le 12/01/2024
                       PJ : releve-syndic-abc.pdf
```

### 9.5 Cycle de vie d'une créance

Le plan comptable révèle un cycle que le modèle initial ignorait. Les comptes `3424`,
`394`, `691`, `6514` et `7514` n'existent pas par hasard : ils décrivent le parcours
complet d'un impayé, et Bayan doit le suivre.

```
        CRÉANCE NORMALE
        3421 / 3422 / 3423
               │
               │  impayé persistant, seuil ou décision
               ▼
        CRÉANCE DOUTEUSE                    ← reclassement comptable
        3424                                  (le montant ne change pas,
               │                               il change de compte)
               │  constatation du risque
               ▼
        DÉPRÉCIATION                        ← 691 / 394
        le risque est provisionné             la créance reste due
               │
      ┌────────┴────────┐
      ▼                 ▼
  RECOUVRÉE         IRRÉCOUVRABLE
  reprise de la     6514 — passée en perte
  dépréciation      après décision de l'AG
      │                 │
      │                 │  si paiement ultérieur inattendu
      │                 ▼
      │             7514 — rentrée sur créance soldée
      ▼
   SOLDÉE
```

**Points de conception :**

- Le **déclassement en douteuse ne change pas le montant dû**. C'est un virement de
  compte à compte sur le même auxiliaire. Le copropriétaire doit toujours la même
  somme ; c'est le regard comptable qui change.
- La **dépréciation est une estimation du risque**, séparée de la créance. Elle
  n'efface rien. Deux grandeurs distinctes coexistent : ce qui est dû (`3424`) et ce
  qu'on pense ne pas récupérer (`394`).
- Le **passage en perte (`6514`) est une décision**, pas un traitement automatique.
  Elle relève de l'AG et doit être tracée avec sa pièce.
- `7514` traite le cas réel du copropriétaire qui paie après que la créance a été
  soldée en perte. Sans ce compte, l'argent n'aurait pas d'imputation possible.

**Ce que ça ajoute au modèle :** un état sur la créance
(`normale` → `douteuse` → `depreciee` → `irrecouvrable` \| `recouvree`), les Actions
correspondantes, et les critères de déclassement (ancienneté, montant, décision
manuelle) — configurables par résidence, jamais codés en dur.

## 10. Trésorerie, dépenses et fournisseurs

Les §7 à 9 décrivent **ce que les copropriétaires doivent**. Cette section décrit
**l'argent réel** : où il est, d'où il vient, où il part.

### 10.1 Le moteur : comptabilité en partie double

**La réglementation impose au syndic la production d'états financiers normalisés.**
Bayan tient donc une véritable **comptabilité en partie double**, avec plan comptable,
journaux, grand livre et balance.

Un seul livre, une seule règle :

> Chaque événement financier produit **une pièce comptable équilibrée** :
> `Σ débits = Σ crédits`.

```
journal_entry            (la pièce : date, journal, libellé, source)
   ├── journal_entry_line   compte · auxiliaire · débit
   └── journal_entry_line   compte · auxiliaire · crédit
```

### 10.1 bis Les trois livres réglementaires

Le règlement impose la tenue de **trois livres**. Ce ne sont pas des rapports de
confort : ce sont des obligations, et ils dictent le modèle.

| Livre | Contenu exigé | Production dans Bayan |
|---|---|---|
| **Livre-journal** | les opérations **jour par jour et opération par opération** | `journal_entries` triées par date |
| **Grand livre** | comptes **individuels et collectifs**, avec solde initial, mouvements débit/crédit et solde final | `journal_entry_lines` groupées par compte et par auxiliaire |
| **Livre d'inventaire** | **état de situation financière** et **compte de gestion général** de chaque exercice | états produits à la clôture |

**Point de conception critique — le journal et le grand livre ne sont pas deux
stockages.** Ce sont **deux lectures du même jeu d'écritures** :

```
                     journal_entries + journal_entry_lines
                              (stockage unique)
                                     │
                    ┌────────────────┴────────────────┐
                    ▼                                 ▼
            LIVRE-JOURNAL                       GRAND LIVRE
        trié par date, par pièce           groupé par compte, par auxiliaire
        « qu'a-t-on fait le 12/03 ? »      « que doit le lot A102 ? »
```

Les stocker séparément créerait deux sources de vérité à réconcilier — l'erreur exacte
que le §9.1 interdit pour les créances. Le grand livre est une **projection**, jamais
une table.

Le **livre d'inventaire** est le seul qui matérialise quelque chose : à la clôture, les
états sont figés et archivés avec leur date et leur validation, car ils doivent rester
consultables à l'identique des années plus tard.

**Vocabulaire réglementaire :** on dit **état de situation financière** (et non
« bilan ») et **compte de gestion général** (et non « compte de résultat »). Ces
intitulés sont ceux du texte et doivent apparaître tels quels dans l'application.

### 10.1 ter Pièces justificatives

Le règlement impose que **chaque écriture soit rattachable à sa pièce justificative**.

Deux chemins coexistent, et les deux doivent fonctionner :

1. **Par la source** : la pièce est portée par l'objet métier (`expenses.facture_document_path`,
   `payments.piece_document_path`). L'écriture y accède via `source_type` / `source_id`.
2. **Directement** : une table `attachments` polymorphe permet d'attacher une ou
   plusieurs pièces à une `journal_entry` elle-même — indispensable pour les écritures
   sans objet métier (ajustements, opérations diverses, à-nouveaux).

**Invariant :** toute pièce comptable résout au moins une pièce justificative, par l'un
ou l'autre chemin. Les seules exceptions autorisées et tracées sont les écritures de
clôture générées par le système.

### 10.1 quater Le plan comptable est réglementaire, pas générique

Le plan applicable est celui du **Ministère, spécifique aux copropriétés** — voir
[Annexe A](#annexe-a--plan-comptable-de-référence).

> ⚠️ **Le plan comptable général des entreprises marocaines ne doit pas être utilisé**,
> ni pour remplacer ce plan, ni pour détailler ses comptes. Le règlement est explicite
> sur ce point. Aucun compte du PCG ne doit apparaître dans le seed ni pouvoir être
> créé par un utilisateur.

Conséquence sur `accounts` : la structure du plan est **fournie et verrouillée**. Une
résidence ne peut pas inventer de comptes. La seule extension autorisée est la création
de **sous-comptes d'un compte existant et de même nature** — typiquement un `512x` par
compte bancaire réel. Un test vérifie qu'aucun compte hors plan de référence n'existe.

Le plan reste néanmoins **de la donnée** : une évolution réglementaire est un nouveau
seed, jamais une migration.

### 10.1 quinquies Comptes collectifs et auxiliaires

Le grand livre exige des comptes **individuels et collectifs**. C'est exactement le
mécanisme collectif + auxiliaire :

| Compte du plan | Auxiliaire | Solde lu |
|---|---|---|
| `342` Collectivité des copropriétaires | un par **lot** | ce que le lot doit |
| `441`° Fournisseurs | un par **fournisseur** | ce qu'on lui doit |
| `512`° Banque | aucun | solde du compte |
| `516`° Caisse | aucun | solde de la caisse |
| `6131` Nettoyage | aucun | réalisé de la ligne |

Le plan reste court (quelques dizaines de comptes) même avec 300 lots, et les soldes
individuels restent des requêtes simples.

### 10.1 sexies Ventilation des créances copropriétaires

**Le règlement impose de ventiler les sommes exigibles auprès de chaque
copropriétaire** par nature. Ce n'est pas un raffinement optionnel : c'est une
obligation, et le plan comptable la matérialise dans les comptes `342x`.

| Nature de la créance | Compte | Produit correspondant |
|---|---|---|
| Opérations courantes | `3422` Copropriétaire – budget prévisionnel | `7111` Provisions sur opérations courantes |
| Travaux et opérations exceptionnelles | `3423` Copropriétaire – travaux et opérations non courantes | `7112` Provisions sur travaux |
| Avances | `3421`° Copropriétaire individualisé | `7113` Avances |
| Emprunts du syndicat | `342x`° *(à confirmer)* | `7121` Emprunts |
| *Reclassement* : créances douteuses | `3424` Copropriétaire – créances douteuses | — |

**Conséquence directe sur le modèle :** un appel de fonds porte une **`nature`**
(`courant` \| `travaux` \| `avance` \| `emprunt`) qui détermine le couple de comptes
mouvementés. Sans elle, la ventilation réglementaire est impossible à produire.

**Conséquence sur le compte de lot :** un lot n'a pas *un* solde mais **un solde par
nature**, plus le total. La table `lot_accounts` reste l'identité auxiliaire ; les
soldes se lisent par `(compte, auxiliaire)`. L'écran de recouvrement doit afficher :

```
Lot A102 — M. Ahmed
  Opérations courantes   3422      2 400 DH
  Travaux                3423      1 600 DH
  Avances                3421          0 DH
  Créances douteuses     3424        500 DH
  ──────────────────────────────────────────
  Total exigible                   4 500 DH
```

> ° Comptes marqués d'un degré : numérotation à confirmer sur le document officiel
> avant le seed (§26 q.3).

### 10.1 ter Écritures types

Correspondance entre les événements métier et les écritures produites. C'est le
contrat que chaque `Action` doit respecter.

| Événement métier | Débit | Crédit |
|---|---|---|
| Appel — opérations courantes | `3422` *(aux : lot)* | `7111` Provisions opérations courantes |
| Appel — travaux | `3423` *(aux : lot)* | `7112` Provisions sur travaux |
| Appel — avance | `3421`° *(aux : lot)* | `7113` Avances |
| Encaissement d'un paiement | `512`° / `516`° Trésorerie | `342x` *(aux : lot)* |
| Facture — eau, électricité, énergie | `6111` / `6112` / `6113` | `441`° *(aux : fournisseur)* |
| Facture — nettoyage | `6131` | `441`° *(aux : fournisseur)* |
| Facture — maintenance, réparations | `6134` / `6135` | `441`° *(aux : fournisseur)* |
| Facture — assurance | `6136` | `441`° *(aux : fournisseur)* |
| **Rémunération du syndic** | `6137` | `441`° *(aux : fournisseur)* |
| Frais bancaires | `6141` | `512`° |
| Salaires et charges du personnel | `6171` / `6172` | `441`° / `512`° |
| Travaux décidés par l'AG | `6511` | `441`° *(aux : fournisseur)* |
| Travaux urgents | `6512` | `441`° *(aux : fournisseur)* |
| Paiement d'une facture (`paid`) | `441`° *(aux : fournisseur)* | `512`° / `516`° |
| Virement entre comptes | Trésorerie destination | Trésorerie origine |
| Indemnité d'assurance reçue | `512`° | `7123` Indemnités d'assurance |
| Produits financiers | `512`° | `7125` Produits financiers |
| Subvention reçue | `512`° | `7122` Subventions |
| Don reçu | `512`° | `7513` Dons reçus |
| **Déclassement en créance douteuse** | `3424` *(aux : lot)* | `342x` d'origine *(aux : lot)* |
| **Dotation à la dépréciation** | `691` | `394` Dépréciation des créances |
| **Créance irrécouvrable** | `6514` Pertes sur créances irrécouvrables | `3424` *(aux : lot)* |
| **Rentrée sur créance soldée** | `512`° | `7514` Rentrées sur créances soldées |
| Dotation au fonds travaux | `7112` | `13x`° Réserves |
| Solde d'ouverture (import, passation) | Compte concerné | `119`° Report à nouveau |
| Ajustement motivé | selon le motif | selon le motif |
| Extourne | inverse exact de la pièce annulée | |

**Remarque importante :** la scission de la dépense en `recorded` puis `paid` (§10.5)
était déjà, sans le nommer, le schéma de la partie double. Elle n'est pas à revoir —
elle devient simplement la règle générale.

**Ces écritures types sont un contrat testable.** Chaque ligne du tableau correspond à
un test : telle Action produit exactement cette pièce, sur ces comptes, avec cet
auxiliaire. C'est la traduction la plus directe du règlement en code.

### 10.2 Convention de sens

Convention comptable standard : **le débit augmente l'actif et les charges, le crédit
augmente le passif et les produits.**

| | Débit | Crédit |
|---|---|---|
| Copropriétaires *(actif)* | le lot doit plus (appel) | le lot doit moins (paiement) |
| Trésorerie *(actif)* | l'argent entre | l'argent sort |
| Fournisseurs *(passif)* | on rembourse | on doit plus |
| Charges | dépense constatée | annulation |
| Produits | annulation | appel émis, produit acquis |

À écrire noir sur blanc pour éviter les erreurs de signe : un paiement **crédite** le
compte du lot et **débite** le compte bancaire, du même montant, dans la même pièce.

### 10.3 Comptes de trésorerie

```
treasury_accounts
  type          banque | caisse | compte_sur_carnet
  affectation   fonctionnement | investissement | mixte
  solde_initial + date_solde_initial
```

- Le compte appartient à la **résidence**, jamais au syndic. Au changement de syndic,
  le compte peut être clôturé et un nouveau ouvert : c'est un **virement de trésorerie
  tracé**, jamais une réécriture de l'historique.
- L'`affectation` permet d'isoler le **fonds travaux / investissement** sur un compte
  dédié et d'en surveiller le solde séparément du fonctionnement.
- Une **caisse ne peut jamais être négative** (invariant bloquant). Une banque peut
  l'être si un découvert est autorisé — alerte, non bloquante.

### 10.4 Ce qui alimente la trésorerie

| Source | Effet trésorerie | Effet compte de lot |
|---|---|---|
| Paiement d'un copropriétaire | débit (entrée) | crédit |
| Dépense payée | crédit (sortie) | aucun |
| Virement entre comptes | crédit sur l'un, débit sur l'autre | aucun |
| Autre produit (location d'un local, intérêts, indemnité d'assurance) | débit | aucun |
| Solde d'ouverture (import ou passation) | débit ou crédit | aucun |
| Ajustement motivé | débit ou crédit | aucun |

Une écriture de trésorerie sans `source_type` résolvable est interdite.

### 10.5 Dépenses et dettes fournisseurs

Une dépense a **deux moments distincts**, et c'est précisément ce qui rend les dettes
fournisseurs calculables :

```
draft ──▶ recorded ──────────▶ paid
             │                   │
             │                   └── l'argent sort :
             │                       une écriture de trésorerie est produite
             │
             └── la facture existe : la résidence DOIT cet argent,
                 mais rien n'a encore bougé en trésorerie
```

Conséquence directe : **dettes fournisseurs = Σ des dépenses `recorded` non encore
`paid`**. Ce n'est plus une saisie manuelle dans la passation, c'est une requête.

Chaque dépense porte sa pièce (facture), son fournisseur, sa date, son rattachement à
un exercice et, quand c'est pertinent, à une ligne budgétaire. Au paiement s'ajoutent
le compte de trésorerie débité, le moyen et la référence.

Conformément à D7, **les dépenses n'impactent jamais les comptes des lots**. Elles
alimentent le suivi budget / réalisé et la trésorerie.

### 10.6 Rapprochement bancaire

Opération centrale du métier de syndic : confronter les mouvements Bayan au relevé de
la banque.

```
Rapprochement · Compte BMCE · 01/01/2026 → 31/03/2026

  Solde Bayan au 31/03      142 350,00 DH
  Solde du relevé           139 850,00 DH
  Écart                       2 500,00 DH
        └── chèque n°102 émis le 28/03, non encore débité

  État : draft ──▶ balanced (écart nul ou justifié) ──▶ closed
```

- Chaque écriture de trésorerie porte un `reconciliation_id` une fois pointée.
- Un rapprochement passe à `balanced` seulement si l'écart est nul **ou** intégralement
  justifié par des mouvements en attente identifiés.
- Un rapprochement `closed` **verrouille** les écritures qu'il a pointées.

### 10.7 Situation de trésorerie

Écran attendu à tout instant :

```
TRÉSORERIE · Résidence Greenwood · au 25/08/2026

  Banque BMCE (fonctionnement)        142 350,00
  Caisse                                3 200,00
  Compte sur carnet (fonds travaux)   380 000,00
  ──────────────────────────────────────────────
  Total disponible                    525 550,00

  − Dettes fournisseurs               − 47 800,00
  ──────────────────────────────────────────────
  Disponible net                      477 750,00

  Pour mémoire :
  Créances copropriétaires             198 400,00   (attendu, non encaissé)
```

La distinction **encaissé / attendu** doit rester visible en permanence. Un solde de
trésorerie confortable accompagné de 200 000 DH de créances n'est pas la même
situation qu'un solde identique sans impayés.

### 10.8 États financiers produits

La partie double permet de produire, à toute date et pour tout exercice :

| État | Contenu |
|---|---|
| **Grand livre** | toutes les écritures d'un compte, dans l'ordre |
| **Balance générale** | débits, crédits et solde de chaque compte du plan |
| **Balance auxiliaire** | idem par lot ou par fournisseur |
| **Compte de résultat** | charges et produits de l'exercice |
| **Bilan** | actif / passif à la date de clôture |
| **Budget vs réalisé** | par ligne budgétaire |
| **Situation de trésorerie** | §10.7 |
| **Relevé de compte copropriétaire** | §9 |

Le format exact des états réglementaires (intitulés, regroupements, présentation) est
**dérivé du plan comptable configuré**, donc adaptable sans changement de schéma.

### 10.9 Fonds travaux : réserve au passif

Le fonds travaux (fonds de réserve pour gros travaux — toiture, ascenseur, façade) est
alimenté par les appels d'investissement et ne doit pas financer le fonctionnement
courant. Trois niveaux de séparation existent, et ils sont cumulables :

| Niveau | Mécanisme | Ce que ça garantit |
|---|---|---|
| **1. Comptable** *(retenu)* | un compte de **réserve au passif** dans le plan | on sait toujours combien la réserve vaut |
| **2. Analytique** | `treasury_accounts.affectation` | on sait sur quel compte l'argent est censé être |
| **3. Physique** | un compte bancaire **dédié**, séparé | on ne *peut pas* dépenser la réserve par erreur |

Bayan implémente les niveaux 1 et 2 en v1. Le niveau 3 est **une règle de gestion
optionnelle et activable** : si un compte de trésorerie est marqué `dedie_fonds_travaux`,
toute tentative d'y imputer une dépense de fonctionnement est **bloquée**.

**Contrôle permanent :** `réserve fonds travaux au passif` comparée à la
`trésorerie affectée investissement`. Si la réserve n'est plus couverte, c'est que le
fonds a été consommé par le fonctionnement — alerte immédiate, visible du conseil
syndical.

> → §26 question 5 : la loi 18-00 impose-t-elle un **compte bancaire dédié** au fonds
> travaux ? Si oui, le niveau 3 devient obligatoire et non plus optionnel.

## 11. Clôture d'exercice

```
Exercice OPEN
     ↓ le syndic lance la clôture
Exercice CLOSING
     ↓ contrôles automatiques
         · tous les paiements confirmés
         · aucune imputation en attente
         · solde de chaque compte de lot = Σ ledger (cohérence)
         · rapprochement bancaire à jour sur tous les comptes de trésorerie
         · aucune dépense laissée en état `draft`
     ↓ écritures de clôture
         · solder les comptes de CHARGES et de PRODUITS
         · résultat de l'exercice → Report à nouveau
     ↓ production des états (bilan, compte de résultat, balance)
     ↓ validation du conseil syndical
Exercice CLOSED
     ↓
À-nouveaux : réouverture des comptes de BILAN uniquement
sur le nouvel exercice
```

**Le résultat n'est pas ventilé sur les comptes des lots** (conséquence de D7) : il
reste au report à nouveau / fonds de roulement de la copropriété. La partie double
fournit le résultat comptable ; le choix de ne pas le répartir reste entier.

Les à-nouveaux ne recréent pas la dette : ils rouvrent les comptes de bilan
(copropriétaires, trésorerie, fournisseurs, réserves) avec leur solde de clôture, dans
une pièce dont la source est `Closing` et qui **pointe l'exercice d'origine**.

## 12. Passation entre mandats

La passation est un **vrai processus métier**, avec états, rapprochement et journal
d'audit — pas un simple recopiage de soldes.

```
Passation TR-2026-001
  Mandat sortant  : M12
  Mandat entrant  : M13
  Date            : 15/01/2026
  État            : draft → reviewed → finalized
```

### 12.1 Catégories transférées

| Catégorie | Origine |
|---|---|
| Créances des copropriétaires | **calculée** depuis le compte collectif Copropriétaires |
| Soldes de trésorerie (banque, caisse, fonds travaux) | **calculée** depuis les comptes de trésorerie |
| Dettes fournisseurs | **calculée** : dépenses `recorded` non `paid` |
| Actifs (matériel, contrats) | saisie |
| Documents | saisie |
| Autres soldes d'ouverture | saisie |

Le suivi de trésorerie (§10) transforme cette section : **trois catégories sur six
deviennent calculées** au lieu d'être saisies à la main. C'est le principal bénéfice
collatéral de son intégration en v1 — la passation cesse d'être un formulaire déclaratif
pour devenir un état rapproché.

Le rapprochement de passation compare, à la date d'effet, les soldes calculés par Bayan
aux soldes déclarés par le syndic sortant. Tout écart doit être justifié avant le
passage à `finalized`.

**Modélisation :** une table `handovers` + une table `handover_items` avec une
catégorie et une référence source polymorphe. Pas cinq tables quasi identiques
(`HandoverReceivable`, `HandoverCash`, `HandoverLiability`…) : ce serait cinq fois
le même schéma à maintenir.

### 12.2 Sortie de la passation

La finalisation produit uniquement des **écritures de ledger** avec
`source_type = Handover`. C'est tout. La passation est donc simple à coder **si et
seulement si** le ledger a été bien conçu en amont — d'où l'ordre de développement
recommandé (§ Plan).

## 13. Import historique

**L'import historique et la passation sont deux mécanismes distincts qu'il ne faut
jamais mélanger.**

| | Import historique | Passation |
|---|---|---|
| Origine | Système externe (Excel, PDF, ancien logiciel) | Interne à Bayan |
| Confiance | Faible, données à valider | Élevée, données calculées |
| Détail | Souvent absent | Complet et traçable |
| Déclencheur | Reprise d'une résidence existante | Fin de mandat |

### 13.1 Assistant en étapes

Pas de « CSV magique ». Un assistant :

```
1. Lots                    A101, A102, A103…  + tantièmes
2. Copropriétaires         lot → détenteurs, quotes-parts
3. Anciens mandats         périodes + sociétés (optionnel)
4. Soldes copropriétaires  A101 : 0 · A102 : 4 500 · A103 : 1 200
5. Comptes de trésorerie   banque, caisse, fonds travaux + soldes à la date de reprise
6. Dettes fournisseurs     factures reçues non réglées (optionnel)
7. Historique détaillé     appels, paiements, charges (optionnel)
8. Soldes d'ouverture      génération des pièces comptables d'ouverture
```

La reprise de trésorerie est **obligatoire** : sans elle, la situation de trésorerie
(§10.7) est fausse dès le premier jour. Chaque solde repris porte sa pièce
justificative (dernier relevé bancaire, PV de comptage de caisse).

### 13.2 Modélisation

**Ne pas créer de tables miroir** (`ImportedMandate`, `ImportedExercise`,
`ImportedOwner`…) : cela double le schéma pour toujours.

Staging générique :

```
import_batches   fichier, type, état, statistiques
import_rows      numéro de ligne, payload JSON, état, erreurs,
                 entité créée (type + id)
import_mappings  colonne source → champ cible → transformation
```

Puis matérialisation dans les entités réelles lors du `commit`.

### 13.3 Deux scénarios à la création d'une résidence

```
Nouvelle résidence          Résidence existante
       ↓                            ↓
Mandat initial              Import historique
       ↓                            ↓
   Exercice                 Soldes d'ouverture
       ↓                            ↓
    Budget                  Mandat courant
```

Le nouveau syndic doit pouvoir dire « je reprends uniquement les soldes actuels, je
ne reconstruis pas 15 ans d'historique » — l'import de niveau 4 (soldes seuls) doit
donc être pleinement fonctionnel sans les niveaux 3 et 5.

## 14. Portail copropriétaire

Le copropriétaire dispose d'un compte utilisateur.

**Ce qu'il voit :**

- ses lots, son solde consolidé et par lot ;
- ses appels de fonds (montant, échéance, payé, restant dû) ;
- ses paiements et leurs pièces ;
- son relevé de compte exportable en PDF ;
- les documents publics : budgets approuvés, PV d'AG, règlement.

**Ce qu'il ne voit pas :** les comptes des autres lots, les dépenses détaillées, la
gestion.

**Liaison identité :** `users` porte l'identité de connexion, `owners` porte la partie
copropriétaire **par résidence**. Une même personne détenant des lots dans deux
résidences gérées par Bayan a **un seul compte utilisateur** rattaché à deux
enregistrements `owners`, avec un sélecteur de résidence.

**Cycle de vie de l'accès :** il s'ouvre quand une détention devient active et se
ferme quand elle se termine. L'ancien propriétaire conserve la consultation de la
période où il détenait le lot.

## 15. Validation par le conseil syndical

Mécanisme léger et uniforme, pas un moteur de workflow.

**Objets soumis à validation :** budget, appel de fonds, clôture d'exercice,
passation.

```
Syndic prépare  →  état draft
       ↓ soumission
    submitted   →  notification au président
       ↓
Président décide
   ├── approved  →  l'objet devient exécutable
   └── rejected  →  retour en draft avec commentaire obligatoire
```

Chaque décision est enregistrée (qui, quand, commentaire) et **immuable**.

Le président du conseil syndical est un copropriétaire élu **avec un mandat daté** :
son rôle est une attribution temporelle, pas un attribut permanent de l'utilisateur.

---

# Partie II — Technique

## 16. Principes directeurs

Ces sept règles priment sur toute considération de confort d'implémentation.

1. **Immuabilité de la comptabilité.** Aucune suppression physique, aucune
   modification d'écriture. Une correction est une **extourne** (`reverses_entry_id`)
   suivie d'une nouvelle écriture.
2. **Partie double.** Chaque événement financier produit une pièce équilibrée
   (`Σ débit = Σ crédit`), vérifiée en un point unique du code. L'équilibre est
   structurel, pas surveillé.
3. **Traçabilité totale.** Chaque écriture porte `source_type` + `source_id`. Aucun
   chiffre ne doit apparaître sans origine remontable.
4. **Une seule source de vérité.** Tous les soldes — lots, trésorerie, fournisseurs,
   charges — sont calculés depuis `journal_entry_lines`. Tout agrégat stocké est un
   cache reconstructible, jamais l'autorité.
5. **Contexte obligatoire.** Chaque opération porte son `mandate_id` et son
   `exercise_id`. Ils ne sont jamais réécrits après coup.
6. **Écriture comptable réservée aux Actions.** Aucun contrôleur, job, observer ou
   commande n'écrit directement dans `journal_entry_lines`. Règle vérifiée par un test
   d'architecture.
7. **Le temps est modélisé, pas déduit.** Mandats, exercices, détentions de lot,
   mandats du conseil : tous portent `started_on` / `ended_on`.

## 17. Schéma de données

Types : montants en **entiers (centimes)**, quotes-parts en **points de base**
(10 000 = 100 %), dates en `date`, horodatages en `datetime`.

### 17.1 Plateforme et identité

```
users
  id, name, email, password, two_factor_*, locale
  is_platform_admin: bool
  timestamps

syndic_companies
  id, nom, forme_juridique, ice, rc, adresse, telephone, email
  timestamps

syndic_company_user
  id, syndic_company_id, user_id
  role: gerant | gestionnaire | comptable
  timestamps

residences
  id, nom, adresse, ville
  total_tantiemes: int (défaut 1000)
  devise: char(3) (défaut MAD)
  settings: json
  timestamps
```

### 17.2 Licence et accès

```
licenses
  id, residence_id
  plan, starts_on, ends_on
  grace_days: int (défaut 30)
  status: active | grace | read_only | suspended
  payer: syndic | copropriete
  timestamps

license_events
  id, license_id
  type: created | renewed | suspended | reactivated | expired
  effective_at, actor_user_id, note
  created_at

residence_accesses
  id, residence_id, syndic_company_id
  status: active | revoked
  granted_at, granted_by_admin_id
  revoked_at, revoked_by_admin_id, revoked_motif, revoked_document_path
  timestamps

residence_roles                      -- président et membres du conseil syndical
  id, residence_id, user_id
  role: president_conseil | membre_conseil
  started_on, ended_on
  granted_by_user_id, pv_ag_document_path
  timestamps

audit_logs
  id, residence_id (nullable), actor_user_id
  action, subject_type, subject_id
  payload: json, ip, user_agent
  created_at
```

### 17.3 Structure de la copropriété

```
lots
  id, residence_id
  reference (A102), type: appartement|magasin|parking|cave|bureau
  batiment, etage, superficie
  tantiemes: int
  description
  status: actif | inactif
  timestamps

owners
  id, residence_id
  user_id (nullable)                 -- liaison portail
  type: physique | morale
  nom, prenom, raison_sociale
  cin_ou_ice, email, telephone
  adresse_correspondance
  timestamps

lot_ownerships
  id, lot_id, owner_id
  quote_part: int (bps, Σ actives = 10000 par lot)
  nature: pleine_propriete | indivision | usufruit | nue_propriete
  started_on, ended_on (nullable)
  acte_document_path
  timestamps

lot_mutations
  id, lot_id, date
  type: vente | succession | donation | autre
  solde_a_la_date: bigint (centimes)
  note, document_path, created_by_user_id
  timestamps

lot_accounts
  id, lot_id (unique)
  timestamps
```

### 17.4 Gestion

```
mandates
  id, residence_id, syndic_company_id
  numero, started_on, ended_on (nullable)
  duree_statutaire_mois: int (défaut 24)
  status: draft | active | suspended | terminated | expired | closed
  pv_ag_document_path, motif_fin
  timestamps

exercises
  id, residence_id, mandate_id
  libelle (2025-A), starts_on, ends_on
  status: open | closing | closed
  closed_at, closed_by_user_id
  timestamps

budgets
  id, exercise_id
  type: fonctionnement | investissement
  libelle, montant_total: bigint
  status: draft | submitted | approved | rejected
  timestamps

budget_lines
  id, budget_id, libelle, categorie
  account_id                          -- le compte de charge imputé
  montant: bigint, ordre
  timestamps
```

### 17.5 Financier

```
fund_calls
  id, residence_id, exercise_id, budget_id (nullable)
  numero (AF-2025-005), libelle
  nature: courant | travaux | avance | emprunt    -- ⚠ pilote les comptes 342x/71xx
  date_emission, date_echeance
  montant_total: bigint
  status: draft | submitted | approved | issued | cancelled
  issued_at, cancelled_at, cancel_motif
  timestamps

fund_call_lines
  id, fund_call_id, lot_id, lot_account_id
  tantiemes_used: int                -- figé à l'émission
  total_tantiemes_used: int          -- figé à l'émission
  montant: bigint
  -- cycle de recouvrement (§9.5)
  creance_status: normale | douteuse | depreciee | irrecouvrable | recouvree
  declassee_at, declassee_by_user_id, declassement_motif
  irrecouvrable_at, irrecouvrable_pv_document_path
  timestamps

payments
  id, residence_id, lot_id, lot_account_id
  paid_by_owner_id (nullable)
  treasury_account_id                 -- où l'argent est encaissé
  mandate_id, exercise_id
  date, montant: bigint
  moyen: especes | cheque | virement | tpe | en_ligne
  reference, piece_document_path
  status: pending | confirmed | bounced | cancelled
  timestamps

payment_allocations
  id, payment_id, fund_call_line_id
  montant: bigint
  auto: bool                          -- FIFO automatique ou manuelle
  allocated_at, allocated_by_user_id
  reversed_at, reversed_by_user_id
  timestamps

expenses
  id, residence_id, exercise_id, budget_line_id (nullable)
  supplier_id, account_id             -- compte de charge imputé
  date_facture, montant: bigint, libelle
  facture_reference, facture_document_path
  status: draft | recorded | paid | cancelled
  -- renseignés au paiement uniquement :
  treasury_account_id (nullable), date_paiement (nullable)
  moyen_paiement (nullable), paiement_reference (nullable)
  timestamps

opening_balances
  id, residence_id
  cible: lot_account | treasury_account
  lot_account_id (nullable), treasury_account_id (nullable)
  source: import | handover
  import_batch_id (nullable), handover_id (nullable)
  date_effet, montant: bigint (signé)
  origine_libelle                     -- « relevé syndic ABC au 31/12/2025 »
  document_path
  timestamps
  -- contrainte : exactement une des deux cibles est renseignée

adjustments
  id, residence_id, lot_account_id, exercise_id
  date, montant: bigint (signé)
  motif (obligatoire), document_path
  created_by_user_id
  reverses_ledger_entry_id (nullable)
  timestamps
```

### 17.6 Le moteur comptable

Cœur du système. **Insert-only.** Pas d'`updated_at`, pas de `deleted_at`, jamais de
`UPDATE` ni de `DELETE`.

```
accounts                              -- le plan comptable, configurable
  id
  residence_id (nullable = plan modèle partagé)
  numero, libelle
  classe: smallint                    -- selon le plan retenu
  type: actif | passif | charge | produit
  auxiliary_kind: null | lot_account | supplier
  is_collectif: bool
  is_treasury: bool
  parent_id (nullable)
  status: active | archived
  timestamps
  unique (residence_id, numero)

journals
  id, residence_id
  code (AC, BQ, CA, OD, AF), libelle
  type: achats | banque | caisse | operations_diverses | appels_de_fonds
  treasury_account_id (nullable)
  timestamps

journal_entries                       -- la pièce comptable
  id, residence_id, journal_id
  mandate_id, exercise_id
  numero, date, libelle
  source_type, source_id              -- FundCall | Payment | Expense
                                      -- | TreasuryTransfer | OtherIncome
                                      -- | OpeningBalance | Handover
                                      -- | Adjustment | Closing
  reverses_entry_id (nullable)
  created_by_user_id
  created_at

  index (residence_id, exercise_id, date)
  index (source_type, source_id)
  index (journal_id, date)

journal_entry_lines
  id, journal_entry_id
  account_id
  auxiliary_type, auxiliary_id (nullable)   -- LotAccount | Supplier
  libelle
  debit: bigint (défaut 0)
  credit: bigint (défaut 0)
  reconciliation_id (nullable)              -- pointage bancaire
  ordre

  index (account_id)
  index (auxiliary_type, auxiliary_id)
  index (reconciliation_id)

suppliers
  id, residence_id
  nom, ice, contact, iban
  timestamps

attachments                           -- pièces justificatives (§10.1 ter)
  id, residence_id
  attachable_type, attachable_id      -- JournalEntry | Expense | Payment | ...
  type: facture | releve | pv_ag | contrat | recu | autre
  filename, path, mime, taille
  uploaded_by_user_id
  timestamps
  index (attachable_type, attachable_id)

financial_statements                  -- livre d'inventaire : états figés à la clôture
  id, residence_id, exercise_id
  type: situation_financiere | compte_de_gestion
  genere_at, genere_by_user_id
  contenu: json                       -- l'état figé, tel que validé
  pdf_path
  validated_at, validated_by_user_id
  timestamps
  unique (exercise_id, type)
```

**`financial_statements` est le seul agrégat matérialisé du système.** Le livre
d'inventaire doit rester consultable **à l'identique** des années plus tard : un état
recalculé pourrait changer si une écriture d'ajustement est passée entre-temps. Il est
donc figé, jamais recalculé. C'est la seule exception au principe « tout est dérivé du
journal » (§16.4), et elle est délibérée.

**Invariant fondamental :** pour chaque `journal_entry`, `Σ debit = Σ credit`.
Tout le reste en découle.

**Lecture des soldes — une seule table interrogée de trois façons :**

```
solde d'un lot          Σ(debit − credit) WHERE auxiliary = ce lot
solde d'un fournisseur  Σ(credit − debit) WHERE auxiliary = ce fournisseur
solde de trésorerie     Σ(debit − credit) WHERE account_id = ce compte
réalisé d'une charge    Σ(debit − credit) WHERE account_id = ce compte de charge
```

Caches optionnels, **à n'ajouter qu'après mesure d'un problème de performance** :

```
account_balances
  account_id, auxiliary_type, auxiliary_id, exercise_id
  debit_cumule, credit_cumule, solde, recomputed_at
  unique (account_id, auxiliary_type, auxiliary_id, exercise_id)
```

### 17.7 Trésorerie

Les comptes de trésorerie sont des **comptes du plan comptable** (`is_treasury`), pas
un livre séparé. Cette table porte leurs caractéristiques bancaires et les règles de
gestion.

```
treasury_accounts
  id, residence_id
  account_id                          -- le compte du plan comptable (1-1)
  type: banque | caisse | compte_sur_carnet
  libelle, banque_nom, rib, devise
  affectation: fonctionnement | investissement | mixte
  dedie_fonds_travaux: bool           -- si true, dépenses de fonctionnement bloquées
  solde_initial: bigint, date_solde_initial
  decouvert_autorise: bigint (défaut 0)
  status: active | closed
  closed_at, closed_motif
  timestamps

treasury_transfers
  id, residence_id
  from_treasury_account_id, to_treasury_account_id
  date, montant: bigint, motif
  document_path, created_by_user_id
  timestamps

other_incomes
  id, residence_id, exercise_id, treasury_account_id
  date, montant: bigint
  type: location | interets | indemnite_assurance | subvention | autre
  libelle, document_path
  timestamps

bank_reconciliations
  id, residence_id, treasury_account_id
  periode_debut, periode_fin
  solde_releve: bigint                -- solde du relevé bancaire
  solde_calcule: bigint               -- solde Bayan à la date
  ecart: bigint
  status: draft | balanced | closed
  releve_document_path
  closed_at, closed_by_user_id
  timestamps
```

Le rapprochement pointe des `journal_entry_lines` (via `reconciliation_id`), pas une
table dédiée.

### 17.8 Passation et import

```
handovers
  id, residence_id, from_mandate_id, to_mandate_id
  date, status: draft | reviewed | finalized
  reviewed_at, reviewed_by_user_id
  finalized_at, finalized_by_user_id
  notes
  timestamps

handover_items
  id, handover_id
  categorie: receivable | cash | liability | asset | document
  lot_account_id (nullable), treasury_account_id (nullable)
  montant_declare: bigint (nullable)   -- déclaré par le syndic sortant
  ecart: bigint (nullable)             -- montant_declare − montant calculé
  ecart_justification
  libelle, montant: bigint
  source_type, source_id (nullable)
  document_path
  timestamps

import_batches
  id, residence_id
  type: lots | owners | balances | treasury | supplier_debts | history
  filename, status: uploaded | mapped | validated | committed | failed
  uploaded_by_user_id, committed_at
  stats: json
  timestamps

import_rows
  id, import_batch_id, ligne
  payload: json, status, errors: json
  created_entity_type, created_entity_id
  timestamps

import_mappings
  id, import_batch_id
  colonne_source, champ_cible, transformation
  timestamps
```

### 17.9 Validation

```
validations
  id, residence_id
  validatable_type, validatable_id    -- Budget | FundCall | Exercise | Handover
  requested_by_user_id, requested_at
  decided_by_user_id, decided_at
  decision: approved | rejected
  commentaire
  timestamps
```

### 17.10 Séquences

```
sequences
  id, residence_id, scope (fund_call, payment, handover), annee
  next_value: int
  unique (residence_id, scope, annee)
```

## 18. Invariants

Chacun fait l'objet d'un test automatisé et, quand c'est possible, d'une contrainte
en base.

**Structure**

- `Σ lots.tantiemes = residences.total_tantiemes` pour chaque résidence.
- Pour chaque lot, `Σ lot_ownerships.quote_part` des détentions actives = `10000`.
- Les détentions d'un même lot pour un même propriétaire ne se chevauchent pas.

**Temporel**

- Les exercices d'une résidence ne se chevauchent jamais.
- Un exercice est entièrement contenu dans les bornes de son mandat.
- Un seul mandat `active` par résidence à un instant donné.
- Un seul `residence_accesses` `active` par résidence.

**Financier**

- `Σ fund_call_lines.montant = fund_calls.montant_total` (après application de la
  règle du plus fort reste).
- `Σ payment_allocations.montant ≤ payments.montant` pour chaque paiement.
- `Σ payment_allocations.montant ≤ fund_call_lines.montant` pour chaque ligne.
- Une écriture de ledger a soit `debit > 0` soit `credit > 0`, jamais les deux,
  jamais aucun.
**Comptabilité (les plus importants)**

- **`Σ debit = Σ credit` pour chaque `journal_entry`.** Invariant fondamental : aucune
  pièce déséquilibrée ne peut être persistée.
- Une ligne a soit `debit > 0` soit `credit > 0`, jamais les deux, jamais aucun.
- Toute pièce a un `source_type` / `source_id` résolvable.
- Toute pièce a au moins deux lignes.
- Une ligne sur un compte dont `auxiliary_kind` est non nul **doit** porter un
  auxiliaire du bon type ; une ligne sur un compte sans auxiliaire n'en porte pas.
- La balance générale est équilibrée : `Σ debit = Σ credit` sur tout l'exercice.
- Une pièce est rattachée à un exercice `open` ou `closing`, jamais `closed`.
- Solde d'un lot = `Σ(debit − credit)` sur son auxiliaire — la seule définition.

**Trésorerie**

- `solde = solde_initial + Σ(debit − credit)` sur le compte du plan associé.
- Le solde d'une **caisse** ne peut jamais être négatif (contrainte bloquante).
- Le solde d'une **banque** ne peut descendre sous `−decouvert_autorise`
  (alerte, non bloquante par défaut).
- Un compte de trésorerie `closed` a un solde nul.
- Un compte `dedie_fonds_travaux` refuse toute imputation de charge de fonctionnement.
- La **réserve fonds travaux au passif** est couverte par la trésorerie affectée
  investissement (alerte si ce n'est plus le cas).

**Écritures types**

- Tout `payment` `confirmed` produit exactement une pièce, débit trésorerie / crédit
  copropriétaire du même montant.
- Toute `expense` `recorded` produit une pièce débit charge / crédit fournisseur ;
  `paid` produit une seconde pièce débit fournisseur / crédit trésorerie.
- Tout `treasury_transfer` produit une pièce à deux lignes sur deux comptes de
  trésorerie différents.
- Un `opening_balance` cible exactement un compte, avec `Report à nouveau` en
  contrepartie.

**Rapprochement bancaire**

- Un rapprochement ne passe à `balanced` que si `ecart = 0` ou si l'écart est
  intégralement couvert par des écritures non pointées identifiées.
- Une écriture pointée par un rapprochement `closed` ne peut plus être extournée
  sans réouverture du rapprochement.
- Les périodes de rapprochement d'un même compte ne se chevauchent pas.

**Immuabilité**

- Aucune pièce ni ligne comptable n'est modifiée ou supprimée après création.
- Aucun objet rattaché à un exercice `closed` n'est modifié.
- Aucune écriture comptable en dehors d'une classe `Action` (test d'architecture).

## 19. Machines à états

```
Licence     active → grace → read_only
                  ↘ suspended (admin)

Mandat      draft → active → suspended → active
                          ↘ terminated ↘ closed
                          ↘ expired    ↗

Exercice    open → closing → closed        (irréversible)

Budget      draft → submitted → approved
                            ↘ rejected → draft

Appel       draft → submitted → approved → issued
                            ↘ rejected → draft
                                          issued → cancelled (extourne)

Paiement    pending → confirmed
                    ↘ bounced
                    ↘ cancelled

Dépense     draft → recorded → paid        -- recorded = dette fournisseur
                            ↘ cancelled       paid    = sortie de trésorerie

Trésorerie  active → closed                (solde nul exigé)

Rapproch.   draft → balanced → closed
                  ↖ réouverture (admin ou syndic, tracée)

Passation   draft → reviewed → finalized   (irréversible)

Import      uploaded → mapped → validated → committed
                                         ↘ failed
```

Toute transition irréversible exige une validation explicite et produit une entrée
d'audit.

## 20. Autorisation

### 20.1 Résolution de l'accès

```
Requête
   ↓
Middleware licence         → licence active/grace ? sinon lecture seule ou 403
   ↓
Middleware résidence       → scope tenant sur residence_id
   ↓
Résolution du rôle
   ├── is_platform_admin                     → admin
   ├── membre d'une société accréditée       → syndic
   ├── residence_roles actif                 → président / membre du conseil
   └── lot_ownerships actif                  → copropriétaire
   ↓
Policy de l'objet
```

Le rôle n'est **jamais** une colonne sur `users`. Il est toujours résolu depuis une
relation datée sur la résidence courante.

### 20.2 Matrice

Colonne « Conseil syndical » = président **et** membres. Les cellules de **validation**
sont réservées au **président seul** ; toutes les autres valent pour les deux.

| Action | Admin | Syndic | Conseil syndical | Copropriétaire |
|---|:--:|:--:|:--:|:--:|
| Gérer licence et accès | ✅ | ❌ | ❌ | ❌ |
| Créer / clore un mandat | ✅ | ✅ | ❌ | ❌ |
| Gérer lots et copropriétaires | ✅ | ✅ | ❌ | ❌ |
| Budget : créer, soumettre | ❌ | ✅ | ❌ | ❌ |
| Budget : valider | ❌ | ❌ | ✅ *président* | ❌ |
| Appel : générer, soumettre | ❌ | ✅ | ❌ | ❌ |
| Appel : valider | ❌ | ❌ | ✅ *président* | ❌ |
| Paiements : saisir, imputer | ❌ | ✅ | ❌ | ❌ |
| Dépenses : saisir (`recorded`) | ❌ | ✅ | ❌ | ❌ |
| Dépenses : payer (`paid`) | ❌ | ✅ | ❌ | ❌ |
| Comptes de trésorerie : créer, clôturer | ✅ | ✅ | ❌ | ❌ |
| Virement entre comptes de trésorerie | ❌ | ✅ | ❌ | ❌ |
| Autres produits : saisir | ❌ | ✅ | ❌ | ❌ |
| Rapprochement bancaire : effectuer | ❌ | ✅ | ❌ | ❌ |
| Rapprochement bancaire : consulter | ✅ | ✅ | ✅ | ❌ |
| Situation de trésorerie : consulter | ✅ | ✅ | ✅ | ❌ |
| Ajustement de compte ou de trésorerie | ❌ | ✅ | ❌ | ❌ |
| Clôture : lancer | ❌ | ✅ | ❌ | ❌ |
| Clôture : valider | ❌ | ❌ | ✅ *président* | ❌ |
| Passation : préparer | ❌ | ✅ | ❌ | ❌ |
| Passation : finaliser | ✅ | ❌ | ✅ *président* | ❌ |
| Import historique | ✅ | ✅ | ❌ | ❌ |
| Plan comptable : configurer | ✅ | ✅ | ❌ | ❌ |
| Consulter tous les comptes | ✅ | ✅ | ✅ | ❌ |
| États financiers : consulter | ✅ | ✅ | ✅ | ❌ |
| Grand livre, balance : consulter | ✅ | ✅ | ✅ | ❌ |
| Consulter son propre compte | — | — | ✅ | ✅ |
| Consulter documents publics | ✅ | ✅ | ✅ | ✅ |
| Export | ✅ | ✅ | ✅ | ✅ (le sien) |

En état de licence `read_only`, **toutes les colonnes d'écriture sont désactivées**,
les colonnes de lecture et d'export restent actives.

## 21. Montants, arrondis, numérotation

### 21.1 Montants

- Stockage : **entiers, en centimes** (`bigint`). Jamais de flottant, jamais de
  `decimal` manipulé en PHP.
- Devise : `MAD` par défaut, portée par la résidence.
- Formatage à l'affichage uniquement.

### 21.2 Répartition et arrondi

```
montant_lot = floor(montant_total × tantiemes_lot / total_tantiemes)
```

Le reliquat (`montant_total − Σ montant_lot`) est distribué **1 centime par lot**, par
ordre décroissant de la partie fractionnaire écartée, puis par référence de lot pour
garantir un résultat **déterministe** (méthode du plus fort reste).

Invariant : `Σ montant_lot = montant_total`, toujours, au centime près.

### 21.3 Numérotation

Format : `AF-2025-005`, `PAY-2025-018`, `TR-2026-001`.

Séquences **sans trou**, par résidence + portée + année, générées dans la transaction
via un verrou sur la table `sequences`. Jamais un `COUNT(*) + 1`.

## 22. Couche applicative (Actions)

Les transitions financières sont des classes `Action` transactionnelles dans
`app/Actions/`, testables isolément. Les contrôleurs restent minces.

```
app/Actions/
  Licensing/      ActivateLicense · RenewLicense · SuspendLicense
  Access/         GrantResidenceAccess · RevokeResidenceAccess
  Mandates/       OpenMandate · SuspendMandate · CloseMandate
  Exercises/      OpenExercise · StartExerciseClosing · CloseExercise
  Budgets/        SubmitBudget · ApproveBudget · RejectBudget
  FundCalls/      GenerateFundCall · IssueFundCall · CancelFundCall
  Payments/       RecordPayment · AllocatePayment · ReversePaymentAllocation
  Expenses/       RecordExpense · PayExpense · CancelExpense
  Treasury/       OpenTreasuryAccount · CloseTreasuryAccount
                  TransferBetweenAccounts · RecordOtherIncome
                  StartBankReconciliation · MatchEntryLine
                  BalanceReconciliation · CloseReconciliation
                  RebuildTreasuryAccountBalance
  Accounts/       RecordAdjustment · RebuildAccountBalances
  Accounting/     PostJournalEntry · ReverseJournalEntry
                  CloseAccountingPeriod · GenerateOpeningEntries
  Reports/        TrialBalance · GeneralLedger · IncomeStatement
                  BalanceSheet · TreasuryPosition
  Handovers/      PrepareHandover · ReviewHandover · FinalizeHandover
  Imports/        StartImport · MapImport · ValidateImport · CommitImport
```

**Règle absolue :** `PostJournalEntry` est le **seul** point d'écriture dans
`journal_entries` et `journal_entry_lines`. Toute autre Action lui passe une pièce
complète et il refuse toute pièce déséquilibrée. Un test d'architecture Pest interdit
l'accès en écriture à ces modèles depuis l'extérieur de `app/Actions/Accounting/`.

**Conséquence :** l'invariant `Σ debit = Σ credit` est vérifié en **un seul endroit du
code**. C'est le principal gain de la partie double par rapport à deux livres
indépendants — il n'existe plus d'invariant d'appariement à surveiller, l'équilibre
est structurel.

Chaque Action : transaction unique, validation des invariants avant commit,
journalisation dans `audit_logs`.

**Ce qui n'est pas une Action :** le CRUD simple (fiche résidence, coordonnées d'un
copropriétaire, libellé d'une ligne budgétaire) reste du contrôleur Inertia classique.
Pas de sur-ingénierie.

## 23. Stratégie de tests

Pest, tests de fonctionnalité majoritaires.

| Niveau | Objet |
|---|---|
| **Invariants** | Un test par invariant du §18, avec le cas limite qui le viole. |
| **Répartition** | Cas non divisibles (360 000 / 7 lots), déterminisme, somme exacte. |
| **Imputation** | FIFO, imputation partielle, multi-appels, réaffectation, annulation. |
| **Temporel** | Mandat interrompu, deux mandats sur la même année civile, mutation de lot en cours d'exercice. |
| **Trésorerie** | Solde après chaque type de mouvement, caisse négative refusée, virement équilibré, compte clôturé à solde nul. |
| **Appariement** | Un paiement produit exactement une écriture dans chaque livre ; échec de l'une → aucune persistée. |
| **Rapprochement** | Écart justifié, verrouillage des écritures pointées, non-chevauchement des périodes. |
| **Dettes fournisseurs** | Dépense `recorded` sans trésorerie, `paid` avec trésorerie, montant de la dette calculé. |
| **Immuabilité** | Toute tentative de modification d'un exercice clos échoue. |
| **Autorisation** | Un test par cellule non triviale de la matrice §20.2. |
| **Licence** | Transitions d'état, blocage des écritures en `read_only`. |
| **Architecture** | Aucune écriture ledger hors `app/Actions/Ledger/`. |
| **Passation** | Solde avant = solde après sur tous les comptes, catégories calculées exactes, pièces correctement sourcées. |
| **Import** | Reprise soldes seuls, sans historique détaillé. |

---

# Annexes

## 24. Décisions actées

| # | Décision | Motif |
|---|---|---|
| D1 | **Tenant = résidence.** Bayan est vendu à la copropriété ; le syndic est un utilisateur invité. | Permet l'historique continu qui survit au changement de syndic. |
| D2 | **Le mandat et l'accès sont deux tables distinctes.** Les permissions ne dérivent jamais du mandat. | Les deux se désynchronisent en permanence dans la réalité. |
| D3 | **La révocation d'accès est une décision de l'administrateur**, sur pièce justificative, avec export obligatoire pour le syndic sortant. | Traçabilité et protection en cas de litige. |
| D4 | **Licence expirée → grâce puis lecture seule**, jamais de blocage sec. | Éviter de couper une copropriété de sa comptabilité. |
| D5 | **Une seule clé de répartition : les tantièmes.** | Simplicité v1. Migration possible plus tard. |
| D6 | **Le tantième est figé dans la ligne d'appel de fonds.** | Un recalcul futur ne doit jamais modifier un appel passé. |
| D7 | **Pas de régularisation de fin d'exercice.** L'appel est la charge définitive, pas une provision. | Simplicité v1. Rend l'ajustement manuel critique. |
| D8 | **Un seul compte par lot**, même en indivision. Le paiement enregistre qui a payé. | La dette est indivisible face au syndicat ; modèle bien plus simple. |
| D9 | **Lot ↔ copropriétaires en N-N daté.** | Indivision, SCI, usufruit, mutations. |
| D10 | **La créance est calculée depuis le ledger, jamais stockée.** | Éviter la double source de vérité. |
| D11 | **Les soldes importés sont des écritures de ledger** avec `source_type = OpeningBalance`. | Même écran, même mécanique, transparence sur le détail disponible. |
| D12 | **Aucune suppression physique dans le livre financier.** Corrections par extourne. | Intégrité de l'historique et auditabilité. |
| D13 | **Un appel de fonds n'est jamais déplacé.** La dette est continuée, pas recréée. | Destruction de l'historique sinon. |
| D14 | **Import historique ≠ passation.** Deux mécanismes séparés. | Niveaux de confiance et de détail incompatibles. |
| D15 | **Le copropriétaire dispose d'un portail** limité à ses lots. | Décidé. |
| D16 | **Le président du conseil syndical a un rôle distinct** : lecture globale + validation. | Décidé. |
| D17 | **Écriture aux livres réservée aux Actions**, vérifiée par test d'architecture. | Garantit que tous les invariants passent par un point unique. |
| D18 | **La trésorerie est suivie dans Bayan dès la v1**, pas seulement transmise en passation. | Besoin métier central : sans elle, on sait ce qui est dû mais pas ce qui existe. |
| D19 | ~~Deux livres auxiliaires~~ → **révisée en D24.** | La réglementation impose des états financiers normalisés. |
| D20 | **Le débit augmente l'actif et les charges** ; le crédit augmente le passif et les produits. Un paiement crédite le lot et débite la banque. | Convention unique, évite les erreurs de signe. |
| D21 | **La dépense a deux moments : `recorded` puis `paid`.** | Rend les dettes fournisseurs calculables au lieu d'être déclarées. |
| D22 | **Le rapprochement bancaire est dans le périmètre v1.** | Opération quotidienne du syndic ; sans elle la trésorerie n'est pas fiable. |
| D23 | **Les comptes de trésorerie appartiennent à la résidence**, pas au syndic. Un changement de compte au changement de syndic est un virement tracé. | Cohérent avec D1 (tenant = résidence). |
| D24 | **Comptabilité en partie double avec plan comptable** (`journal_entries` / `journal_entry_lines`), remplaçant les deux livres de D19. | La réglementation marocaine impose au syndic la production d'états financiers normalisés. |
| D25 | **Le plan comptable est de la donnée configurable**, pas du code. Plan modèle fourni, adaptable par résidence. | Une évolution réglementaire ne doit jamais devenir une migration. Le texte exact peut être sourcé après le début du développement. |
| D26 | **Copropriétaires et fournisseurs en comptes collectifs + auxiliaires.** | Le plan reste court même avec 300 lots. |
| D27 | **Le résultat de l'exercice n'est pas ventilé sur les comptes des lots.** Il reste au report à nouveau. | Cohérence avec D7 (pas de régularisation). La partie double fournit le résultat sans imposer sa répartition. |
| D28 | **Le fonds travaux est une réserve au passif** (niveau 1) + affectation de trésorerie (niveau 2). Le compte bancaire dédié (niveau 3) est une règle activable. | Contrôle de couverture permanent sans imposer une contrainte bancaire non exigée. |
| D29 | **Le conseil syndical (président et membres) accède en lecture à la trésorerie et aux états financiers.** La validation reste au président seul. | Transparence vis-à-vis de l'organe de contrôle, sans diluer la responsabilité de validation. |
| D30 | **Le copropriétaire ne voit pas la trésorerie de la résidence.** | Décidé. |
| D31 | **Plan comptable spécifique copropriété uniquement.** Le PCG des entreprises marocaines est proscrit, y compris pour détailler les comptes. Structure verrouillée, extension limitée aux sous-comptes de même nature. | Exigence explicite du règlement. |
| D32 | **Les trois livres réglementaires** (journal, grand livre, livre d'inventaire) sont des obligations, pas des rapports. Journal et grand livre sont **deux lectures d'un stockage unique**. | Éviter la double source de vérité que le §9.1 interdit déjà pour les créances. |
| D33 | **Le livre d'inventaire est figé et archivé** à la clôture (`financial_statements`), jamais recalculé. | Il doit rester consultable à l'identique des années plus tard. Seule exception assumée au principe « tout est dérivé ». |
| D34 | **L'appel de fonds porte une `nature`** (courant, travaux, avance, emprunt) qui pilote les comptes `342x` / `71xx`. | Le règlement impose la ventilation des sommes exigibles par nature. |
| D35 | **Un lot n'a pas un solde mais un solde par nature**, plus le total. | Conséquence directe de D34 et de la structure `342x`. |
| D36 | **Le cycle de recouvrement est modélisé** : normale → douteuse (`3424`) → dépréciée (`691`/`394`) → irrécouvrable (`6514`) ou recouvrée, avec `7514` pour les rentrées sur créances soldées. | Les comptes existent dans le plan ; ne pas les exploiter reviendrait à ne pas tenir la comptabilité exigée. |
| D37 | **Le passage en perte est une décision de l'AG**, tracée avec sa pièce, jamais automatique. | Le déclassement affecte les droits du syndicat sur le copropriétaire. |
| D38 | **Toute écriture est rattachable à une pièce justificative**, par sa source ou par `attachments`. | Exigence explicite du règlement. |
| D39 | **Vocabulaire réglementaire dans l'application** : « état de situation financière » et « compte de gestion général », pas « bilan » ni « compte de résultat ». | Les intitulés du texte doivent apparaître tels quels. |

## 25. Hors périmètre v1

À ne pas construire maintenant, mais dont le modèle ne doit pas empêcher l'ajout :

- clés de répartition multiples (ascenseur, chauffage, cage d'escalier) ;
- régularisation et approbation des comptes en AG ;
- comptabilité analytique par bâtiment ou par cage ;
- consolidation multi-résidences pour un syndic ;
- gestion des locataires et charges récupérables ;
- paiement en ligne (le moyen `en_ligne` existe, l'intégration non) ;
- import automatique des relevés bancaires (CSV/MT940) — le rapprochement est manuel
  en v1 ;
- gestion des assemblées générales (convocations, votes, procurations) ;
- gestion des sinistres, contrats et prestataires ;
- échéanciers fournisseurs et relances automatiques — la dette fournisseur est
  calculée (D21), mais son pilotage ne l'est pas ;
- prévisionnel et plan de trésorerie.

## 26. Questions ouvertes

À trancher avant ou pendant l'implémentation, sans bloquer la tranche 1.

1. **Loi 18-00 :** sort juridique exact des arriérés lors d'une mutation de lot
   (opposition du syndic). Détermine si le solde suit automatiquement le lot ou peut
   rester à la charge du vendeur. → à vérifier avant de coder la règle de mutation.
2. **Format des tantièmes :** base 1000 ou 10 000 par défaut ? Configurable par
   résidence ?
3. **⚠️ Plan comptable de référence — bloquant avant le seed, pas avant le code.**
   Quel plan comptable et quels formats d'états s'imposent aux syndics au Maroc
   (CGNC / plan spécifique copropriété) ? À sourcer auprès du **texte officiel ou d'un
   expert-comptable**, jamais à deviner. Le schéma (D25) est indépendant de la
   réponse : le développement peut démarrer avec un plan provisoire.
4. **Approbation des comptes en AG :** si la réglementation impose des états
   normalisés, impose-t-elle aussi leur approbation en assemblée et la répartition du
   résultat ? Si oui, D7 et D27 doivent être revues. **Même source que la question 3.**
5. **Fonds travaux :** la loi impose-t-elle un **compte bancaire dédié** ? Si oui, le
   niveau 3 du §10.9 devient obligatoire au lieu d'optionnel.
6. **Rétention des données** après révocation d'un syndic : durée de conservation de
   l'export généré côté plateforme ?
7. **Notifications :** quels événements déclenchent un email au copropriétaire
   (nouvel appel, relance, reçu de paiement) ?
8. **Multi-résidence pour un copropriétaire :** confirmer le sélecteur de résidence
   sur un compte utilisateur unique.

## Annexe A — Plan comptable de référence

Plan comptable **spécifique aux copropriétés**, publié par le Ministère. Source du
seed `PlanComptableSeeder`.

> ⚠️ **Le plan comptable général des entreprises marocaines ne doit jamais être
> utilisé**, ni en remplacement, ni pour détailler ces comptes. Le règlement l'exclut
> explicitement (D31).
>
> Les comptes marqués **°** ont une numérotation à confirmer sur le document officiel
> avant le seed. Les libellés non marqués sont ceux du texte.

### Classes

| Classe | Comptes | Utilisation |
|---|---|---|
| **1** | 111, 119, 131, 151… | Fonds propres, réserves, résultat, provisions |
| **3** | 341, 342, 345, 348, 349, 394 | Créances |
| **4** | 441, 442, 443, 444, 445, 448, 449 | Dettes |
| **5** | 511, 512, 516, 554 | Trésorerie |
| **6** | 611, 612, 613/614, 616, 617, 651, 691 | Charges |
| **7** | 711, 712, 751, 791 | Produits |

### 342 — Collectivité des copropriétaires *(cœur du module recouvrement)*

| Compte | Libellé |
|---|---|
| `3421` | Copropriétaire individualisé |
| `3422` | Copropriétaire – budget prévisionnel |
| `3423` | Copropriétaire – travaux et opérations non courantes |
| `3424` | Copropriétaire – créances douteuses |

`394` — Dépréciation des créances.

### 711 — Appels de fonds

| Compte | Libellé |
|---|---|
| `7111` | Provisions sur opérations courantes |
| `7112` | Provisions sur travaux |
| `7113` | Avances |

### 712 — Autres produits

| Compte | Libellé |
|---|---|
| `7121` | Emprunts |
| `7122` | Subventions |
| `7123` | Indemnités d'assurance |
| `7124` | Produits divers |
| `7125` | Produits financiers |

### 751 — Produits sur opérations non courantes

| Compte | Libellé |
|---|---|
| `7511` | Autres produits décidés par l'AG |
| `7512` | Produits de cession reçus |
| `7513` | Dons reçus |
| `7514` | Rentrées sur créances soldées |
| `7515` | Autres produits non courants |

### 611 — Achats de matières et fournitures

| Compte | Libellé |
|---|---|
| `6111` | Eau |
| `6112` | Électricité |
| `6113` | Chauffage, énergie et combustibles |
| `6114` | Produits d'entretien et petits équipements |
| `6115` | Petit matériel |
| `6116` | Fournitures |

### 613 / 614 — Services extérieurs

| Compte | Libellé |
|---|---|
| `6131` | Nettoyage |
| `6132` | Locations immobilières |
| `6133` | Locations mobilières |
| `6134` | Contrats de maintenance |
| `6135` | Entretien et petites réparations |
| `6136` | Primes d'assurance |
| `6137` | **Rémunération du syndic** |
| `6138` | Autres rémunérations |
| `6140` | Frais postaux |
| `6141` | Frais bancaires |
| `6142` | Honoraires |
| `6143` | Autres charges |
| `6144` | Charges d'intérêts |

### 616 / 617 — Impôts et personnel

| Compte | Libellé |
|---|---|
| `6161` | Impôts et taxes |
| `6171` | Salaires |
| `6172` | Charges sociales |
| `6173` | Autres frais de personnel |
| `6174` | Assurance accident du travail |

### 651 / 691 — Opérations non courantes et dépréciations

| Compte | Libellé |
|---|---|
| `6511` | Travaux décidés par l'AG |
| `6512` | Travaux urgents |
| `6513` | Études techniques, diagnostic, consultation |
| `6514` | Pertes sur créances irrécouvrables |
| `6515` | Charges non courantes |
| `691` | Dotations aux dépréciations sur créances douteuses |

### À compléter avant le seed

- détail des comptes de **classe 1** (111, 119, 131, 151) — identifier le report à
  nouveau, les réserves et le compte de résultat ;
- détail des comptes de **classe 4** (441 à 449) — identifier le compte fournisseurs ;
- détail des comptes de **classe 5** (511, 512, 516, 554) — identifier banque, caisse
  et découvert ;
- comptes `341`, `345`, `348`, `349` de la classe 3 ;
- compte de créance rattaché aux **emprunts du syndicat** (contrepartie de `7121`) ;
- comptes `612` et `791` ;
- détail complet des comptes `611` et `711` d'après le document officiel du Ministère.

## 27. Checklist de validation

À cocher avant de générer les migrations.

**Fonctionnel**

- [ ] §3 Acteurs et rôles — les quatre rôles et leurs périmètres
- [ ] §4 Licence, états, période de grâce, procédure de révocation
- [ ] §5 Cycle mandat / exercice, y compris l'interruption en cours d'année
- [ ] §6 Tantièmes uniques, compte unique par lot, détention N-N datée
- [ ] §7 Appel définitif (pas de provision), règle « on ne déplace jamais un appel »
- [ ] §8 Imputation explicite, FIFO comme défaut modifiable
- [ ] §9 Créance calculée, traitement des soldes importés
- [ ] §10.1 Partie double, plan comptable configurable
- [ ] §10.1 ter Tableau des écritures types
- [ ] §10.2 Convention de sens débit / crédit
- [ ] §10.3 Comptes de trésorerie, affectation, caisse jamais négative
- [ ] §10.5 Dépense en deux temps, dette fournisseur calculée
- [ ] §10.6 Rapprochement bancaire dans le périmètre v1
- [ ] §10.7 Écran situation de trésorerie, distinction encaissé / attendu
- [ ] §10.8 Liste des états financiers produits
- [ ] §10.9 Fonds travaux : les trois niveaux de séparation
- [ ] §11 Clôture d'exercice et report
- [ ] §12 Passation : catégories et processus draft → reviewed → finalized
- [ ] §13 Import : assistant en 6 étapes, soldes seuls suffisants
- [ ] §14 Portail copropriétaire : périmètre exact
- [ ] §15 Validation conseil syndical : les 4 objets soumis

**Technique**

- [ ] §16 Les six principes directeurs
- [ ] §17 Schéma de données, table par table
- [ ] §18 Liste des invariants
- [ ] §19 Machines à états
- [ ] §20 Matrice d'autorisation
- [ ] §21 Centimes, plus fort reste, séquences sans trou
- [ ] §22 Arborescence des Actions et règle d'écriture ledger

**Annexes**

- [ ] §24 Les 30 décisions actées (D19 est révisée par D24)
- [ ] §25 Périmètre exclu de la v1
- [ ] §26 Questions ouvertes — **points 3 et 4 à sourcer auprès d'un expert-comptable**

---

## Plan de développement recommandé

Ne pas modéliser les 27 sections d'un coup. Ordre imposé par les dépendances de
risque : ce qui peut casser le modèle doit être construit et prouvé en premier.

```
Tranche 1  ── Fondations
   residences · licenses · syndic_companies · residence_accesses
   users · rôles · audit_logs
   → prouve le modèle d'accès avant tout le reste

Tranche 2  ── Structure
   lots · owners · lot_ownerships · lot_accounts · lot_mutations
   → invariants tantièmes et quotes-parts

Tranche 3  ── Contexte de gestion
   mandates · exercises · budgets · budget_lines
   → invariants temporels (chevauchement, interruption)

Tranche 3bis ── Moteur comptable  ⚠ NOUVEAU, prérequis de la tranche 4
   accounts (plan comptable) · journals
   journal_entries · journal_entry_lines · PostJournalEntry
   → l'invariant Σ débit = Σ crédit, prouvé et verrouillé,
     AVANT qu'une seule écriture métier ne soit produite
   → le plan modèle peut être provisoire (§26 q.3)

Tranche 4  ── Cœur financier
   fund_calls · fund_call_lines · payments · payment_allocations
   treasury_accounts · treasury_transfers · suppliers
   expenses (recorded / paid) · other_incomes · adjustments
   → répartition, imputation, écritures types du §10.1 ter
   ⚠ chaque Action produit UNE pièce équilibrée via PostJournalEntry

Tranche 5  ── Clôture, états et recouvrement
   rapprochement bancaire · situation de trésorerie · dettes fournisseurs
   grand livre · balance · bilan · compte de résultat
   clôture d'exercice · à-nouveaux · écrans créances · relevés · balance âgée

Tranche 6  ── Portail copropriétaire

Tranche 7  ── Passation
   (devient simple si le ledger de la tranche 4 est bon)

Tranche 8  ── Import historique
```

Le prérequis technique avant la tranche 1 : le dépôt n'est pas encore sous
contrôle de version (`git init`).
