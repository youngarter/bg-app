# 17 — Recouvrement et relances

← [Sommaire](README.md)

Prolonge [09](09-compte-de-lot.md) : celui-ci décrit **ce qui est dû**, celui-ci décrit
**ce qu'on fait pour le récupérer**.

---

## 1. Rappel — deux dimensions séparées

| Dimension | Champ | Nature |
|---|---|---|
| Où en est le paiement | `payment_status` | **calculé** : `open` / `partial` / `paid` / `overdue` / `cancelled` |
| Où en est le risque | `recovery_status` | **décidé** : `normal` / `doubtful` / `impaired` / `written_off` / `recovered` |

Le recouvrement agit sur la **seconde**. Une relance n'est jamais un changement d'état
de paiement : elle ne modifie aucun montant.

---

## 2. Échelle de relance

Configurable par résidence (`residences.settings`), **jamais codée en dur**.

```
Échéance dépassée
      │
      ▼  seuil 1 (défaut J+15)
  RAPPEL AMIABLE           courrier simple, ton neutre
      │
      ▼  seuil 2 (défaut J+45)
  RELANCE FORMELLE         courrier recommandé, rappel des intérêts éventuels
      │
      ▼  seuil 3 (défaut J+75)
  MISE EN DEMEURE          recommandé avec AR, délai impératif
      │
      ▼  décision explicite
  PRÉ-CONTENTIEUX          dossier transmis, sommation
```

### Modèle

```
dunning_notices
  id, residence_id, lot_account_id
  niveau : rappel | relance | mise_en_demeure | precontentieux
  montant_reclame           ← GELÉ à l'émission
  lines_snapshot : json     ← les lignes d'appel concernées, GELÉES
  emitted_on, due_by
  canal : courrier | email | remise_en_main | recommande_ar
  document_path             ← le courrier généré, archivé
  accuse_reception_path (nullable)
  created_by_user_id
  state : draft | sent | acknowledged | closed
```

### Règles

- Le **montant réclamé est gelé** à l'émission, comme le débiteur d'une ligne d'appel.
  Un courrier archivé doit rester lisible à l'identique dix ans plus tard, même si le
  lot a payé entre-temps.
- Une relance **ne produit aucune écriture comptable**. Elle ne change ni le solde ni
  le montant dû.
- Le passage au niveau suivant exige que le niveau précédent soit `sent`.
- Un paiement intégral **clôt automatiquement** les relances ouvertes du lot
  (`state = closed`), sans les supprimer.
- Le déclenchement est **proposé, jamais automatique** : Bayan liste les lots éligibles,
  le gestionnaire décide et envoie. Aucun courrier ne part sans acte humain.

---

## 3. Cycle de vie de la créance

Le plan comptable décrit le parcours complet d'un impayé, et Bayan doit le suivre.

```
        CRÉANCE NORMALE
        3421 / 3422 / 3423
               │
               │  impayé persistant, seuil ou décision
               ▼
        CRÉANCE DOUTEUSE                    ← reclassement comptable
        3424                                  le montant NE CHANGE PAS,
               │                               il change de compte
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

### Points de conception

- **Le déclassement en douteuse ne change pas le montant dû.** C'est un virement de
  compte à compte sur le **même auxiliaire**. Le copropriétaire doit toujours la même
  somme ; c'est le regard comptable qui change.
- **La dépréciation est une estimation du risque, séparée de la créance.** Elle
  n'efface rien. Deux grandeurs coexistent : ce qui est dû (`3424`) et ce qu'on pense
  ne pas récupérer (`394`).
- **Le passage en perte est une décision, pas un traitement automatique.** Il relève de
  l'AG et doit être tracé avec sa pièce — la résolution d'AG est référencée à titre
  justificatif ([16](16-assemblees-generales.md)), sans que l'AG déclenche quoi que ce soit.
- `7514` traite le cas réel du copropriétaire qui paie **après** que la créance a été
  soldée en perte. Sans ce compte, l'argent n'aurait aucune imputation possible.

---

## 4. Écritures types — extension du contrat

| Événement métier | Débit | Crédit |
|---|---|---|
| Déclassement en créance douteuse | `3424` *(aux : lot)* | `342x` d'origine *(aux : lot)* |
| Reclassement inverse (retour en normal) | `342x` d'origine *(aux : lot)* | `3424` *(aux : lot)* |
| Dotation à la dépréciation | `691` | `394` |
| Reprise de dépréciation | `394` | `791x` |
| Créance irrécouvrable | `6514` | `3424` *(aux : lot)* |
| Rentrée sur créance soldée | `512x` / `516x` | `7514` |

Chaque ligne est un test — voir [13](13-invariants-tests.md) §3.

---

## 5. Critères de déclassement

Stockés dans `residences.settings`, **configurables, jamais codés en dur** :

```json
{
  "recouvrement": {
    "seuils_relance_jours": [15, 45, 75],
    "doubtful_apres_jours": 180,
    "doubtful_montant_minimum": 500000,
    "depreciation_taux_defaut": 50
  }
}
```

Bayan **propose** les lots franchissant un seuil. Le déclassement reste un acte du
comptable, motivé et tracé. Aucun changement de `recovery_status` n'est automatique.

---

## 6. Écran de recouvrement

| Lot | Détenteur actuel | Courant | Travaux | Douteux | **Total** | Plus ancien | Dernière relance |
|---|---|---|---|---|---|---|---|
| A102 | Ahmed BENALI | 2 400 | 1 600 | 500 | **4 500** | 05/04/2025 | mise en demeure · 12/08 |
| B004 | SCI ATLAS | 1 200 | 0 | 0 | **1 200** | 05/02/2026 | rappel · 20/02 |

- Le **détenteur actuel** est affiché pour le contact ; le **débiteur d'origine** de
  chaque ligne reste consultable au détail (verrou n°3).
- Filtres : ancienneté, `recovery_status`, niveau de relance atteint, montant minimum.
- Action de masse : générer les courriers du niveau proposé pour une sélection de lots.
  **La génération produit des brouillons**, l'envoi reste un acte distinct.
