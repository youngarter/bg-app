<?php

/**
 * ==============================================================================
 * SyndicPro Maroc — Fonctions de Formatage Financier, Dates & Constantes Métier
 * ==============================================================================
 * Ce module utilitaire centralise la mise en forme des valeurs affichées à travers
 * l'ensemble des modules de la copropriété (tableaux de bord, quittances, appels, PV) :
 *
 * - Formatage monétaire conforme aux normes bancaires marocaines (Dirhams / MAD).
 * - Formatage littéral des dates du calendrier grégorien en langue française.
 * - Tables de correspondance pour les modes de paiement et statuts de réclamations.
 * ==============================================================================
 */

declare(strict_types=1);

/**
 * Formate un montant numérique sous forme de chaîne monétaire en Dirhams Marocains (MAD).
 * Utilise la convention typographique : virgule décimale et espace fine comme séparateur de milliers.
 *
 * @param  float|int  $amount  Valeur numérique du montant (ex: 12500.5 ou 12500).
 * @return string Montant formaté avec deux décimales suivi de l'abréviation de devise (ex: "12 500,50 MAD").
 */
function formatMAD(float|int $amount): string
{
    return number_format((float) $amount, 2, ',', ' ').' MAD';
}

/**
 * Convertit une date ISO (YYYY-MM-DD ou YYYY-MM-DD HH:MM:SS) en date littérale française.
 * Exemple : "2026-04-15" -> "15 Avril 2026".
 *
 * @param  string|null  $dateStr  Date au format chaîne ou null.
 * @return string Date formatée en français ou 'N/A' si la date est vide ou invalide.
 */
function formatDateFR(?string $dateStr): string
{
    // Gestion des valeurs nulles ou vides
    if (! $dateStr) {
        return 'N/A';
    }

    // Conversion en timestamp UNIX
    $ts = strtotime($dateStr);
    if (! $ts) {
        return $dateStr; // Retourne la chaîne brute si non analysable
    }

    // Dictionnaire de traduction des mois de l'année
    $mois = [
        1 => 'Janvier',   2 => 'Février',   3 => 'Mars',      4 => 'Avril',
        5 => 'Mai',       6 => 'Juin',      7 => 'Juillet',   8 => 'Août',
        9 => 'Septembre', 10 => 'Octobre',  11 => 'Novembre', 12 => 'Décembre',
    ];

    $j = date('j', $ts); // Jour du mois sans zéro initial (1 à 31)
    $mIndex = (int) date('n', $ts); // Numéro du mois (1 à 12)
    $m = $mois[$mIndex] ?? date('m', $ts);
    $y = date('Y', $ts); // Année sur 4 chiffres

    return "$j $m $y";
}

/**
 * Référentiel des modes de paiement acceptés en comptabilité de copropriété au Maroc.
 * Utilisé pour alimenter les listes déroulantes et l'impression des quittances libératoires.
 */
const MODE_PAIEMENT_LABELS = [
    'virement' => 'Virement Bancaire',
    'cheque' => 'Chèque Bancaire',
    'versement' => 'Versement Espèces en Banque',
    'especes' => 'Espèces en Caisse',
    'prelevement' => 'Prélèvement Automatique',
];

/**
 * Cycle de vie et statuts de traitement des tickets de réclamations techniques.
 */
const STATUT_TICKET_LABELS = [
    'recu' => 'Reçu',
    'en_cours' => 'En Cours de Traitement',
    'resolu' => 'Résolu / Clôturé',
    'rejete' => 'Non Retenu',
];
