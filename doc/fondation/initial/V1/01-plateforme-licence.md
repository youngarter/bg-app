# 01 — Plateforme, licence et accès syndic

← [Sommaire](README.md)

---

## 1. Le modèle commercial

SaaS **vendu à la copropriété**. La résidence est le tenant ; le syndic est un
utilisateur invité dont l'accès est révocable.

```
Administrateur plateforme (Bayan)
        │ crée
        ▼
   RÉSIDENCE ──────── LICENCE
        │                (droit d'usage de la plateforme)
        │ accrédite
        ▼
  SOCIÉTÉ DE SYNDIC
        │ ses collaborateurs accèdent
        ▼
   Données de la résidence
```

**La donnée appartient à la copropriété, jamais au syndic.**

---

## 2. L'administrateur plateforme

Interne à Bayan (`users.is_platform_admin`). N'est pas un utilisateur de la copropriété.

Prérogatives :
- créer une résidence, gérer sa licence ;
- **accorder et révoquer** l'accès d'une société de syndic, sur pièce justificative ;
- consulter en support, exporter, auditer.

Chacune de ses actions est journalisée : acteur, date, motif, pièce.

---

## 3. Licence

### 3.1 États

```
active ──(date de fin dépassée)──▶ grace ──(grâce épuisée)──▶ read_only
   ▲                                  │                           │
   └──────── renouvellement ──────────┴───────────────────────────┘

                    suspended  ◀── décision administrateur uniquement
```

| État | Lecture | Écriture | Export |
|---|---|---|---|
| `active` | ✅ | ✅ | ✅ |
| `grace` | ✅ | ✅ + bandeau d'alerte | ✅ |
| `read_only` | ✅ | ❌ | ✅ |
| `suspended` | ❌ | ❌ | ❌ (admin seul) |

### 3.2 Décision actée — pas de blocage sec

Couper une copropriété de sa propre comptabilité pour un retard de paiement crée un
incident support et un risque juridique.

- Période de grâce **configurable**, défaut **30 jours** (`licenses.grace_days`).
- Puis lecture seule, jamais coupure.
- L'export reste disponible dans tous les états sauf `suspended`.

### 3.3 Transitions

| De → Vers | Déclencheur | Acteur |
|---|---|---|
| — → `active` | création / activation | admin |
| `active` → `grace` | `ends_on` dépassé (job quotidien) | système |
| `grace` → `read_only` | `ends_on + grace_days` dépassé | système |
| `grace` / `read_only` → `active` | renouvellement | admin |
| tout état → `suspended` | décision motivée | admin |
| `suspended` → `active` | réactivation motivée | admin |

Chaque transition écrit un `license_events` : type, `effective_at`, acteur, note.

---

## 4. Accréditation : mandat ≠ accès

> **Point d'architecture central. Les permissions ne sont jamais dérivées du mandat.**

Le **mandat** est un fait juridique (l'AG a élu ABC du 01/01/24 au 31/12/25).
L'**accès** est une décision de la plateforme, prise par l'administrateur sur pièce.

Les deux se désynchronisent en permanence :

```
10/01  l'AG vote le nouveau syndic
                    │
                    │  ← 3 semaines pendant lesquelles l'ancien syndic
                    │     DOIT conserver l'accès pour achever la passation
                    ▼
03/02  la lettre d'information arrive → l'admin révoque
```

Coder `accès = mandat actif` casserait cette période et rendrait la passation
impossible. `residence_accesses` est donc une table indépendante de `mandates`.

---

## 5. Révocation d'un accès syndic

```
Réception de la lettre d'information
        ↓
Admin ouvre la fiche résidence
        ↓
Révocation de l'accès de la société A
  · motif obligatoire
  · pièce justificative obligatoire
  · date d'effet
        ↓
Génération automatique de l'export de sortie pour A
  (relevés, appels, paiements de ses mandats)
        ↓
Accréditation de la société B
        ↓
Journalisation
```

### Conséquences assumées

**A ne voit plus rien après révocation.** L'export généré à la révocation est donc
**obligatoire et bloquant** : sans lui, A ne peut plus produire ses propres
justificatifs et un litige est certain.

**B voit l'intégralité de l'historique**, y compris les données saisies par A.
C'est cohérent avec « tenant = résidence » : la donnée appartient à la copropriété.

---

## 6. Journal d'audit

Table `audit_logs`, alimentée par toute action sensible.

| Champ | Contenu |
|---|---|
| `actor_user_id` | qui |
| `action` | quoi (`license.suspended`, `access.revoked`, `fund_call.issued`…) |
| `auditable_type` / `auditable_id` | sur quoi |
| `motif` | pourquoi (obligatoire pour les actions admin) |
| `document_path` | pièce (obligatoire pour révocation et suspension) |
| `ip`, `user_agent`, `created_at` | contexte |

**Le journal d'audit est immuable** : insertion seule, aucune mise à jour, aucune
suppression. Vérifié par test.

Périmètre minimal audité en V1 : toute action de l'administrateur plateforme, toute
transition de licence ou d'accréditation, toute décision d'approbation, tout posting
et toute extourne.
