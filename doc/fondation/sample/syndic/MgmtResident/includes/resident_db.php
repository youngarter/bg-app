<?php

/**
 * ==============================================================================
 * SyndicPro Maroc — Couche d'Accès Données Sécurisée Espace Résident (ResidentDB)
 * ==============================================================================
 * Ce module encapsule l'accès aux données partitionnées pour le portail copropriétaire.
 *
 * Principes d'Isolation & Sécurité :
 * 1. Périmètre Privatif Strict : Le copropriétaire authentifié ne peut consulter que
 *    ses propres fiches (ses lots cadastraux, son historique de paiements, ses quittances
 *    officielles et ses tickets de réclamation).
 * 2. Transparence de la Vie Collective : Accès en lecture seule aux informations communes
 *    de l'immeuble (Assemblées Générales, procès-verbaux, carnet d'entretien, grands projets).
 * 3. Étanchéité Comptable : En l'absence d'appel de fonds formellement émis par le syndic,
 *    aucune dette forfaitaire n'est imputée au résident (solde exigible = 0.00 DH).
 * ==============================================================================
 */

declare(strict_types=1);

// Inclusion de la couche d'accès à la base de données de la copropriété
require_once dirname(__DIR__, 2).'/MgmtResidence/includes/tenant_db.php';

class ResidentDB
{
    /**
     * Récupère la fiche d'identité complète du copropriétaire connecté.
     *
     * @param  string|null  $copId  Identifiant unique de la fiche copropriétaire (ex: 'cop-1111').
     * @return array|null Tableau associatif contenant les coordonnées (nom, prénom, CIN, RIB, tel)
     *                    ou null si l'identifiant est absent ou non trouvé.
     */
    public static function getCoproprietaireInfo(?string $copId): ?array
    {
        // Si aucun identifiant n'est renseigné en session, abandon immédiat
        if (! $copId) {
            return null;
        }

        // Obtention de l'instance PDO de la base dédiée du tenant
        $pdo = TenantDB::getPdo();

        // Requête préparée pour prémunir toute injection SQL
        $stmt = $pdo->prepare('SELECT * FROM coproprietaires WHERE id = ?');
        $stmt->execute([$copId]);
        $cop = $stmt->fetch();

        return $cop ?: null;
    }

    /**
     * Récupère la liste de l'ensemble des lots rattachés au copropriétaire (appartements, parkings, caves).
     *
     * @param  string|null  $copId  Identifiant unique du copropriétaire.
     * @return array Liste des lots triés par numéro d'appartement croissant.
     */
    public static function getResidentLots(?string $copId): array
    {
        if (! $copId) {
            return [];
        }

        $pdo = TenantDB::getPdo();

        // Tri naturel des lots par numéro numérique puis alphabétique
        $stmt = $pdo->prepare('
            SELECT * FROM lots 
            WHERE coproprietaire_id = ? 
            ORDER BY CAST(numero AS INTEGER) ASC, numero ASC
        ');
        $stmt->execute([$copId]);

        return $stmt->fetchAll();
    }

    /**
     * Récupère l'historique chronologique des encaissements et quittances d'un copropriétaire.
     *
     * @param  string|null  $copId  Identifiant unique du copropriétaire.
     * @param  int|null  $exercice  Filtrage optionnel par année fiscale (ex: 2026).
     * @return array Liste des paiements avec jointure sur les informations du lot associé.
     */
    public static function getResidentPaiements(?string $copId, ?int $exercice = null): array
    {
        if (! $copId) {
            return [];
        }

        $pdo = TenantDB::getPdo();

        // Jointure gauche sur la table lots pour afficher le numéro et le type de bien sur la quittance
        $sql = '
            SELECT p.*, l.numero as lot_numero, l.type as lot_type, l.etage
            FROM paiements p
            LEFT JOIN lots l ON p.lot_id = l.id
            WHERE p.coproprietaire_id = ?
        ';

        if ($exercice !== null) {
            // Filtrage par année calendaire extrait de la date de paiement ISO (YYYY-MM-DD)
            $sql .= " AND strftime('%Y', p.date_paiement) = ?";
            $sql .= ' ORDER BY p.date_paiement DESC';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$copId, (string) $exercice]);
        } else {
            $sql .= ' ORDER BY p.date_paiement DESC';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$copId]);
        }

        return $stmt->fetchAll();
    }

    /**
     * Récupère les tickets d'incidents techniques signalés par le résident ou rattachés à son nom.
     *
     * @param  string|null  $copId  Identifiant unique du copropriétaire.
     * @param  string|null  $userNom  Nom de l'utilisateur connecté pour recherche par auteur.
     * @return array Liste des réclamations classées de la plus récente à la plus ancienne.
     */
    public static function getResidentReclamations(?string $copId, ?string $userNom = null): array
    {
        $pdo = TenantDB::getPdo();
        $nom = trim($userNom ?? '');

        // Si le nom d'affichage n'est pas passé, le résoudre depuis la fiche copropriétaire
        if ($nom === '') {
            $cop = self::getCoproprietaireInfo($copId);
            if ($cop) {
                $nom = trim(($cop['prenom'] ?? '').' '.($cop['nom'] ?? ''));
            }
        }

        // Recherche floue par auteur pour couvrir le nom complet ou le nom de famille
        $nomPattern = '%'.$nom.'%';
        $stmt = $pdo->prepare('
            SELECT * FROM reclamations
            WHERE auteur LIKE ?
            ORDER BY date_creation DESC
        ');
        $stmt->execute([$nomPattern]);

        return $stmt->fetchAll();
    }

    /**
     * Calcule la situation comptable et financière individuelle exacte d'un copropriétaire.
     *
     * Règle de calcul conforme à la Loi 18-00 :
     * 1. Quote-part = Somme(Tantièmes du résident) / Total Tantièmes Résidence.
     * 2. Montant Appelé = Budget Appelé Total de l'exercice × Quote-part.
     * 3. Total Payé = Somme des règlements effectués par le copropriétaire sur l'exercice.
     * 4. Solde Dû = Max(0, Montant Appelé - Total Payé).
     *
     * @param  string|null  $copId  Identifiant unique du copropriétaire.
     * @param  int|null  $exercice  Exercice comptable analysé (défaut : année en cours).
     * @return array Bilan comptable individuel complet avec indicateurs de conformité.
     */
    public static function getResidentSituation(?string $copId, ?int $exercice = null): array
    {
        $pdo = TenantDB::getPdo();
        $exercice = $exercice ?? (int) date('Y');

        // Récupération des lots détenus par le copropriétaire
        $lots = self::getResidentLots($copId);

        // Étape 1 : Calcul de l'assiette privative de tantièmes
        $residentTantiemes = 0;
        foreach ($lots as $l) {
            $residentTantiemes += (int) ($l['tantiemes'] ?? 0);
        }

        // Étape 2 : Récupération de l'assiette totale de copropriété (10 000 tantièmes)
        $stmtTotal = $pdo->query('SELECT SUM(tantiemes) FROM lots');
        $totalResidenceTantiemes = (int) $stmtTotal->fetchColumn();
        if ($totalResidenceTantiemes <= 0) {
            $totalResidenceTantiemes = 10000;
        }

        // Pourcentage indivis de détention dans les parties communes
        $quotePartPct = $totalResidenceTantiemes > 0
            ? round(($residentTantiemes / $totalResidenceTantiemes) * 100, 2)
            : 0;

        // Étape 3 : Total des versements enregistrés pour ce résident sur l'exercice
        $paiements = self::getResidentPaiements($copId, $exercice);
        $totalPaye = 0.0;
        foreach ($paiements as $p) {
            $totalPaye += (float) ($p['montant'] ?? 0);
        }

        // Étape 4 : Somme des appels de fonds formellement votés et émis pour l'exercice
        $stmtAppels = $pdo->prepare('SELECT SUM(montant_total) FROM appels_fonds WHERE exercice = ?');
        $stmtAppels->execute([$exercice]);
        $budgetAppeleTotal = (float) $stmtAppels->fetchColumn();

        // Étape 5 : Montant appelé dû au prorata exact des tantièmes
        // Si aucun appel n'a été émis sur l'immeuble, le montant appelé est strictement de 0.00 DH
        $totalAppele = ($totalResidenceTantiemes > 0 && $budgetAppeleTotal > 0)
            ? round($budgetAppeleTotal * ($residentTantiemes / $totalResidenceTantiemes), 2)
            : 0.0;

        // Solde net restant dû et badge de situation (À Jour si solde <= 0)
        $soldeDu = max(0.0, round($totalAppele - $totalPaye, 2));
        $isAJour = $soldeDu <= 0;

        return [
            'lots' => $lots,
            'nombreLots' => count($lots),
            'residentTantiemes' => $residentTantiemes,
            'totalResidenceTantiemes' => $totalResidenceTantiemes,
            'quotePartPct' => $quotePartPct,
            'totalPaye' => $totalPaye,
            'totalAppele' => $totalAppele,
            'soldeDu' => $soldeDu,
            'isAJour' => $isAJour,
            'nombrePaiements' => count($paiements),
            'dernierPaiement' => $paiements[0] ?? null,
        ];
    }

    /**
     * Récupère la liste des Assemblées Générales de la copropriété pour consultation par le résident.
     *
     * @return array Liste des séances d'AG avec résolutions et procès-verbaux.
     */
    public static function getAssemblees(): array
    {
        return TenantDB::getAssemblees();
    }

    /**
     * Récupère les projets de grands travaux et rénovations votés en AG.
     *
     * @return array Liste des chantiers avec budgets estimés et dates d'avancement.
     */
    public static function getProjets(): array
    {
        return TenantDB::getProjets();
    }

    /**
     * Récupère l'historique officiel du carnet d'entretien de l'immeuble (Loi 18-00).
     *
     * @return array Liste des interventions techniques sur les parties communes.
     */
    public static function getCarnet(): array
    {
        return TenantDB::getCarnet();
    }
}
