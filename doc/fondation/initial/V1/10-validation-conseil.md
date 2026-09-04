# 10 — Validation par le conseil syndical

← [Sommaire](README.md)

**Mécanisme léger et uniforme, pas un moteur de workflow.**

---

## 1. Abstraction retenue

> **Correction actée (revue du 03/09/2026) :** une seule paire de tables polymorphes,
> plutôt que `BudgetApproval` / `FundCallApproval` / `ClosingApproval` — quatre schémas
> quasi identiques à maintenir en parallèle.

```
approval_requests
  · approvable_type · approvable_id     ← polymorphe
  · residence_id
  · status : pending | approved | rejected | withdrawn
  · submitted_by_user_id · submitted_at
  · decided_at
  · timestamps
      │
      └──< approval_decisions
             · user_id · role
             · decision : approved | rejected
             · comment          ← OBLIGATOIRE si rejected
             · decided_at
```

**Garde-fou anti-sur-abstraction :** en V1, une `approval_request` a **exactement un
décideur** (le président du conseil syndical) et **une** décision. Pas de quorum, pas
de collège, pas de délégation. La table `approval_decisions` existe séparément pour
permettre le vote multiple en V2 sans migration — pas pour l'implémenter maintenant.

---

## 2. Cycle

```
Le syndic prépare l'objet      →  état draft
              │
              ▼ soumission
      approval_request créée   →  status pending
              │                   notification au président
              ▼
      Le président décide
              │
     ┌────────┴────────┐
     ▼                 ▼
  approved          rejected
     │                 │  commentaire OBLIGATOIRE
     ▼                 ▼
 l'objet devient   l'objet retourne en draft
 exécutable        modifiable, resoumettable
```

**Chaque décision est enregistrée (qui, quand, commentaire) et immuable.**
Une décision ne se modifie pas : on retire la demande (`withdrawn`) et on resoumet.

---

## 3. Objets soumis à validation — V1

| Objet | Ce que débloque l'approbation | Réversible ? |
|---|---|---|
| **Budget** | l'émission d'appels sur l'exercice | non — révision = nouveau budget |
| **Appel de fonds** | l'émission (`issue`) et le posting | non — correction = nouvel événement |
| **Clôture d'exercice** | le passage en `closed` et les à-nouveaux | non |

La **passation** est en V2 (voir [14](14-hors-perimetre.md)), mais utilisera le même
mécanisme sans modification.

---

## 4. Règles

1. **Le président ne saisit rien, ne modifie rien.** Sa seule action d'écriture est la
   décision.
2. Le rôle de président est **daté** (`residence_roles`). Une décision porte le rôle au
   moment où elle est prise : le changement de président ne réécrit pas l'historique.
3. Une demande ne peut être soumise que si l'objet est en `draft` **et** l'exercice `open`.
4. **Aucune approbation n'est requise pour :** paiements, imputations, ajustements,
   structure (lots, copropriétaires). Ces opérations sont du courant de gestion.
5. Un objet approuvé dont l'approbation est retirée redevient `draft` ; s'il avait
   déjà été posté, il faut d'abord l'**extourner**.
6. `approvable_type` est **liste blanche** : un test vérifie qu'aucun autre type ne
   peut être soumis.

---

## 5. Absence de président

Cas réel : le poste est vacant, ou le mandat du président a expiré.

**Décision actée :** l'émission d'appels est **bloquée** tant qu'aucun président en
fonction n'existe. L'écran affiche une anomalie nommée et propose l'enregistrement d'un
nouveau rôle (PV d'AG en pièce).

Ne **pas** prévoir de contournement automatique ni de délai d'approbation tacite : ce
serait ouvrir la porte à des appels non validés, exactement ce que le mécanisme existe
pour empêcher.
