# 22 — Import historique

← [Sommaire](README.md)

> **L'import historique et la passation sont deux mécanismes distincts qu'il ne faut
> jamais mélanger.**

| | Import historique | Passation |
|---|---|---|
| Origine | système externe (Excel, PDF, ancien logiciel) | interne à Bayan |
| Confiance | **faible**, données à valider | élevée, données calculées |
| Détail | souvent absent | complet et traçable |
| Déclencheur | reprise d'une résidence existante | fin de mandat |

---

## 1. Deux scénarios à la création d'une résidence

```
Nouvelle résidence          Résidence existante
       ↓                            ↓
Mandat initial              Import historique
       ↓                            ↓
   Exercice                 Soldes d'ouverture
       ↓                            ↓
    Budget                  Mandat courant
```

---

## 2. Assistant en étapes

Pas de « CSV magique ». Un assistant, étape par étape, chacune validable seule.

```
1. Lots                    A101, A102, A103…  + tantièmes        ← OBLIGATOIRE
2. Copropriétaires         lot → détenteurs, quotes-parts        ← OBLIGATOIRE
3. Soldes copropriétaires  A101 : 0 · A102 : 4 500 · A103 : 1 200 ← OBLIGATOIRE
4. Comptes de trésorerie   banque, caisse, fonds travaux + soldes ← OBLIGATOIRE
5. Anciens mandats         périodes + sociétés                    ← optionnel
6. Dettes fournisseurs     factures reçues non réglées            ← optionnel
7. Historique détaillé     appels, paiements, charges             ← optionnel
8. Soldes d'ouverture      génération des pièces comptables       ← automatique
```

### Les quatre premières étapes sont le socle

> Le nouveau syndic doit pouvoir dire « je reprends uniquement les soldes actuels, je ne
> reconstruis pas 15 ans d'historique ».

**L'import de niveau 3 + 4 (soldes seuls) doit être pleinement fonctionnel sans les
niveaux 5 à 7.** C'est le cas d'usage majoritaire, pas un mode dégradé.

### La reprise de trésorerie est obligatoire

Sans elle, la situation de trésorerie ([15](15-tresorerie-depenses.md) §7) est fausse
dès le premier jour. Chaque solde repris porte sa pièce justificative : dernier relevé
bancaire, PV de comptage de caisse.

---

## 3. Modélisation — staging générique

> **Ne pas créer de tables miroir** (`ImportedMandate`, `ImportedExercise`,
> `ImportedOwner`…). Cela doublerait le schéma **pour toujours**.

```
import_batches
  id, residence_id
  type : lots | owners | balances | treasury | mandates | suppliers | history
  filename, file_path
  state : uploaded | mapped | validated | committed | failed
  stats : json        ← lignes totales, valides, en erreur
  created_by_user_id, committed_at

import_rows
  id, import_batch_id
  line_number
  payload : json                  ← la ligne brute
  state : pending | valid | error | committed | skipped
  errors : json
  entity_type, entity_id (nullable)   ← l'entité créée au commit

import_mappings
  id, import_batch_id
  source_column → target_field
  transformation (nullable)       ← trim, upper, date_format, montant_centimes…
```

Le `commit` **matérialise** dans les entités réelles. Après commit, les tables de
staging deviennent une **trace d'audit**, jamais une source de lecture.

---

## 4. Les soldes d'ouverture

Étape 8, automatique, produit les seules écritures de l'import.

```
Posting :
  DÉBIT   342x  (auxiliaire = lot)     solde repris
  CRÉDIT  119x  Report à nouveau

  DÉBIT   512x / 516x                  solde de trésorerie repris
  CRÉDIT  119x  Report à nouveau

  source_type = OpeningBalance
  attachment  = OBLIGATOIRE (relevé, PV de caisse, état du syndic sortant)
```

### Le drill-down honnête

Pour une résidence importée, **le détail n'existe pas**. Ce n'est pas un cas
particulier : c'est le même ledger avec un `source_type` différent
([09](09-compte-de-lot.md) §4).

```
1 500,00  Solde d'ouverture · import du 12/01/2026
             └── « Solde repris au 01/01/2026, relevé du syndic ABC »
             └── PJ : releve-syndic-abc.pdf
```

Les soldes importés en bloc sont marqués **« antérieur à la reprise »** dans la balance
âgée, plutôt que rattachés artificiellement à une année qu'on ne connaît pas.

---

## 5. Règles de validation

| Étape | Contrôle bloquant |
|---|---|
| Lots | `Σ tantiemes = residences.total_tantiemes` |
| Copropriétaires | `Σ quote_part` par lot = 10 000 sur chaque axe |
| Soldes | montant en centimes, entier, pièce justificative présente |
| Trésorerie | date de solde initial cohérente avec la date de reprise |
| Tous | aucune référence de lot en doublon |

Un batch avec au moins une ligne en `error` **ne peut pas être committé**. On corrige la
ligne dans l'assistant et on revalide — le fichier source n'est jamais réimporté en
entier.

---

## 6. Réversibilité

Un `import_batch` committé peut être **annulé** tant qu'aucune opération n'a été
enregistrée par-dessus.

```
Contrôles :
  1. aucun appel de fonds émis depuis le commit
  2. aucun paiement enregistré depuis le commit
  3. l'exercice est toujours open

Effet :
  extourne des pièces OpeningBalance
  entités créées supprimées si elles n'ont aucune dépendance
  import_batch.state → reverted
```

Au-delà de ces conditions, la correction passe par un **ajustement motivé**
([08](08-ledger-comptable.md) §6), jamais par une réécriture de l'import.
