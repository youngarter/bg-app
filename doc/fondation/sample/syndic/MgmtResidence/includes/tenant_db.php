<?php

/**
 * ==============================================================================
 * SyndicPro Maroc — Couche d'Accès aux Données Dédiées du Tenant (TenantDB)
 * ==============================================================================
 * Ce module constitue le cœur opérationnel de l'application Syndic (MgmtResidence).
 * Il encapsule toutes les opérations d'accès et de manipulation de la base SQLite
 * dédiée à une copropriété spécifique (data/tenants/{guid}.sqlite) :
 *
 * 1. Cloisonnement Physique Strict (Air-Gap) :
 *    - Résolution dynamique et hermétique du GUID de la copropriété.
 *    - Connexion PDO dédiée sans risque de fuite de données inter-résidences.
 *
 * 2. Moteur Comptable & Règle d'Étanchéité Financière (Loi 18-00) :
 *    - Calcul des quotes-parts d'appels de charges selon l'assiette des 10 000 tantièmes.
 *    - Émission de quittances libératoires officielles horodatées.
 *    - Absence totale de dettes ou créances fictives en l'absence d'appels votés.
 *
 * 3. Gestion des Assemblées Générales & Passation de Mandat (Art. 20) :
 *    - Enregistrement du budget prévisionnel annuel et ventilation sur 8 rubriques.
 *    - Protocole automatique de passation de mandat lors de l'élection d'un nouveau syndic.
 *
 * 4. Délégations Syndicales & Contrôle d'Accès Granulaire (RBAC) :
 *    - Habilitation des copropriétaires au bureau syndical (Vice-Syndic, Comptable, Secrétaire).
 *    - Vérification des permissions par module avant toute action d'écriture.
 * ==============================================================================
 */

declare(strict_types=1);

require_once __DIR__.'/formatters.php';

class TenantDB
{
    /**
     * @var PDO|null Instance PDO en cache vers la base dédiée du tenant courant.
     */
    private static ?PDO $pdo = null;

    /**
     * @var string|null GUID de la copropriété résolu pour la requête courante.
     */
    private static ?string $currentGuid = null;

    /**
     * @var array|null Cache des métadonnées de licence du tenant depuis master.sqlite.
     */
    private static ?array $tenantMeta = null;

    /**
     * Résout l'identifiant unique universel (GUID v4) de la copropriété active.
     *
     * Ordre d'évaluation en cascade :
     * 1. Cache statique en mémoire si déjà résolu.
     * 2. Paramètre GET 'tenant' (ex: ?tenant=e2b819f4-...).
     * 3. Motif regex dans le chemin d'URL (ex: /Syndic/MgmtResidence/e2b819f4-.../).
     * 4. Variable de session active 'tenant_guid'.
     * 5. Repli : première copropriété active enregistrée dans data/master.sqlite.
     *
     * @return string GUID v4 de la copropriété.
     *
     * @throws RuntimeException Si aucun GUID valide ne peut être identifié.
     */
    public static function resolveGuid(): string
    {
        // 1. Retour immédiat si le GUID a déjà été résolu dans ce cycle de vie PHP
        if (self::$currentGuid !== null) {
            return self::$currentGuid;
        }

        // 2. Vérification dans les paramètres d'URL (query string)
        $guid = $_GET['tenant'] ?? '';

        // 3. Analyse du chemin de l'URI avec expression régulière pour capturer un GUID v4
        if (empty($guid)) {
            $uri = $_SERVER['REQUEST_URI'] ?? '';
            if (preg_match('#\b([a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})\b#i', $uri, $m)) {
                $guid = $m[1];
            }
        }

        // 4. Vérification de la persistance en session utilisateur
        if (empty($guid) && ! empty($_SESSION['tenant_guid'])) {
            $guid = $_SESSION['tenant_guid'];
        }

        // 5. Repli : extraction du premier tenant valide dans le registre maître
        if (empty($guid)) {
            $masterPath = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'master.sqlite';
            if (file_exists($masterPath)) {
                $mPdo = new PDO('sqlite:'.$masterPath);
                $first = $mPdo->query('SELECT id FROM tenants ORDER BY date_creation ASC LIMIT 1')->fetchColumn();
                if ($first) {
                    $guid = $first;
                }
            }
        }

        // Blocage de sécurité si aucun tenant n'est déterminé
        if (empty($guid)) {
            exit("<div style='font-family:sans-serif;padding:2rem;text-align:center;color:#b91c1c;'>
                    <h2>Erreur Critique d'Aiguillage</h2>
                    <p>Identifiant de copropriété manquant (GUID non spécifié).</p>
                    <a href='/Syndic/'>Retour au portail d'accueil</a>
                 </div>");
        }

        self::$currentGuid = $guid;

        return $guid;
    }

    /**
     * Réinitialise le cache statique de connexion et de métadonnées.
     * Indispensable lors des tests multi-tenants ou de l'exécution de scripts CLI en boucle.
     */
    public static function resetCache(): void
    {
        self::$pdo = null;
        self::$currentGuid = null;
        self::$tenantMeta = null;
    }

    /**
     * Récupère les métadonnées de la copropriété depuis le registre maître master.sqlite.
     * Évalue dynamiquement le statut de licence (active, période de grâce ou lecture seule).
     *
     * @return array Tableau associatif des métadonnées du tenant avec booléen 'isReadOnly'.
     */
    public static function getTenantMeta(): array
    {
        if (self::$tenantMeta !== null) {
            return self::$tenantMeta;
        }

        $guid = self::resolveGuid();
        $masterPath = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'master.sqlite';
        $mPdo = new PDO('sqlite:'.$masterPath);
        $mPdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $stmt = $mPdo->prepare('SELECT * FROM tenants WHERE id = ?');
        $stmt->execute([$guid]);
        $meta = $stmt->fetch();

        if (! $meta) {
            exit('Erreur : Copropriété avec le GUID '.htmlspecialchars($guid).' introuvable dans le registre.');
        }

        // Évaluation des dates d'expiration et de grâce
        $now = time();
        $graceEnd = strtotime($meta['grace_period_end_date'] ?? '2027-01-31');

        // Verrouillage en lecture seule si verrou forcé d'impayé OU dépassement de période de grâce
        $isReadOnly = (! empty($meta['faulty_payment_lock'])) || ($now > $graceEnd);
        $meta['isReadOnly'] = $isReadOnly;

        self::$tenantMeta = $meta;

        return $meta;
    }

    /**
     * Détermine si la copropriété est verrouillée en mode Lecture Seule.
     *
     * @return bool True si toute mutation est interdite, false si les écritures sont autorisées.
     */
    public static function isReadOnly(): bool
    {
        $meta = self::getTenantMeta();

        return ! empty($meta['isReadOnly']);
    }

    /**
     * Établit ou retourne la connexion PDO vers la base dédiée du tenant (tenants/{guid}.sqlite).
     * Applique automatiquement les migrations DDL si de nouvelles colonnes ont été introduites.
     *
     * @return PDO Instance PDO connectée à la base de la copropriété.
     *
     * @throws RuntimeException Si le fichier de base SQLite du tenant n'existe pas.
     */
    public static function getPdo(): PDO
    {
        if (self::$pdo === null) {
            $guid = self::resolveGuid();
            $dbPath = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'tenants'.DIRECTORY_SEPARATOR.$guid.'.sqlite';

            if (! file_exists($dbPath)) {
                exit("<div style='font-family:sans-serif;padding:2rem;text-align:center;'>
                        <h2>Base de Données Non Initialisée</h2>
                        <p>La base de données de cette copropriété n'a pas été créée.</p>
                     </div>");
            }

            self::$pdo = new PDO('sqlite:'.$dbPath);
            self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Migration incrémentale à chaud des colonnes sans interruption de service
            $alterCols = [
                'ALTER TABLE residence ADD COLUMN logo_url TEXT',
                'ALTER TABLE assemblees ADD COLUMN ordre_du_jour TEXT',
                'ALTER TABLE assemblees ADD COLUMN tantiemes_presents INTEGER DEFAULT 8500',
                'ALTER TABLE assemblees ADD COLUMN changement_syndic INTEGER DEFAULT 0',
                'ALTER TABLE assemblees ADD COLUMN nouveau_syndic_nom TEXT',
                'ALTER TABLE assemblees ADD COLUMN nouveau_syndic_email TEXT',
                'ALTER TABLE assemblees ADD COLUMN nouveau_syndic_tel TEXT',
                'ALTER TABLE assemblees ADD COLUMN date_effet_mandat TEXT',
                'ALTER TABLE assemblees ADD COLUMN tresorerie_arretee REAL DEFAULT 0',
                'ALTER TABLE assemblees ADD COLUMN president_seance TEXT',
                'ALTER TABLE assemblees ADD COLUMN secretaire_seance TEXT',
                'ALTER TABLE assemblees ADD COLUMN pv_texte TEXT',
                'ALTER TABLE assemblees ADD COLUMN exercice INTEGER DEFAULT 2026',
                'ALTER TABLE assemblees ADD COLUMN budget_annuel_vote REAL DEFAULT 0',
                "ALTER TABLE assemblees ADD COLUMN frequence_appels TEXT DEFAULT 'trimestrielle'",
                "ALTER TABLE assemblees ADD COLUMN budget_rubriques TEXT DEFAULT '{}'",
            ];
            foreach ($alterCols as $sql) {
                try {
                    self::$pdo->exec($sql);
                } catch (Throwable $e) {
                    // Ignorer si la colonne existe déjà dans le schéma
                }
            }
        }

        return self::$pdo;
    }

    /**
     * Génère un tag court et convivial pour la résidence (ex: 'atlas', 'majorelle', 'marinabay').
     * Utilisé pour la génération d'identifiants résidents au format prenom.nom@[tag].
     *
     * @return string Identifiant textuel court en minuscules.
     */
    public static function getResidenceTag(): string
    {
        $res = self::getResidence();
        $nom = $res['nom'] ?? 'residence';

        // Correspondances prioritaires pour les résidences types
        if (stripos($nom, 'atlas') !== false) {
            return 'atlas';
        }
        if (stripos($nom, 'majorelle') !== false) {
            return 'majorelle';
        }
        if (stripos($nom, 'marina') !== false) {
            return 'marinabay';
        }
        if (stripos($nom, 'greenwood') !== false) {
            return 'greenwood';
        }
        if (stripos($nom, 'ryad') !== false) {
            return 'ryadanfa';
        }
        if (stripos($nom, 'palmier') !== false) {
            return 'palmierdor';
        }

        // Extraction du mot principal en éliminant les préfixes communs
        $clean = preg_replace('/^(résidence|immeuble|complexe)\s+(les|la|le|du|des|d\'|de)?\s*/iu', '', trim($nom));
        $words = preg_split('/[\s\-_]+/u', $clean);
        $short = strtolower($words[0] ?? 'residence');
        $cleanShort = preg_replace('/[^a-z0-9]/i', '', $short);

        return $cleanShort ?: 'residence';
    }

    // =========================================================================
    // LECTURE DES DONNÉES DE LA COPROPRIÉTÉ
    // =========================================================================

    /**
     * Récupère la fiche signalétique officielle de la copropriété.
     *
     * @return array Fiche d'identité (nom, RIB, banque, syndic, tantièmes...).
     */
    public static function getResidence(): array
    {
        $pdo = self::getPdo();
        $res = $pdo->query('SELECT * FROM residence LIMIT 1')->fetch();

        return $res ?: [];
    }

    /**
     * Récupère le registre cadastral complet des lots avec jointure sur le copropriétaire détenteur.
     *
     * @return array Liste des lots triés par numéro d'appartement.
     */
    public static function getLots(): array
    {
        $pdo = self::getPdo();

        return $pdo->query('
            SELECT l.*, c.nom as coproprietaire_nom, c.prenom as coproprietaire_prenom
            FROM lots l
            LEFT JOIN coproprietaires c ON l.coproprietaire_id = c.id
            ORDER BY CAST(l.numero AS INTEGER) ASC, l.numero ASC
        ')->fetchAll();
    }

    /**
     * Calcule la somme globale des tantièmes déclarés sur l'ensemble des lots.
     * Conforme au standard légal marocain de 10 000 tantièmes.
     *
     * @return int Somme des tantièmes (défaut : 10000 si aucun lot).
     */
    public static function getTotalTantiemes(): int
    {
        $pdo = self::getPdo();
        $total = (int) $pdo->query('SELECT SUM(tantiemes) FROM lots')->fetchColumn();

        return $total > 0 ? $total : 10000;
    }

    /**
     * Récupère la liste nominative des copropriétaires enrichie de l'identifiant convivial généré.
     *
     * @return array Liste des copropriétaires avec 'friendly_username' (prenom.nom@tag).
     */
    public static function getCoproprietaires(): array
    {
        $pdo = self::getPdo();
        $rows = $pdo->query('SELECT * FROM coproprietaires ORDER BY nom ASC, prenom ASC')->fetchAll();
        $tag = self::getResidenceTag();

        foreach ($rows as &$c) {
            $prenomSlug = strtolower(preg_replace('/[^a-z0-9]/i', '', $c['prenom'] ?? 'user'));
            $nomSlug = strtolower(preg_replace('/[^a-z0-9]/i', '', $c['nom'] ?? 'resident'));
            $c['friendly_username'] = "{$prenomSlug}.{$nomSlug}@{$tag}";
        }
        unset($c);

        return $rows;
    }

    /**
     * Récupère le registre des paiements et encaissements enregistrés.
     *
     * @param  int|null  $exercice  Filtrage optionnel par année fiscale (ex: 2026).
     * @return array Liste chronologique décroissante des encaissements avec numéro de quittance.
     */
    public static function getPaiements(?int $exercice = null): array
    {
        $pdo = self::getPdo();
        $sql = '
            SELECT p.*, c.nom as coproprietaire_nom, c.prenom as coproprietaire_prenom, l.numero as lot_numero
            FROM paiements p
            LEFT JOIN coproprietaires c ON p.coproprietaire_id = c.id
            LEFT JOIN lots l ON p.lot_id = l.id
        ';
        if ($exercice !== null) {
            $sql .= " WHERE strftime('%Y', p.date_paiement) = :ex";
        }
        $sql .= ' ORDER BY p.date_paiement DESC';

        $stmt = $pdo->prepare($sql);
        if ($exercice !== null) {
            $stmt->execute(['ex' => (string) $exercice]);
        } else {
            $stmt->execute();
        }

        return $stmt->fetchAll();
    }

    /**
     * Récupère le registre des dépenses d'exploitation et factures prestataires.
     *
     * @param  int|null  $exercice  Filtrage optionnel par exercice comptable.
     * @return array Liste des dépenses avec montants HT et TTC.
     */
    public static function getDepenses(?int $exercice = null): array
    {
        $pdo = self::getPdo();
        $sql = 'SELECT * FROM depenses';
        if ($exercice !== null) {
            $sql .= ' WHERE exercice = :ex';
        }
        $sql .= ' ORDER BY date DESC';

        $stmt = $pdo->prepare($sql);
        if ($exercice !== null) {
            $stmt->execute(['ex' => $exercice]);
        } else {
            $stmt->execute();
        }

        return $stmt->fetchAll();
    }

    /**
     * Récupère l'annuaire des prestataires et fournisseurs de l'immeuble.
     *
     * @return array Liste alphabétique des fournisseurs avec coordonnées et ICE.
     */
    public static function getFournisseurs(): array
    {
        $pdo = self::getPdo();

        return $pdo->query('SELECT * FROM fournisseurs ORDER BY nom ASC')->fetchAll();
    }

    /**
     * Récupère l'historique complet du carnet d'entretien de l'immeuble (Art. 26 Loi 18-00).
     *
     * @return array Liste chronologique des opérations techniques réalisées.
     */
    public static function getCarnet(): array
    {
        $pdo = self::getPdo();

        return $pdo->query('SELECT * FROM carnet_entretien ORDER BY date_intervention DESC')->fetchAll();
    }

    /**
     * Récupère la liste des Assemblées Générales tenues ou convoquées.
     *
     * @return array Liste des AGs triées par date décroissante.
     */
    public static function getAssemblees(): array
    {
        $pdo = self::getPdo();

        return $pdo->query('SELECT * FROM assemblees ORDER BY date DESC')->fetchAll();
    }

    /**
     * Récupère les tickets de réclamations et d'incidents techniques des copropriétaires.
     *
     * @return array Liste des réclamations avec statut et réponse du syndic.
     */
    public static function getReclamations(): array
    {
        $pdo = self::getPdo();

        return $pdo->query('SELECT * FROM reclamations ORDER BY date_creation DESC')->fetchAll();
    }

    /**
     * Récupère la liste des grands travaux et opérations exceptionnelles votées en AG.
     *
     * @return array Liste des chantiers avec enveloppe prévisionnelle et planning.
     */
    public static function getProjets(): array
    {
        $pdo = self::getPdo();

        return $pdo->query('SELECT * FROM projets ORDER BY date_debut DESC')->fetchAll();
    }

    /**
     * Récupère la liste des appels de fonds formellement émis par le syndic.
     *
     * @param  int|null  $exercice  Année d'imputation fiscale.
     * @return array Liste des appels de charges exigibles.
     */
    public static function getAppels(?int $exercice = null): array
    {
        $pdo = self::getPdo();
        $sql = 'SELECT * FROM appels_fonds';
        if ($exercice !== null) {
            $sql .= ' WHERE exercice = :ex';
        }
        $sql .= ' ORDER BY date_exigibilite DESC';
        $stmt = $pdo->prepare($sql);
        if ($exercice !== null) {
            $stmt->execute(['ex' => $exercice]);
        } else {
            $stmt->execute();
        }

        return $stmt->fetchAll();
    }

    /**
     * Calcule la synthèse financière globale pour le cockpit du syndic.
     *
     * Règles de calcul strictes (Conformité Loi 18-00) :
     * 1. Total Encaissé = Somme des paiements sur l'exercice.
     * 2. Total Appelé = Somme des appels de fonds réels de l'exercice (0 si aucun appel).
     * 3. Créances Impayées = Max(0, Total Appelé - Total Encaissé). Si aucun appel, strictement 0.00 DH.
     * 4. Taux de Recouvrement = (Total Encaissé / Total Appelé) * 100 (100% si aucun appel).
     * 5. Trésorerie Disponible = Solde Initial + Tous Encaissements - Tous Décaissements.
     *
     * @param  int|null  $exercice  Exercice comptable analysé (défaut : année en cours).
     * @return array KPIs financiers certifiés pour le tableau de bord.
     */
    public static function getFinancialCockpit(?int $exercice = null): array
    {
        $pdo = self::getPdo();
        $res = self::getResidence();
        if ($exercice === null) {
            $exercice = (int) date('Y');
        }

        // 1. Total Encaissé sur l'exercice
        $stmt = $pdo->prepare("SELECT SUM(montant) FROM paiements WHERE strftime('%Y', date_paiement) = ?");
        $stmt->execute([(string) $exercice]);
        $totalEncaisse = (float) $stmt->fetchColumn();

        // 2. Total Appelé sur l'exercice (Strictement issu des appels de fonds réels)
        $stmt = $pdo->prepare('SELECT SUM(montant_total) FROM appels_fonds WHERE exercice = ?');
        $stmt->execute([$exercice]);
        $totalAppele = (float) $stmt->fetchColumn();

        // 3. Total Dépenses sur l'exercice
        $stmt = $pdo->prepare('SELECT SUM(montant_ttc) FROM depenses WHERE exercice = ?');
        $stmt->execute([$exercice]);
        $totalDepenses = (float) $stmt->fetchColumn();

        // 4. Soldes de Trésorerie Globaux
        $soldeBanque = (float) ($res['solde_banque_initial'] ?? 0);
        $soldeCaisse = (float) ($res['solde_caisse_initial'] ?? 0);
        $fondTravaux = (float) ($res['fond_travaux_initial'] ?? 0);

        // Cumul de l'ensemble des flux depuis l'ouverture de la copropriété
        $allPaiements = (float) $pdo->query('SELECT SUM(montant) FROM paiements')->fetchColumn();
        $allDepenses = (float) $pdo->query('SELECT SUM(montant_ttc) FROM depenses')->fetchColumn();

        $tresorerieDisponible = $soldeBanque + $soldeCaisse + $allPaiements - $allDepenses;

        // Créances impayées réelles : si aucun appel n'a été émis, STRICTEMENT 0 DH !
        $totalImpayes = ($totalAppele > 0) ? max(0.0, round($totalAppele - $totalEncaisse, 2)) : 0.0;

        // Taux de recouvrement
        $tauxRecouvrement = ($totalAppele > 0) ? round(($totalEncaisse / $totalAppele) * 100, 1) : 100.0;

        return [
            'totalEncaisse' => $totalEncaisse,
            'totalAppele' => $totalAppele,
            'totalDepenses' => $totalDepenses,
            'totalImpayes' => $totalImpayes,
            'tauxRecouvrement' => min(100.0, $tauxRecouvrement),
            'soldeBanque' => max(0.0, $soldeBanque + $allPaiements - $allDepenses),
            'soldeCaisse' => $soldeCaisse,
            'tresorerieDisponible' => $tresorerieDisponible,
            'fondTravaux' => $fondTravaux,
            'exercice' => $exercice,
        ];
    }

    /**
     * Récupère le budget annuel voté lors de la dernière Assemblée Générale Ordinaire (AGO).
     *
     * @param  int|null  $exercice  Exercice comptable recherché.
     * @return array|null Fiche d'AG avec détail du budget voté et tableau des 8 rubriques.
     */
    public static function getVotedBudgetInfo(?int $exercice = null): ?array
    {
        $pdo = self::getPdo();
        if ($exercice === null) {
            $exercice = (int) date('Y');
        }

        $stmt = $pdo->prepare("
            SELECT * FROM assemblees 
            WHERE (exercice = ? OR strftime('%Y', date) = ?)
              AND budget_annuel_vote > 0
            ORDER BY date DESC 
            LIMIT 1
        ");
        $stmt->execute([$exercice, (string) $exercice]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $row['rubriques_array'] = json_decode($row['budget_rubriques'] ?? '{}', true) ?: [];

            return $row;
        }

        // Repli : recherche de la dernière AG ayant un budget voté
        $stmt = $pdo->query('
            SELECT * FROM assemblees 
            WHERE budget_annuel_vote > 0
            ORDER BY date DESC 
            LIMIT 1
        ');
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $row['rubriques_array'] = json_decode($row['budget_rubriques'] ?? '{}', true) ?: [];

            return $row;
        }

        return null;
    }

    /**
     * Calcule la répartition exacte d'un montant d'appel de fonds pour chaque lot selon ses tantièmes.
     * Formule légale : Cotisation = Montant Total × (Tantièmes du Lot / Somme Totale des Tantièmes).
     *
     * @param  float  $montantTotal  Somme globale appelée à répartir entre les copropriétaires.
     * @return array Tableau des quotes-parts calculées par lot privatif.
     */
    public static function calculateAppelBreakdown(float $montantTotal): array
    {
        $lots = self::getLots();
        $totalTantiemes = array_sum(array_column($lots, 'tantiemes'));
        if ($totalTantiemes <= 0) {
            $totalTantiemes = 10000;
        }

        $breakdown = [];
        foreach ($lots as $lot) {
            $lotTantiemes = (int) ($lot['tantiemes'] ?? 0);
            $part = round($montantTotal * ($lotTantiemes / $totalTantiemes), 2);
            $copNom = trim(($lot['coproprietaire_prenom'] ?? '').' '.($lot['coproprietaire_nom'] ?? ''));
            $breakdown[] = [
                'lot_id' => $lot['id'],
                'numero' => $lot['numero'],
                'immeuble' => $lot['immeuble'] ?? 'Principal',
                'etage' => $lot['etage'] ?? 0,
                'type' => $lot['type'] ?? 'appartement',
                'tantiemes' => $lotTantiemes,
                'coproprietaire_id' => $lot['coproprietaire_id'] ?? null,
                'coproprietaire_nom' => $copNom ?: 'Non attribué',
                'montant_du' => $part,
            ];
        }

        return $breakdown;
    }

    /**
     * Enregistre un nouvel appel de fonds formel avec numérotation séquentielle automatique.
     *
     * @param  array  $data  Données du formulaire (exercice, période, montant_total, type, description).
     * @return string Identifiant technique généré pour l'appel (ex: 'app-a1b2c3').
     *
     * @throws RuntimeException Si le mode lecture seule est actif.
     */
    public static function addAppel(array $data): string
    {
        self::checkWritePermission();
        $pdo = self::getPdo();
        $id = 'app-'.bin2hex(random_bytes(6));
        $ex = (int) ($data['exercice'] ?? date('Y'));

        // Numérotation séquentielle automatique (ex: APP-2026-01)
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM appels_fonds WHERE exercice = ?');
        $stmt->execute([$ex]);
        $count = (int) $stmt->fetchColumn() + 1;
        $num = 'APP-'.$ex.'-'.str_pad((string) $count, 2, '0', STR_PAD_LEFT);

        $stmt = $pdo->prepare('
            INSERT INTO appels_fonds (id, numero, type, exercice, periode, date_exigibilite, montant_total, description, statut)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $id,
            $num,
            $data['type'] ?? 'charges_courantes',
            $ex,
            $data['periode'] ?? ('Appel n°'.$count.' ('.$ex.')'),
            $data['date_exigibilite'] ?? date('Y-m-d'),
            (float) ($data['montant_total'] ?? 0),
            $data['description'] ?? '',
            $data['statut'] ?? 'exigible',
        ]);

        return $id;
    }

    // =========================================================================
    // MUTATIONS / INSERTIONS (Contrôle du Mode Lecture Seule)
    // =========================================================================

    /**
     * Intercepteur middleware de contrôle des droits d'écriture.
     * Si la copropriété est en mode Lecture Seule, bloque immédiatement l'exécution
     * et redirige la requête avec le code d'erreur 'read_only_mode'.
     */
    public static function checkWritePermission(): void
    {
        if (self::isReadOnly()) {
            $guid = self::resolveGuid();
            $referer = $_SERVER['HTTP_REFERER'] ?? '';
            if (! empty($referer)) {
                $cleanReferer = preg_replace('/([?&])error=[^&]+(&|$)/', '$1', $referer);
                $cleanReferer = rtrim($cleanReferer, '?&');
                $sep = (strpos($cleanReferer, '?') !== false) ? '&' : '?';
                header('Location: '.$cleanReferer.$sep.'error=read_only_mode');
                exit;
            }
            $base = (strpos($_SERVER['REQUEST_URI'] ?? '', 'MgmtResident') !== false) ? '/Syndic/MgmtResident/' : '/Syndic/MgmtResidence/';
            header('Location: '.$base.urlencode($guid).'/index.php?error=read_only_mode');
            exit;
        }
    }

    /**
     * Enregistre un versement et génère une quittance libératoire numérotée.
     *
     * @param  array  $data  Informations de paiement (coproprietaire_id, montant, date, mode, ref...).
     * @return string Identifiant technique du versement.
     */
    public static function addPaiement(array $data): string
    {
        self::checkWritePermission();
        $pdo = self::getPdo();
        $id = 'pay-'.bin2hex(random_bytes(6));
        $quittanceNum = 'QUITT-'.date('Y').'-'.strtoupper(bin2hex(random_bytes(3)));

        $stmt = $pdo->prepare('INSERT INTO paiements VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $id,
            $quittanceNum,
            $data['coproprietaire_id'],
            $data['lot_id'] ?? null,
            (float) $data['montant'],
            $data['date_paiement'] ?? date('Y-m-d'),
            $data['mode_paiement'] ?? 'virement',
            $data['reference'] ?? '',
            $data['affectation'] ?? 'charges_courantes',
        ]);

        return $id;
    }

    /**
     * Enregistre une facture de dépense d'exploitation sur l'exercice comptable.
     *
     * @param  array  $data  Données de la dépense (fournisseur, description, categorie, montant_ht, ttc).
     * @return string Identifiant technique de la dépense.
     */
    public static function addDepense(array $data): string
    {
        self::checkWritePermission();
        $pdo = self::getPdo();
        $id = 'dep-'.bin2hex(random_bytes(6));
        $ex = (int) date('Y', strtotime($data['date'] ?? date('Y-m-d')));

        $stmt = $pdo->prepare('INSERT INTO depenses VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $id,
            $data['date'] ?? date('Y-m-d'),
            $data['fournisseur_nom'],
            $data['description'],
            $data['categorie'] ?? 'Entretien',
            (float) ($data['montant_ht'] ?? $data['montant_ttc']),
            (float) $data['montant_ttc'],
            $ex,
            $data['statut_paiement'] ?? 'paye',
            $data['piece_justificative'] ?? null,
        ]);

        return $id;
    }

    /**
     * Récupère une séance d'Assemblée Générale par son identifiant unique.
     *
     * @param  string  $id  Identifiant de l'AG (ex: 'ag-xxxx').
     * @return array|null Fiche de l'AG ou null si introuvable.
     */
    public static function getAssembleeById(string $id): ?array
    {
        $pdo = self::getPdo();
        $stmt = $pdo->prepare('SELECT * FROM assemblees WHERE id = ?');
        $stmt->execute([$id]);
        $ag = $stmt->fetch();

        return $ag ?: null;
    }

    /**
     * Enregistre un procès-verbal d'Assemblée Générale avec vote budgétaire et passation de mandat (Art. 20).
     *
     * Si l'AG acte l'élection d'un nouveau syndic :
     * 1. Met à jour la fiche signalétique de la copropriété.
     * 2. Crée immédiatement le compte utilisateur administrateur du nouveau syndic.
     * 3. Répercute le changement dans la base maître master.sqlite.
     * 4. Inscrit un événement d'audit système PASSATION_SYNDIC.
     *
     * @param  array  $data  Informations de la délibération et du scrutin.
     * @return string Identifiant de l'assemblée enregistrée.
     */
    public static function addAssemblee(array $data): string
    {
        self::checkWritePermission();
        $pdo = self::getPdo();
        $id = 'ag-'.bin2hex(random_bytes(6));
        $type = in_array($data['type'] ?? '', ['ordinaire', 'extraordinaire']) ? $data['type'] : 'ordinaire';
        $changementSyndic = ! empty($data['changement_syndic']) ? 1 : 0;

        $ex = (int) ($data['exercice'] ?? date('Y', strtotime($data['date'] ?? 'now')));
        $budgetVote = (float) ($data['budget_annuel_vote'] ?? 0);
        $frequence = in_array($data['frequence_appels'] ?? '', ['mensuelle', 'trimestrielle', 'semestrielle', 'annuelle']) ? $data['frequence_appels'] : 'trimestrielle';
        $rubriques = $data['rubriques'] ?? $data['budget_rubriques'] ?? [];
        if (! is_array($rubriques)) {
            $rubriques = [];
        }
        $rubriquesJson = json_encode($rubriques, JSON_UNESCAPED_UNICODE);

        $stmt = $pdo->prepare("
            INSERT INTO assemblees (
                id, date, type, lieu, statut, description,
                ordre_du_jour, tantiemes_presents, changement_syndic,
                nouveau_syndic_nom, nouveau_syndic_email, nouveau_syndic_tel,
                date_effet_mandat, tresorerie_arretee, president_seance, secretaire_seance, pv_texte,
                exercice, budget_annuel_vote, frequence_appels, budget_rubriques
            ) VALUES (?, ?, ?, ?, 'cloturee', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $id,
            $data['date'] ?? date('Y-m-d'),
            $type,
            $data['lieu'] ?? 'Hall de la résidence',
            $data['description'] ?? ('Assemblée Générale '.($type === 'extraordinaire' ? 'Extraordinaire (AGE)' : 'Ordinaire (AGO)')),
            $data['ordre_du_jour'] ?? '',
            (int) ($data['tantiemes_presents'] ?? 8500),
            $changementSyndic,
            $data['nouveau_syndic_nom'] ?? null,
            $data['nouveau_syndic_email'] ?? null,
            $data['nouveau_syndic_tel'] ?? null,
            $data['date_effet_mandat'] ?? null,
            (float) ($data['tresorerie_arretee'] ?? 0),
            $data['president_seance'] ?? 'Président de séance élu',
            $data['secretaire_seance'] ?? 'Secrétaire de séance élu',
            $data['pv_texte'] ?? '',
            $ex,
            $budgetVote,
            $frequence,
            $rubriquesJson,
        ]);

        // Protocole de passation de mandat conforme à l'Article 20 de la Loi 18-00
        if ($changementSyndic && ! empty($data['nouveau_syndic_nom']) && ! empty($data['nouveau_syndic_email'])) {
            $guid = self::resolveGuid();

            // 1. Mise à jour de la fiche d'identité de la résidence
            $uRes = $pdo->prepare('
                UPDATE residence SET
                    nom_syndic = ?,
                    email_syndic = ?,
                    telephone_syndic = ?
                WHERE id = ?
            ');
            $uRes->execute([
                $data['nouveau_syndic_nom'],
                $data['nouveau_syndic_email'],
                $data['nouveau_syndic_tel'] ?? '+212 6 00 00 00 00',
                $guid,
            ]);

            // 2. Création ou mise à jour du compte administrateur Syndic
            $newPass = $data['nouveau_syndic_password'] ?? 'syndic2026';
            $uUser = $pdo->prepare("
                INSERT OR REPLACE INTO users (id, email, password_hash, nom, role, coproprietaire_id, telephone, date_creation)
                VALUES (?, ?, ?, ?, 'syndic', NULL, ?, ?)
            ");
            $uUser->execute([
                'user-syndic-'.substr($guid, 0, 8),
                $data['nouveau_syndic_email'],
                password_hash($newPass, PASSWORD_BCRYPT),
                $data['nouveau_syndic_nom'],
                $data['nouveau_syndic_tel'] ?? '',
                date('Y-m-d H:i:s'),
            ]);

            // 3. Répercussion dans master.sqlite pour la supervision centrale
            $masterPath = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'master.sqlite';
            if (file_exists($masterPath)) {
                try {
                    $mPdo = new PDO('sqlite:'.$masterPath);
                    $mStmt = $mPdo->prepare('
                        UPDATE tenants SET
                            nom_syndic = ?,
                            email_syndic = ?,
                            derniere_activite = ?
                        WHERE id = ?
                    ');
                    $mStmt->execute([
                        $data['nouveau_syndic_nom'],
                        $data['nouveau_syndic_email'],
                        date('Y-m-d H:i:s'),
                        $guid,
                    ]);

                    $logStmt = $mPdo->prepare("
                        INSERT INTO system_logs (timestamp, action, tenant_id, details)
                        VALUES (?, 'PASSATION_SYNDIC', ?, ?)
                    ");
                    $logStmt->execute([
                        date('Y-m-d H:i:s'),
                        $guid,
                        "AG {$type} : Élection du nouveau syndic {$data['nouveau_syndic_nom']} ({$data['nouveau_syndic_email']})",
                    ]);
                } catch (Throwable $e) {
                    // Ne bloque pas la transaction principale
                }
            }
        }

        return $id;
    }

    /**
     * Enregistre un copropriétaire et configure automatiquement ses identifiants d'accès conviviaux.
     *
     * @param  array  $data  Informations d'état civil, CIN et coordonnées.
     * @return string Identifiant technique de la fiche copropriétaire (ex: 'cop-xxxx').
     */
    public static function addCoproprietaire(array $data): string
    {
        self::checkWritePermission();
        $pdo = self::getPdo();
        $id = 'cop-'.bin2hex(random_bytes(6));

        $stmt = $pdo->prepare('INSERT INTO coproprietaires VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $id,
            $data['civilite'] ?? 'M.',
            strtoupper($data['nom']),
            ucwords($data['prenom'] ?? ''),
            strtoupper($data['cin'] ?? ''),
            $data['telephone'] ?? '',
            $data['email'] ?? '',
            isset($data['est_resident']) ? 1 : 0,
            (float) ($data['solde_initial'] ?? 0),
        ]);

        // Génération de l'identifiant d'accès convivial au format universel prenom.nom@[tag]
        $tag = self::getResidenceTag();
        $prenomSlug = strtolower(preg_replace('/[^a-z0-9]/i', '', $data['prenom'] ?? 'user'));
        $nomSlug = strtolower(preg_replace('/[^a-z0-9]/i', '', $data['nom'] ?? 'resident'));
        $friendlyUsername = "{$prenomSlug}.{$nomSlug}@{$tag}";

        $userId = 'user-res-'.bin2hex(random_bytes(4));
        $pass = 'resident2026';

        $uStmt = $pdo->prepare("INSERT OR REPLACE INTO users VALUES (?, ?, ?, ?, 'resident', ?, ?, ?)");
        $uStmt->execute([
            $userId,
            $friendlyUsername,
            password_hash($pass, PASSWORD_BCRYPT),
            ($data['prenom'] ?? '').' '.$data['nom'],
            $id,
            $data['telephone'] ?? '',
            date('Y-m-d H:i:s'),
        ]);

        // Création d'un alias si un email externe (Gmail, Outlook...) a été renseigné
        if (! empty($data['email']) && strtolower($data['email']) !== $friendlyUsername) {
            $uStmtAlias = $pdo->prepare("INSERT OR IGNORE INTO users VALUES (?, ?, ?, ?, 'resident', ?, ?, ?)");
            $uStmtAlias->execute([
                $userId.'-ext',
                strtolower($data['email']),
                password_hash($pass, PASSWORD_BCRYPT),
                ($data['prenom'] ?? '').' '.$data['nom'],
                $id,
                $data['telephone'] ?? '',
                date('Y-m-d H:i:s'),
            ]);
        }

        return $id;
    }

    /**
     * Enregistre un lot privatif avec sa pondération en tantièmes sur 10 000.
     *
     * @param  array  $data  Attributs du lot (numero, type, etage, tantiemes, surface, coproprietaire_id).
     * @return string Identifiant technique du lot (ex: 'lot-xxxx').
     */
    public static function addLot(array $data): string
    {
        self::checkWritePermission();
        $pdo = self::getPdo();
        $id = 'lot-'.bin2hex(random_bytes(6));

        $stmt = $pdo->prepare('INSERT INTO lots VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $id,
            $data['numero'],
            $data['immeuble'] ?? 'Principal',
            (int) ($data['etage'] ?? 0),
            $data['type'] ?? 'appartement',
            (int) ($data['tantiemes'] ?? 100),
            (float) ($data['surface'] ?? 0),
            $data['coproprietaire_id'] ?? null,
        ]);

        return $id;
    }

    /**
     * Enregistre une réclamation technique ou un signalement d'incident.
     *
     * @param  array  $data  Objet, description, priorité et auteur de la réclamation.
     * @return string Identifiant technique du ticket.
     */
    public static function addReclamation(array $data): string
    {
        self::checkWritePermission();
        $pdo = self::getPdo();
        $id = 'rec-'.bin2hex(random_bytes(6));

        $stmt = $pdo->prepare('INSERT INTO reclamations VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $id,
            $data['titre'],
            $data['description'],
            $data['priorite'] ?? 'normale',
            'recu',
            date('Y-m-d H:i:s'),
            null,
            $data['auteur'] ?? 'Résident',
        ]);

        return $id;
    }

    /**
     * Met à jour les coordonnées juridiques, bancaires et administratives de la copropriété.
     *
     * @param  array  $data  Données du formulaire de paramétrage général.
     */
    public static function updateResidence(array $data): void
    {
        self::checkWritePermission();
        $pdo = self::getPdo();
        $guid = self::resolveGuid();

        $stmt = $pdo->prepare('
            UPDATE residence SET
                nom = ?,
                adresse = ?,
                ville = ?,
                code_postal = ?,
                titre_foncier_mere = ?,
                rib_bancaire = ?,
                banque = ?,
                nom_syndic = ?,
                telephone_syndic = ?,
                email_syndic = ?
            WHERE id = ?
        ');
        $stmt->execute([
            $data['nom'],
            $data['adresse'] ?? '',
            $data['ville'],
            $data['code_postal'] ?? '20000',
            $data['titre_foncier_mere'] ?? '',
            $data['rib_bancaire'],
            $data['banque'],
            $data['nom_syndic'],
            $data['telephone_syndic'] ?? '',
            $data['email_syndic'],
            $guid,
        ]);
    }

    /**
     * Met à jour le chemin ou l'URL du logo officiel de la copropriété.
     *
     * @param  string|null  $logoUrl  Chemin web du logo téléversé (ou null pour réinitialiser le placeholder SVG).
     */
    public static function updateResidenceLogo(?string $logoUrl): void
    {
        self::checkWritePermission();
        $pdo = self::getPdo();
        $guid = self::resolveGuid();

        $stmt = $pdo->prepare('UPDATE residence SET logo_url = ? WHERE id = ?');
        $stmt->execute([$logoUrl, $guid]);
    }

    // =========================================================================
    // DÉLÉGUÉS DU SYNDIC & GOUVERNANCE (Vice-Syndic, Comptable, Secrétaire...)
    // =========================================================================

    /**
     * Récupère la liste de tous les membres délégués du conseil syndical avec leurs permissions.
     *
     * @return array Liste des délégations ordonnées par préséance hiérarchique.
     */
    public static function getDelegates(): array
    {
        $pdo = self::getPdo();
        $stmt = $pdo->query("
            SELECT d.*, 
                   u.email as user_email, u.nom as user_nom, u.telephone as user_tel,
                   c.nom as cop_nom, c.prenom as cop_prenom, c.cin as cop_cin, c.email as cop_email, c.telephone as cop_tel,
                   l.numero as lot_numero, l.type as lot_type, l.tantiemes as lot_tantiemes
            FROM delegates d
            JOIN users u ON d.user_id = u.id
            JOIN coproprietaires c ON d.coproprietaire_id = c.id
            LEFT JOIN lots l ON l.coproprietaire_id = c.id
            ORDER BY 
                CASE d.titre_role
                    WHEN 'vice_syndic' THEN 1
                    WHEN 'comptable' THEN 2
                    WHEN 'secretaire' THEN 3
                    ELSE 4
                END, d.date_nomination DESC
        ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $delegates = [];
        foreach ($rows as $r) {
            $id = $r['id'];
            if (! isset($delegates[$id])) {
                $r['permissions_array'] = json_decode($r['permissions'] ?? '[]', true) ?: [];
                $r['lots'] = [];
                $delegates[$id] = $r;
            }
            if (! empty($r['lot_numero'])) {
                $delegates[$id]['lots'][] = [
                    'numero' => $r['lot_numero'],
                    'type' => $r['lot_type'],
                    'tantiemes' => $r['lot_tantiemes'],
                ];
            }
        }

        return array_values($delegates);
    }

    /**
     * Récupère l'éventuelle délégation active associée à un compte utilisateur.
     *
     * @param  string  $userId  Identifiant de compte dans la table users.
     * @return array|null Fiche de délégation active avec tableau de permissions ou null.
     */
    public static function getDelegateByUserId(string $userId): ?array
    {
        $pdo = self::getPdo();
        $stmt = $pdo->prepare("
            SELECT d.*, c.nom as cop_nom, c.prenom as cop_prenom
            FROM delegates d
            JOIN coproprietaires c ON d.coproprietaire_id = c.id
            LEFT JOIN users u ON u.id = ?
            WHERE (d.user_id = ? OR (u.coproprietaire_id IS NOT NULL AND d.coproprietaire_id = u.coproprietaire_id))
              AND d.statut = 'actif'
            LIMIT 1
        ");
        $stmt->execute([$userId, $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $row['permissions_array'] = json_decode($row['permissions'] ?? '[]', true) ?: [];

            return $row;
        }

        return null;
    }

    /**
     * Récupère une délégation par son identifiant unique.
     *
     * @param  string  $id  Identifiant technique de la délégation (ex: 'del-xxxx').
     * @return array|null Données de la délégation ou null si introuvable.
     */
    public static function getDelegateById(string $id): ?array
    {
        $pdo = self::getPdo();
        $stmt = $pdo->prepare('
            SELECT d.*, u.email as user_email, u.nom as user_nom,
                   c.nom as cop_nom, c.prenom as cop_prenom
            FROM delegates d
            JOIN users u ON d.user_id = u.id
            JOIN coproprietaires c ON d.coproprietaire_id = c.id
            WHERE d.id = ?
            LIMIT 1
        ');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $row['permissions_array'] = json_decode($row['permissions'] ?? '[]', true) ?: [];

            return $row;
        }

        return null;
    }

    /**
     * Récupère la liste des copropriétaires éligibles pour être nommés au conseil syndical.
     *
     * @return array Annuaire des copropriétaires avec indicateur de mandat actif.
     */
    public static function getEligibleResidentsForDelegation(): array
    {
        $pdo = self::getPdo();
        $stmt = $pdo->query("
            SELECT c.*, 
                   u.id as user_id, u.email as user_email,
                   (SELECT COUNT(*) FROM delegates d WHERE d.coproprietaire_id = c.id AND d.statut = 'actif') as is_delegate,
                   (SELECT l.numero FROM lots l WHERE l.coproprietaire_id = c.id LIMIT 1) as lot_numero
            FROM coproprietaires c
            LEFT JOIN users u ON u.coproprietaire_id = c.id
            ORDER BY c.nom ASC, c.prenom ASC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Crée une nouvelle délégation de pouvoirs syndicaux à un copropriétaire.
     *
     * @param  array  $data  Attributs (coproprietaire_id, titre_role, role_label, permissions JSON, notes).
     * @return string Identifiant de la délégation générée.
     *
     * @throws InvalidArgumentException Si le copropriétaire sélectionné est invalide.
     */
    public static function addDelegate(array $data): string
    {
        self::checkWritePermission();
        $pdo = self::getPdo();

        $copId = trim($data['coproprietaire_id'] ?? '');
        if (empty($copId)) {
            throw new InvalidArgumentException('Le copropriétaire sélectionné est invalide.');
        }

        $stmt = $pdo->prepare('SELECT * FROM coproprietaires WHERE id = ?');
        $stmt->execute([$copId]);
        $cop = $stmt->fetch(PDO::FETCH_ASSOC);
        if (! $cop) {
            throw new InvalidArgumentException('Copropriétaire introuvable.');
        }

        // Résolution du compte utilisateur lié
        $stmt = $pdo->prepare('SELECT * FROM users WHERE coproprietaire_id = ? LIMIT 1');
        $stmt->execute([$copId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (! $user) {
            $resTag = self::getResidenceTag();
            $slug = strtolower(preg_replace('/[^a-z0-9]/', '', ($cop['prenom'] ?? '').'.'.$cop['nom']));
            if (empty($slug)) {
                $slug = 'resident.'.substr(md5($copId), 0, 4);
            }
            $email = $slug.'@'.$resTag;
            $userId = 'usr-'.bin2hex(random_bytes(5));
            $passHash = password_hash('resident2026', PASSWORD_DEFAULT);

            $ins = $pdo->prepare("
                INSERT INTO users (id, email, password_hash, nom, role, coproprietaire_id, telephone, date_creation)
                VALUES (?, ?, ?, ?, 'resident', ?, ?, ?)
            ");
            $fullName = trim(($cop['prenom'] ?? '').' '.$cop['nom']);
            $ins->execute([$userId, $email, $passHash, $fullName, $copId, $cop['telephone'] ?? '', date('Y-m-d')]);
            $user = ['id' => $userId, 'email' => $email];
        }

        $id = 'del-'.bin2hex(random_bytes(6));
        $titreRole = $data['titre_role'] ?? 'delegue';
        $roleLabel = trim($data['role_label'] ?? '');
        if (empty($roleLabel)) {
            $labels = [
                'vice_syndic' => 'Vice-Syndic Adjoint',
                'comptable' => 'Comptable / Trésorier',
                'secretaire' => 'Secrétaire Général',
                'delegue' => 'Membre Délégué du Bureau',
            ];
            $roleLabel = $labels[$titreRole] ?? 'Délégué';
        }

        $permissions = $data['permissions'] ?? [];
        if (! is_array($permissions)) {
            $permissions = [];
        }
        $permsJson = json_encode(array_values(array_unique($permissions)));

        $insDel = $pdo->prepare('
            INSERT OR REPLACE INTO delegates (id, user_id, coproprietaire_id, titre_role, role_label, permissions, statut, date_nomination, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $insDel->execute([
            $id,
            $user['id'],
            $copId,
            $titreRole,
            $roleLabel,
            $permsJson,
            $data['statut'] ?? 'actif',
            $data['date_nomination'] ?? date('Y-m-d'),
            trim($data['notes'] ?? ''),
        ]);

        return $id;
    }

    /**
     * Met à jour les attributions et permissions d'une délégation existante.
     *
     * @param  string  $id  Identifiant technique de la délégation.
     * @param  array  $data  Nouvelles permissions, libellé et statut.
     * @return bool True si la mise à jour est effective.
     */
    public static function updateDelegate(string $id, array $data): bool
    {
        self::checkWritePermission();
        $pdo = self::getPdo();

        $titreRole = $data['titre_role'] ?? 'delegue';
        $roleLabel = trim($data['role_label'] ?? '');
        if (empty($roleLabel)) {
            $labels = [
                'vice_syndic' => 'Vice-Syndic Adjoint',
                'comptable' => 'Comptable / Trésorier',
                'secretaire' => 'Secrétaire Général',
                'delegue' => 'Membre Délégué du Bureau',
            ];
            $roleLabel = $labels[$titreRole] ?? 'Délégué';
        }

        $permissions = $data['permissions'] ?? [];
        if (! is_array($permissions)) {
            $permissions = [];
        }
        $permsJson = json_encode(array_values(array_unique($permissions)));

        $stmt = $pdo->prepare('
            UPDATE delegates SET
                titre_role = ?,
                role_label = ?,
                permissions = ?,
                statut = ?,
                notes = ?
            WHERE id = ?
        ');

        return $stmt->execute([
            $titreRole,
            $roleLabel,
            $permsJson,
            $data['statut'] ?? 'actif',
            trim($data['notes'] ?? ''),
            $id,
        ]);
    }

    /**
     * Révoque définitivement une délégation syndicale.
     *
     * @param  string  $id  Identifiant de la délégation à supprimer.
     * @return bool True si la suppression est réussie.
     */
    public static function deleteDelegate(string $id): bool
    {
        self::checkWritePermission();
        $pdo = self::getPdo();
        $stmt = $pdo->prepare('DELETE FROM delegates WHERE id = ?');

        return $stmt->execute([$id]);
    }

    /**
     * Vérifie si un utilisateur dispose des permissions requises pour accéder à un module de gestion.
     *
     * Règles de décision :
     * 1. Syndic en titre : accès universel à l'ensemble des modules.
     * 2. Membre délégué : consultation de la liste des permissions JSON accordées par le syndic.
     * 3. Autre utilisateur : accès refusé.
     *
     * @param  array|null  $user  Profil utilisateur en session (avec clé 'delegate' éventuelle).
     * @param  string  $page  Nom du module interrogé (ex: 'lots', 'appels', 'depenses').
     * @return bool True si l'accès est autorisé, false sinon.
     */
    public static function hasPermission(?array $user, string $page): bool
    {
        if (! $user) {
            return false;
        }

        // Le syndic en exercice a tous les pouvoirs
        if (($user['role'] ?? '') === 'syndic') {
            return true;
        }

        // Contrôle des attributions déléguées
        if (! empty($user['delegate']) && is_array($user['delegate'])) {
            $perms = $user['delegate']['permissions_array'] ?? [];
            if (empty($perms) && ! empty($user['delegate']['permissions'])) {
                $perms = json_decode($user['delegate']['permissions'], true) ?: [];
            }

            return in_array($page, $perms, true);
        }

        return false;
    }
}
