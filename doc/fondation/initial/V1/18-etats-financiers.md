# 18 — États financiers et annexes légales

← [Sommaire](README.md)

> **Aucun état n'est une table.** Tous sont des **projections** de
> `journal_entry_lines`, sauf ceux figés à la clôture (§4).

---

## 1. Les trois livres réglementaires

Le règlement impose la tenue de trois livres. Ce ne sont pas des rapports de confort :
ce sont des obligations, et ils dictent le modèle.

| Livre | Contenu exigé | Production dans Bayan |
|---|---|---|
| **Livre-journal** | les opérations **jour par jour et opération par opération** | `journal_entries` triées par date |
| **Grand livre** | comptes **individuels et collectifs**, solde initial, mouvements débit/crédit, solde final | `journal_entry_lines` groupées par compte et auxiliaire |
| **Livre d'inventaire** | **état de situation financière** et **compte de gestion général** de chaque exercice | états figés à la clôture |

### Point de conception critique

**Le journal et le grand livre ne sont pas deux stockages.** Ce sont **deux lectures du
même jeu d'écritures** :

```
             journal_entries + journal_entry_lines
                      (stockage unique)
                             │
            ┌────────────────┴────────────────┐
            ▼                                 ▼
    LIVRE-JOURNAL                       GRAND LIVRE
 trié par date, par pièce         groupé par compte, par auxiliaire
 « qu'a-t-on fait le 12/03 ? »    « que doit le lot A102 ? »
```

Les stocker séparément créerait deux sources de vérité à réconcilier — l'erreur exacte
que [09](09-compte-de-lot.md) §1 interdit pour les créances.

### Vocabulaire réglementaire

On dit **état de situation financière** (et non « bilan ») et **compte de gestion
général** (et non « compte de résultat »). Ces intitulés sont ceux du texte et doivent
apparaître **tels quels** dans l'application.

---

## 2. États produits à toute date

| État | Contenu | Source |
|---|---|---|
| **Livre-journal** | toutes les pièces d'une période, par date | projection |
| **Grand livre** | toutes les écritures d'un compte, dans l'ordre | projection |
| **Balance générale** | débits, crédits et solde de chaque compte du plan | projection |
| **Balance auxiliaire** | idem par lot ou par fournisseur | projection |
| **Budget vs réalisé** | par ligne budgétaire | `budget_lines.account_id` × charges |
| **Situation de trésorerie** | [15](15-tresorerie-depenses.md) §7 | projection |
| **Relevé de compte copropriétaire** | [09](09-compte-de-lot.md) §6 | projection |
| **Balance âgée** | dette par exercice et par mandat | projection |

**Invariant de cohérence :** la balance générale est équilibrée à toute date —
`Σ débits = Σ crédits` sur l'intégralité du ledger. Testé.

---

## 3. Annexes légales 1 à 5

Documents réglementaires, mise en page **optimisée pour l'impression A4**.

| Annexe | Intitulé | Composition |
|---|---|---|
| **1** | État de situation financière et trésorerie | actif / passif / réserve fonds travaux |
| **2** | Compte de gestion général des charges courantes | ventilé par poste de dépense (`61xx`) |
| **3** | Compte de gestion des travaux et opérations exceptionnelles | `65xx` et provisions `7112` |
| **4** | État des dettes et créances des copropriétaires à la clôture | balance auxiliaire `342x`, ventilée par nature |
| **5** | Budget prévisionnel comparatif | réalisé N vs voté N+1 |

### Règles de production

- Les annexes sont **dérivées du plan comptable configuré**. Leur format s'adapte sans
  changement de schéma.
- Elles sont produites **à toute date** en consultation, et **figées** à la clôture.
- Chaque annexe porte en pied : résidence, exercice, date d'édition, état de l'exercice
  (`open` / `closed`) et mention « document provisoire » tant que l'exercice est ouvert.

> ⚠️ Le format exact (intitulés, regroupements, présentation) doit être confirmé sur le
> texte officiel — voir [14](14-hors-perimetre.md) q.3. Le schéma est indépendant de la
> réponse.

---

## 4. Le livre d'inventaire — le seul état matérialisé

À la clôture, les états sont **figés et archivés** avec leur date et leur validation :
ils doivent rester consultables **à l'identique** des années plus tard.

```
closing_statements
  id, residence_id, exercise_id
  type : situation_financiere | compte_gestion_general
       | annexe_1 | annexe_2 | annexe_3 | annexe_4 | annexe_5
  payload : json          ← le contenu calculé, FIGÉ
  document_path           ← le PDF généré, archivé
  generated_at, generated_by_user_id
  approval_request_id     ← l'approbation du conseil syndical
```

### Pourquoi figer

Un état recalculé cinq ans plus tard depuis le ledger **peut différer** : une écriture
d'ajustement postée en N+1 sur l'exercice N modifie la lecture. L'état approuvé par le
conseil syndical doit rester celui qui a été approuvé.

**Le ledger reste l'autorité comptable. `closing_statements` est l'autorité
documentaire.** Les deux coexistent sans se contredire : le second est un instantané
horodaté du premier.

---

## 5. Résultat de l'exercice

La partie double fournit le résultat comptable. **Il n'est pas ventilé sur les comptes
des lots** — conséquence de la décision D7 et de l'appel définitif
([06](06-appels-de-fonds.md) §2).

```
Clôture :  solder 711x / 61xx / 65xx
           résultat → 119x Report à nouveau
```

Il reste au report à nouveau / fonds de roulement de la copropriété.

> ⚠️ Si la réglementation impose l'approbation des comptes en AG **et** la répartition
> du résultat, cette décision doit être revue — voir [14](14-hors-perimetre.md) q.4.
> Dans ce cas seulement, l'AG cesserait d'être un simple registre.

---

## 6. Export

Tout état est exportable en **PDF** (impression) et en **CSV / XLSX** (retraitement).

L'export reste disponible dans **tous les états de licence sauf `suspended`**, y compris
`read_only` — couper une copropriété de ses propres états serait un risque juridique
([01](01-plateforme-licence.md) §3.2).
