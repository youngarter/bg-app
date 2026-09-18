# 24 — Plan comptable officiel et points à faire valider

← [Sommaire](README.md)

---

## 1. Base réglementaire

| Texte | Objet |
|---|---|
| Loi n° 18-00 | Statut de la copropriété des immeubles bâtis |
| Loi n° 106-12 | Modifie et complète la loi 18-00 |
| **Décret n° 2-23-700** du 23 janvier 2025 | Règles comptables spécifiques aux syndicats de copropriétaires |
| Annexes du décret | États normalisés |
| Avis n° 25 du Conseil National de la Comptabilité | Précède le décret, même objet |

> ⚠️ **Ce fichier n'a pas été établi sur lecture du texte officiel.** Il consigne une
> nomenclature rapportée, non vérifiée à la source par l'équipe technique. Elle
> corrobore la numérotation que [08](08-ledger-comptable.md) avait déduite —
> `3421`–`3424` et `7111`–`7113` coïncident — mais une corroboration n'est pas une
> vérification. Le seed du plan comptable exige une confrontation au Bulletin
> officiel et la validation d'un expert-comptable marocain.

**Entrée en vigueur.** Le décret s'applique à compter du début de l'exercice suivant
sa publication au Bulletin officiel. Pour les copropriétés déjà en activité, la
période concernée et le sort des exercices antérieurs doivent être confirmés — cela
détermine à partir de quand un import historique doit respecter cette nomenclature.

---

## 2. Nomenclature

### Classe 1 — Provisions, fonds de réserve et résultat

| Compte | Intitulé |
|---|---|
| `1111` | Réserves pour dépenses imprévues |
| `1112` | Réserves pour dépenses prévues à long terme |
| `1191` | Résultat — excédent |
| `1199` | Résultat — déficit |
| `1311` | Subventions reçues |
| `1511` | Provisions pour travaux décidés |
| `1512` | Provisions pour litiges |
| `1513` | Provisions pour risque |
| `1514` | Provisions pour charge |

### Classe 3 — Créances

| Compte | Intitulé |
|---|---|
| `3411` | Fournisseurs débiteurs |
| `3412` | Fournisseurs, avances sur travaux |
| `3413` | Autres avances |
| `3421` | Copropriétaire individualisé |
| `3422` | Copropriétaire — budget prévisionnel |
| `3423` | Copropriétaire — travaux et opérations non courantes |
| `3424` | Copropriétaire — créances douteuses |
| `3451` | Subventions à recevoir |
| `3452` | État et autres organismes débiteurs |
| `3481` | Débiteurs divers |
| `3491` | Charges constatées d'avance |
| `3497` | Comptes transitoires / d'attente débiteurs |
| `3942` | Provisions pour dépréciation — copropriétaires |
| `3943` | Provisions pour dépréciation — autres créances |

### Classe 4 — Dettes

| Compte | Intitulé |
|---|---|
| `4411` | Fournisseurs |
| `4412` | Factures non parvenues |
| `4413` | Autres fournisseurs |
| `4421` | Copropriétaires — avances |
| `4431` | Rémunérations dues |
| `4441` | Sécurité sociale |
| `4442` | Autres organismes sociaux |
| `4452` | État — impôts et versements assimilés |
| `4453` | État et autres organismes créditeurs |
| `4481` | Créditeurs divers |
| `4491` | Compte en attente d'imputation |
| `4492` | Produits encaissés d'avance |
| `4497` | Comptes transitoires / d'attente créditeurs |

### Classe 5 — Trésorerie

| Compte | Intitulé |
|---|---|
| `5111` | Compte à terme |
| `5112` | Autres comptes de fonds placés |
| `5121` | Banques |
| `5122` | Chèques |
| `5161` | Caisse |
| `5541` | Banque — solde créditeur |

### Classes 6 et 7 — Charges et produits

| Compte | Intitulé |
|---|---|
| `611` | Achats de matières et fournitures |
| `612` | Autres charges |
| `613` / `614` | Services extérieurs |
| `616` | Impôts et taxes |
| `617` | Personnel |
| `651` | Travaux et opérations non courants (charge) |
| `691` | Dotations aux dépréciations |
| `7111` | Appels de fonds — provisions sur opérations courantes |
| `7112` | Appels de fonds — provisions sur travaux |
| `7113` | Appels de fonds — avances |
| `7121` | Emprunts |
| `7122` | Subventions |
| `7123` | Indemnités d'assurance |
| `7124` | Produits divers |
| `7125` | Produits financiers |
| `751` | Travaux et opérations non courants (produit) |
| `791` | Reprises de dépréciations |

> ⚠️ **Divergence à trancher.** [08](08-ledger-comptable.md) mentionnait `6514` et
> `7514` ; la nomenclature ci-dessus donne `651` et `751`. Confirmer le niveau de
> subdivision autorisé avant le seed.

---

## 3. Le résultat n'est pas affecté par le système

`1191` / `1199` constatent l'écart entre produits et charges **dans l'attente de la
décision de l'assemblée**. Solder les produits vers `119x` clôt le calcul, pas
l'exercice au sens décisionnel.

Quatre suites sont possibles, et **aucune ne doit être programmée comme règle
universelle avant validation** :

| Cas | Suite |
|---|---|
| A | Excédent affecté au syndicat |
| B | Excédent réparti entre copropriétaires selon les quotes-parts, en crédit individuel |
| C | Excédent reporté sur l'exercice suivant |
| D | Déficit régularisé — répartition, appel complémentaire ou autre traitement autorisé |

Le système **propose** et **enregistre** une décision ; il ne la déduit pas.

### Conséquence sur le découplage n° 2

[14](14-hors-perimetre.md) §2 découplait l'assemblée générale du budget et du mandat,
en la traitant comme un registre sans effet comptable direct. **Ce découplage ne tient
plus tel quel** : l'affectation du résultat est une décision d'assemblée qui produit
des écritures. L'assemblée reste découplée du budget et du mandat, mais elle acquiert
un effet comptable propre, sur le seul résultat.

---

## 4. Fonds de réserve ≠ résultat

`1111` / `1112` (réserves) et `1191` / `1199` (résultat) sont deux notions distinctes.
Une réserve n'est pas un résultat accumulé.

> ⚠️ **Un fonds de réserve ne porte pas de colonne `balance`** — verrou n° 1. Son solde
> est la lecture de `111x` dans le grand livre, exactement comme le compte de lot est
> la lecture de `342x`. Son historique — dotations, prélèvements, affectations — n'est
> pas une table parallèle : ce sont les écritures elles-mêmes, tracées par
> `source_type` / `source_id` vers la décision d'assemblée qui les autorise.
>
> Ce qui doit exister en propre, c'est la **décision** (objet, montant, autorité,
> PV), pas le solde.

À faire valider : conditions de création, règles d'alimentation, autorité requise pour
un prélèvement, traitement comptable d'une dépense financée par la réserve, et sort de
la réserve lors d'une passation de mandat.

---

## 5. Décision d'assemblée à effet comptable

Une assemblée ne se contente pas d'approuver les comptes. Elle prend des décisions
financières distinctes, chacune avec son propre effet.

```
AccountingDecision
  residence_id, exercise_id, assemblee_id
  decision_type : approbation des comptes | affectation d'excédent |
                  régularisation de déficit | constitution ou emploi de réserve |
                  travaux | autre
  montant, méthode de répartition
  résolution (référence PV), pièce, date, état
```

Cet objet est distinct de l'`ApprovalRequest` polymorphe de
[10](10-validation-conseil.md) : celle-ci porte une validation interne au syndic,
celui-là porte une décision souveraine des copropriétaires, opposable.

---

## 6. États réglementaires — le niveau dépend des produits

Le décret gradue les annexes exigées selon les produits de l'exercice :

| Produits de l'exercice | Annexes |
|---|---|
| ≥ 500 000 DH | 3 à 10 |
| > 200 000 et < 500 000 DH | 10, 11, 12 |
| ≤ 200 000 DH | 10, 13-1, 13-2 |

Il n'y a donc pas un bouton « générer le rapport annuel », mais un enchaînement :

```
Exercice → calcul des produits → détermination du palier →
génération des annexes requises → verrouillage → mise à disposition →
assemblée → décision → affectation
```

Ces seuils s'ajoutent aux états déjà prévus en [18](18-etats-financiers.md) : état de
situation financière, compte de gestion général, budget prévisionnel, comptes
individuels des copropriétaires.

---

## 7. Livres et intangibilité

Le décret impose un livre-journal, un grand livre et un livre d'inventaire, avec des
exigences de pièces justificatives et de conservation. Une écriture erronée s'annule
par une écriture contraire, jamais par modification.

C'est **exactement le verrou n° 4** de V1, déjà implémenté : `journal_entries`
immuables, correction par extourne (`reverses_entry_id`), pièce rattachée.

> ⚠️ Ne pas passer à un modèle `debit_account` / `credit_account` porté par l'écriture.
> Une écriture réelle a *n* lignes, pas deux — une répartition sur plusieurs natures de
> charges ou un règlement partiel multi-postes ne tiennent pas dans deux colonnes.
> V1 conserve `journal_entries` + `journal_entry_lines`, avec `Σ débit = Σ crédit`
> vérifié par pièce (invariant C1).

---

## 8. Points à faire valider par un professionnel

Aucun de ces points ne bloque le grand livre, qui est agnostique. Tous bloquent une
**automatisation irréversible**.

**Référentiel** — subdivisions autorisées ; jusqu'où créer des sous-comptes
analytiques ; traitement des auxiliaires par copropriétaire et par lot ; frontière
entre opérations courantes et non courantes ; divergence `651`/`6514` et `751`/`7514`.

**Appels de fonds** — écriture à l'émission, écriture à la date d'exigibilité, écriture
au paiement ; paiement partiel ; trop-perçu ; avoir ; créance ancienne, douteuse,
irrécouvrable.

**Résultat** — quand `1191`, quand `1199` ; comment l'assemblée affecte un excédent et
traite un déficit ; l'excédent peut-il devenir un crédit individuel de copropriétaire ;
quel compte reçoit l'affectation finale ; comment reporter sur N+1.

**Réserve** — voir §4.

**Travaux** — appel travaux, avance travaux, facture fournisseur, travaux décidés non
exécutés, avances aux fournisseurs, travaux urgents.

**Clôture** — date et procédure ; verrouillage ; correction après clôture ; réouverture
exceptionnelle ; écriture d'ouverture N+1 ; report des créances copropriétaires, des
dettes fournisseurs, des réserves et du résultat.

**Assemblée** — documents obligatoires et délais de mise à disposition ; approbation des
comptes ; vote du budget et des travaux ; affectation du résultat ; décision sur la
réserve ; procès-verbal ; effet juridique de la décision sur la comptabilité.

**Passation** — quels soldes transférer ; quel historique remettre ; transfert des
créances, des avances, de la réserve, des dettes fournisseurs ; date de coupure
comptable ; appels émis avant et après la passation ; paiements reçus après le
changement de syndic ; génération de l'état de passation. Voir [21](21-passation.md).
