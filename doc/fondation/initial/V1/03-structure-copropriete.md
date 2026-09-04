# 03 — Structure de la copropriété

← [Sommaire](README.md)

---

## 1. Lots et tantièmes

Chaque lot porte un **tantième**, unique clé de répartition du système.

### 1.1 Décision actée — une seule clé de répartition

Pas de clé ascenseur, chauffage ou cage d'escalier en V1. Deux garde-fous peu coûteux
protègent l'avenir :

**1. Invariant de cohérence**
```
Σ lots.tantiemes  =  residences.total_tantiemes
```
Typiquement 1 000 ou 10 000. Attrape définitivement les erreurs de saisie.
Testé, et vérifié avant toute émission d'appel.

**2. Gel du tantième dans la ligne d'appel**
```
fund_call_lines.tantiemes_used
fund_call_lines.total_tantiemes_used
```
Les tantièmes changent (division de lot, modificatif du règlement). Sans ce gel, un
recalcul futur modifierait un appel passé. **Deux colonnes suffisent**, pas de table
de versioning.

> *Évolution :* si une deuxième clé devient nécessaire, la migration consiste à
> ajouter `repartition_keys` et à basculer l'existant sur une clé « générale ».
> Ne pas la construire maintenant.

### 1.2 Types de lot

`appartement` · `magasin` · `bureau` · `parking` · `cave` · `local_technique` · `autre`

Un lot porte : référence (`A102`), type, étage, bâtiment/cage (texte libre en V1),
superficie indicative, tantième.

---

## 2. Copropriétaires et détentions

Relation **plusieurs-à-plusieurs, datée**.

```
lots ──< lot_ownerships >── owners
             · quote_part      (points de base, 10 000 = 100 %)
             · nature
             · started_on / ended_on
```

- un copropriétaire peut détenir **plusieurs lots** ;
- un lot peut appartenir à **plusieurs copropriétaires**.

### 2.1 Nature juridique de la détention

| Nature | Sens |
|---|---|
| `pleine_propriete` | un seul détenteur, quote-part 10 000 |
| `indivision` | plusieurs détenteurs se partagent le lot (héritage, achat conjoint) |
| `usufruit` | détient l'usage et les fruits |
| `nue_propriete` | détient le titre sans l'usage |

**Une SCI n'est pas une nature** : c'est un `owner` de type `personne_morale`
détenant en `pleine_propriete`.

### 2.2 Invariants

```
Σ quote_part des détentions ACTIVES d'un lot à une date  =  10 000
```
sur l'axe `pleine_propriete` + `indivision`. L'usufruit et la nue-propriété forment un
axe parallèle, également à 10 000, sans se cumuler au premier.

Aucun chevauchement de deux détentions du même `owner` sur le même lot.

### 2.3 On ne remplace jamais, on ferme et on ouvre

```
❌ UPDATE lot_ownerships SET owner_id = ... 

✅ UPDATE ... SET ended_on = '2026-03-15'   (l'ancienne détention)
   INSERT ...     started_on = '2026-03-16' (la nouvelle)
```

On sait ainsi toujours qui détenait quoi à n'importe quelle date passée.

---

## 3. Le compte est celui du lot

### Décision actée — un seul compte par lot

Si `A102` est en indivision Ahmed 50 % / Fatima 50 % avec 4 000 DH d'impayé, il y a
**un compte à 4 000 DH**, pas deux comptes à 2 000 DH. La dette est indivisible face
au syndicat et les indivisaires sont solidaires.

```
Résidence
   └── Lot A102
          ├── lot_ownerships   Ahmed 5 000 bp · Fatima 5 000 bp
          └── lot_account      ← identité auxiliaire permanente
                 └── projection du ledger sur (342x, aux = lot_account)
```

Le paiement enregistre **qui a payé** (`paid_by_owner_id`) pour la traçabilité, mais
le solde reste celui du lot.

---

## 4. 🔒 Verrou n°3 — le débiteur est gelé

> **Ne jamais déterminer le débiteur d'une obligation depuis la table des détentions
> actuelles. C'est le piège le plus coûteux du domaine.**

Le problème :

```
01/01  Appel émis, échéance 31/03
15/03  Le lot est vendu
31/03  Échéance
       │
       └── Qui doit ? Si on lit lot_ownerships aujourd'hui,
           on répond « le nouveau propriétaire » — et on aura
           réécrit l'histoire à chaque mutation future.
```

### La règle

`fund_call_lines` porte, **figés à l'émission** :

| Champ | Contenu |
|---|---|
| `debtor_owner_id` | le détenteur redevable au moment de l'émission |
| `debtor_snapshot` | json : nom, quotes-parts, natures des détenteurs à cette date |
| `tantiemes_used` | le tantième appliqué |
| `total_tantiemes_used` | la base de répartition appliquée |

Le relevé d'un lot affiche donc, pour chaque ligne, le débiteur **d'origine**, même dix
mutations plus tard. La question « qui devait quoi en mars 2026 » a une réponse stable.

### Ce qui reste ouvert
Le **sort juridique** des arriérés à la mutation (le solde suit-il le lot, ou reste-t-il
à la charge du vendeur ?) dépend de la loi 18-00 — voir [14](14-hors-perimetre.md) q.1.

**Le gel du débiteur est nécessaire quelle que soit la réponse.** Il permet
d'implémenter l'une ou l'autre règle plus tard sans migration de données.

---

## 5. Mutations

Une mutation est un **événement**, pas une mise à jour.

`lot_mutations` enregistre : date d'effet, ancien(s) détenteur(s), nouveau(x),
**solde du compte à la date**, prix, pièce justificative (acte, attestation).

Elle permet au relevé d'afficher :

```
Solde repris de M. Ahmed au 15/03/2026 ............ 4 500,00 DH
   └── mutation MUT-2026-004 · acte de vente joint
```

au lieu d'un montant sans origine.

### Comportement V1

- La mutation **clôt** les détentions sortantes et **ouvre** les entrantes, à la date d'effet.
- Elle **ne transfère ni n'efface aucune créance**. Les lignes d'appel gardent leur
  `debtor_owner_id` d'origine.
- Le compte de lot est **continu** : son solde ne bouge pas du fait de la mutation.
- Une écriture de transfert de dette est un **ajustement motivé** distinct, décidé
  explicitement, jamais un effet de bord de la mutation.
