# 13 — Invariants et stratégie de tests

← [Sommaire](README.md)

> **Un invariant qui n'est pas testé n'est pas un invariant, c'est un commentaire.**

---

## 1. Les invariants, un par test nommé

### 1.1 Structure

| # | Invariant | Portée |
|---|---|---|
| S1 | `Σ lots.tantiemes = residences.total_tantiemes` | résidence |
| S2 | `Σ quote_part` des détentions actives d'un lot à une date = 10 000, par axe | lot, à toute date |
| S3 | Deux détentions du même owner sur le même lot ne se chevauchent jamais | lot |
| S4 | Tout lot possède exactement un `lot_account` | lot |

### 1.2 Temps

| # | Invariant | Portée |
|---|---|---|
| T1 | Les exercices d'une résidence ne se chevauchent jamais | résidence |
| T2 | `[exercise.started_on, ended_on]` ⊆ `[mandate.started_on, ended_on]` | exercice |
| T3 | Un seul exercice `open` par résidence à la fois | résidence |
| T4 | Chaque exercice appartient à exactement un mandat | exercice |

### 1.3 Comptabilité

| # | Invariant | Portée |
|---|---|---|
| C1 | `Σ debit = Σ credit` sur **chaque** pièce | pièce |
| C2 | Une ligne porte un débit **ou** un crédit, jamais les deux | ligne |
| C3 | `source_type` + `source_id` toujours renseignés et **résolvables** | pièce |
| C4 | Une pièce mouvementant un compte `requires_auxiliary` porte un auxiliaire | ligne |
| C5 | Une pièce ne peut être extournée qu'une seule fois | pièce |
| C6 | Toute pièce résout au moins une pièce justificative (sauf clôture système) | pièce |
| C7 | Aucun compte hors plan de référence n'existe en base | résidence |
| C8 | Aucune pièce dont `effective_date` sort de son exercice | pièce |

### 1.4 Appels et imputations

| # | Invariant | Portée |
|---|---|---|
| A1 | `Σ fund_call_lines.montant = fund_calls.montant_total`, au centime | appel |
| A2 | Une ligne `issued` porte `tantiemes_used`, `total_tantiemes_used`, `debtor_owner_id` non nuls | ligne |
| A3 | `Σ allocations actives d'un paiement ≤ paiement.montant` | paiement |
| A4 | `Σ allocations actives d'une ligne ≤ ligne.montant` | ligne |
| A5 | Paiement et ligne d'une allocation partagent le **même** `lot_account` | allocation |
| A6 | Aucune allocation active vers une ligne d'un appel `reversed` | allocation |

### 1.5 Trésorerie, dépenses, recouvrement

| # | Invariant | Portée |
|---|---|---|
| D1 | Une pièce `source_type = Transfer` ne mouvemente que des comptes `512x` / `516x` | pièce |
| D2 | Une dépense `recorded` ou `paid` porte une facture (`facture_document_path`) | dépense |
| D3 | Dettes fournisseurs = Σ dépenses `recorded` non `paid`, par auxiliaire `441x` | résidence |
| D4 | Aucune dépense ne mouvemente un compte `342x` | pièce |
| D5 | Une dépense de fonctionnement ne peut être payée depuis un compte `dedie_fonds_travaux` | dépense |
| D6 | Un rapprochement `closed` interdit l'extourne des écritures qu'il a pointées | écriture |
| D7 | Une relance ne produit aucune écriture comptable | relance |
| D8 | `dunning_notices.montant_reclame` est immuable après `sent` | relance |
| D9 | Réserve fonds travaux au passif ≤ trésorerie affectée investissement (alerte) | résidence |

### 1.6 Assemblées générales — le verrou du découplage

| # | Invariant | Portée |
|---|---|---|
| **AG1** | **Aucune Action de `budgets` ou de `mandates` ne lit la table `assemblies`** | architecture |
| AG2 | Une AG ne produit jamais de pièce comptable (`source_type = Assembly` inexistant) | ledger |
| AG3 | `assembly_attendances.tantiemes` est immuable après `tenue` | AG |
| AG4 | Une AG `archivee` est immuable | AG |
| AG5 | Une résidence peut être gérée sans aucune AG saisie | scénario |

AG1 et AG5 sont les tests qui **prouvent** le découplage acté en
[16](16-assemblees-generales.md). Sans eux, le couplage reviendra par accident.

### 1.7 Passation et import

| # | Invariant | Portée |
|---|---|---|
| P1 | Une passation `finalized` a tous ses écarts justifiés | passation |
| P2 | Les à-nouveaux d'une passation ne sont générés qu'une fois | passation |
| P3 | Une passation peut rester `finalized` sans à-nouveaux — état valide | passation |
| P4 | Un `import_batch` avec une ligne en `error` ne peut être committé | import |
| P5 | Toute écriture `OpeningBalance` porte une pièce justificative | pièce |
| P6 | L'import ne crée aucune table miroir : les entités réelles sont les seules lues | architecture |

### 1.8 Cohérence globale — le test qui protège tout

| # | Invariant | Portée |
|---|---|---|
| **G1** | Pour tout lot : `solde projeté du ledger` = `Σ appelé − Σ imputé + Σ non imputé` | **résidence** |
| G2 | Une caisse n'est jamais négative, à aucune date | compte de trésorerie |
| G3 | `Σ soldes 342x` = `Σ créances lues sur l'écran de recouvrement` | résidence |

**G1 est le test le plus important du projet.** Il vérifie qu'il n'existe pas deux
vérités. Il tourne sur une résidence de fixture réaliste (100 lots, 3 exercices,
mutations, extournes, imputations partielles) à chaque exécution de la suite.

---

## 2. Tests d'architecture

Exprimés avec `arch()`. Ils portent les principes de [00](00-principes.md).

```
1. Seules les classes de App\Actions écrivent dans JournalEntry / JournalEntryLine
2. Aucun contrôleur, job, observer, commande ou seeder n'appelle ->create()
   sur ces modèles
3. Aucune assignation directe d'un champ d'état hors des Actions
   (grep : ->status = , ->state = )
4. JournalEntry, JournalEntryLine, ApprovalDecision, AuditLog, LicenseEvent
   n'exposent ni update() ni delete()
5. Aucun modèle métier n'est requêté sans scope résidence
6. Aucun montant n'est typé float ou decimal
7. Toute Action expose exactement une méthode publique handle()
8. Aucun appel à now() dans les Actions — l'horloge est injectée
9. Aucune classe des namespaces Budget\ ou Mandate\ ne référence Assembly
10. Aucune Action ne lit une table de staging d'import (import_rows, import_batches)
11. delegations.modules est validé contre une liste blanche, jamais interprété
    comme « tout autoriser » quand il est vide ou inconnu
```

---

## 3. Le contrat des écritures types

Chaque ligne du tableau de [08](08-ledger-comptable.md) §4 est **un test**.

Forme canonique :

```
GIVEN  un exercice ouvert, un budget approuvé, un lot à 250/1000 tantièmes
WHEN   on émet un appel courant de 100 000,00
THEN   il existe exactement UNE pièce
       ET elle porte source_type = FundCall
       ET elle contient une ligne DÉBIT 3422 auxiliaire = lot_account, 25 000,00
       ET elle contient une ligne CRÉDIT 7111 de 100 000,00
       ET Σ debit = Σ credit
```

**C'est la traduction la plus directe du règlement en code.**
Une écriture type sans test correspondant ne doit pas être implémentée.

---

## 4. Pyramide de tests

| Niveau | Objet | Volume attendu |
|---|---|---|
| **Architecture** | les 8 règles ci-dessus | 8 tests |
| **Unitaire** | répartition par tantièmes, arrondis, FIFO, calcul de solde | ~40 |
| **Feature — Actions** | une par Action, cas nominal + chaque contrôle bloquant | ~120 |
| **Feature — invariants** | les invariants du §1 | ~48 |
| **Feature — autorisation** | la matrice de [02](02-identite-roles.md) §4, ligne par ligne | ~60 |
| **Feature — machines à états** | chaque transition **et chaque transition interdite** | ~70 |
| **Scénario** | parcours complets de bout en bout | ~16 |

### Les scénarios de bout en bout à écrire en premier

```
1. Résidence neuve → mandat → exercice → budget approuvé → appel émis
   → paiement → relevé juste
2. Paiement partiel → FIFO sur deux appels → reliquat → appel suivant
3. Chèque impayé : extourne de paiement, allocations annulées, solde restauré
4. Erreur d'appel : extourne intégrale, relevé barré, solde restauré
5. Mutation de lot en cours d'exercice : le débiteur d'origine reste affiché
6. Clôture d'exercice : contrôles, écritures, à-nouveaux, solde reporté
7. Licence en grace puis read_only : écriture bloquée, lecture et export maintenus
8. Révocation d'accès : export bloquant, A ne voit plus rien, B voit tout
9. Indivision : un paiement de Fatima solde la dette du lot d'Ahmed et Fatima
10. Rejet du conseil : commentaire obligatoire, retour en draft, resoumission
11. Dépense : recorded → dette fournisseur calculée → paid → trésorerie diminuée
12. Rapprochement bancaire : écart justifié, balanced, closed, écritures verrouillées
13. Recouvrement : relance N1 → N2 → mise en demeure → déclassement douteux → perte
14. AG : convocation, quorum, résolutions, PV archivé — SANS toucher budget ni mandat
15. Passation : snapshot rapproché, écarts justifiés, finalized, puis à-nouveaux séparés
16. Import soldes seuls : lots, propriétaires, soldes, trésorerie → relevé juste
```

---

## 5. Fixtures

Une factory par modèle, avec des **états nommés** plutôt que des configurations manuelles :

```
Residence::factory()->withLots(100)->withTantiemes(10_000)
Exercise::factory()->open()  ->closing()  ->closed()
FundCall::factory()->issued()->partiallyPaid()->fullyPaid()->reversed()
Lot::factory()->indivision(2)  ->usufruit()
Payment::factory()->confirmed()->unallocated()
```

Une **fixture de référence** `ResidenceRealiste` : 100 lots, 3 exercices dont 2 clos,
mutations, extournes, imputations partielles, une indivision, un lot en créance
douteuse, des dépenses payées et non payées, un rapprochement clos. C'est sur elle que
tourne G1.

Les quatre résidences de démonstration du prototype (Jardins de l'Atlas, Majorelle,
Marina Bay, Greenwood vierge) fournissent les **scénarios métier** à reproduire :
lot à jour, paiement partiel, retard simple, mise en demeure, bailleur non occupant,
copropriétaire délégué, et résidence 100 % vierge pour l'amorçage.

---

## 6. Règles d'exécution

- `php artisan test --compact`, filtré au strict nécessaire pendant le développement.
- **Aucune fonctionnalité n'est considérée terminée sans test.** Sans exception.
- Les tests d'invariants et d'architecture tournent en CI sur **chaque** commit.
- Aucun test ne s'appuie sur `now()` réel : l'horloge est gelée (`travelTo`).
