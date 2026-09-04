# 20 — Portail copropriétaire

← [Sommaire](README.md)

---

## 1. Liaison identité

```
users      ← identité de connexion, UNE par personne
   │
   └──▶ owners   ← partie copropriétaire, PAR RÉSIDENCE
           │
           └──▶ lot_ownerships   ← détentions datées
```

Une même personne détenant des lots dans deux résidences gérées par Bayan a **un seul
compte utilisateur** rattaché à **deux enregistrements `owners`**, avec un sélecteur de
résidence.

Un `owner` peut exister **sans `user`** : le copropriétaire connu du syndic qui ne s'est
jamais connecté. L'invitation crée le lien.

---

## 2. Cycle de vie de l'accès

> L'accès **découle de la détention d'un lot**. Il s'ouvre à l'acquisition, se ferme à
> la mutation.

```
Détention active à aujourd'hui
        │
        ▼
   Accès ouvert  ──────────────▶  Mutation, ended_on atteint
        │                                    │
        │                                    ▼
        │                          Accès en LECTURE HISTORIQUE
        │                          la période détenue reste visible
        ▼
   Verrou n°2 de [02](02-identite-roles.md)
```

**L'ancien propriétaire conserve la consultation de la période où il détenait le lot.**
Il ne voit rien de ce qui s'est passé après sa mutation. C'est un filtre de dates sur
les mêmes requêtes, pas un mode d'accès distinct.

Aucun job ne « désactive » un compte : le verrou teste une plage de dates.

---

## 3. Ce que le copropriétaire voit

- ses lots, son **solde consolidé** et le solde par lot ;
- la **ventilation par nature** : courant, travaux, avances, douteux ;
- ses appels de fonds — montant, échéance, payé, restant dû ;
- ses paiements et leurs pièces ;
- son **relevé de compte exportable en PDF** ;
- ses réclamations et leur suivi ([19](19-exploitation-technique.md)) ;
- les **documents publics** : budgets approuvés, PV d'AG archivés, règlement de
  copropriété, carnet d'entretien.

## 4. Ce qu'il ne voit jamais

| Interdit | Raison |
|---|---|
| Les comptes des autres lots | isolation stricte |
| Les dépenses détaillées et les fournisseurs | gestion, pas information |
| La trésorerie, le grand livre, les balances | idem |
| L'identité des autres débiteurs | protection des données |
| Les relances émises à d'autres lots | idem |

Il voit **les états agrégés approuvés** (annexes légales, budget voté), jamais le
détail nominatif d'autrui.

**Invariant testé :** toute requête du portail est scopée par
`lot_ownerships` actives ou passées du `owner` connecté. Aucune exception.

---

## 5. Le copropriétaire délégué — double navigation

Un copropriétaire peut être **membre du conseil syndical** ou porteur d'une délégation
([02](02-identite-roles.md)). Il a alors deux contextes :

```
   ESPACE COPROPRIÉTAIRE                    ESPACE GESTION
   mes lots, mon solde        ◀────────▶    modules autorisés par
   mes appels, mes paiements    bascule     sa délégation
```

- La bascule est **explicite** et affichée en permanence (badge dans l'en-tête).
- Elle **ne ressaisit pas le mot de passe** — c'est la même session, deux contextes.
- Les droits en gestion viennent **exclusivement** de sa délégation, jamais de sa
  qualité de copropriétaire.
- En espace copropriétaire, il ne voit **que ses lots**, même s'il a accès à tout en
  gestion. Les deux contextes ne fuient pas l'un dans l'autre.

---

## 6. Ce que le copropriétaire peut écrire

Périmètre d'écriture volontairement minimal :

| Action | Autorisé |
|---|---|
| Créer une réclamation, la commenter, joindre des photos | ✅ |
| Mettre à jour ses coordonnées (téléphone, email, adresse) | ✅ *(tracé)* |
| Déclarer un paiement effectué | ❌ — seul le syndic encaisse |
| Modifier quoi que ce soit de comptable | ❌ |
| Changer l'état d'une réclamation | ❌ |

Une mise à jour de coordonnées écrit un `audit_logs` : le syndic doit pouvoir savoir
qui a changé quoi.

---

## 7. Invitation et activation

```
Le syndic crée l'owner
        ↓
Invitation envoyée (email)
        ↓
Le copropriétaire choisit son mot de passe
        ↓
users.id lié à owners.user_id
        ↓
Accès ouvert si une détention est active
```

- L'invitation est **révocable** et **expirable**.
- Un `owner` sans `user` reste pleinement fonctionnel côté gestion : le portail est un
  service, pas un prérequis.
- Aucun mot de passe par défaut n'est jamais généré ni communiqué.

> ⚠️ Les notifications par email (nouvel appel, relance, reçu de paiement) restent à
> définir — voir [14](14-hors-perimetre.md) q.7.
