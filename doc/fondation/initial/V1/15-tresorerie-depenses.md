# 15 — Trésorerie, dépenses et fournisseurs

← [Sommaire](README.md)

Les fichiers [06](06-appels-de-fonds.md) à [09](09-compte-de-lot.md) décrivent **ce que
les copropriétaires doivent**. Celui-ci décrit **l'argent réel** : où il est, d'où il
vient, où il part.

---

## 1. Comptes de trésorerie

```
treasury_accounts
  type          banque | caisse | compte_sur_carnet
  affectation   fonctionnement | investissement | mixte
  account_id    compte du plan (512x / 516x)
  libelle, banque, rib
  solde_initial + date_solde_initial
  dedie_fonds_travaux : bool
  actif : bool
```

### Règles

- Le compte appartient à la **résidence**, jamais au syndic. Au changement de syndic,
  le compte peut être clôturé et un nouveau ouvert : c'est un **virement de trésorerie
  tracé**, jamais une réécriture de l'historique.
- L'`affectation` isole le fonds travaux du fonctionnement et permet d'en surveiller le
  solde séparément.
- **Une caisse ne peut jamais être négative** — invariant bloquant.
  Une banque peut l'être si un découvert est autorisé — alerte, non bloquante.

---

## 2. Ce qui alimente la trésorerie

| Source | Effet trésorerie | Effet compte de lot |
|---|---|---|
| Paiement d'un copropriétaire | débit (entrée) | crédit |
| Dépense payée | crédit (sortie) | **aucun** |
| Virement entre comptes | crédit sur l'un, débit sur l'autre | aucun |
| Autre produit (location d'un local, intérêts, indemnité d'assurance) | débit | aucun |
| Solde d'ouverture (import ou passation) | débit ou crédit | aucun |
| Ajustement motivé | débit ou crédit | aucun |

**Une écriture de trésorerie sans `source_type` résolvable est interdite.**

---

## 3. Virements entre comptes

Opération courante : alimenter la caisse depuis la banque, transférer vers le compte
sur carnet du fonds travaux.

```
transfers
  · from_treasury_account_id · to_treasury_account_id
  · montant · effective_date
  · motif · document_path
  · state : draft → posted → (reversed)

Posting :   DÉBIT  trésorerie destination
            CRÉDIT trésorerie origine
```

Un virement **ne touche jamais** un compte de lot ni un compte de charge.
Invariant testé : une pièce `source_type = Transfer` ne mouvemente que des comptes
`512x` / `516x`.

---

## 4. Dépenses et dettes fournisseurs

Une dépense a **deux moments distincts**, et c'est ce qui rend la dette fournisseur
calculable.

```
draft ──▶ recorded ──────────▶ paid
             │                   │
             │                   └── l'argent sort :
             │                       écriture de trésorerie produite
             │
             └── la facture existe : la résidence DOIT cet argent,
                 mais rien n'a bougé en trésorerie
```

> **Dettes fournisseurs = Σ des dépenses `recorded` non encore `paid`.**
> C'est une **requête**, pas une saisie manuelle, pas une table.

### Modèle

```
suppliers
  nom, ice, rc, adresse, telephone, email, rib
  account_id   sous-compte 441x (auxiliaire)

expenses
  supplier_id, exercise_id, mandate_id
  budget_line_id (nullable)     ← alimente le budget vs réalisé
  account_id                    ← compte de charge (61xx / 65xx)
  montant
  document_date                 ← date de la facture
  effective_date                ← date de rattachement comptable
  facture_document_path         ← OBLIGATOIRE dès recorded
  state, paid_at
  treasury_account_id (nullable), moyen, reference   ← renseignés au paiement
```

### Postings

| Étape | Débit | Crédit |
|---|---|---|
| `recorded` | compte de charge `61xx` / `65xx` | `441x` *(aux : fournisseur)* |
| `paid` | `441x` *(aux : fournisseur)* | `512x` / `516x` |

**Conformément à la décision D7, les dépenses n'impactent jamais les comptes des lots.**
Elles alimentent le suivi budget / réalisé et la trésorerie.

---

## 5. Écritures types — extension du contrat

Complète le tableau de [08](08-ledger-comptable.md) §4. Chaque ligne est un test.

| Événement métier | Débit | Crédit |
|---|---|---|
| Facture — eau, électricité, énergie | `6111` / `6112` / `6113` | `441x` *(aux : fournisseur)* |
| Facture — nettoyage | `6131` | `441x` |
| Facture — maintenance, réparations | `6134` / `6135` | `441x` |
| Facture — assurance | `6136` | `441x` |
| **Rémunération du syndic** | `6137` | `441x` |
| Frais bancaires | `6141` | `512x` |
| Salaires et charges du personnel | `6171` / `6172` | `441x` / `512x` |
| Travaux décidés en AG | `6511` | `441x` |
| Travaux urgents | `6512` | `441x` |
| Paiement d'une facture | `441x` *(aux : fournisseur)* | `512x` / `516x` |
| Virement entre comptes | trésorerie destination | trésorerie origine |
| Indemnité d'assurance reçue | `512x` | `7123` |
| Produits financiers | `512x` | `7125` |
| Subvention reçue | `512x` | `7122` |
| Don reçu | `512x` | `7513` |
| Dotation au fonds travaux | `7112` | `13x` Réserves |

---

## 6. Rapprochement bancaire

Opération centrale du métier de syndic : confronter les mouvements Bayan au relevé de
la banque. **Manuel en V1** — l'import CSV / MT940 reste hors périmètre.

```
Rapprochement · Compte BMCE · 01/01/2026 → 31/03/2026

  Solde Bayan au 31/03      142 350,00 DH
  Solde du relevé           139 850,00 DH
  Écart                       2 500,00 DH
        └── chèque n°102 émis le 28/03, non encore débité

  État : draft ──▶ balanced ──▶ closed
```

```
reconciliations
  treasury_account_id, started_on, ended_on
  solde_bayan, solde_releve, ecart
  state : draft | balanced | closed
  releve_document_path
```

### Règles

- Chaque `journal_entry_line` de trésorerie porte un `reconciliation_id` une fois pointée.
- Passage à `balanced` seulement si l'écart est **nul ou intégralement justifié** par
  des mouvements en attente identifiés.
- Un rapprochement `closed` **verrouille** les écritures qu'il a pointées : elles ne
  peuvent plus être extournées sans rouvrir le rapprochement.
- Un rapprochement à jour sur tous les comptes est un **contrôle bloquant** de la
  clôture d'exercice ([04](04-mandats-exercices.md) §4).

---

## 7. Situation de trésorerie

Écran attendu à tout instant.

```
TRÉSORERIE · Résidence Greenwood · au 04/09/2026

  Banque BMCE (fonctionnement)        142 350,00
  Caisse                                3 200,00
  Compte sur carnet (fonds travaux)   380 000,00
  ──────────────────────────────────────────────
  Total disponible                    525 550,00

  − Dettes fournisseurs               − 47 800,00
  ──────────────────────────────────────────────
  Disponible net                      477 750,00

  Pour mémoire :
  Créances copropriétaires             198 400,00   (attendu, non encaissé)
```

**La distinction encaissé / attendu doit rester visible en permanence.** Un solde
confortable accompagné de 200 000 DH de créances n'est pas la même situation qu'un
solde identique sans impayés.

---

## 8. Fonds travaux

Réserve pour gros travaux (toiture, ascenseur, façade), alimentée par les appels de
nature `travaux`. Elle ne doit pas financer le fonctionnement courant.

| Niveau | Mécanisme | Ce que ça garantit | V1 |
|---|---|---|---|
| 1. Comptable | compte de **réserve au passif** (`13x`) | on sait ce que la réserve vaut | ✅ |
| 2. Analytique | `treasury_accounts.affectation` | on sait où l'argent est censé être | ✅ |
| 3. Physique | compte bancaire **dédié** | on ne *peut pas* dépenser la réserve par erreur | ✅ optionnel |

Le niveau 3 est une **règle de gestion activable** : si un compte porte
`dedie_fonds_travaux = true`, toute tentative d'y imputer une dépense de fonctionnement
est **bloquée**.

### Contrôle permanent

`réserve fonds travaux au passif` comparée à la `trésorerie affectée investissement`.
Si la réserve n'est plus couverte, c'est que le fonds a été consommé par le
fonctionnement — **alerte immédiate, visible du conseil syndical**.

> ⚠️ La loi 18-00 impose-t-elle un compte bancaire dédié ? Voir
> [14](14-hors-perimetre.md) q.5. Si oui, le niveau 3 devient obligatoire.
