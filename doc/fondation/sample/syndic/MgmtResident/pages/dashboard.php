<?php
/**
 * ============================================================================
 * SYNDIC CONNECT - PORTAIL RÉSIDENT : TABLEAU DE BORD PERSONNEL (COCKPIT)
 * ============================================================================
 *
 * @author     ZetaAdmin / Syndic Development Team
 *
 * @version    2.4.0
 *
 * @license    Proprietary / Strict Dahir n° 1-02-298 compliance
 *
 * RÔLE DU MODULE ET CADRE JURIDIQUE :
 * ----------------------------------------------------------------------------
 * Ce tableau de bord constitue l'interface centrale du copropriétaire ou résident.
 * Il regroupe en un coup d'œil l'état comptable personnel, les quittances délivrées,
 * les réclamations ouvertes et les coordonnées utiles de la copropriété.
 *
 * Principes clés (Dahir n° 1-02-298 portant promulgation de la Loi n° 18-00) :
 * - Article 25 : Exigibilité des provisions trimestrielles votées en AG et décharge
 *   par quittance libératoire numérotée.
 * - Article 36 & 37 : Calcul des quotes-parts et droits de vote proportionnels
 *   aux tantièmes détenus sur les 10 000 tantièmes de la copropriété.
 * - Transparence et Reddition : Consultation directe des procès-verbaux de la
 *   dernière assemblée générale et des signalements techniques.
 * - Contrôle d'accès strict : Les informations comptables et lots affichés
 *   sont strictement restreints au copropriétaire connecté ($copId), éliminant
 *   tout risque d'escalade horizontale de privilèges.
 */

declare(strict_types=1);

// ============================================================================
// 1. EXTRACTION DU CONTEXTE DE SESSION ET DES DONNÉES DU COPROPRIÉTAIRE
// ============================================================================

/**
 * Utilisateur copropriétaire authentifié en session.
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
 * Paramètres généraux et coordonnées officielles de la copropriété.
 *
 * @var array<string, mixed> $residence
 */
$residence = $residence ?? TenantDB::getResidence();

/**
 * Indicateur de verrouillage administratif de la base tenant (lecture seule).
 *
 * @var bool $isReadOnly
 */
$isReadOnly = $isReadOnly ?? TenantDB::isReadOnly();

/**
 * Identifiant primaire de l'enregistrement copropriétaire associé au compte.
 *
 * @var int|null $copId
 */
$copId = $copId ?? ($user['coproprietaire_id'] ?? null);

/**
 * Liste des lots privatifs détenus par ce copropriétaire dans l'immeuble.
 *
 * @var array<int, array<string, mixed>> $residentLots
 */
$residentLots = $residentLots ?? ResidentDB::getResidentLots($copId);

/**
 * Exercice comptable sélectionné pour l'analyse des charges (par défaut 2025).
 *
 * @var int $ex
 */
$ex = (int) ($selectedExercice ?? 2025);

/**
 * Situation comptable calculée du copropriétaire (tantièmes, quote-part %, total payé, solde dû).
 *
 * @var array<string, mixed> $situation
 */
$situation = $situation ?? ResidentDB::getResidentSituation($copId, $ex);

/**
 * Liste des règlements et quittances libératoires obtenus sur l'exercice.
 *
 * @var array<int, array<string, mixed>> $paiements
 */
$paiements = ResidentDB::getResidentPaiements($copId, $ex);

/**
 * Tickets d'incidents techniques enregistrés par ce copropriétaire.
 *
 * @var array<int, array<string, mixed>> $reclamations
 */
$reclamations = ResidentDB::getResidentReclamations($copId, $user['nom'] ?? '');

/**
 * Liste chronologique des assemblées générales enregistrées.
 *
 * @var array<int, array<string, mixed>> $assemblees
 */
$assemblees = ResidentDB::getAssemblees();

/**
 * Liste des chantiers et grands travaux de copropriété.
 *
 * @var array<int, array<string, mixed>> $projets
 */
$projets = ResidentDB::getProjets();
?>

<!-- ========================================================================= -->
<!-- CONTENEUR PRINCIPAL DU TABLEAU DE BORD RÉSIDENT                           -->
<!-- ========================================================================= -->
<div class="space-y-6">

    <!-- ===================================================================== -->
    <!-- 1. HERO BANNER : ACCUEIL PERSONNALISÉ & STATUT COMPTABLE              -->
    <!-- ===================================================================== -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-[#1E0427] via-[#2D063A] to-[#1E0427] border border-[#3D154F] p-6 lg:p-8 text-white shadow-xl">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-2">
                <!-- Badges de conformité légale et de situation comptable -->
                <div class="flex items-center gap-2">
                    <span class="px-3 py-1 rounded-full text-[11px] font-bold bg-white/10 border border-white/20 text-[#F26968] uppercase tracking-wide">
                        Portail Officiel Dahir n° 1-02-298 (Loi 18-00)
                    </span>
                    <?php if ($situation['isAJour']) { ?>
                        <!-- Badge vert : Aucune dette de charges -->
                        <span class="px-3 py-1 rounded-full text-[11px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                            ✅ Situation Comptable à Jour
                        </span>
                    <?php } else { ?>
                        <!-- Badge rouge / ambre : Solde débiteur en attente de régularisation -->
                        <span class="px-3 py-1 rounded-full text-[11px] font-bold bg-rose-500/20 text-rose-300 border border-rose-500/30">
                            ⚠️ Solde Débiteur en Attente
                        </span>
                    <?php } ?>
                </div>

                <!-- Salutation personnalisée au copropriétaire -->
                <h2 class="text-2xl lg:text-3xl font-extrabold tracking-tight">
                    Bonjour, <?= htmlspecialchars($user['nom'] ?? 'Copropriétaire') ?>
                </h2>

                <p class="text-xs text-slate-300 max-w-2xl leading-relaxed">
                    Bienvenue dans votre cockpit privé. Retrouvez ici en toute transparence l'état de vos charges de copropriété, téléchargez vos quittances officielles avec valeur libératoire et suivez la vie de votre immeuble.
                </p>
            </div>

            <!-- Boutons d'actions rapides (Signalement d'incident / Téléchargement quittances) -->
            <div class="flex flex-wrap md:flex-col gap-2.5 shrink-0">
                <?php if (! $isReadOnly) { ?>
                    <button
                        onclick="document.getElementById('modal-add-reclamation')?.classList.remove('hidden')"
                        class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-[#D91C6E] to-[#F27835] hover:opacity-95 text-white font-bold text-xs shadow-lg flex items-center gap-2 transition"
                    >
                        <span aria-hidden="true">🛠️</span>
                        <span>Signaler un Incident</span>
                    </button>
                <?php } else { ?>
                    <span class="px-4 py-2.5 rounded-xl bg-white/10 border border-white/20 text-slate-300 font-bold text-xs flex items-center gap-2" title="Mode lecture seule">
                        <span aria-hidden="true">🔒</span>
                        <span>Consultation Seule</span>
                    </span>
                <?php } ?>

                <a
                    href="index.php?tenant=<?= urlencode($guid) ?>&page=paiements"
                    class="px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white font-semibold text-xs border border-white/20 flex items-center gap-2 transition"
                >
                    <span aria-hidden="true">🧾</span>
                    <span>Mes Quittances (<?= count($paiements) ?>)</span>
                </a>
            </div>
        </div>

        <!-- Effet visuel d'arrière-plan avec dégradé flouté -->
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-[#D91C6E]/10 rounded-full blur-3xl pointer-events-none"></div>
    </div>

    <!-- ===================================================================== -->
    <!-- 2. CARTES KPI : PATRIMOINE, QUOTE-PART, RÈGLEMENTS ET SOLDE COMPTABLE -->
    <!-- ===================================================================== -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- KPI 1 : Lots Détenus dans la Copropriété -->
        <div class="p-5 rounded-2xl bg-white dark:bg-[#1E0427] border border-[#F0E4DC] dark:border-[#3D154F] shadow-sm space-y-3">
            <div class="flex items-center justify-between text-xs">
                <span class="font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider text-[10px]">Mes Lots</span>
                <span class="p-2 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400" aria-hidden="true">🏠</span>
            </div>
            <div>
                <div class="text-2xl font-black text-slate-900 dark:text-white">
                    <?= count($residentLots) ?> <?= count($residentLots) > 1 ? 'Lots' : 'Lot' ?>
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    <?php if (! empty($residentLots)) { ?>
                        <?php foreach ($residentLots as $idx => $lt) { ?>
                            <span class="font-bold text-slate-800 dark:text-slate-200">#<?= htmlspecialchars($lt['numero']) ?></span> (<?= htmlspecialchars($lt['type']) ?>)<?= $idx < count($residentLots) - 1 ? ', ' : '' ?>
                        <?php } ?>
                    <?php } else { ?>
                        Lot personnel
                    <?php } ?>
                </div>
            </div>
        </div>

        <!-- KPI 2 : Quote-Part en Tantièmes & Poids électoral en AG -->
        <div class="p-5 rounded-2xl bg-white dark:bg-[#1E0427] border border-[#F0E4DC] dark:border-[#3D154F] shadow-sm space-y-3">
            <div class="flex items-center justify-between text-xs">
                <span class="font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider text-[10px]">Tantièmes</span>
                <span class="p-2 rounded-xl bg-[#D91C6E]/10 text-[#D91C6E]" aria-hidden="true">📊</span>
            </div>
            <div>
                <div class="text-2xl font-black text-[#D91C6E] dark:text-[#F26968]">
                    <?= number_format($situation['residentTantiemes'], 0, ',', ' ') ?>
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    Sur <?= number_format($situation['totalResidenceTantiemes'], 0, ',', ' ') ?> &bull; <strong><?= $situation['quotePartPct'] ?>%</strong> des voix
                </div>
            </div>
        </div>

        <!-- KPI 3 : Total Cotisations Réglées sur l'Exercice -->
        <div class="p-5 rounded-2xl bg-white dark:bg-[#1E0427] border border-[#F0E4DC] dark:border-[#3D154F] shadow-sm space-y-3">
            <div class="flex items-center justify-between text-xs">
                <span class="font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider text-[10px]">Cotisations <?= $ex ?></span>
                <span class="p-2 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400" aria-hidden="true">💳</span>
            </div>
            <div>
                <div class="text-2xl font-black text-slate-900 dark:text-white">
                    <?= number_format($situation['totalPaye'], 2, ',', ' ') ?> <span class="text-xs font-semibold text-slate-400">MAD</span>
                </div>
                <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    <?= count($paiements) ?> versement(s) avec quittance
                </div>
            </div>
        </div>

        <!-- KPI 4 : Solde Comptable & Exigibilité Immédiate -->
        <div class="p-5 rounded-2xl bg-white dark:bg-[#1E0427] border border-[#F0E4DC] dark:border-[#3D154F] shadow-sm space-y-3">
            <div class="flex items-center justify-between text-xs">
                <span class="font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider text-[10px]">Situation Comptable</span>
                <span class="p-2 rounded-xl <?= $situation['isAJour'] ? 'bg-emerald-500/10 text-emerald-600' : 'bg-rose-500/10 text-rose-600' ?>" aria-hidden="true">
                    <?= $situation['isAJour'] ? '⚖️' : '⚠️' ?>
                </span>
            </div>
            <div>
                <div class="text-2xl font-black <?= $situation['isAJour'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' ?>">
                    <?= $situation['isAJour'] ? '0,00 MAD' : number_format($situation['soldeDu'], 2, ',', ' ').' MAD' ?>
                </div>
                <div class="text-xs <?= $situation['isAJour'] ? 'text-emerald-600 dark:text-emerald-400 font-semibold' : 'text-rose-600 dark:text-rose-400' ?> mt-0.5">
                    <?= $situation['isAJour'] ? 'Aucun arriéré en cours' : 'Montant restant à régler' ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- 3. DISPOSITION EN 2 COLONNES (2/3 ACTIVITÉS & 1/3 COORDONNÉES)        -->
    <!-- ===================================================================== -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- COLONNE GAUCHE (2/3) : Quittances Libératoires & Signalements d'Incidents -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Section 1 : Mes Derniers Règlements & Quittances Libératoires -->
            <div class="rounded-3xl bg-white dark:bg-[#1E0427] border border-[#F0E4DC] dark:border-[#3D154F] p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-[#D91C6E] to-[#F27835] text-white flex items-center justify-center font-bold text-sm">
                            🧾
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Mes Dernières Quittances Libératoires</h3>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">Reçus officiels délivrés par le syndic (Dahir n° 1-02-298, Art. 25)</p>
                        </div>
                    </div>
                    <a
                        href="index.php?tenant=<?= urlencode($guid) ?>&page=paiements"
                        class="text-xs font-bold text-[#D91C6E] dark:text-[#F26968] hover:underline"
                    >
                        Tout voir &rarr;
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-[#FDF8F5] dark:bg-[#14021C] border-y border-[#F0E4DC] dark:border-[#3D154F] text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                            <tr>
                                <th class="p-3">N° Quittance</th>
                                <th class="p-3">Date</th>
                                <th class="p-3">Lot</th>
                                <th class="p-3">Mode</th>
                                <th class="p-3 text-right">Montant</th>
                                <th class="p-3 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#F0E4DC]/60 dark:divide-[#3D154F]/60">
                            <?php if (empty($paiements)) { ?>
                                <tr>
                                    <td colspan="6" class="p-6 text-center text-slate-400 text-xs">
                                        Aucun règlement enregistré pour l'exercice <?= $ex ?>.
                                    </td>
                                </tr>
                            <?php } else { ?>
                                <!-- Affichage limité aux 5 derniers règlements -->
                                <?php foreach (array_slice($paiements, 0, 5) as $p) { ?>
                                    <tr class="hover:bg-slate-50/50 dark:hover:bg-[#250832]/50 transition">
                                        <td class="p-3 font-mono font-bold text-[#D91C6E] dark:text-[#F26968]">
                                            <?= htmlspecialchars($p['numero_quittance']) ?>
                                        </td>
                                        <td class="p-3 text-slate-600 dark:text-slate-300">
                                            <?= formatDateFR($p['date_paiement']) ?>
                                        </td>
                                        <td class="p-3 text-slate-700 dark:text-slate-200 font-semibold">
                                            Lot #<?= htmlspecialchars((string) ($p['lot_numero'] ?: '101')) ?>
                                        </td>
                                        <td class="p-3 text-slate-500 dark:text-slate-400 capitalize">
                                            <?= htmlspecialchars($p['mode_paiement']) ?>
                                        </td>
                                        <td class="p-3 text-right font-black text-slate-900 dark:text-white">
                                            <?= formatMAD((float) $p['montant']) ?>
                                        </td>
                                        <td class="p-3 text-center">
                                            <!-- Bouton d'impression directe du reçu officiel format A4 -->
                                            <a
                                                href="quittance.php?tenant=<?= urlencode($guid) ?>&id=<?= urlencode((string) $p['id']) ?>"
                                                target="_blank"
                                                class="px-2.5 py-1 rounded-lg bg-[#D91C6E]/10 hover:bg-[#D91C6E]/20 text-[#D91C6E] dark:text-[#F26968] font-bold text-[11px] transition inline-flex items-center gap-1"
                                                title="Télécharger / Imprimer la quittance officielle"
                                            >
                                                <span aria-hidden="true">🖨️</span>
                                                <span>Imprimer</span>
                                            </a>
                                        </td>
                                    </tr>
                                <?php } ?>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Section 2 : Mes Signalements & Incidents Récents -->
            <div class="rounded-3xl bg-white dark:bg-[#1E0427] border border-[#F0E4DC] dark:border-[#3D154F] p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-amber-500 to-[#F27835] text-white flex items-center justify-center font-bold text-sm">
                            🛠️
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Mes Signalements d'Incidents</h3>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">Pannes signalées dans les parties communes ou privatives</p>
                        </div>
                    </div>

                    <?php if (! $isReadOnly) { ?>
                        <button
                            onclick="document.getElementById('modal-add-reclamation')?.classList.remove('hidden')"
                            class="text-xs font-bold text-[#D91C6E] dark:text-[#F26968] hover:underline"
                        >
                            ➕ Nouveau &rarr;
                        </button>
                    <?php } ?>
                </div>

                <div class="space-y-3">
                    <?php if (empty($reclamations)) { ?>
                        <div class="p-6 text-center text-slate-400 text-xs rounded-2xl bg-[#FDF8F5] dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F]">
                            Aucun signalement en cours. Tout fonctionne normalement !
                        </div>
                    <?php } else { ?>
                        <!-- Affichage des 3 derniers tickets déposés -->
                        <?php foreach (array_slice($reclamations, 0, 3) as $r) { ?>
                            <?php
                                $statusBadge = match ($r['statut']) {
                                    'resolu' => 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20',
                                    'en_cours' => 'bg-amber-500/10 text-amber-600 border-amber-500/20',
                                    'annule' => 'bg-slate-500/10 text-slate-600 border-slate-500/20',
                                    default => 'bg-rose-500/10 text-rose-600 border-rose-500/20'
                                };
                            $statusLabel = match ($r['statut']) {
                                'resolu' => 'Résolu',
                                'en_cours' => 'En Cours de Traitement',
                                'annule' => 'Annulé',
                                default => 'Ouvert / En Attente'
                            };
                            ?>
                            <div class="p-4 rounded-2xl bg-[#FDF8F5] dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F] space-y-2">
                                <div class="flex items-center justify-between gap-2 flex-wrap">
                                    <div class="font-bold text-xs text-slate-900 dark:text-white">
                                        <?= htmlspecialchars($r['titre']) ?>
                                    </div>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border <?= $statusBadge ?>">
                                        <?= $statusLabel ?>
                                    </span>
                                </div>
                                <p class="text-xs text-slate-600 dark:text-slate-400">
                                    <?= htmlspecialchars($r['description']) ?>
                                </p>
                                <div class="flex items-center justify-between text-[11px] text-slate-400 pt-1 border-t border-[#F0E4DC]/60 dark:border-[#3D154F]/60">
                                    <span>Signalé le <?= formatDateFR($r['date_creation'] ?? date('Y-m-d')) ?></span>
                                    <?php if (! empty($r['reponse_syndic'])) { ?>
                                        <span class="text-[#D91C6E] dark:text-[#F26968] font-medium">Réponse syndic disponible 💬</span>
                                    <?php } ?>
                                </div>
                            </div>
                        <?php } ?>
                    <?php } ?>
                </div>
            </div>
        </div>

        <!-- COLONNE DROITE (1/3) : Coordonnées Bancaires, Contacts Syndic & AG -->
        <div class="space-y-6">

            <!-- Card RIB & Paiement des Charges -->
            <div class="rounded-3xl bg-white dark:bg-[#1E0427] border border-[#F0E4DC] dark:border-[#3D154F] p-6 shadow-sm space-y-4">
                <div class="flex items-center gap-2.5 pb-2 border-b border-[#F0E4DC] dark:border-[#3D154F]">
                    <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white flex items-center justify-center font-bold text-sm">
                        🏦
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Règlement des Charges</h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Coordonnées bancaires de la copropriété</p>
                    </div>
                </div>

                <div class="p-4 rounded-2xl bg-[#FDF8F5] dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F] space-y-3 text-xs">
                    <div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase">Titulaire du Compte</div>
                        <div class="font-bold text-slate-900 dark:text-white mt-0.5">
                            Syndicat des Copropriétaires <?= htmlspecialchars($residence['nom']) ?>
                        </div>
                    </div>

                    <div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase">Banque Domiciliataire</div>
                        <div class="font-semibold text-slate-800 dark:text-slate-200 mt-0.5">
                            <?= htmlspecialchars($residence['banque_nom'] ?? 'Attijariwafa Bank') ?>
                        </div>
                    </div>

                    <div>
                        <div class="text-[10px] font-bold text-slate-400 uppercase">Relevé d'Identité Bancaire (RIB 24 chiffres)</div>
                        <div class="p-2.5 rounded-xl bg-white dark:bg-[#250832] border border-[#F0E4DC] dark:border-[#3D154F] font-mono font-bold text-slate-900 dark:text-white text-xs break-all select-all mt-1">
                            <?= htmlspecialchars($residence['rib_bancaire'] ?? '007 780 0001234567890123 45') ?>
                        </div>
                    </div>

                    <div class="p-2.5 rounded-xl bg-amber-500/10 border border-amber-500/20 text-[11px] text-amber-800 dark:text-amber-300">
                        ⚠️ <strong>Important :</strong> Indiquez impérativement votre nom et votre <strong>Lot #<?= htmlspecialchars((string) ($residentLots[0]['numero'] ?? '101')) ?></strong> dans le libellé de tout virement bancaire pour validation rapide de votre quittance.
                    </div>
                </div>
            </div>

            <!-- Card Mon Syndic & Contacts Immeuble -->
            <div class="rounded-3xl bg-white dark:bg-[#1E0427] border border-[#F0E4DC] dark:border-[#3D154F] p-6 shadow-sm space-y-4">
                <div class="flex items-center gap-2.5 pb-2 border-b border-[#F0E4DC] dark:border-[#3D154F]">
                    <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-[#D91C6E] to-[#F27835] text-white flex items-center justify-center font-bold text-sm">
                        📞
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Mon Syndic & Urgences</h3>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Interlocuteurs officiels de l'immeuble</p>
                    </div>
                </div>

                <div class="space-y-3 text-xs">
                    <div class="p-3.5 rounded-2xl bg-[#FDF8F5] dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F] space-y-1">
                        <div class="text-[10px] font-bold text-[#D91C6E] dark:text-[#F26968] uppercase">Syndic en Exercice</div>
                        <div class="font-bold text-slate-900 dark:text-white text-sm">
                            <?= htmlspecialchars($residence['nom_syndic'] ?? 'Syndic Élu') ?>
                        </div>
                        <div class="text-slate-600 dark:text-slate-400 flex items-center gap-1.5 pt-1">
                            <span>📧</span>
                            <a href="mailto:<?= htmlspecialchars($residence['email_syndic']) ?>" class="hover:underline"><?= htmlspecialchars($residence['email_syndic']) ?></a>
                        </div>
                        <?php if (! empty($residence['tel_syndic'])) { ?>
                            <div class="text-slate-600 dark:text-slate-400 flex items-center gap-1.5">
                                <span>📱</span>
                                <a href="tel:<?= htmlspecialchars($residence['tel_syndic']) ?>" class="hover:underline"><?= htmlspecialchars($residence['tel_syndic']) ?></a>
                            </div>
                        <?php } ?>
                    </div>

                    <!-- Concierge / Gardien d'immeuble -->
                    <div class="p-3.5 rounded-2xl bg-[#FDF8F5] dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F] space-y-1">
                        <div class="text-[10px] font-bold text-slate-400 uppercase">Gardien / Concierge</div>
                        <div class="font-bold text-slate-900 dark:text-white">
                            Si Mohamed (Poste Accueil & Loge)
                        </div>
                        <div class="text-slate-500 text-[11px]">Disponible 7j/7 pour la réception des colis et accès parkings.</div>
                    </div>

                    <!-- Numéros d'Urgence Officiels du Royaume du Maroc -->
                    <div class="p-3 rounded-2xl bg-slate-50 dark:bg-[#14021C] border border-slate-200 dark:border-[#3D154F] space-y-1.5 text-[11px]">
                        <div class="font-bold text-slate-700 dark:text-slate-300">Numéros d'Urgence :</div>
                        <div class="grid grid-cols-2 gap-2 text-slate-600 dark:text-slate-400 font-mono">
                            <div>🚒 Pompiers : <strong>15</strong></div>
                            <div>👮 Police : <strong>19</strong></div>
                            <div>🚑 SAMU : <strong>141</strong></div>
                            <div>🚨 Gendarmerie : <strong>177</strong></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dernière Assemblée Générale et consultation du PV officiel -->
            <?php if (! empty($assemblees)) { ?>
                <?php $lastAg = $assemblees[0]; ?>
                <div class="rounded-3xl bg-white dark:bg-[#1E0427] border border-[#F0E4DC] dark:border-[#3D154F] p-6 shadow-sm space-y-3">
                    <div class="flex items-center justify-between pb-2 border-b border-[#F0E4DC] dark:border-[#3D154F]">
                        <div class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <span>📋</span>
                            <span>Dernière Assemblée Générale</span>
                        </div>
                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-[#D91C6E]/10 text-[#D91C6E] font-bold uppercase">
                            <?= htmlspecialchars($lastAg['type']) ?>
                        </span>
                    </div>
                    <div class="text-xs space-y-1.5">
                        <div class="font-bold text-slate-900 dark:text-white">Séance du <?= formatDateFR($lastAg['date']) ?></div>
                        <div class="text-slate-500 text-[11px] line-clamp-2"><?= htmlspecialchars($lastAg['description'] ?: 'Approbation des comptes et résolutions votées.') ?></div>
                        <div class="pt-2">
                            <a
                                href="pv_assemblee.php?tenant=<?= urlencode($guid) ?>&id=<?= urlencode((string) $lastAg['id']) ?>"
                                target="_blank"
                                class="block w-full py-2 text-center rounded-xl bg-[#FDF8F5] dark:bg-[#14021C] hover:bg-slate-100 dark:hover:bg-[#250832] border border-[#F0E4DC] dark:border-[#3D154F] text-xs font-bold text-[#D91C6E] dark:text-[#F26968] transition"
                            >
                                📄 Consulter le PV Officiel &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            <?php } ?>

        </div>
    </div>
</div>
