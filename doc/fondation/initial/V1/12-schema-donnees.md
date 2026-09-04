# 12 — Schéma de données V1

← [Sommaire](README.md)

**Types :** montants en `bigint` (**centimes**), quotes-parts en `int` (**points de
base**, 10 000 = 100 %), dates en `date`, horodatages en `datetime`.

Toutes les tables métier portent `residence_id` et sont scopées globalement.

---

## 1. Plateforme et identité

```
users
  id, name, email, password, two_factor_*, locale
  is_platform_admin: bool
  timestamps

syndic_companies
  id, nom, forme_juridique, ice, rc, adresse, telephone, email
  timestamps

syndic_company_user
  id, syndic_company_id, user_id
  role: gerant | gestionnaire | comptable
  timestamps
  unique (syndic_company_id, user_id)

residences
  id, nom, adresse, ville
  total_tantiemes: int (défaut 1000)
  devise: char(3) (défaut MAD)
  settings: json          -- critères de déclassement, préférences
  timestamps
```

## 2. Licence et accès

```
licenses
  id, residence_id
  plan, starts_on, ends_on
  grace_days: int (défaut 30)
  status: active | grace | read_only | suspended
  payer: syndic | copropriete
  timestamps
  unique (residence_id)

license_events
  id, license_id
  type: created | renewed | suspended | reactivated | expired
  effective_at, actor_user_id, note
  created_at

residence_accesses
  id, residence_id, syndic_company_id
  status: active | revoked
  granted_at, granted_by_admin_id
  revoked_at, revoked_by_admin_id, revoked_motif, revoked_document_path
  export_generated_at                     -- bloquant à la révocation
  timestamps
  index (residence_id, status)

residence_roles
  id, residence_id, user_id
  role: president_conseil | membre_conseil
  started_on, ended_on (nullable)
  granted_by_user_id, pv_ag_document_path
  timestamps
  index (residence_id, role, started_on, ended_on)

audit_logs
  id, residence_id (nullable), actor_user_id
  action, auditable_type, auditable_id
  motif, document_path
  ip, user_agent, created_at
  -- INSERT ONLY
```

## 3. Structure de la copropriété

```
lots
  id, residence_id
  reference, type, batiment, etage, superficie
  tantiemes: int
  timestamps
  unique (residence_id, reference)

owners
  id, residence_id
  user_id (nullable)                      -- lien identité, optionnel
  type: personne_physique | personne_morale
  nom, prenom, raison_sociale
  cin, ice, telephone, email, adresse
  timestamps
  index (residence_id)

lot_ownerships
  id, lot_id, owner_id
  quote_part: int                         -- points de base
  nature: pleine_propriete | indivision | usufruit | nue_propriete
  started_on, ended_on (nullable)
  document_path
  timestamps
  index (lot_id, started_on, ended_on)

lot_accounts
  id, lot_id                              -- identité auxiliaire permanente
  code                                    -- auxiliaire du grand livre
  timestamps
  unique (lot_id)

lot_mutations
  id, lot_id
  effective_date
  outgoing_snapshot: json                 -- détenteurs sortants
  incoming_snapshot: json                 -- détenteurs entrants
  balance_at_date: bigint                 -- solde du compte à la date
  prix: bigint (nullable)
  document_path
  created_by_user_id, timestamps
```

## 4. Mandats et exercices

```
mandates
  id, residence_id, syndic_company_id
  started_on, ended_on
  state: draft | active | suspended | terminated | expired | closed
  pv_ag_reference, pv_ag_document_path
  honoraires: bigint (nullable)
  terminated_motif, terminated_document_path
  timestamps

exercises
  id, residence_id, mandate_id
  label: string                           -- "2025", "2025-A"  JAMAIS un entier
  started_on, ended_on
  state: open | closing | closed
  closed_at, closed_by_user_id
  timestamps
  index (residence_id, started_on, ended_on)
```

## 5. Budget

```
budgets
  id, residence_id, exercise_id
  type: fonctionnement                    -- investissement en V2
  state: draft | submitted | approved | rejected | superseded
  montant_total: bigint
  timestamps

budget_lines
  id, budget_id, account_id
  libelle, montant_prevu: bigint, ordre: int
  timestamps
```

## 6. Appels de fonds

```
fund_calls
  id, residence_id, mandate_id, exercise_id, budget_id
  reference                               -- AF-2026-001
  nature: courant | travaux | avance
  libelle
  montant_total: bigint
  effective_date, due_date
  state: draft | submitted | approved | rejected | issued | reversed
  issued_at, issued_by_user_id
  reversed_at, reversed_motif
  timestamps
  unique (residence_id, reference)

fund_call_lines
  id, fund_call_id, lot_account_id
  montant: bigint
  due_date

  tantiemes_used: int                     -- 🔒 GELÉ à l'émission
  total_tantiemes_used: int               -- 🔒 GELÉ
  debtor_owner_id                         -- 🔒 GELÉ
  debtor_snapshot: json                   -- 🔒 GELÉ

  payment_status: open | partial | paid | cancelled     -- CALCULÉ
  recovery_status: normal | doubtful | impaired | written_off | recovered
  recovery_changed_at, recovery_motif

  timestamps
  index (lot_account_id, payment_status, due_date)
```

## 7. Trésorerie et paiements

```
treasury_accounts
  id, residence_id, account_id
  type: banque | caisse
  libelle, banque, rib
  solde_initial: bigint, date_solde_initial
  actif: bool
  timestamps

payments
  id, residence_id, mandate_id, exercise_id
  reference                               -- PAY-2026-00042
  lot_account_id, paid_by_owner_id (nullable)
  treasury_account_id
  montant: bigint
  moyen: especes | cheque | virement | tpe | en_ligne
  bank_reference
  effective_date, document_date (nullable)
  piece_document_path
  state: draft | confirmed | reversed
  confirmed_at, reversed_at, reversed_motif
  timestamps
  unique (residence_id, reference)
  index (lot_account_id, effective_date)

payment_allocations
  id, payment_id, fund_call_line_id
  montant: bigint
  methode: fifo | manuelle
  created_by_user_id
  cancelled_at, cancelled_by_user_id, cancel_motif
  timestamps
  index (fund_call_line_id, cancelled_at)
```

## 8. Ledger

```
accounts                                  -- PLAN VERROUILLÉ, chargé par seed
  id, code, intitule
  parent_id (nullable)
  nature: actif | passif | charge | produit
  requires_auxiliary: bool
  is_system: bool                         -- non supprimable, non modifiable
  timestamps
  unique (code)

journal_entries
  id, residence_id, mandate_id, exercise_id
  journal: string                         -- AC, TR, OD
  reference                               -- AC-2026-000042
  libelle
  effective_date, posted_at
  source_type, source_id                  -- NOT NULL
  reverses_entry_id (nullable)
  created_by_user_id
  created_at
  unique (residence_id, reference)
  unique (reverses_entry_id)              -- une pièce extournée une seule fois
  index (residence_id, exercise_id, effective_date)
  -- INSERT ONLY

journal_entry_lines
  id, journal_entry_id, account_id
  auxiliary_type, auxiliary_id (nullable) -- lot_accounts en V1
  debit: bigint (défaut 0)
  credit: bigint (défaut 0)
  libelle
  check (debit = 0 OR credit = 0)
  index (account_id, auxiliary_type, auxiliary_id)
  -- INSERT ONLY

adjustments
  id, residence_id, mandate_id, exercise_id
  lot_account_id (nullable)
  debit_account_id, credit_account_id
  montant: bigint
  effective_date
  motif                                   -- OBLIGATOIRE
  document_path                           -- OBLIGATOIRE
  state: draft | posted | reversed
  created_by_user_id, posted_at
  timestamps

attachments                               -- polymorphe
  id, residence_id
  attachable_type, attachable_id
  path, nom_original, mime, taille
  uploaded_by_user_id, timestamps
  index (attachable_type, attachable_id)
```

## 9. Validation

```
approval_requests
  id, residence_id
  approvable_type, approvable_id          -- liste blanche testée
  status: pending | approved | rejected | withdrawn
  submitted_by_user_id, submitted_at
  decided_at
  timestamps
  index (approvable_type, approvable_id)

approval_decisions
  id, approval_request_id, user_id
  role                                    -- rôle AU MOMENT de la décision
  decision: approved | rejected
  comment                                 -- obligatoire si rejected
  decided_at
  -- INSERT ONLY
```


## 10. Trésorerie étendue, dépenses et fournisseurs

```
treasury_accounts        (étendu — voir 07 pour la forme minimale)
  + affectation : fonctionnement | investissement | mixte
  + dedie_fonds_travaux : bool

transfers
  id, residence_id, mandate_id, exercise_id
  from_treasury_account_id, to_treasury_account_id
  montant: bigint, effective_date
  motif, document_path
  state: draft | posted | reversed
  created_by_user_id, timestamps

suppliers
  id, residence_id, account_id
  nom, ice, rc, adresse, telephone, email, rib
  actif: bool, timestamps

expenses
  id, residence_id, mandate_id, exercise_id
  supplier_id, account_id
  budget_line_id (nullable), project_id (nullable)
  montant: bigint
  document_date, effective_date
  facture_document_path
  state: draft | recorded | paid | reversed
  paid_at, treasury_account_id (nullable), moyen, reference
  timestamps
  index (residence_id, state)

reconciliations
  id, residence_id, treasury_account_id
  started_on, ended_on
  solde_bayan: bigint, solde_releve: bigint, ecart: bigint
  state: draft | balanced | closed
  releve_document_path
  closed_at, closed_by_user_id, timestamps

journal_entry_lines
  + reconciliation_id (nullable)   -- pointage bancaire
```

## 11. Recouvrement

```
dunning_notices
  id, residence_id, lot_account_id
  niveau: rappel | relance | mise_en_demeure | precontentieux
  montant_reclame: bigint          -- GELÉ
  lines_snapshot: json             -- GELÉ
  emitted_on, due_by
  canal: courrier | email | remise_en_main | recommande_ar
  document_path, accuse_reception_path (nullable)
  state: draft | sent | acknowledged | closed
  created_by_user_id, timestamps

fund_call_lines
  + dunning_level: int (défaut 0)  -- niveau atteint, dénormalisé pour le tri
```

## 12. Assemblées générales

```
assemblies
  id, residence_id, mandate_id, exercise_id (nullable)
  type: ordinaire | extraordinaire
  held_on, lieu
  convocation_document_path, convocation_sent_at
  president_seance, secretaire_seance
  tantiemes_presents: int, tantiemes_representes: int
  ordre_du_jour, pv_texte, pv_document_path
  state: planifiee | tenue | pv_redige | archivee
  timestamps

assembly_resolutions
  id, assembly_id, ordre: int
  intitule
  majorite_requise: simple | absolue | deux_tiers | unanimite
  tantiemes_pour, tantiemes_contre, tantiemes_abstention
  resultat: adoptee | rejetee | reportee
  references_type, references_id (nullable)   -- DOCUMENTAIRE uniquement

assembly_attendances
  id, assembly_id, lot_id
  presence: present | represente | absent
  mandataire_owner_id (nullable)
  tantiemes: int                    -- GELÉS à la tenue
```

## 13. Exploitation technique

```
claims
  id, residence_id, lot_id (nullable), reported_by_owner_id
  categorie, urgence, objet, description, localisation
  state: ouverte | prise_en_charge | en_cours | resolue | fermee | rejetee
  assigned_supplier_id (nullable), expense_id (nullable)
  resolved_at, resolution_note, timestamps

claim_events
  id, claim_id, user_id
  type: commentaire | changement_etat | affectation | piece_jointe
  contenu, created_at
  -- INSERT ONLY

projects
  id, residence_id, exercise_id (nullable)
  intitule, description
  budget_prevu: bigint
  date_debut_prevue, date_fin_prevue, date_debut_reelle, date_fin_reelle
  supplier_id (nullable), assembly_resolution_id (nullable)
  state: envisage | vote | planifie | en_cours | receptionne | abandonne
  timestamps

maintenance_records
  id, residence_id
  type: contrat | intervention | controle_reglementaire
      | garantie | diagnostic | sinistre
  intitule, description, equipement
  supplier_id (nullable), expense_id (nullable)
  date_evenement, date_echeance (nullable), periodicite_mois (nullable)
  document_path, timestamps
```

## 14. Délégations, passation, import, états figés

```
delegations
  id, residence_id, owner_id, user_id
  titre: vice_syndic | tresorier | secretaire | delegue
  modules: json                     -- liste blanche testée
  started_on, ended_on
  pv_ag_document_path, granted_by_user_id
  state: active | revoquee
  timestamps

handovers
  id, residence_id
  outgoing_mandate_id, incoming_mandate_id
  effective_date
  state: draft | reviewed | finalized
  reviewed_at, finalized_at, finalized_by_user_id
  approval_request_id, opening_entry_id (nullable)
  timestamps

handover_items
  id, handover_id
  categorie: creances | tresorerie | dettes_fournisseurs
           | actifs | documents | autres_soldes
  libelle
  montant_calcule (nullable), montant_declare (nullable), ecart
  justification_ecart
  source_type, source_id (nullable), document_path

import_batches
  id, residence_id
  type: lots | owners | balances | treasury | mandates | suppliers | history
  filename, file_path
  state: uploaded | mapped | validated | committed | reverted | failed
  stats: json, created_by_user_id, committed_at, timestamps

import_rows
  id, import_batch_id, line_number
  payload: json
  state: pending | valid | error | committed | skipped
  errors: json
  entity_type, entity_id (nullable)

import_mappings
  id, import_batch_id
  source_column, target_field, transformation (nullable)

closing_statements
  id, residence_id, exercise_id
  type: situation_financiere | compte_gestion_general
      | annexe_1 | annexe_2 | annexe_3 | annexe_4 | annexe_5
  payload: json                     -- FIGÉ
  document_path
  generated_at, generated_by_user_id, approval_request_id
```

---

## 15. Récapitulatif des tables

| Domaine | Tables |
|---|---|
| Identité | `users`, `syndic_companies`, `syndic_company_user`, `residences` |
| Licence & accès | `licenses`, `license_events`, `residence_accesses`, `residence_roles`, `audit_logs` |
| Structure | `lots`, `owners`, `lot_ownerships`, `lot_accounts`, `lot_mutations` |
| Temps | `mandates`, `exercises` |
| Budget | `budgets`, `budget_lines` |
| Appels | `fund_calls`, `fund_call_lines` |
| Encaissement | `treasury_accounts`, `payments`, `payment_allocations` |
| Ledger | `accounts`, `journal_entries`, `journal_entry_lines`, `adjustments`, `attachments` |
| Validation | `approval_requests`, `approval_decisions` |
| Trésorerie & dépenses | `transfers`, `suppliers`, `expenses`, `reconciliations` |
| Recouvrement | `dunning_notices` |
| Assemblées | `assemblies`, `assembly_resolutions`, `assembly_attendances` |
| Exploitation | `claims`, `claim_events`, `projects`, `maintenance_records` |
| Délégations | `delegations` |
| Passation | `handovers`, `handover_items` |
| Import | `import_batches`, `import_rows`, `import_mappings` |
| États figés | `closing_statements` |

## 16. Les 10 entités fondamentales

Le noyau que tout le reste sert :

```
Residence · Mandate · Exercise · Lot · LotOwnership
FundCall · FundCallLine · Payment · PaymentAllocation · JournalEntryLine
```

## 17. Tables INSERT ONLY

`journal_entries` · `journal_entry_lines` · `approval_decisions` · `audit_logs` ·
`license_events` · `claim_events`

Aucun `UPDATE`, aucun `DELETE`. Vérifié par test d'architecture **et** par restriction
des droits de l'utilisateur SQL applicatif en production.
