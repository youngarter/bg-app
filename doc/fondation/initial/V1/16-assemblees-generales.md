# 16 — Assemblées générales

← [Sommaire](README.md)

## 🔒 Décision d'architecture — l'AG est un registre, pas un moteur

> **L'assemblée générale ne pilote ni le vote du budget, ni la passation de mandat.**
>
> `budgets` et `mandates` restent des **entités autonomes**. L'AG les **référence**,
> elle ne les crée pas, ne les modifie pas, ne déclenche aucune transition d'état.

### Pourquoi

Coupler l'AG au budget et au mandat rendrait trois cas réels impossibles :

```
❌ Si l'AG pilotait                        ✅ Avec l'AG en registre

Budget saisi avant la tenue de l'AG        Le budget existe en draft, l'AG est
→ impossible                               enregistrée après et le référence

AG tenue mais PV pas encore rédigé         Le mandat vit sa propre machine à états,
→ mandat bloqué                            le PV est rattaché quand il arrive

Résidence reprise sans historique d'AG     Budgets et mandats existent sans AG
→ aucun budget possible                    référencée. Rien ne bloque.
```

C'est la même règle que « mandat ≠ accès » de [01](01-plateforme-licence.md) : un fait
juridique et un mécanisme applicatif sont deux choses distinctes qui se désynchronisent
en permanence.

### La règle en une phrase

> L'AG **constate et documente** des décisions. La **validation exécutoire** reste le
> mécanisme d'approbation du conseil syndical de [10](10-validation-conseil.md).

---

## 1. Modèle

```
assemblies
  id, residence_id, exercise_id (nullable), mandate_id
  type : ordinaire | extraordinaire
  held_on, lieu
  convocation_document_path, convocation_sent_at
  president_seance, secretaire_seance
  tantiemes_presents        ← quorum constaté
  tantiemes_representes
  ordre_du_jour             ← texte
  pv_texte, pv_document_path
  state : planifiee | tenue | pv_redige | archivee
  timestamps

assembly_resolutions
  id, assembly_id, ordre
  intitule
  majorite_requise : simple | absolue | deux_tiers | unanimite
  tantiemes_pour, tantiemes_contre, tantiemes_abstention
  resultat : adoptee | rejetee | reportee
  -- référence documentaire, JAMAIS un lien de commande
  references_type, references_id   (nullable, polymorphe)

assembly_attendances
  id, assembly_id, lot_id
  presence : present | represente | absent
  mandataire_owner_id (nullable)
  tantiemes                 ← GELÉS à la tenue de l'AG
```

### Le lien vers budget et mandat

`assembly_resolutions.references_type` / `references_id` pointe **facultativement** un
`Budget`, un `Mandate`, une `Expense` ou un `Project`.

**Ce lien est documentaire.** Il permet d'afficher « ce budget a été voté en AG du
15/03/2026, résolution n°3 ». Il ne déclenche rien, ne verrouille rien, et son absence
n'empêche aucune opération.

Invariant testé : **aucune Action de `budgets` ou de `mandates` ne lit la table
`assemblies`.** Vérifié par test d'architecture.

---

## 2. Quorum

Constaté sur les **tantièmes**, pas sur le nombre de personnes.

```
Quorum = Σ tantiemes des présences (present + represente)
         ─────────────────────────────────────────────────
                  residences.total_tantiemes
```

- Les tantièmes sont **gelés dans `assembly_attendances`** à la tenue de l'AG, comme
  pour les lignes d'appel ([03](03-structure-copropriete.md) §4). Une division de lot
  ultérieure ne doit pas modifier un quorum constaté.
- Le seuil légal dépend du type d'AG et de la majorité requise par résolution.
  **Configurable par résidence, jamais codé en dur** — la réglementation évolue.
- Le quorum est **affiché et alerté**, jamais bloquant : c'est au président de séance
  de décider si l'assemblée peut délibérer.

---

## 3. Cycle de vie

```
planifiee ──▶ tenue ──▶ pv_redige ──▶ archivee
    │           │
    │           └── présences et tantièmes GELÉS
    │               résolutions et votes saisis
    │
    └── convocation générée et envoyée
```

| De → Vers | Acteur | Conditions | Irréversible |
|---|---|---|---|
| — → `planifiee` | gérant | date, lieu, ordre du jour | non |
| `planifiee` → `tenue` | gérant | ≥ 1 présence saisie | **oui** (gel des tantièmes) |
| `tenue` → `pv_redige` | gérant / secrétaire | toutes les résolutions ont un résultat | non |
| `pv_redige` → `archivee` | gérant | PV signé téléversé | **oui** |

Une AG `archivee` est **immuable**. Une correction est une AG rectificative distincte.

---

## 4. Ce que l'AG produit

### Documents
- **Convocation** avec ordre du jour, générée et horodatée
- **Feuille de présence** avec tantièmes
- **Procès-verbal** — voir [23](23-branding-documents.md)

### Aucune écriture comptable
> **Une AG ne produit jamais de pièce comptable.** Aucun `source_type = Assembly`
> n'existe dans le ledger.

Si une résolution décide une dépense exceptionnelle, c'est une `Expense` saisie
séparément, qui peut référencer la résolution à titre documentaire.

---

## 5. Ce qui reste hors AG — récapitulatif

| Sujet | Traité par |
|---|---|
| Vote et approbation du budget | [05](05-budget.md) + [10](10-validation-conseil.md) |
| Élection et changement de syndic | [04](04-mandats-exercices.md) — `mandates` |
| Accès de la nouvelle société | [01](01-plateforme-licence.md) — accréditation admin |
| Transfert de situation entre mandats | [21](21-passation.md) |
| Passage d'une créance en perte | [17](17-recouvrement-relances.md) |

Chacun de ces objets peut **référencer** une résolution d'AG comme justification.
Aucun n'en **dépend** pour fonctionner.

> Conséquence pratique : une résidence peut être gérée dans Bayan sans qu'aucune AG
> n'ait jamais été saisie. Le module est utile, il n'est pas structurant.
