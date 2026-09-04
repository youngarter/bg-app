# 02 — Identité, rôles et autorisation

← [Sommaire](README.md)

---

## 1. Séparation identité / rôle métier

```
users                          ← identité de connexion, unique par personne
  │
  ├──▶ syndic_company_user     ← collaborateur d'une société de syndic
  ├──▶ residence_roles         ← président ou membre du conseil, DATÉ
  └──▶ owners                  ← copropriétaire, PAR RÉSIDENCE
```

Une même personne détenant des lots dans deux résidences gérées par Bayan a **un seul
`user`** rattaché à **deux `owners`**, avec un sélecteur de résidence.

Un `owner` peut exister **sans `user`** : le copropriétaire connu du syndic qui ne
s'est jamais connecté. Le lien est optionnel et se crée à l'activation du compte.

---

## 2. Les quatre acteurs

### 2.1 Administrateur plateforme
Voir [01](01-plateforme-licence.md). Hors copropriété.

### 2.2 Société de syndic et ses collaborateurs

Accès opérationnel complet aux résidences sur lesquelles la société est accréditée.

| Rôle collaborateur | Portée V1 |
|---|---|
| `gerant` | tout, y compris soumission à validation |
| `gestionnaire` | structure, appels, paiements, saisie |
| `comptable` | ledger, ajustements, états, clôture |

Les droits d'un collaborateur **découlent de l'accréditation de sa société**.
Révoquer la société révoque tous ses collaborateurs, instantanément.

### 2.3 Président du conseil syndical

Copropriétaire élu, **avec un mandat daté** (`residence_roles`, PV d'AG en pièce).
Rôle **lecture + validation** :
- consulte l'intégralité des comptes de la résidence, tous lots confondus ;
- **approuve ou refuse** : budget, appel de fonds, clôture d'exercice ;
- **ne saisit rien, ne modifie rien**.

Son rôle est une attribution temporelle, jamais un attribut permanent du `user`.

### 2.4 Copropriétaire

Accès strictement limité à **ses propres lots**, via le portail décrit en
[20](20-portail-coproprietaire.md).

### 2.5 Copropriétaire délégué (bureau du conseil syndical)

Un copropriétaire peut recevoir une **délégation** sur une partie des modules de
gestion : vice-syndic, trésorier, secrétaire, ou sélection sur mesure.

```
delegations
  id, residence_id, owner_id, user_id
  titre : vice_syndic | tresorier | secretaire | delegue
  modules : json            ← liste blanche de modules autorisés
  started_on, ended_on      ← DATÉE, comme tout rôle
  pv_ag_document_path
  granted_by_user_id
  state : active | revoquee
```

| Profil | Modules recommandés |
|---|---|
| `vice_syndic` | tous les modules opérationnels |
| `tresorier` | tableau de bord, appels, paiements, dépenses, fournisseurs, trésorerie |
| `secretaire` | assemblées, réclamations, projets, carnet d'entretien |
| `delegue` | sélection sur mesure |

**Règles :**

1. **Éligibilité stricte** : seul un `owner` de la résidence peut être délégué.
2. La délégation est **datée**. Elle expire seule, sans job de désactivation.
3. Les droits en gestion viennent **exclusivement** de `delegations.modules`, jamais de
   la qualité de copropriétaire.
4. Un délégué garde son **espace copropriétaire limité à ses lots** — les deux contextes
   ne fuient pas l'un dans l'autre ([20](20-portail-coproprietaire.md) §5).
5. Une délégation ne donne **jamais** le droit d'approuver : l'approbation reste au
   président du conseil syndical ([10](10-validation-conseil.md)).
6. `modules` est une **liste blanche testée** : un module inconnu est ignoré, jamais
   interprété comme « tout autoriser ».

---

## 3. Autorisation : trois verrous indépendants

Un utilisateur accède à une ressource **si et seulement si les trois passent** :

```
        Requête
           │
           ▼
   ┌───────────────┐
   │ 1. LICENCE    │  la licence de la résidence autorise l'opération ?
   │               │  active/grace → R+W · read_only → R · suspended → ✗
   └───────┬───────┘
           │ ✓
           ▼
   ┌───────────────┐
   │ 2. RATTACHE-  │  le lien de l'utilisateur à cette résidence
   │    MENT       │  est-il ACTIF À CETTE DATE ?
   │               │  accréditation · rôle conseil · détention de lot
   └───────┬───────┘
           │ ✓
           ▼
   ┌───────────────┐
   │ 3. POLICY     │  la policy de l'objet demandé autorise-t-elle
   │               │  cette action à ce rôle ?
   └───────┬───────┘
           │ ✓
           ▼
        Accordé
```

### Règles d'implémentation

- Les verrous 1 et 2 sont des **middlewares**, jamais dupliqués dans les policies.
- Le verrou 2 teste toujours une **plage de dates**, jamais un booléen : un rôle expiré
  ne donne plus accès, sans qu'aucun job n'ait eu à le désactiver.
- L'administrateur plateforme **contourne les verrous 1 et 2**, jamais le journal d'audit.
- Aucune requête métier ne s'exécute sans scope résidence. Vérifié par test d'architecture.

---

## 4. Matrice des droits — V1

| Objet | Admin | Gérant | Gestionnaire | Comptable | Président CS |
|---|---|---|---|---|---|
| Résidence, licence | CRUD | R | R | R | R |
| Accréditation syndic | CRUD | — | — | — | — |
| Lots, tantièmes | R | CRUD | CRUD | R | R |
| Copropriétaires, détentions | R | CRUD | CRUD | R | R |
| Mandat, exercice | R | CRUD | R | R | R |
| Budget | R | CRUD + soumettre | CU | R | R + **approuver** |
| Appel de fonds | R | CRUD + soumettre + émettre | CU | R | R + **approuver** |
| Paiement, imputation | R | CRUD | CRUD | CRUD | R |
| Ajustement | R | — | — | **CRUD** | R |
| Ledger, états | R | R | R | R | R |
| Clôture d'exercice | R | lancer | — | exécuter | **approuver** |
| Dépenses, fournisseurs | R | CRUD | CU | CRUD | R |
| Trésorerie, virements | R | CRUD | R | CRUD | R |
| Rapprochement bancaire | R | R | — | CRUD | R |
| Relances | R | CRUD | CRUD | R | R |
| Assemblées générales | R | CRUD | CU | R | R |
| Réclamations, projets, carnet | R | CRUD | CRUD | R | R |
| Passation | R | CRUD | — | R | **approuver** |
| Import historique | R | CRUD | CU | R | R |
| Journal d'audit | R | R | — | R | R |

`C`réer · `R`ead · `U`pdate · `D`elete *(D = extourne pour tout objet posté)*

Un **copropriétaire délégué** hérite des colonnes ci-dessus limitées aux modules listés
dans sa délégation, sans jamais obtenir le droit d'approuver.
