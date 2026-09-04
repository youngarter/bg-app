<?php
/**
 * ============================================================================
 * SYNDIC CONNECT - PORTAIL RÉSIDENT : PIED DE PAGE & SIGNALEMENT D'INCIDENTS
 * ============================================================================
 *
 * @author     ZetaAdmin / Syndic Development Team
 *
 * @version    2.4.0
 *
 * @license    Proprietary / Strict Dahir n° 1-02-298 compliance
 *
 * RÔLE DU MODULE ET SÉCURISATION DU WORKFLOW INCIDENTS :
 * ----------------------------------------------------------------------------
 * Ce module ferme la structure HTML de l'espace résident et fournit le formulaire
 * modulaire de signalement de panne technique ou de dégradation.
 *
 * Principes clés :
 * - Pied de page institutionnel : Rappel du cadre légal du Dahir n° 1-02-298
 *   (Loi 18-00) régissant la copropriété et la transparence financière.
 * - Modale de réclamation (#modal-add-reclamation) :
 *   Permet au résident de qualifier l'incident (ascenseur, plomberie, éclairage, etc.),
 *   de cibler son lot privatif ou les parties communes générales, et de soumettre
 *   directement le ticket d'intervention au bureau du syndic.
 * - Contrôle Lecture Seule : Si la résidence est verrouillée administrativement,
 *   la modale de déclaration est exclue du rendu DOM pour interdire toute écriture.
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2).'/MgmtResidence/includes/brand.php';

/**
 * Indicateur de verrouillage de la résidence en consultation seule.
 *
 * @var bool $isReadOnly
 */
$isReadOnly = TenantDB::isReadOnly();

/**
 * GUID du tenant actif.
 *
 * @var string $guid
 */
$guid = TenantDB::resolveGuid();

/**
 * Utilisateur copropriétaire en session.
 *
 * @var array<string, mixed> $user
 */
$user = getCurrentResidentUser();

/**
 * Identifiant du copropriétaire rattaché.
 *
 * @var int|null $copId
 */
$copId = $user['coproprietaire_id'] ?? null;

/**
 * Liste des lots personnels pour le sélecteur d'emplacement du ticket.
 *
 * @var array<int, array<string, mixed>> $residentLots
 */
$residentLots = ResidentDB::getResidentLots($copId);
?>
        </main>

        <!-- ================================================================= -->
        <!-- PIED DE PAGE LÉGISLATIF & MENTIONS OFFICIELLES DU PORTAIL         -->
        <!-- ================================================================= -->
        <footer class="border-t border-[#F0E4DC] dark:border-[#3D154F] bg-white/60 dark:bg-[#1E0427]/60 py-4 px-6 text-center text-xs text-slate-400 space-y-1 transition-colors">
            <div class="flex items-center justify-center gap-2 font-medium">
                <span>Espace Résident & Copropriétaire développé par</span>
                <span class="font-bold text-[#D91C6E] dark:text-[#F26968]">Bayan Gestion</span>
                <span>&bull;</span>
                <span><?= htmlspecialchars((string) ($residence['nom'] ?? 'Copropriété')) ?></span>
            </div>
            <div class="text-[11px] text-slate-500">
                Royaume du Maroc &bull; Dahir n° 1-02-298 (Loi 18-00 relative à la copropriété des immeubles bâtis) &bull; Droit de regard et transparence financière
            </div>
        </footer>
    </div>

    <?php if (! $isReadOnly) { ?>
        <!-- ================================================================= -->
        <!-- MODALE FLOTTANTE DE DÉCLARATION D'INCIDENT / PANNE TECHNIQUE      -->
        <!-- ================================================================= -->
        <div id="modal-add-reclamation" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
            <div class="bg-white dark:bg-[#1E0427] border border-[#F0E4DC] dark:border-[#3D154F] rounded-3xl p-6 max-w-lg w-full space-y-4 shadow-2xl">
                <!-- En-tête de la modale de réclamation -->
                <div class="flex items-center justify-between pb-3 border-b border-[#F0E4DC] dark:border-[#3D154F]">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-[#D91C6E] to-[#F27835] text-white flex items-center justify-center font-bold text-sm">
                            🛠️
                        </div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Signaler un Incident au Syndic</h3>
                    </div>
                    <button
                        onclick="document.getElementById('modal-add-reclamation').classList.add('hidden')"
                        class="p-1 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-white"
                        aria-label="Fermer la fenêtre modale"
                    >
                        ✕
                    </button>
                </div>

                <!-- Formulaire de transmission vers actions/add_reclamation.php -->
                <form method="POST" action="actions/add_reclamation.php" class="space-y-4 text-xs">
                    <!-- Isolation tenant et attribution auteur -->
                    <input type="hidden" name="tenant" value="<?= htmlspecialchars($guid) ?>">
                    <input type="hidden" name="coproprietaire_id" value="<?= htmlspecialchars((string) ($copId ?? '')) ?>">
                    <input type="hidden" name="auteur" value="<?= htmlspecialchars($user['nom'] ?? 'Résident') ?>">

                    <!-- Catégorisation technique de l'anomalie -->
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Catégorie de l'Incident</label>
                        <select
                            name="type"
                            required
                            class="w-full px-3 py-2 bg-slate-50 dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F] rounded-xl text-slate-900 dark:text-white focus:outline-none focus:border-[#D91C6E]"
                        >
                            <option value="panne">Ascenseur / Équipement bloqué</option>
                            <option value="eclairage">Éclairage des parties communes</option>
                            <option value="plomberie">Fuite d'eau / Plomberie parties communes</option>
                            <option value="proprete">Nettoyage / Propreté / Ordures</option>
                            <option value="securite">Sécurité / Porte d'entrée / Interphone</option>
                            <option value="autre">Autre réclamation</option>
                        </select>
                    </div>

                    <!-- Localisation du problème (parties communes ou lot individuel) -->
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Lot / Emplacement concerné</label>
                        <select
                            name="lot_id"
                            class="w-full px-3 py-2 bg-slate-50 dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F] rounded-xl text-slate-900 dark:text-white focus:outline-none focus:border-[#D91C6E]"
                        >
                            <option value="">-- Parties Communes Générales --</option>
                            <?php foreach ($residentLots as $lot) { ?>
                                <option value="<?= htmlspecialchars((string) $lot['id']) ?>">
                                    Mon Lot #<?= htmlspecialchars((string) $lot['numero']) ?> (<?= htmlspecialchars((string) $lot['type']) ?>)
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <!-- Intitulé court de la réclamation -->
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Titre de votre Signalement *</label>
                        <input
                            type="text"
                            name="titre"
                            required
                            placeholder="Ex: Interphone en panne, ampoule palier 2ème étage..."
                            class="w-full px-3 py-2 bg-slate-50 dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F] rounded-xl text-slate-900 dark:text-white focus:outline-none focus:border-[#D91C6E]"
                        >
                    </div>

                    <!-- Description détaillée des faits observés -->
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Description détaillée du problème *</label>
                        <textarea
                            name="description"
                            rows="3"
                            required
                            placeholder="Précisez les faits, la localisation exacte et la gêne occasionnée..."
                            class="w-full px-3 py-2 bg-slate-50 dark:bg-[#14021C] border border-[#F0E4DC] dark:border-[#3D154F] rounded-xl text-slate-900 dark:text-white focus:outline-none focus:border-[#D91C6E]"
                        ></textarea>
                    </div>

                    <!-- Boutons d'action -->
                    <div class="flex justify-end gap-2 pt-2 border-t border-[#F0E4DC] dark:border-[#3D154F]">
                        <button
                            type="button"
                            onclick="document.getElementById('modal-add-reclamation').classList.add('hidden')"
                            class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-[#250832] text-slate-700 dark:text-slate-300 font-semibold"
                        >
                            Annuler
                        </button>
                        <button
                            type="submit"
                            class="px-5 py-2 rounded-xl bg-gradient-to-r from-[#D91C6E] to-[#F27835] hover:opacity-95 text-white font-bold shadow-md"
                        >
                            Transmettre au Syndic
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php } ?>
</body>
</html>
