# 23 — Branding et documents imprimables

← [Sommaire](README.md)

---

## 1. Identité visuelle

### Palette Bayan

| Rôle | Valeur |
|---|---|
| Fond nuit exécutif | `#1E0427` |
| Accent magenta royal | `#D91C6E` |
| Corail lumineux | `#F26968` |
| Orange énergie | `#F27835` |
| Blanc chaud / toile de fond | `#FDF8F5` |

Ces couleurs sont déclarées en **variables CSS**, jamais en classes utilitaires
codées en dur, afin que le thème clair / sombre et la personnalisation par résidence
opèrent sur un point unique.

### Personnalisation par résidence

```
residences.settings.branding
  logo_path            ← téléversé par le syndic (PNG, JPG, WebP, ≤ 4 Mo)
  couleur_primaire     ← optionnelle, sinon palette Bayan
  denomination_affichee
```

Le logo personnalisé remplace le placeholder sur les écrans de connexion, les
quittances, les relevés et les PV.

### Placeholder généré

Toute résidence sans logo reçoit des **armoiries SVG générées à la volée** : initiales
de la résidence sur un fond dérivé de la palette. Aucun fichier n'est stocké, aucune
résidence n'affiche jamais d'image cassée.

---

## 2. Documents produits

| Document | Source | Format |
|---|---|---|
| **Quittance / reçu de paiement** | `payments` | PDF A4 |
| **Relevé de compte copropriétaire** | projection ledger ([09](09-compte-de-lot.md)) | PDF A4 |
| **Appel de fonds** (avis au copropriétaire) | `fund_call_lines` | PDF A4 |
| **Courrier de relance** (4 niveaux) | `dunning_notices` ([17](17-recouvrement-relances.md)) | PDF A4 |
| **Convocation d'AG** | `assemblies` | PDF A4 |
| **Feuille de présence** | `assembly_attendances` | PDF A4 |
| **Procès-verbal d'AG** | `assemblies` | PDF A4 |
| **Annexes légales 1 à 5** | `closing_statements` ([18](18-etats-financiers.md)) | PDF A4 |
| **Bordereau de passation** | `handover_items` ([21](21-passation.md)) | PDF A4 |

---

## 3. Règles de génération

### Un document reflète un état, il ne le calcule pas

```
❌  Le PDF recalcule le solde au moment de l'impression
✅  Le PDF rend une projection figée, horodatée
```

Un document réimprimé six mois plus tard **doit être identique**. Deux mécanismes selon
le cas :

| Cas | Mécanisme |
|---|---|
| Le document constate un événement ponctuel (quittance) | rendu depuis l'entité, immuable par construction |
| Le document constate une situation à une date (relevé, annexe) | **payload figé** au moment de la génération |

### Numérotation

| Document | Format | Portée |
|---|---|---|
| Quittance | `QUITT-{année}-{séquence:5}` | résidence |
| Avis d'appel | `AVIS-{appel}-{lot}` | appel |
| Relance | `REL-{niveau}-{année}-{séquence:4}` | résidence |
| PV d'AG | `PV-{année}-{séquence:3}` | résidence |

Séquences **sans trou**, attribuées à la génération, verrou au niveau base
([00](00-principes.md) §7).

### Mention d'état

Tout document produit sur un exercice `open` porte la mention **« document
provisoire »**. Seuls les documents issus d'un exercice `closed` ou d'un événement
immuable en sont exempts.

---

## 4. Pied de page réglementaire

Chaque document porte, sans exception :

```
Résidence Greenwood · 12 rue des Orangers, Casablanca
Syndic : ABC Gestion SARL · ICE 001234567000089
Exercice 2026 · Édité le 04/09/2026 à 14:32 par Y. BENFATIH
Document généré par Bayan · QUITT-2026-00042
```

L'acteur, l'horodatage et la référence sont **obligatoires** : un document sans origine
traçable contredit le principe P3 ([00](00-principes.md)).

---

## 5. Archivage

Tout document généré est **stocké** et rattaché à son entité via `attachments`.

- Stockage objet S3 (MinIO en local, bucket dédié).
- Le chemin n'est **jamais** exposé directement : l'accès passe par une route signée
  qui repasse les trois verrous d'autorisation ([02](02-identite-roles.md) §3).
- Un document archivé n'est **jamais régénéré** : on le sert tel quel. La régénération
  produirait un fichier différent, ce qui casserait la valeur probante.

---

## 6. Impression

- Feuille A4, marges 15 mm, police à empattements pour le corps des documents légaux.
- Les tableaux financiers ne se coupent jamais au milieu d'une ligne.
- En-tête et pied répétés sur chaque page, avec `page N / M`.
- Aucun élément d'interface (bouton, navigation) dans la feuille imprimée.
