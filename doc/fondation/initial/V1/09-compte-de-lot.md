# 09 — Compte de lot, créances et relevé

← [Sommaire](README.md)

**C'est le produit visible de la V1.** Le gestionnaire doit répondre immédiatement à
« combien ce lot doit-il ? », et le copropriétaire à « pourquoi dois-je cette somme ? ».

---

## 1. La créance est calculée, jamais stockée

C'est le point où un système comme celui-ci se casse habituellement.

```
Solde d'un lot = Σ débits − Σ crédits
                 sur journal_entry_lines
                 où compte ∈ 342x ET auxiliaire = lot_account
```

**Stocker la créance en parallèle du ledger crée deux sources de vérité qui divergent.**
Le bug se manifeste six mois après la mise en production et il est très coûteux à réparer.

L'écran « Créances » est une **requête d'agrégation**, pas une table à maintenir.

### Cache — seulement si mesuré

Si une page devient réellement lente, on ajoute `lot_account_balances` avec
`recomputed_at` et une commande de reconstruction. **Le cache n'est jamais l'autorité.
On ne l'ajoute pas avant d'avoir mesuré un problème.**

---

## 2. 🔒 Deux dimensions séparées

> **Correction actée (revue du 03/09/2026) : l'état de paiement et l'état de
> recouvrement sont deux dimensions distinctes, portées par deux champs.**

Le README cible les mélangeait dans un seul cycle
`normale → douteuse → dépréciée → irrécouvrable`. C'était un défaut : une créance
douteuse peut être partiellement payée, et une créance à l'échéance dépassée n'est pas
automatiquement douteuse.

### 2.1 `payment_status` — état de paiement

**Calculé**, jamais stocké comme vérité. Porté par `fund_call_lines`.

```
                montant imputé
                      │
     ┌────────────────┼────────────────┐
     ▼                ▼                ▼
    = 0            0 < x < M          = M
     │                │                │
     ▼                ▼                ▼
   open            partial           paid
     │                │
     └────────┬───────┘
              ▼  si due_date dépassée
           overdue          (qualificatif, pas un état terminal)

  cancelled   ← l'appel a été extourné
```

### 2.2 `recovery_status` — état de recouvrement

**Décidé**, stocké, transitions tracées. Porté par `fund_call_lines`.

```
normal ──▶ doubtful ──▶ impaired ──┬──▶ written_off
   ▲          │            │       │
   └──────────┴────────────┴───────┴──▶ recovered
        (paiement intégral, quel que soit l'état atteint)
```

| État | Sens | Effet sur le montant dû |
|---|---|---|
| `normal` | créance ordinaire | — |
| `doubtful` | impayé persistant, reclassement `3422/3423 → 3424` | **aucun** |
| `impaired` | risque provisionné (dotation) | **aucun** |
| `written_off` | passée en perte, sur décision tracée | soldée comptablement |
| `recovered` | intégralement encaissée | — |

### 2.3 Points de conception

- **Le déclassement en douteuse ne change pas le montant dû.** C'est un virement de
  compte à compte sur le même auxiliaire. Le copropriétaire doit toujours la même
  somme ; c'est le regard comptable qui change.
- La **dépréciation est une estimation du risque**, séparée de la créance. Deux
  grandeurs distinctes coexistent : ce qui est dû (`3424`) et ce qu'on pense ne pas
  récupérer (`394`).
- Le **passage en perte est une décision**, pas un traitement automatique. Il doit être
  tracé avec sa pièce.
- Les **critères de déclassement** (ancienneté, montant, décision manuelle) sont
  **configurables par résidence, jamais codés en dur**.

Le cycle complet — déclassement, dépréciation, passage en perte, rentrée sur créance
soldée — et l'échelle de relance sont traités en
[17](17-recouvrement-relances.md).

---

## 3. Solde par nature

Un lot n'a pas *un* solde mais **un solde par nature**, plus le total.
`lot_accounts` est l'identité auxiliaire ; les soldes se lisent par `(compte, auxiliaire)`.

```
Lot A102 — M. Ahmed BENALI

  Opérations courantes    3422      2 400,00
  Travaux                 3423      1 600,00
  Avances                 3421          0,00
  Créances douteuses      3424        500,00
  ─────────────────────────────────────────────
  Total exigible                    4 500,00
```

**Le règlement impose cette ventilation.** Ce n'est pas un raffinement optionnel.

---

## 4. Traçabilité — l'écran qui justifie tout

À « pourquoi le lot A102 doit-il 4 500 DH ? », Bayan répond :

```
4 500,00 DH
  ├── 1 000,00  Appel AF-2025-003 · Mandat M12 · Exercice 2025
  │                appelé 2 000,00 · payé 1 000,00 · échéance 05/04/2025
  │                débiteur à l'émission : M. Ahmed BENALI
  ├── 2 000,00  Appel AF-2026-001 · Mandat M12 · Exercice 2026
  │                échéance 05/02/2026 · overdue
  └── 1 500,00  Solde d'ouverture · import du 12/01/2024
                   └── PJ : releve-syndic-abc.pdf
```

Chaque ligne est cliquable et remonte à sa source via `source_type` / `source_id`.

### Le cas des données importées

Pour une résidence reprise, **le détail n'existe pas**. Ce n'est pas un cas
particulier : c'est le **même ledger avec un `source_type` différent**.

```
OpeningBalance → « Solde repris au 01/01/2026, import du relevé
                   du syndic ABC, pièce jointe »
```

**Même table, même écran, transparence honnête sur le niveau de détail disponible.**
On obtient l'écran voulu sans table `creances` parallèle, donc sans risque de divergence.

---

## 5. Balance âgée

Simple `GROUP BY exercise_id` sur les lignes non soldées.

```
Lot A102 — dette totale 7 800,00

  Antérieur à la reprise    1 000,00
  2024                      2 300,00     Mandat M9
  2025                      3 500,00     Mandat M12
  2026                      1 000,00     Mandat M13
```

Les soldes importés en bloc sont marqués **« antérieur à la reprise »** plutôt que
rattachés artificiellement à une année qu'on ne connaît pas.

---

## 6. Relevé de compte

Document exportable en PDF, par lot et par période.

```
RELEVÉ DE COMPTE · Lot A102 · Résidence Greenwood
Période : 01/01/2026 → 03/09/2026

Date        Libellé                        Débit      Crédit     Solde
──────────────────────────────────────────────────────────────────────
01/01/26    Solde à nouveau                                   3 000,00
05/01/26    Appel AF-2026-001 T1         2 000,00             5 000,00
10/02/26    Paiement PAY-00042 chèque                1 500,00 3 500,00
              └── imputé sur AF-2026-001
05/04/26    Appel AF-2026-002 T2         2 000,00             5 500,00
──────────────────────────────────────────────────────────────────────
                              Solde au 03/09/2026            5 500,00

Ventilation :  courant 4 000,00 · travaux 1 000,00 · douteux 500,00
```

Le relevé est produit **entièrement depuis le ledger**. Aucune donnée dérivée n'y entre.

---

## 7. Écran de recouvrement

Liste des lots débiteurs de la résidence, triable et filtrable.

| Lot | Détenteur actuel | Courant | Travaux | Douteux | **Total** | Plus ancien impayé |
|---|---|---|---|---|---|---|
| A102 | Ahmed BENALI | 2 400 | 1 600 | 500 | **4 500** | 05/04/2025 |
| B004 | SCI ATLAS | 1 200 | 0 | 0 | **1 200** | 05/02/2026 |

- Le **détenteur actuel** est affiché pour le contact ; le **débiteur d'origine** de
  chaque ligne reste consultable au détail (verrou n°3).
- Un clic descend au détail du §4.
- Filtres V1 : ancienneté, `recovery_status`, montant minimum.
