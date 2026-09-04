# 14 — Hors périmètre et questions ouvertes

← [Sommaire](README.md)

> **Décision du 04/09/2026 :** la V1 couvre désormais **l'intégralité du périmètre
> fonctionnel cible**, soit les 15 modules du prototype `sample/syndic`. Ce fichier ne
> liste plus des reports, mais ce qui reste **volontairement exclu** et ce qui reste
> **à trancher**.

---

## 1. Volontairement exclu

Rien de cette liste ne doit être construit. Le modèle ne doit cependant **empêcher**
aucun de ces ajouts — chaque entrée précise le point d'accroche déjà présent.

| Exclu | Point d'accroche |
|---|---|
| Clés de répartition multiples (ascenseur, chauffage, cage) | ajouter `repartition_keys`, basculer l'existant sur une clé « générale » ([03](03-structure-copropriete.md) §1.1) |
| Régularisation de fin d'exercice | l'appel est définitif ([06](06-appels-de-fonds.md) §2) — décision structurante, pas un manque |
| Répartition du résultat sur les lots | conséquence de D7 ([18](18-etats-financiers.md) §5) |
| Comptabilité analytique par bâtiment ou par cage | `lots.batiment` existe en texte libre |
| Consolidation multi-résidences pour un syndic | scope `residence_id` déjà systématique |
| Gestion des locataires et charges récupérables | `lot_ownerships.nature` extensible |
| **Paiement en ligne** | le moyen `en_ligne` existe, l'intégration non |
| Import automatique de relevés bancaires (CSV / MT940) | `reconciliations` existe, le pointage est manuel ([15](15-tresorerie-depenses.md) §6) |
| Vote électronique et procurations dématérialisées en AG | `assembly_attendances.mandataire_owner_id` existe |
| Échéanciers fournisseurs et relances automatiques | la dette fournisseur est calculée, son pilotage non |
| Prévisionnel et plan de trésorerie | projection du ledger, à ajouter sans schéma |
| Notifications email et push | à définir — question 7 |

### Deux exclusions qui ne sont pas des manques

**L'appel de fonds définitif** et **le résultat non ventilé** sont des décisions
d'architecture assumées, pas des fonctionnalités reportées. Les revisiter suppose de
rouvrir [06](06-appels-de-fonds.md) §2 et [18](18-etats-financiers.md) §5.

---

## 2. Les découplages à ne jamais casser

Trois séparations structurent la V1. Chacune est protégée par un test nommé.

| Découplage | Fichier | Test |
|---|---|---|
| **Mandat ≠ accès** — les permissions ne dérivent jamais du mandat | [01](01-plateforme-licence.md) §4 | verrou 2 de [02](02-identite-roles.md) §3 |
| **AG ≠ budget, AG ≠ mandat** — l'AG est un registre documentaire | [16](16-assemblees-generales.md) | **AG1**, **AG5** de [13](13-invariants-tests.md) |
| **Snapshot de passation ≠ écritures d'ouverture** | [21](21-passation.md) | **P2**, **P3** |

> Ces trois découplages ont le même motif : un **fait constaté** et son **effet
> applicatif** sont deux choses distinctes qui se désynchronisent en permanence dans la
> vie réelle. Les coupler paraît simplificateur et rend des cas courants impossibles.

---

## 3. Questions ouvertes

À trancher avant ou pendant l'implémentation. **Aucune ne bloque le démarrage du code.**

| # | Question | Bloque | Impact si mal tranché |
|---|---|---|---|
| 1 | **Loi 18-00** — sort juridique des arriérés à la mutation d'un lot (opposition du syndic) : le solde suit-il le lot, ou reste-t-il à la charge du vendeur ? | la règle de mutation automatique | **Aucun sur le schéma** : le gel du débiteur (verrou n°3) permet d'implémenter l'une ou l'autre règle après coup |
| 2 | Format des tantièmes : base 1 000 ou 10 000 par défaut ? Configurable par résidence ? | — | nul, `total_tantiemes` est déjà une colonne |
| 3 | ⚠️ **Plan comptable de référence** — numérotation exacte de `3421`, `512x`, `516x`, `119x`, `13x`. À sourcer auprès du **texte officiel ou d'un expert-comptable, jamais à deviner** | **le seed, pas le code** | le schéma est indépendant ; le développement démarre avec un plan provisoire |
| 4 | **Approbation des comptes en AG** : la réglementation impose-t-elle l'approbation en assemblée et la répartition du résultat ? *(même source que q.3)* | [18](18-etats-financiers.md) §5 | **si oui, l'AG cesse d'être un simple registre** — c'est la seule question qui pourrait rouvrir le découplage n°2 |
| 5 | **Fonds travaux** : la loi impose-t-elle un compte bancaire dédié ? | [15](15-tresorerie-depenses.md) §8 | si oui, le niveau 3 devient obligatoire au lieu d'optionnel |
| 6 | Seuils légaux de quorum et de majorité par type de résolution | [16](16-assemblees-generales.md) §2 | nul : déjà configurables par résidence |
| 7 | Notifications : quels événements déclenchent un email au copropriétaire (nouvel appel, relance, reçu) ? | — | opérationnel |
| 8 | Rétention des données après révocation d'un syndic : durée de conservation de l'export ? | — | opérationnel |
| 9 | Valeur probante des documents PDF générés : signature électronique requise ? | [23](23-branding-documents.md) §5 | si oui, ajouter un horodatage qualifié |

---

## 4. Checklist de validation

À cocher avant la première migration.

### Verrous et découplages
- [ ] Les 4 verrous fondateurs sont compris et acceptés par toute l'équipe
- [ ] Le rejet du double-ledger est acté et documenté
- [ ] Le gel du débiteur sur `fund_call_lines` est acté
- [ ] La séparation `payment_status` / `recovery_status` est actée
- [ ] Les 3 découplages du §2 sont compris, avec leurs tests nommés

### Conventions
- [ ] La convention des 5 dates est adoptée
- [ ] Montants en centimes, quotes-parts en points de base
- [ ] La règle d'arrondi de répartition est implémentée et testée isolément

### Périmètre
- [ ] Le périmètre est figé — plus aucun ajout avant la mise en production
- [ ] La liste des exclusions du §1 est acceptée

### Préalables externes
- [ ] Question 3 (plan comptable) : source identifiée, plan provisoire arrêté
- [ ] Question 4 (approbation en AG) : lecture engagée — **seule question pouvant
      rouvrir le découplage AG**
- [ ] Question 1 (loi 18-00) : lecture engagée, sans bloquer le code

### Tests
- [ ] La fixture `ResidenceRealiste` est spécifiée
- [ ] Le test **G1** est écrit **avant** la première Action financière
- [ ] Les tests **AG1** et **AG5** sont écrits **avant** le module Assemblées
