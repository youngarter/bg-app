# Bayan — V1 · Périmètre fonctionnel complet

**Version 1.1 · 04/09/2026 · statut : à valider**

Ce dossier définit le **périmètre fonctionnel complet** de Bayan.
Il dérive de [`../README.md`](../README.md) (architecture cible), corrigé par la revue
d'architecture du 03/09/2026, puis élargi le 04/09/2026 aux 15 modules du prototype
`doc/fondation/sample/syndic`.

> **Règle de lecture :** ce qui n'est pas dans ce dossier n'est pas dans la V1.
> Voir [`14-hors-perimetre.md`](14-hors-perimetre.md) pour ce qui est volontairement
> exclu et pour les questions ouvertes.

---

## Le noyau en une phrase

> Bayan permet à un syndic accrédité de tenir la comptabilité complète d'une
> copropriété — structure, budget, appels, encaissements, dépenses, trésorerie,
> recouvrement, clôture, passation — et de répondre à tout moment, de façon
> traçable, à la question : **« pourquoi ce lot doit-il cette somme ? »**

## Les 4 verrous fondateurs

Verrouillés avant tout développement. Toute décision ultérieure s'y soumet.

| # | Verrou | Fichier |
|---|---|---|
| 1 | **Source de vérité financière unique** — un seul ledger en partie double, tous les soldes en sont des projections | [08](08-ledger-comptable.md) |
| 2 | **Pas de sous-ledger séparé** — le compte de lot est la lecture `(compte 342x, auxiliaire = lot)` du ledger | [09](09-compte-de-lot.md) |
| 3 | **Débiteur gelé** — l'obligation porte son débiteur au moment de son émission, jamais recalculé | [03](03-structure-copropriete.md) · [06](06-appels-de-fonds.md) |
| 4 | **Irréversibilité et extourne** — aucune modification ni suppression d'un événement posté ; correction = extourne + nouvel événement | [00](00-principes.md) · [11](11-machines-etats.md) |

---

## Plan du dossier

### Noyau transactionnel

| Fichier | Contenu |
|---|---|
| [00-principes.md](00-principes.md) | Principes directeurs, conventions de dates et de montants, cycle de posting |
| [01-plateforme-licence.md](01-plateforme-licence.md) | Résidence, licence, accréditation et révocation d'un syndic, audit |
| [02-identite-roles.md](02-identite-roles.md) | Utilisateurs, sociétés de syndic, rôles datés, autorisation à trois verrous |
| [03-structure-copropriete.md](03-structure-copropriete.md) | Lots, tantièmes, copropriétaires, détentions datées, mutations |
| [04-mandats-exercices.md](04-mandats-exercices.md) | Mandats, exercices autonomes, non-chevauchement |
| [05-budget.md](05-budget.md) | Budget de fonctionnement, lignes budgétaires |
| [06-appels-de-fonds.md](06-appels-de-fonds.md) | Émission, répartition, gel des données, échéances |
| [07-paiements-imputation.md](07-paiements-imputation.md) | Encaissement, imputation FIFO, réaffectation, annulation |
| [08-ledger-comptable.md](08-ledger-comptable.md) | Moteur partie double, plan comptable restreint V1, écritures types |
| [09-compte-de-lot.md](09-compte-de-lot.md) | Soldes, états de créance, relevé, balance âgée |
| [10-validation-conseil.md](10-validation-conseil.md) | `ApprovalRequest` / `ApprovalDecision` polymorphes |
| [11-machines-etats.md](11-machines-etats.md) | Toutes les machines à états, en un point unique |
| [12-schema-donnees.md](12-schema-donnees.md) | Tables de la V1, types, index, contraintes |
| [13-invariants-tests.md](13-invariants-tests.md) | Invariants testés, tests d'architecture, stratégie |
| [14-hors-perimetre.md](14-hors-perimetre.md) | Exclusions assumées, découplages, questions ouvertes |

### Gestion financière étendue

| Fichier | Contenu |
|---|---|
| [15-tresorerie-depenses.md](15-tresorerie-depenses.md) | Comptes de trésorerie, virements, dépenses, fournisseurs, rapprochement bancaire, fonds travaux |
| [17-recouvrement-relances.md](17-recouvrement-relances.md) | Échelle de relance, cycle de vie de la créance, dépréciation, passage en perte |
| [18-etats-financiers.md](18-etats-financiers.md) | Les trois livres réglementaires, annexes légales 1 à 5, états figés à la clôture |

### Vie de la copropriété

| Fichier | Contenu |
|---|---|
| [16-assemblees-generales.md](16-assemblees-generales.md) | AG comme **registre documentaire** — quorum, résolutions, PV |
| [19-exploitation-technique.md](19-exploitation-technique.md) | Réclamations, projets et chantiers, carnet d'entretien |
| [20-portail-coproprietaire.md](20-portail-coproprietaire.md) | Espace copropriétaire, double navigation des délégués |

### Cycle de vie et présentation

| Fichier | Contenu |
|---|---|
| [21-passation.md](21-passation.md) | Snapshot rapproché, écarts justifiés, à-nouveaux **séparés** |
| [22-import-historique.md](22-import-historique.md) | Assistant, staging générique, import « soldes seuls » |
| [23-branding-documents.md](23-branding-documents.md) | Charte, logos, quittances, PV, courriers, impression A4 |

---

## Glossaire

| Terme | Définition |
|---|---|
| **Résidence** | Entité permanente, **tenant** du SaaS. Survit aux changements de syndic. |
| **Société de syndic** | Personne morale qui exerce la gestion. Invitée sur une résidence, jamais propriétaire de la donnée. |
| **Accréditation** | Autorisation d'accès d'une société de syndic à une résidence, accordée et révoquée par l'administrateur plateforme sur pièce. Distincte du mandat. |
| **Licence** | Droit d'usage de la plateforme par la résidence. États : `active`, `grace`, `read_only`, `suspended`. |
| **Mandat** | Fait juridique : période pendant laquelle une société exerce la gestion. Ne confère aucun droit d'accès par lui-même. |
| **Exercice** | Période comptable. Entité **autonome**, jamais dérivée du mandat. |
| **Lot** | Unité de propriété (appartement, magasin, parking, cave). Porte un tantième. |
| **Tantième** | Quote-part du lot dans les charges communes. Unique clé de répartition en V1. |
| **Copropriétaire (`owner`)** | Personne physique ou morale, **par résidence**. Distinct du `user` (identité de connexion). |
| **Détention (`ownership`)** | Lien daté lot ↔ copropriétaire, avec quote-part et nature juridique. |
| **Compte de lot** | Identité auxiliaire permanente attachée au **lot**, pas au propriétaire. Son solde est une projection du ledger. |
| **Appel de fonds** | Événement qui crée l'obligation de payer. Réparti par tantièmes. Porte une **nature**. |
| **Ligne d'appel (`fund_call_line`)** | L'obligation d'un lot précis : montant, échéance, tantième gelé, débiteur gelé. C'est **la** créance. |
| **Paiement** | Argent réellement reçu. Enregistre qui a payé. |
| **Imputation (`allocation`)** | Affectation explicite d'un paiement à une ou plusieurs lignes d'appel. Matérialisée, jamais implicite. |
| **Créance** | Solde restant dû sur une obligation. **Grandeur calculée**, jamais stockée comme vérité. |
| **Ledger** | `journal_entries` + `journal_entry_lines`. Source de vérité financière unique et immuable. |
| **Pièce comptable (`journal_entry`)** | Événement financier équilibré : `Σ débits = Σ crédits`. |
| **Auxiliaire** | Sous-identité d'un compte collectif : un lot pour `342x`, un fournisseur pour `441x`. |
| **Posting** | Acte de traduire un événement métier validé en écritures immuables. Réservé aux Actions. |
| **Extourne** | Pièce inverse exacte d'une pièce annulée. Seul mécanisme d'annulation. |
| **Ajustement** | Écriture motivée corrigeant un compte, avec auteur et pièce. Entité de première classe. |
| **Solde d'ouverture** | Écriture initiale d'un compte, issue d'un import ou d'une passation. |
| **Approbation** | Décision datée et immuable du président du conseil syndical sur un objet soumis. |
| **Délégation** | Habilitation datée d'un copropriétaire sur une liste blanche de modules de gestion. Ne confère jamais le droit d'approuver. |
| **Dépense** | Facture fournisseur. Deux moments : `recorded` (la dette existe) puis `paid` (l'argent sort). |
| **Dette fournisseur** | Σ des dépenses `recorded` non `paid`. Grandeur **calculée**. |
| **Virement** | Mouvement entre deux comptes de trésorerie. Ne touche jamais un compte de lot. |
| **Rapprochement bancaire** | Confrontation des mouvements Bayan au relevé de la banque. Verrouille les écritures pointées. |
| **Fonds travaux** | Réserve au passif pour gros travaux, isolée du fonctionnement courant. |
| **Relance** | Courrier de recouvrement à montant **gelé**. Ne produit aucune écriture. |
| **Créance douteuse** | Créance reclassée en `3424`. Le montant dû ne change pas, seul le regard comptable change. |
| **Assemblée générale** | **Registre documentaire** d'une réunion. Ne pilote ni le budget, ni le mandat, ni aucune écriture. |
| **Résolution** | Point voté en AG, avec majorité requise et tantièmes exprimés. Peut *référencer* un objet, jamais le commander. |
| **Passation** | Snapshot rapproché de la situation entre deux mandats. Distincte des écritures d'ouverture qu'elle autorise. |
| **Import historique** | Reprise de données d'un système externe. Confiance faible, à valider — à ne pas confondre avec la passation. |
| **État figé** | Instantané horodaté d'un état financier, archivé à la clôture. Autorité **documentaire**, le ledger restant l'autorité comptable. |

---

## Ce qui a changé par rapport au README cible

| Changement | Raison |
|---|---|
| Périmètre réduit au noyau P0 | Livrer une résidence réelle en production avant d'élargir |
| « Pas de régularisation » → « appels immuables, correction par nouvel événement » | Même intention, formulation qui n'interdit pas l'appel complémentaire légitime |
| État de paiement **séparé** de l'état de recouvrement | Deux dimensions distinctes qui étaient mélangées |
| `debtor_owner_id` gelé sur la ligne d'appel | Ne jamais recalculer le débiteur depuis les détentions actuelles |
| Dates normalisées en 5 rôles explicites | Coût nul maintenant, irrattrapable plus tard |
| `ApprovalRequest` polymorphe | Évite quatre tables d'approbation quasi identiques |
| Passation : snapshot **séparé** de la génération des à-nouveaux | Le document de passation n'est pas le mécanisme comptable (V2) |
| **Rejeté** : sous-ledger copropriétaire distinct du grand livre | Deux moteurs = deux vérités qui divergent |
| **Rejeté** : entité `LotReceivable` parallèle | Elle existe déjà, c'est `fund_call_lines` |

## Élargissement du 04/09/2026

| Changement | Raison |
|---|---|
| Périmètre porté aux 15 modules du prototype | décision de reprendre l'intégralité du produit existant |
| **L'AG ne pilote ni le budget ni la passation** | coupler AG et budget rendrait impossibles trois cas courants — voir [16](16-assemblees-generales.md) |
| Trésorerie, dépenses et fournisseurs intégrés | rendent **trois catégories de passation sur six** calculables au lieu de déclaratives |
| Cycle complet de la créance (dépréciation, perte) | plus de renvoi en V2 : `impaired` et `written_off` sont implémentés |
| Portail copropriétaire et délégations intégrés | l'entité `owner` existait déjà, le portail la sert |
| Passation et import historique intégrés | complètent le cycle de vie d'une résidence reprise |
