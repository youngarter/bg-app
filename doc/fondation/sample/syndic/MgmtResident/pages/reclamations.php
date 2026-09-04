<?php
/**
 * ============================================================================
 * SYNDIC CONNECT - PORTAIL RÉSIDENT : SIGNALEMENT ET SUIVI DES INCIDENTS
 * ============================================================================
 *
 * @author     ZetaAdmin / Syndic Development Team
 *
 * @version    2.4.0
 *
 * @license    Proprietary / Strict Dahir n° 1-02-298 compliance
 *
 * CADRE JURIDIQUE ET TRAITEMENT DES DÉGRADATIONS :
 * ----------------------------------------------------------------------------
 * Ce module permet à chaque copropriétaire ou occupant d'ouvrir un ticket
 * d'incident technique concernant soit les parties communes, soit les équipements
 * collectifs de l'immeuble.
 *
 * Fondements légaux (Loi n° 18-00, Dahir n° 1-02-298) :
 * - Article 26 : Le syndic est chargé d'exécuter les réparations urgentes
 *   nécessaires à la sauvegarde de l'immeuble ou à la sécurité des occupants.
 * - Périmètre de sécurité et isolation des données :
 *   Chaque résident ne visualise que ses propres signalements ou ceux déclarés
 *   sous son nom / identifiant de copropriétaire ($copId), garantissant la
 *   confidentialité des échanges avec le bureau du syndic.
 * - Mode Lecture Seule (Tenant Read-Only) :
 *   Si la résidence est verrouillée administrativement, la soumission de nouveaux
 *   incidents est suspendue tout en conservant l'accès à l'historique résolu.
 */

declare(strict_types=1);

// ============================================================================
// 1. INITIALISATION DU CONTEXTE DE SESSION ET RÉCUPÉRATION DES TICKETS
// ============================================================================

/**
 * Utilisateur copropriétaire connecté extrait de la session sécurisée.
 *
 * @var array<string, mixed> $user
 */
$user = $user ?? getCurrentResidentUser();

/**
 * GUID unique du tenant actif résolu depuis la requête ou le cookie de session.
 *
 * @var string $guid
 */
$guid = $guid ?? TenantDB::resolveGuid();

/**
 * Indicateur de verrouillage administratif de la base tenant (lecture seule).
 *
 * @var bool $isReadOnly
 */
$isReadOnly = $isReadOnly ?? TenantDB::isReadOnly();

/**
 * Identifiant primaire du copropriétaire rattaché au compte utilisateur.
 *
 * @var int|null $copId
 */
$copId = $copId ?? ($user['coproprietaire_id'] ?? null);

/**
 * Récupération filtrée des réclamations créées par ce copropriétaire.
 *
 * @var array<int, array<string, mixed>> $reclamations Liste des tickets du résident
 */
$reclamations = ResidentDB::getResidentReclamations($copId, $user['nom'] ?? '');
?>

<!-- ========================================================================= -->
<!-- CONTENEUR PRINCIPAL DE LA VUE RÉSIDENT : GESTION DES RÉCLAMATIONS         -->
<!-- ========================================================================= -->
<div class="space-y-6">

    <!-- En-tête de page avec bouton d'action contextuel (création ou verrouillé) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-3 border-b border-[#F0E4DC] dark:border-[#3D154F]">
        <div>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">
                Mes Signalements & Incidents
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Suivi en temps réel de vos réclamations et des interventions techniques du syndic
            </p>
        </div>

        <div>
            <?php if (! $isReadOnly) { ?>
                <!-- Bouton d'ouverture de la modale de signalement d'incident -->
                <button
                    onclick="document.getElementById('modal-add-reclamation')?.classList.remove('hidden')"
                    class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-[#D91C6E] to-[#F27835] hover:opacity-95 text-white font-bold text-xs shadow-md flex items-center gap-2 transition"
                >
                    <span aria-hidden="true">➕</span>
                    <span>Signaler un Incident au Syndic</span>
                </button>
            <?php } else { ?>
                <!-- Badge d'avertissement si la résidence est passée en lecture seule -->
                <span class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-700 dark:text-rose-300 text-xs font-bold shadow-sm" title="Création suspendue en lecture seule">
                    <span aria-hidden="true">🔒</span>
                    <span>Signalements Suspendus (Lecture Seule)</span>
                </span>
            <?php } ?>
        </div>
    </div>

    <!-- Instructions d'intervention d'urgence et délais de prise en charge -->
    <div class="p-4 rounded-2xl bg-[#FDF8F5] dark:bg-[#1E0427] border border-[#F0E4DC] dark:border-[#3D154F] text-xs text-slate-600 dark:text-slate-300 flex items-start gap-3">
        <span class="text-xl" aria-hidden="true">ℹ️</span>
        <div class="space-y-1">
            <div class="font-bold text-slate-900 dark:text-white">
                Procédure de Traitement des Pannes & Signalements
            </div>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-relaxed">
                Toute réclamation enregistrée est immédiatement transmise au Syndic et archivée dans le journal technique. Les pannes critiques (ascenseur, fuite majeure en parties communes) font l'objet d'un déclenchement de prestataire sous 4h à 24h. En cas d'urgence absolue, veuillez contacter directement le syndic ou le concierge par téléphone.
            </p>
        </div>
    </div>

    <!-- Liste verticale des tickets d'incidents -->
    <div class="space-y-4">
        <?php if (empty($reclamations)) { ?>
            <!-- État vide : Aucun signalement actif -->
            <div class="p-12 text-center text-slate-400 rounded-3xl bg-white dark:bg-[#1E0427] border border-[#F0E4DC] dark:border-[#3D154F] shadow-sm space-y-2">
                <div class="text-3xl" aria-hidden="true">🎉</div>
                <div class="font-bold text-slate-700 dark:text-slate-200">Aucun incident ouvert</div>
                <div class="text-xs">Vous n'avez aucun signalement en cours. Tout fonctionne normalement dans votre copropriété.</div>
            </div>
        <?php } else { ?>
            <!-- Itération sur chaque ticket d'incident -->
            <?php foreach ($reclamations as $r) { ?>
                <?php
                    // Définition des classes CSS et libellés selon le statut du ticket
                    $statusBadge = match ($r['statut']) {
                        'resolu' => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/30',
                        'en_cours' => 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/30',
                        'annule' => 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/30',
                        default => 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/30'
                    };
                $statusLabel = match ($r['statut']) {
                    'resolu' => 'Résolu & Clôturé',
                    'en_cours' => 'En Cours de Traitement / Prestataire Dépêché',
                    'annule' => 'Annulé',
                    default => 'Ouvert / En Attente de Prise en Charge'
                };
                ?>
                <div class="p-6 rounded-3xl bg-white dark:bg-[#1E0427] border border-[#F0E4DC] dark:border-[#3D154F] shadow-sm space-y-4">
                    <!-- En-tête du ticket : Identifiant, Titre, Catégorie et Statut -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-mono text-[11px] font-bold text-slate-400 uppercase">
                                    #<?= htmlspecialchars((string) $r['id']) ?>
                                </span>
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                                    <?= htmlspecialchars($r['titre']) ?>
                                </h3>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#D91C6E]/10 text-[#D91C6E] dark:text-[#F26968] uppercase">
                                    <?= htmlspecialchars($r['type'] ?? 'Incident') ?>
                                </span>
                            </div>
                            <div class="text-[11px] text-slate-500 dark:text-slate-400">
                                Enregistré le <?= formatDateFR($r['date_creation'] ?? date('Y-m-d')) ?> &bull; Déposé par <strong><?= htmlspecialchars($r['auteur'] ?: $user['nom']) ?></strong>
                            </div>
                        </div>

                        <span class="px-3 py-1 rounded-full text-xs font-bold border <?= $statusBadge ?> self-start sm:self-center">
                            <?= $statusLabel ?>
                        </span>
                    </div>

                    <!-- Description du désordre ou de la panne déclarée -->
                    <div class="p-4 rounded-2xl bg-[#FDF8F5] dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F] text-xs text-slate-700 dark:text-slate-300 leading-relaxed">
                        <?= nl2br(htmlspecialchars($r['description'])) ?>
                    </div>

                    <!-- Réponse officielle ou prise en charge par le bureau du syndic -->
                    <div class="pt-3 border-t border-[#F0E4DC]/60 dark:border-[#3D154F]/60 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
                        <?php if (! empty($r['reponse_syndic'])) { ?>
                            <div class="p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-800 dark:text-emerald-300 flex items-start gap-2 flex-1">
                                <span aria-hidden="true">💬</span>
                                <div>
                                    <strong>Retour du Syndic :</strong> <?= htmlspecialchars($r['reponse_syndic']) ?>
                                </div>
                            </div>
                        <?php } else { ?>
                            <div class="text-slate-400 text-[11px] italic">
                                En cours d'analyse par le bureau du syndic.
                            </div>
                        <?php } ?>

                        <!-- Horodatage de la résolution définitive ou mention ticket actif -->
                        <div class="text-slate-400 text-[11px] font-mono shrink-0">
                            <?= ! empty($r['date_resolution']) ? 'Clôturé le '.formatDateFR($r['date_resolution']) : 'Ticket Actif' ?>
                        </div>
                    </div>
                </div>
            <?php } ?>
        <?php } ?>
    </div>
</div>
