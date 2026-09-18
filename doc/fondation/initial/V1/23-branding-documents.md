# 23 — Système visuel et documents imprimables

← [Sommaire](README.md)

**Statut : validé le 04/09/2026.** Implémenté dans
[`resources/css/app.css`](../../../../resources/css/app.css) et
[`vite.config.ts`](../../../../vite.config.ts).

> Ce fichier fait autorité. `systeme-visuel.html`, dans ce même dossier, en est le
> rendu visuel — utile à l'œil humain, **inutilisable par un agent** (810 Ko, dont le
> logo en base64). Ne pas le lire pour coder.

---

## 1. Les quatre arbitrages fondateurs

| Décision | Raison |
|---|---|
| **Le magenta corporate, pas le violet SYNDIC** | l'application porte l'identité de l'éditeur ; le violet reste au système de sous-marques du groupe |
| **Deux magentas** | `#FF007F` n'atteint que **3,8:1** sur blanc et échoue au seuil AA. Il reste graphique ; `#C4005F` (**6,0:1**) porte texte et actions |
| **Le dégradé sort des éléments interactifs** | il n'a ni état survolé ni état désactivé cohérents, et il domine un tableau dense |
| **Les couleurs d'état sortent de la plage de marque** | le dégradé magenta → orange occupe exactement la zone « attention / danger » |

---

## 2. Palette

Toutes les valeurs vivent dans `app.css`. **Aucun composant ne redéclare une couleur.**

### Marque

| Rôle | Clair | Sombre | Usage |
|---|---|---|---|
| Magenta vif | `#FF007F` | idem | logo, dégradé, graphiques — **jamais de texte** |
| Magenta d'action (`--primary`) | `#C4005F` | `#FF5C9E` | boutons, liens, focus, anneau |
| Orange | `#FF5500` | idem | fin de dégradé ; texte **uniquement** dans le bloc-marque |

> Sur fond sombre le magenta s'**éclaircit**, il ne s'assombrit pas. C'est pour cela
> que `--primary` change de valeur entre les thèmes plutôt que d'être opacifié.

### Neutres teintés prune

Aucun gris pur : chaque neutre tire vers `#1C1220`, ce qui évite l'effet « gabarit »
d'une grille grise sous un accent coloré.

| Jeton | Clair | Sombre |
|---|---|---|
| `--background` | `#FAF7F9` | `#141019` |
| `--card` · `--popover` | `#FFFFFF` | `#1D1724` |
| `--secondary` · `--muted` · `--accent` | `#F3EEF2` | `#261E2F` |
| `--border` · `--input` | `#E5DCE4` | `#332940` |
| `--foreground` | `#1C1220` | `#F3EEF5` |
| `--muted-foreground` | `#7A6A7C` | `#8E7F97` |

### Couleurs d'état

Exposées en utilitaires : `text-ok`, `bg-warn-surface`, `border-crit`, `text-info`…

| Rôle | Clair | Sombre | Emploi |
|---|---|---|---|
| `ok` | `#0E7C66` | `#4FD1AE` | lot soldé, appel payé, rapprochement équilibré |
| `warn` | `#A45B00` | `#E5A44B` | échéance dépassée, licence en grâce, écart à justifier |
| `crit` | `#9B2226` | `#F08A83` | mise en demeure, créance douteuse, lecture seule |
| `info` | `#3A5A9B` | `#8FAAE4` | brouillon, soumis, état neutre |

---

## 3. Typographie

Trois rôles, trois polices, auto-hébergées en woff2 via Bunny (`@fonts`).

| Classe | Police | Emploi |
|---|---|---|
| `font-display` | **Poppins** 500/600/700 | titres **uniquement** — voix de la marque |
| `font-sans` | **Inter** 400→700 | tout ce qui se lit : corps, formulaires, tableaux, montants |
| `font-mono` | **JetBrains Mono** 400/500 | références comptables |

> **Pourquoi ne pas tout composer en Poppins.** C'est la police de la charte, mais ses
> chiffres sont larges et mous à 13 px. Dans une colonne de montants, Inter est
> nettement plus lisible — et ses chiffres tabulaires s'alignent.

Le monospace n'est pas décoratif : `AF-2026-001`, `QUITT-2026-00042`, `3422` se lisent
caractère par caractère et se dictent au téléphone.

Libellés en capitales : 10,5 px, graisse 600, interlettrage +0,09 em.

---

## 4. Rayons, ombres, densité

`--radius: 0.5rem` → `sm` 4 px · `md` **6 px** (boutons) · `lg` 8 px · `xl` **13 px** (cartes).

Le prototype employait `rounded-3xl` partout ; sur un tableau de trois cents lots,
l'arrondi large mange la place et brouille l'alignement des colonnes.

**Une seule ombre, avec parcimonie.** Bordure, remplissage, rayon et ombre disent tous
« objet séparé ». Les empiler aplatit la hiérarchie. Seules les cartes et les fenêtres
modales portent une ombre ; **les lignes de tableau n'en ont jamais**.

---

## 5. Les deux blocs-marques

### En-tête de l'application — identité de l'éditeur

```
┌──────┐   bayan          Poppins 700 · 46 px · blanc · bas de casse
│  b   │   G E S T I O N  Poppins 500 · 19 px · orange · +0,34 em
└──────┘
   78 px      espace 20 px
```

### Documents imprimés — pôle émetteur

```
┌────┐  GESTION    Poppins 700 · 16 px · #1C1220 · +0,2 em
│ b  │  SYNDIC     Poppins 600 · 16 px · #FF5500
└────┘
 35 px   espace 8 px · interligne 3 px
```

**La hauteur du logo n'est pas une valeur fixe.** Le bloc est en `align-items: stretch`
avec `aspect-ratio: 1/1` sur le monogramme : il se cale seul sur la hauteur du texte.
Changer le corps de la typo n'oblige à recalculer aucun pixel.

L'orange porte du texte **ici seulement**. À 16 px en graisse 600, il relève du seuil
« grand texte » et devient conforme. L'exception ne se propage nulle part ailleurs.

### Variantes autorisées

| Variante | Emploi |
|---|---|
| Symbole seul | sidebar repliée, favicon, avatar, en-tête de document — la plus fréquente |
| Horizontal compact | en-tête d'application, écran de connexion |
| Monochrome | tampons, télédéclarations, impressions économiques |

**Interdits :** dans un bouton ou un badge (le monogramme identifie l'éditeur, pas une
action) ; recoloré, déformé, ou l'angle du dégradé modifié.

Zone de protection = largeur de la hampe du `b`. Taille minimale : 20 mm à
l'impression, 24 px à l'écran.

---

## 6. Documents produits

| Document | Source |
|---|---|
| Quittance / reçu | `payments` |
| Relevé de compte | projection du ledger ([09](09-compte-de-lot.md)) |
| Avis d'appel de fonds | `fund_call_lines` |
| Courrier de relance (4 niveaux) | `dunning_notices` ([17](17-recouvrement-relances.md)) |
| Convocation, feuille de présence, PV d'AG | `assemblies` ([16](16-assemblees-generales.md)) |
| Annexes légales 1 à 5 | `closing_statements` ([18](18-etats-financiers.md)) |
| Bordereau de passation | `handover_items` ([21](21-passation.md)) |

### Un document reflète un état, il ne le calcule pas

```
❌  le PDF recalcule le solde au moment de l'impression
✅  le PDF rend une projection figée, horodatée
```

Un document réimprimé six mois plus tard **doit être identique**.

| Cas | Mécanisme |
|---|---|
| Événement ponctuel (quittance) | rendu depuis l'entité, immuable par construction |
| Situation à une date (relevé, annexe) | **payload figé** à la génération |

### Numérotation

| Document | Format |
|---|---|
| Quittance | `QUITT-{année}-{séquence:5}` |
| Avis d'appel | `AVIS-{appel}-{lot}` |
| Relance | `REL-{niveau}-{année}-{séquence:4}` |
| PV d'AG | `PV-{année}-{séquence:3}` |

Séquences **sans trou**, attribuées à la génération, verrou en base
([00](00-principes.md) §7).

### Pied de page obligatoire

```
Résidence Greenwood · 12 rue des Orangers, Casablanca
Syndic : ABC Gestion SARL · ICE 001234567000089
Exercice 2026 · Édité le 04/09/2026 à 14:32 par Y. BENFATIH
QUITT-2026-00042
```

Acteur, horodatage et référence sont **obligatoires** : un document sans origine
traçable contredit le principe P3.

Tout document issu d'un exercice `open` porte la mention **« document provisoire »**.

### Impression

Le thème sombre ne s'imprime **jamais** : noir sur blanc, le dégradé réduit à un filet
de pied de page. A4, marges 15 mm. Les tableaux financiers ne se coupent pas au milieu
d'une ligne. En-tête et pied répétés, `page N / M`. Aucun élément d'interface dans la
feuille.

---

## 7. Archivage

Tout document généré est stocké et rattaché à son entité via `attachments`
(MinIO en local, S3 en production).

- Le chemin n'est **jamais** exposé : l'accès passe par une route signée qui repasse
  les trois verrous d'autorisation ([02](02-identite-roles.md) §3).
- Un document archivé n'est **jamais régénéré** : on le sert tel quel. Le régénérer
  produirait un fichier différent et détruirait sa valeur probante.

---

## 8. Reste à obtenir

| Élément | Impact |
|---|---|
| **Le lockup en vectoriel** (SVG / EPS) | bloquant à l'impression : seul du PNG 2000 px est disponible |
| **Les hexadécimaux de la charte** | plusieurs pastilles ne correspondent pas à leurs étiquettes, et `#F70u2e` n'est pas une couleur valide |
| La police de la signature manuscrite | optionnel : sans elle, la baseline se compose en Poppins |
| Un `logo.png` optimisé | 600 Ko actuellement ; le SVG réglerait les deux problèmes à la fois |

> **Écart connu.** Le SVG du prototype descend `#C71F67 → #D91C6E → #F26968 → #F27835`
> en quatre arrêts ; la charte annonce `#FF007F → #FF5500` en deux. Les deux doivent
> être alignés avant la première impression, sinon écran et papier ne montreront pas le
> même logo.
