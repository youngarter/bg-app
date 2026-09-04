# 11 — Machines à états

← [Sommaire](README.md)

**Point unique de définition.** Aucune transition n'est écrite ailleurs dans le code.

> Ne jamais avoir `$model->status = 'active';` dispersé dans l'application.
> Chaque transition est une **méthode nommée** portée par une Action, qui vérifie ses
> conditions, son acteur, et déclenche ses effets.

Format de lecture de chaque table :

| De → Vers | Acteur | Conditions | Effets | Irréversible |
|---|---|---|---|---|

---

## 1. `licenses`

| De → Vers | Acteur | Conditions | Effets | Irrév. |
|---|---|---|---|---|
| — → `active` | admin | résidence existe | `license_events` | non |
| `active` → `grace` | système | `ends_on` dépassé | bandeau d'alerte | non |
| `grace` → `read_only` | système | `ends_on + grace_days` dépassé | écriture bloquée | non |
| `grace`/`read_only` → `active` | admin | renouvellement enregistré | déblocage | non |
| `*` → `suspended` | admin | motif obligatoire | accès coupé sauf admin | non |
| `suspended` → `active` | admin | motif obligatoire | — | non |

---

## 2. `residence_accesses`

| De → Vers | Acteur | Conditions | Effets | Irrév. |
|---|---|---|---|---|
| — → `active` | admin | licence ≠ `suspended` | accès des collaborateurs ouvert | non |
| `active` → `revoked` | admin | **motif + pièce + date d'effet** | export de sortie **généré et bloquant** ; accès coupé | **oui** |

Une accréditation révoquée ne se réactive pas : on en crée une nouvelle.

---

## 3. `mandates`

| De → Vers | Acteur | Conditions | Effets | Irrév. |
|---|---|---|---|---|
| `draft` → `active` | gérant | dates cohérentes, pas de chevauchement | — | non |
| `active` → `suspended` | admin | motif | opérations gelées | non |
| `suspended` → `active` | admin | — | — | non |
| `active` → `expired` | système | `ends_on` atteint | — | non |
| `active` → `terminated` | gérant/admin | **motif + pièce** | exercice en cours à clôturer | **oui** |
| `expired`/`terminated` → `closed` | gérant | tous les exercices `closed` | — | **oui** |

---

## 4. `exercises`

| De → Vers | Acteur | Conditions | Effets | Irrév. |
|---|---|---|---|---|
| — → `open` | gérant | aucun autre exercice `open` ; pas de chevauchement ; ⊆ mandat | — | non |
| `open` → `closing` | gérant | — | saisie gelée | non |
| `closing` → `open` | système | **échec** d'un contrôle | anomalies listées, rien n'est posté | non |
| `closing` → `closed` | comptable | contrôles OK **et** approbation du conseil | écritures de clôture + à-nouveaux + états archivés | **oui** |

Contrôles bloquants du passage `closing → closed` : voir [04](04-mandats-exercices.md) §4.

---

## 5. `budgets`

| De → Vers | Acteur | Conditions | Effets | Irrév. |
|---|---|---|---|---|
| `draft` → `submitted` | gérant | ≥ 1 ligne, total > 0, exercice `open` | `approval_request` créée | non |
| `submitted` → `rejected` | président | **commentaire obligatoire** | retour `draft` | non |
| `submitted` → `approved` | président | — | débloque l'émission d'appels | **oui** |
| `approved` → `superseded` | gérant | un nouveau budget est `approved` | l'ancien reste consultable | **oui** |

---

## 6. `fund_calls` — la machine critique

| De → Vers | Acteur | Conditions | Effets | Irrév. |
|---|---|---|---|---|
| `draft` → `submitted` | gérant | budget `approved`, montant > 0, exercice `open` | `approval_request` | non |
| `submitted` → `rejected` | président | **commentaire obligatoire** | retour `draft` | non |
| `submitted` → `approved` | président | — | — | **oui** |
| `approved` → `issued` | gérant | **8 contrôles** de [06](06-appels-de-fonds.md) §5 | lignes créées, données **gelées**, pièce comptable postée | **🔒 OUI** |
| `issued` → `reversed` | comptable | **aucune imputation active** + motif | pièce inverse, lignes `cancelled` | **oui** |

```
                      ╔═══════════════════════════════╗
draft → submitted →   ║  approved → ISSUED            ║ → reversed
                      ║      point de non-retour      ║
                      ╚═══════════════════════════════╝
```

Après `issued` : **aucune modification du montant, de la répartition, du débiteur, de
la nature ou de l'exercice n'est possible.** Jamais.

---

## 7. `fund_call_lines` — deux dimensions

### 7.1 `payment_status` — **calculé**, jamais assigné

```
open ──┬──▶ partial ──▶ paid
       └──────────────▶ paid
              (+ qualificatif overdue si due_date < today et reste dû)

cancelled  ← l'appel parent est reversed
```

Recalculé à chaque création ou annulation d'imputation. N'est jamais l'autorité :
l'autorité est `Σ allocations actives`.

### 7.2 `recovery_status` — **décidé**, tracé

| De → Vers | Acteur | Conditions | Effets | Irrév. |
|---|---|---|---|---|
| `normal` → `doubtful` | comptable | critères de la résidence ou décision motivée | écriture `3424 / 342x` | non |
| `doubtful` → `normal` | comptable | motif | écriture inverse | non |
| `*` → `recovered` | système | `payment_status = paid` | — | non |
| `doubtful` → `impaired` | comptable | dotation motivée | écriture `691 / 394` | non |
| `impaired` → `written_off` | comptable | décision tracée + pièce | écriture `6514 / 3424` | **oui** |

---

## 8. `payments`

| De → Vers | Acteur | Conditions | Effets | Irrév. |
|---|---|---|---|---|
| — → `draft` | gestionnaire | lot, montant > 0, moyen | — | non |
| `draft` → `confirmed` | gestionnaire | exercice `open`, compte de trésorerie valide, **caisse non négative** | **pièce postée**, imputation FIFO proposée | **oui** |
| `confirmed` → `reversed` | comptable | **motif** (chèque impayé, double saisie) | allocations annulées, pièce inverse | **oui** |

---

## 9. `payment_allocations`

Pas d'états : une allocation est **active** ou **annulée** (`cancelled_at` renseigné).

| Action | Acteur | Conditions | Effets |
|---|---|---|---|
| créer | gestionnaire | invariants 1→5 de [07](07-paiements-imputation.md) §5 | `payment_status` recalculé |
| annuler | gestionnaire | **motif** | `cancelled_at`, auteur, motif ; `payment_status` recalculé |

**Aucune suppression.** Aucune modification : on annule et on recrée.

---

## 10. `adjustments`

| De → Vers | Acteur | Conditions | Effets | Irrév. |
|---|---|---|---|---|
| — → `draft` | comptable | **motif + pièce obligatoires** | — | non |
| `draft` → `posted` | comptable | pièce équilibrée, exercice `open` | écritures postées | **oui** |
| `posted` → `reversed` | comptable | motif | pièce inverse | **oui** |

---

## 11. `approval_requests`

| De → Vers | Acteur | Conditions | Effets | Irrév. |
|---|---|---|---|---|
| — → `pending` | gérant | objet `draft`, exercice `open`, **un président en fonction existe** | notification | non |
| `pending` → `approved` | président | rôle actif à la date | l'objet devient exécutable | **oui** |
| `pending` → `rejected` | président | **commentaire obligatoire** | objet → `draft` | **oui** |
| `pending` → `withdrawn` | gérant | — | objet → `draft` | **oui** |

---

## 12. Règles transverses

1. Toute transition irréversible écrit un `audit_logs`.
2. Toute transition exigeant un motif **refuse** un motif vide ou trivial (< 10 caractères).
3. Aucune transition n'est possible si la licence est en `read_only` ou `suspended`,
   sauf les transitions déclenchées par l'administrateur plateforme.
4. Une transition qui doit produire des écritures les produit **dans la même
   transaction** que le changement d'état. Jamais en job asynchrone.
5. Test d'architecture : aucune assignation directe d'un champ d'état hors des Actions.
