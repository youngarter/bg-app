<?php

/**
 * ==============================================================================
 * SyndicPro Maroc — Initialisation des Schémas de Données SQLite (Air-Gap)
 * ==============================================================================
 * Ce fichier définit et instancie l'ensemble des schémas relationnels de la plateforme :
 *
 * 1. Base de Données Maître (data/master.sqlite) :
 *    - Registre central de l'éditeur Bayan Gestion.
 *    - Gestion des licences logicielles, durées d'abonnement, périodes de grâce.
 *    - Comptes super-administrateurs et journal d'audit système unifié.
 *
 * 2. Bases de Données Copropriétés Dédiées (data/tenants/{guid}.sqlite) :
 *    - Cloisonnement physique étanche (Air-Gap) pour chaque copropriété.
 *    - Conformité stricte au Dahir n° 1-02-298 du 25 Rejeb 1423 (Loi n° 18-00).
 *    - Registres cadastraux des lots et tantièmes (base standard de 10 000 tantièmes).
 *    - Comptabilité financière : appels de charges, encaissements, quittances libératoires.
 *    - Traçabilité des Assemblées Générales, votes de budgets et passations de mandats.
 *    - Bureau du conseil syndical et délégations de pouvoirs.
 * ==============================================================================
 */

declare(strict_types=1);

// ----------------------------------------------------------------------------
// 1. Initialisation du Schéma Master (data/master.sqlite)
// ----------------------------------------------------------------------------

/**
 * Établit la connexion PDO vers la base maître SQLite et crée les tables si nécessaires.
 *
 * Tables gérées :
 * - `super_admins` : Identifiants sécurisés des administrateurs de la console Bayan Gestion.
 * - `tenants`      : Registre des copropriétés, métadonnées de licence, verrous de paiement.
 * - `system_logs`  : Journal d'audit infalsifiable des événements de supervision.
 *
 * @return PDO Instance PDO connectée avec le mode d'exception activé.
 *
 * @throws PDOException Si le fichier master.sqlite ne peut pas être créé ou écrit.
 */
function getMasterPdo(): PDO
{
    // Localisation du répertoire des données de l'application
    $dataDir = __DIR__;
    $masterDbPath = $dataDir.DIRECTORY_SEPARATOR.'master.sqlite';

    // Instanciation du connecteur SQLite natif PHP avec exceptions levées en cas d'erreur
    $masterPdo = new PDO('sqlite:'.$masterDbPath);
    $masterPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Définition DDL des tables maîtresses
    $masterPdo->exec("
    -- Table des Super Administrateurs Bayan Gestion
    CREATE TABLE IF NOT EXISTS super_admins (
        id TEXT PRIMARY KEY,               -- Identifiant unique (ex: superadmin-01)
        email TEXT UNIQUE NOT NULL,        -- Adresse email unique de connexion
        password_hash TEXT NOT NULL,       -- Empreinte Bcrypt sécurisée du mot de passe
        nom TEXT NOT NULL,                 -- Nom complet de l'administrateur
        role TEXT NOT NULL DEFAULT 'super_admin', -- Rôle d'autorisation
        date_creation TEXT NOT NULL        -- Horodatage ISO de création du compte
    );

    -- Table du Registre Multi-Tenant des Copropriétés
    CREATE TABLE IF NOT EXISTS tenants (
        id TEXT PRIMARY KEY,               -- GUID v4 RFC 4122 étanche de la résidence
        slug TEXT,                         -- Identifiant textuel d'URL (ex: jardins-atlas)
        nom TEXT NOT NULL,                 -- Nom commercial de la copropriété
        ville TEXT NOT NULL,               -- Ville marocaine (Casablanca, Tanger, Marrakech...)
        code_unique TEXT NOT NULL,         -- Code interne de référence comptable
        nom_syndic TEXT NOT NULL,          -- Nom du syndic en exercice responsable
        email_syndic TEXT NOT NULL,        -- Email de notification et de login du syndic
        statut TEXT NOT NULL DEFAULT 'actif', -- Statut du tenant : 'actif', 'suspendu', 'resilie'
        plan TEXT NOT NULL DEFAULT 'premium', -- Gamme tarifaire : 'standard', 'premium', 'enterprise'
        license_duration_months INTEGER NOT NULL DEFAULT 12, -- Durée contractuelle en mois
        license_start_date TEXT NOT NULL,  -- Date de début d'effet de la licence (YYYY-MM-DD)
        license_expiry_date TEXT NOT NULL, -- Date d'expiration nominale de la licence
        grace_period_end_date TEXT NOT NULL, -- Fin de période de grâce (+30 jours après expiration)
        faulty_payment_lock INTEGER NOT NULL DEFAULT 0, -- Verrou d'impayé activé par l'éditeur (0/1)
        lock_reason TEXT,                  -- Motif du verrouillage lecture seule si applicable
        date_creation TEXT NOT NULL,       -- Date de provisionnement initial
        derniere_activite TEXT NOT NULL    -- Horodatage de la dernière opération enregistrée
    );

    -- Table d'Audit Trail et Logs Système
    CREATE TABLE IF NOT EXISTS system_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT, -- Identifiant incrémental de l'événement
        timestamp TEXT NOT NULL,           -- Horodatage précis de l'action (YYYY-MM-DD HH:MM:SS)
        action TEXT NOT NULL,              -- Code d'action (PROVISION, RENEW, LOCK, LOGIN...)
        tenant_id TEXT,                    -- GUID de la copropriété concernée (ou null)
        details TEXT NOT NULL              -- Description textuelle détaillée de l'opération
    );
    ");

    return $masterPdo;
}

// ----------------------------------------------------------------------------
// 2. Fonction de Création d'une Base Dédiée pour un Tenant ({guid}.sqlite)
// ----------------------------------------------------------------------------

/**
 * Initialise le fichier de base de données partitionné SQLite d'une copropriété.
 * Crée l'ensemble des tables métier conformes au droit marocain de la copropriété (Loi 18-00).
 *
 * @param  string  $guid  GUID v4 RFC 4122 identifiant de manière unique la copropriété.
 * @param  array  $residence  Métadonnées de la copropriété (nom, adresse, ville, RIB, syndic...).
 * @param  string  $syndicPassword  Mot de passe en clair du compte administrateur du syndic.
 * @param  bool  $isDemo  Si true, peuple avec des jeux de données d'exemple pour Atlas.
 *
 * @throws PDOException En cas d'échec de création du schéma SQLite.
 */
function initializeTenantDatabase(string $guid, array $residence, string $syndicPassword, bool $isDemo = false): void
{
    // S'assurer de l'existence du dossier de stockage physique étanche
    $tenantsDir = __DIR__.DIRECTORY_SEPARATOR.'tenants';
    if (! is_dir($tenantsDir)) {
        mkdir($tenantsDir, 0777, true);
    }

    // Connexion à la base SQLite spécifique de la résidence
    $dbPath = $tenantsDir.DIRECTORY_SEPARATOR.$guid.'.sqlite';
    $pdo = new PDO('sqlite:'.$dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Définition DDL des tables de la copropriété
    $pdo->exec("
    -- 1. Fiche d'Identité Juridique et Financière de la Résidence
    CREATE TABLE IF NOT EXISTS residence (
        id TEXT PRIMARY KEY,               -- GUID du tenant
        nom TEXT NOT NULL,                 -- Nom officiel de la copropriété
        code_unique TEXT NOT NULL,         -- Code interne d'immatriculation
        adresse TEXT,                      -- Adresse physique de l'immeuble
        ville TEXT NOT NULL,               -- Ville de situation de l'immeuble
        code_postal TEXT,                  -- Code postal
        titre_foncier_mere TEXT,           -- Numéro de Titre Foncier Mère à la Conservation Foncière
        rib_bancaire TEXT NOT NULL,        -- RIB bancaire officiel 24 chiffres de la copropriété
        banque TEXT NOT NULL,              -- Établissement bancaire teneur du compte
        nom_syndic TEXT NOT NULL,          -- Nom complet de la personne physique ou morale syndic
        qualite_syndic TEXT DEFAULT 'Syndic en exercice', -- Qualité juridique déclarée
        telephone_syndic TEXT,             -- Ligne téléphonique directe du syndic
        email_syndic TEXT NOT NULL,        -- Adresse email officielle du syndic
        date_creation TEXT NOT NULL,       -- Date de mise en service
        total_tantiemes INTEGER DEFAULT 10000, -- Assiette globale des tantièmes (standard 10 000)
        solde_banque_initial REAL DEFAULT 0,   -- Trésorerie bancaire à l'ouverture de l'exercice (DH)
        solde_caisse_initial REAL DEFAULT 0,   -- Espèces en caisse à l'ouverture de l'exercice (DH)
        fond_travaux_initial REAL DEFAULT 0,   -- Réserve Fonds de Travaux Art. 18 Loi 18-00 (DH)
        logo_url TEXT                      -- Chemin ou URL de l'armoirie / logo officiel
    );

    -- 2. Utilisateurs et Accès Authentifiés (Syndic & Résidents)
    CREATE TABLE IF NOT EXISTS users (
        id TEXT PRIMARY KEY,               -- Identifiant de compte (ex: user-syndic-xxxx)
        email TEXT UNIQUE NOT NULL,        -- Email ou identifiant convivial (prenom.nom@tag)
        password_hash TEXT NOT NULL,       -- Hachage Bcrypt du mot de passe
        nom TEXT NOT NULL,                 -- Nom et prénom de l'utilisateur
        role TEXT NOT NULL CHECK(role IN ('syndic', 'resident')), -- Profil d'accès
        coproprietaire_id TEXT,            -- Référence vers la fiche copropriétaire liée si résident
        telephone TEXT,                    -- Numéro de contact mobile
        date_creation TEXT NOT NULL        -- Date de création du profil
    );

    -- 3. Registre Cadastral des Lots de Copropriété (Appartements, Parkings, Boutiques)
    CREATE TABLE IF NOT EXISTS lots (
        id TEXT PRIMARY KEY,               -- Identifiant technique du lot (ex: lot-101)
        numero TEXT NOT NULL,              -- Numéro d'appartement ou désignation (ex: 101, A2)
        immeuble TEXT DEFAULT 'Principal', -- Bloc ou immeuble au sein de la copropriété
        etage INTEGER DEFAULT 0,           -- Niveau d'étage (0 pour RDC)
        type TEXT DEFAULT 'appartement',   -- Type de bien : 'appartement', 'magasin', 'parking', 'cave'
        tantiemes INTEGER NOT NULL,        -- Quote-part indivise exprimée en tantièmes sur 10 000
        surface REAL DEFAULT 0,            -- Surface privative en mètres carrés (m²)
        coproprietaire_id TEXT             -- Référence du copropriétaire détenteur du titre
    );

    -- 4. Registre Nominatif des Copropriétaires
    CREATE TABLE IF NOT EXISTS coproprietaires (
        id TEXT PRIMARY KEY,               -- Identifiant technique (ex: cop-1111)
        civilite TEXT DEFAULT 'M.',        -- Civilité ('M.', 'Mme', 'Sté')
        nom TEXT NOT NULL,                 -- Nom de famille ou raison sociale
        prenom TEXT,                       -- Prénom pour personnes physiques
        cin TEXT,                          -- Numéro de Carte d'Identité Nationale ou RC
        telephone TEXT,                    -- Téléphone principal
        email TEXT,                        -- Email de correspondance
        est_resident INTEGER DEFAULT 1,    -- 1 = Résident occupant, 0 = Bailleur non-occupant (MRE)
        solde_initial REAL DEFAULT 0       -- Solde antérieur à la reprise de gestion (+/- DH)
    );

    -- 5. Appels de Fonds & Cotisations Trimestrielles (Art. 18 & 25 Loi 18-00)
    CREATE TABLE IF NOT EXISTS appels_fonds (
        id TEXT PRIMARY KEY,               -- Identifiant technique (ex: app-xxxx)
        numero TEXT NOT NULL,              -- Numéro de pièce comptable (ex: APP-2026-T1)
        type TEXT DEFAULT 'charges_courantes', -- Nature : 'charges_courantes' ou 'travaux_exceptionnels'
        exercice INTEGER NOT NULL,         -- Année fiscale concernée (ex: 2026)
        periode TEXT,                      -- Trimestre ou mois (ex: 'Trimestre 1', 'Annuel')
        date_exigibilite TEXT NOT NULL,    -- Date limite réglementaire de règlement
        montant_total REAL NOT NULL,       -- Montant total voté en AG à répartir
        description TEXT,                  -- Intitulé de l'appel pour affichage
        statut TEXT DEFAULT 'exigible'     -- État : 'exigible', 'cloture'
    );

    -- 6. Encaissements, Règlements & Quittances Libératoires
    CREATE TABLE IF NOT EXISTS paiements (
        id TEXT PRIMARY KEY,               -- Identifiant technique du règlement
        numero_quittance TEXT NOT NULL,    -- Numéro officiel de quittance libératoire (QUITT-XXXX)
        coproprietaire_id TEXT NOT NULL,   -- Débiteur ayant effectué le versement
        lot_id TEXT,                       -- Lot auquel se rattache le versement
        montant REAL NOT NULL,             -- Montant encaissé en Dirhams (DH)
        date_paiement TEXT NOT NULL,       -- Date de valeur de l'encaissement (YYYY-MM-DD)
        mode_paiement TEXT NOT NULL,       -- 'virement', 'cheque', 'versement', 'especes'
        reference TEXT,                    -- Référence bancaire du chèque ou virement
        affectation TEXT DEFAULT 'charges_courantes' -- Imputation comptable
    );

    -- 7. Dépenses d'Exploitation & Factures Fournisseurs
    CREATE TABLE IF NOT EXISTS depenses (
        id TEXT PRIMARY KEY,               -- Identifiant de la dépense
        date TEXT NOT NULL,                -- Date de comptabilisation de la facture
        fournisseur_nom TEXT NOT NULL,     -- Raison sociale du prestataire ou organisme public
        description TEXT NOT NULL,         -- Description des travaux ou fournitures
        categorie TEXT NOT NULL,           -- Rubrique de ventilation budgétaire
        montant_ht REAL NOT NULL,          -- Montant Hors Taxes (DH)
        montant_ttc REAL NOT NULL,         -- Montant Toutes Taxes Comprises (DH)
        exercice INTEGER NOT NULL,         -- Exercice comptable de rattachement
        statut_paiement TEXT DEFAULT 'paye', -- 'paye', 'en_attente'
        piece_justificative TEXT           -- Nom du fichier justificatif téléversé (PDF/JPG)
    );

    -- 8. Annuaire des Prestataires & Fournisseurs Agréés
    CREATE TABLE IF NOT EXISTS fournisseurs (
        id TEXT PRIMARY KEY,               -- Identifiant du fournisseur
        nom TEXT NOT NULL,                 -- Raison sociale
        activite TEXT NOT NULL,            -- Corps de métier (Ascenseurs, Électricité, Gardiennage...)
        telephone TEXT,                    -- Contact téléphonique
        email TEXT,                        -- Email de contact
        ice TEXT,                          -- Identifiant Commun de l'Entreprise (ICE Maroc)
        statut TEXT DEFAULT 'actif'        -- 'actif', 'inactif'
    );

    -- 9. Carnet d'Entretien de l'Immeuble (Obligation Légale Art. 26 Loi 18-00)
    CREATE TABLE IF NOT EXISTS carnet_entretien (
        id TEXT PRIMARY KEY,               -- Identifiant de l'entrée d'entretien
        equipement TEXT NOT NULL,          -- Équipement commun concerné (Ascenseur, Bâche à eau...)
        description TEXT NOT NULL,         -- Nature de l'intervention technique (préventif/curatif)
        date_intervention TEXT NOT NULL,   -- Date de réalisation de l'opération
        prestataire TEXT NOT NULL,         -- Société ayant exécuté la prestation
        cout REAL DEFAULT 0,               -- Coût de l'intervention (DH)
        statut TEXT DEFAULT 'realise'      -- 'programme', 'en_cours', 'realise'
    );

    -- 10. Registre des Assemblées Générales & Passations de Mandat (Art. 20 Loi 18-00)
    CREATE TABLE IF NOT EXISTS assemblees (
        id TEXT PRIMARY KEY,               -- Identifiant de la séance d'AG
        date TEXT NOT NULL,                -- Date de tenue de l'assemblée
        type TEXT DEFAULT 'ordinaire',     -- 'ordinaire' (AGO) ou 'extraordinaire' (AGE)
        lieu TEXT,                         -- Lieu de réunion physique ou distanciel
        statut TEXT DEFAULT 'cloturee',    -- 'convoquee', 'tenue', 'cloturee'
        description TEXT,                  -- Intitulé de la séance
        ordre_du_jour TEXT,                -- Résumé textuel de l'ordre du jour
        tantiemes_presents INTEGER DEFAULT 8500, -- Quorum vérifié en tantièmes
        changement_syndic INTEGER DEFAULT 0,     -- 1 si élection d'un nouveau syndic (Art. 20)
        nouveau_syndic_nom TEXT,           -- Identité du syndic nouvellement élu
        nouveau_syndic_email TEXT,         -- Email pour création automatique du compte administrateur
        nouveau_syndic_tel TEXT,           -- Téléphone du nouveau syndic
        date_effet_mandat TEXT,            -- Date de prise de fonction officielle
        tresorerie_arretee REAL DEFAULT 0, -- Bilan de trésorerie contradictoire lors de la remise
        president_seance TEXT,             -- Copropriétaire ayant présidé la séance
        secretaire_seance TEXT,            -- Secrétaire de séance désigné
        pv_texte TEXT,                     -- Procès-verbal formel et résolutions votées
        exercice INTEGER DEFAULT 2026,     -- Exercice budgétaire approuvé
        budget_annuel_vote REAL DEFAULT 0, -- Montant total du budget voté en dirhams
        frequence_appels TEXT DEFAULT 'trimestrielle', -- Périodicité ('mensuelle', 'trimestrielle'...)
        budget_rubriques TEXT DEFAULT '{}' -- Répartition JSON sur les 8 rubriques standard
    );

    -- 11. Tickets d'Incidents & Réclamations Techniques des Résidents
    CREATE TABLE IF NOT EXISTS reclamations (
        id TEXT PRIMARY KEY,               -- Identifiant du ticket
        titre TEXT NOT NULL,               -- Objet succinct de la réclamation
        description TEXT NOT NULL,         -- Description des désordres constatés
        priorite TEXT DEFAULT 'normale',   -- 'basse', 'normale', 'urgente'
        statut TEXT DEFAULT 'recu',        -- 'recu', 'en_cours', 'resolu', 'rejete'
        date_creation TEXT NOT NULL,       -- Horodatage d'ouverture du ticket
        reponse_syndic TEXT,               -- Commentaire ou plan d'action consigné par le syndic
        auteur TEXT                        -- Nom du copropriétaire déclarant
    );

    -- 12. Grands Travaux & Opérations Exceptionnelles
    CREATE TABLE IF NOT EXISTS projets (
        id TEXT PRIMARY KEY,               -- Identifiant du projet de travaux
        titre TEXT NOT NULL,               -- Intitulé des travaux (ex: Ravalement façade)
        description TEXT,                  -- Cahier des charges et résolutions d'AG
        budget_estime REAL NOT NULL,       -- Enveloppe budgétaire prévisionnelle (DH)
        date_debut TEXT,                   -- Date de démarrage du chantier
        date_fin_prevue TEXT,              -- Échéance contractuelle d'achèvement
        statut TEXT DEFAULT 'en_cours'     -- 'vote', 'en_cours', 'termine'
    );

    -- 13. Délégations Syndicales & Bureau du Conseil Syndical (Art. 18 & 26 Loi 18-00)
    CREATE TABLE IF NOT EXISTS delegates (
        id TEXT PRIMARY KEY,               -- Identifiant de la délégation
        user_id TEXT NOT NULL,             -- Compte utilisateur bénéficiaire
        coproprietaire_id TEXT NOT NULL,   -- Fiche copropriétaire du membre du bureau
        titre_role TEXT NOT NULL,          -- Rôle type : 'vice_syndic', 'comptable', 'secretaire'
        role_label TEXT NOT NULL,          -- Libellé d'affichage (ex: 'Vice-Syndic Adjoint')
        permissions TEXT NOT NULL,         -- Liste JSON des modules autorisés
        statut TEXT NOT NULL DEFAULT 'actif', -- Statut de la délégation : 'actif', 'suspendu'
        date_nomination TEXT NOT NULL,     -- Date d'habilitation par le syndic
        notes TEXT,                        -- Remarques et attributions spécifiques
        FOREIGN KEY(user_id) REFERENCES users(id),
        FOREIGN KEY(coproprietaire_id) REFERENCES coproprietaires(id)
    );
    ");

    // Enregistrement de la fiche d'identité principale de la copropriété
    $stmt = $pdo->prepare('INSERT OR REPLACE INTO residence VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $guid,
        $residence['nom'],
        $residence['code_unique'],
        $residence['adresse'] ?? '',
        $residence['ville'],
        $residence['code_postal'] ?? '20000',
        $residence['titre_foncier_mere'] ?? '',
        $residence['rib_bancaire'],
        $residence['banque'],
        $residence['nom_syndic'],
        $residence['qualite_syndic'] ?? 'Syndic en exercice',
        $residence['telephone_syndic'] ?? '+212 6 00 00 00 00',
        $residence['email_syndic'],
        $residence['date_creation'] ?? date('Y-m-d'),
        $residence['total_tantiemes'] ?? 10000,
        $residence['solde_banque_initial'] ?? 0,
        $residence['solde_caisse_initial'] ?? 0,
        $residence['fond_travaux_initial'] ?? 0,
        $residence['logo_url'] ?? null,
    ]);

    // Enregistrement du compte Administrateur Syndic principal
    $userStmt = $pdo->prepare('INSERT OR REPLACE INTO users VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $userStmt->execute([
        'user-syndic-'.substr($guid, 0, 8),
        $residence['email_syndic'],
        password_hash($syndicPassword, PASSWORD_BCRYPT),
        $residence['nom_syndic'],
        'syndic',
        null,
        $residence['telephone_syndic'] ?? '',
        date('Y-m-d H:i:s'),
    ]);

    // Données de démonstration pour la résidence Atlas si sollicitée en mode démo
    if ($isDemo) {
        $cop1 = 'cop-1111';
        $cop2 = 'cop-2222';
        $cop3 = 'cop-3333';
        $copStmt = $pdo->prepare('INSERT INTO coproprietaires VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $copStmt->execute([$cop1, 'M.', 'EL AMRANI', 'Mehdi', 'BE458912', '+212 6 61 11 22 33', 'mehdi.elamrani@gmail.com', 1, 0]);
        $copStmt->execute([$cop2, 'Mme', 'BENNANI', 'Khadija', 'A128901', '+212 6 62 44 55 66', 'khadija.bennani@gmail.com', 1, 0]);
        $copStmt->execute([$cop3, 'M.', 'TAHIRI', 'Anas', 'C981245', '+212 6 63 77 88 99', 'anas.tahiri@gmail.com', 0, -2500]);

        // Comptes résidents conviviaux
        $userStmt->execute([
            'user-res-1',
            'mehdi.elamrani@atlas',
            password_hash('resident2026', PASSWORD_BCRYPT),
            'Mehdi EL AMRANI',
            'resident',
            $cop1,
            '+212 6 61 11 22 33',
            date('Y-m-d H:i:s'),
        ]);
        $userStmt->execute([
            'user-res-1-short',
            'mehdi@atlas',
            password_hash('resident2026', PASSWORD_BCRYPT),
            'Mehdi EL AMRANI',
            'resident',
            $cop1,
            '+212 6 61 11 22 33',
            date('Y-m-d H:i:s'),
        ]);
        $userStmt->execute([
            'user-res-1-email',
            'mehdi.elamrani@gmail.com',
            password_hash('resident2026', PASSWORD_BCRYPT),
            'Mehdi EL AMRANI',
            'resident',
            $cop1,
            '+212 6 61 11 22 33',
            date('Y-m-d H:i:s'),
        ]);

        // Lots privatifs
        $lotStmt = $pdo->prepare('INSERT INTO lots VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $lotStmt->execute(['lot-101', '101', 'Immeuble A', 1, 'appartement', 3500, 115.5, $cop1]);
        $lotStmt->execute(['lot-102', '102', 'Immeuble A', 1, 'appartement', 3200, 98.0, $cop2]);
        $lotStmt->execute(['lot-201', '201', 'Immeuble A', 2, 'appartement', 3300, 105.0, $cop3]);

        // Historique des encaissements
        $payStmt = $pdo->prepare('INSERT INTO paiements VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $payStmt->execute(['pay-01', 'QUITT-2025-001', $cop1, 'lot-101', 3500, '2025-01-10', 'virement', 'VIR-BMCE-8912', 'charges_courantes']);
        $payStmt->execute(['pay-02', 'QUITT-2025-002', $cop2, 'lot-102', 3200, '2025-01-12', 'cheque', 'CHQ-4589214', 'charges_courantes']);
        $payStmt->execute(['pay-03', 'QUITT-2025-003', $cop1, 'lot-101', 3500, '2025-04-05', 'virement', 'VIR-BMCE-9402', 'charges_courantes']);

        // Dépenses courantes
        $depStmt = $pdo->prepare('INSERT INTO depenses VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $depStmt->execute(['dep-01', '2025-01-15', 'Atlas Ascenseurs SARL', 'Maintenance mensuelle ascenseur principal', 'Ascenseur', 1800, 2160, 2025, 'paye', 'fact-01.pdf']);
        $depStmt->execute(['dep-02', '2025-02-01', 'Gardiennage & Sécurité Maroc', 'Prestation sécurité et conciergerie Janvier', 'Nettoyage / Sécurité', 4500, 5400, 2025, 'paye', 'fact-02.pdf']);
        $depStmt->execute(['dep-03', '2025-02-20', 'Lydec Casablanca', 'Facture électricité parties communes Janvier', 'Électricité', 1250, 1425, 2025, 'paye', 'lydec-01.pdf']);

        // Fournisseurs
        $fournStmt = $pdo->prepare('INSERT INTO fournisseurs VALUES (?, ?, ?, ?, ?, ?, ?)');
        $fournStmt->execute(['f-01', 'Atlas Ascenseurs SARL', 'Maintenance ascenseurs & monte-charges', '+212 5 22 40 10 20', 'contact@atlas-ascenseurs.ma', '001589412000045', 'actif']);
        $fournStmt->execute(['f-02', 'Gardiennage & Sécurité Maroc', 'Surveillance 24/7 & accueil', '+212 5 22 25 30 40', 'contact@securite-maroc.ma', '002489114000088', 'actif']);
        $fournStmt->execute(['f-03', 'Lydec Casablanca', 'Distribution eau & électricité', '05 22 54 55 55', 'contact@lydec.co.ma', '000014785000012', 'actif']);

        // Ticket de réclamation
        $recStmt = $pdo->prepare('INSERT INTO reclamations VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $recStmt->execute(['rec-01', 'Éclairage palier 2ème étage défaillant', 'Les 2 spots du couloir clignotent depuis 2 jours.', 'urgente', 'en_cours', '2025-05-10', 'Électricien mandaté pour intervention demain matin.', 'Mehdi EL AMRANI']);

        // Projet de ravalement
        $projStmt = $pdo->prepare('INSERT INTO projets VALUES (?, ?, ?, ?, ?, ?, ?)');
        $projStmt->execute(['proj-01', 'Ravalement de façade et étanchéité terrasse', 'Travaux de rénovation votés lors de l\'AG extraordinaire du 15 Octobre 2024.', 85000, '2025-03-01', '2025-08-31', 'en_cours']);
    }
}

// ----------------------------------------------------------------------------
// 3. Exécution Autonome en Ligne de Commande (CLI Seeding)
// ----------------------------------------------------------------------------
if (php_sapi_name() === 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    $masterPdo = getMasterPdo();

    // Création du compte Super Administrateur racine si inexistant
    $stmt = $masterPdo->prepare('SELECT COUNT(*) FROM super_admins WHERE email = ?');
    $stmt->execute(['admin@syndicpro.ma']);
    if ($stmt->fetchColumn() == 0) {
        $ins = $masterPdo->prepare('INSERT INTO super_admins (id, email, password_hash, nom, role, date_creation) VALUES (?, ?, ?, ?, ?, ?)');
        $ins->execute([
            'superadmin-01',
            'admin@syndicpro.ma',
            password_hash('master2026', PASSWORD_BCRYPT),
            'Direction Générale SyndicPro',
            'super_admin',
            date('Y-m-d H:i:s'),
        ]);
    }

    echo "Bases SQLite Master et Tenants initialisées avec succès.\n";
}
