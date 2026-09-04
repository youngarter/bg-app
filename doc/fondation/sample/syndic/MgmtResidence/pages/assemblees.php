<?php
/**
 * ============================================================================
 * BAYAN GESTION - SYNDIC APPLICATION SUITE (PHP 8.2+ PURE VANILLA)
 * ============================================================================
 * FICHIER        : MgmtResidence/pages/assemblees.php
 * TYPE           : Vue Métier / Registre des Assemblées Générales & Passations
 * MODULE         : Gouvernance, Quorum, Délibérations & Changement de Syndic
 * CADRE JURIDIQUE: Dahir n° 1-02-298 du 25 rejeb 1423 (3 octobre 2002)
 *                  promulguant la Loi n° 18-00 relative au statut de la copropriété
 *                  - Articles 16 à 18 : Modalités de convocation et de tenue
 *                  - Article 19       : Constatation du quorum et majorités de vote
 *                  - Article 20       : Passation contradictoire et élection du Syndic
 *                  - Article 24       : Approbation des comptes et quitus
 * ============================================================================
 * RÔLE & LOGIQUE MÉTIER :
 * ----------------------------------------------------------------------------
 * Ce module centralise les procès-verbaux officiels des Assemblées Générales
 * Ordinaires (AGO) et Extraordinaires (AGE) de la copropriété.
 *
 * Fonctionnalités majeures :
 * 1. Contrôle du Quorum Légal (Article 19) :
 *    - Vérification du respect du quorum (tantièmes représentés sur le total de 10 000).
 * 2. Vote du Budget Prévisionnel Annuel & Rubriques :
 *    - Enregistrement du budget global voté et sa répartition estimative sur
 *      8 postes clés (sécurité, nettoyage, énergie, eau, ascenseur, assurance,
 *      honoraires, réserve pour travaux de l'Art. 18).
 * 3. Acte Officiel de Passation d'Exercice (Article 20) :
 *    - En cas d'élection d'un nouveau Syndic, génération automatique du compte
 *      d'accès du successeur, mise à jour de la fiche de copropriété, consignation
 *      de la trésorerie arrêtée et bordereau de remise des archives.
 * 4. Édition des Procès-Verbaux Imprimables :
 *    - Accès direct au PV officiel certifié conforme (`pv_assemblee.php`).
 * ============================================================================
 */

declare(strict_types=1);

// ----------------------------------------------------------------------------
// RÉCUPÉRATION DES ASSEMBLÉES ET MÉTADONNÉES DU TENANT
// ----------------------------------------------------------------------------
// Exercice comptable actif.
$ex = (int) ($selectedExercice ?? date('Y'));

// Ensemble des assemblées générales consignées dans la base de la copropriété.
$assemblees = TenantDB::getAssemblees();

// Fiche signalétique de la résidence.
$res = TenantDB::getResidence();

// Synthèse financière pour préremplir l'état de trésorerie en cas de passation.
$cockpit = TenantDB::getFinancialCockpit($ex);

// Base totale des tantièmes pour calcul du quorum (10 000 par défaut).
$totalTantiemes = TenantDB::getTotalTantiemes();
if ($totalTantiemes <= 0) {
    $totalTantiemes = 10000;
}

// Quorum légal indicatif par défaut (75% des tantièmes pour première convocation).
$defaultQuorum = (int) round($totalTantiemes * 0.75);

// Messages flash et identifiant d'AG passés en URL.
$msg = $_GET['msg'] ?? null;
$error = $_GET['error'] ?? null;
$agId = $_GET['ag_id'] ?? null;

// Vérification du statut de licence (mode lecture seule).
$isReadOnly = TenantDB::isReadOnly();
?>

<div class="space-y-6">
    <!-- En-tête de section avec bouton d'enregistrement d'une nouvelle AG -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2 border-b border-slate-200 dark:border-slate-800">
        <div>
            <h2 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">Assemblées Générales & Passation de Mandat</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Procès-verbaux officiels, quorum légal, AGO, AGE et passation d'exercice (Art. 16 à 24 Loi 18-00)
            </p>
        </div>

        <!-- Contrôle de l'action de tenue d'AG selon la licence -->
        <?php if (! $isReadOnly) { ?>
            <button
                onclick="document.getElementById('modal-add-ag')?.classList.remove('hidden')"
                class="px-3.5 py-2 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-md"
            >
                <span>➕ Enregistrer une Assemblée Générale</span>
            </button>
        <?php } else { ?>
            <span class="px-3.5 py-2 rounded-xl bg-slate-100 dark:bg-[#250832] text-slate-400 dark:text-slate-500 text-xs font-semibold flex items-center gap-1.5 border border-slate-200 dark:border-[#3D154F]">
                <span>🔒</span>
                <span>Enregistrement AG désactivé (Lecture seule)</span>
            </span>
        <?php } ?>
    </div>

    <!-- Alertes et Notifications Spéciales (Passation / Création d'AG) -->
    <?php if ($msg === 'passation_success') { ?>
        <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-300 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 text-xs flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-2">
                <span class="text-lg">🔄</span>
                <div>
                    <strong>Passation d'Exercice Réussie !</strong>
                    <span>L'élection du nouveau Syndic a été enregistrée. Les informations de la copropriété et les accès ont été mis à jour conformément à l'article 20 de la Loi 18-00.</span>
                </div>
            </div>
            <?php if ($agId) { ?>
                <a href="pv_assemblee.php?tenant=<?= urlencode($guid) ?>&id=<?= urlencode($agId) ?>" target="_blank" class="px-3 py-1 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-lg whitespace-nowrap">
                    📄 Voir le PV Officiel ↗
                </a>
            <?php } ?>
        </div>
    <?php } elseif ($msg === 'ag_created') { ?>
        <div class="p-4 rounded-2xl bg-blue-50 dark:bg-blue-950/40 border border-blue-300 dark:border-blue-800 text-blue-800 dark:text-blue-200 text-xs flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-2">
                <span class="text-lg">✅</span>
                <span>L'Assemblée Générale a été enregistrée avec succès.</span>
            </div>
            <?php if ($agId) { ?>
                <a href="pv_assemblee.php?tenant=<?= urlencode($guid) ?>&id=<?= urlencode($agId) ?>" target="_blank" class="px-3 py-1 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-lg whitespace-nowrap">
                    📄 Voir le PV Officiel ↗
                </a>
            <?php } ?>
        </div>
    <?php } ?>

    <?php if ($error) { ?>
        <div class="p-4 rounded-2xl bg-red-50 dark:bg-red-950/40 border border-red-300 dark:border-red-800 text-red-800 dark:text-red-200 text-xs flex items-center gap-2">
            <span>⚠️</span>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php } ?>

    <!-- Grille des Fiches d'Assemblées Générales Enregistrées -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <?php if (empty($assemblees)) { ?>
            <!-- État vide lorsqu'aucune AG n'est encore consignée -->
            <div class="col-span-2 p-12 text-center text-slate-400 dark:text-zinc-500 rounded-2xl bg-white dark:bg-[#131927] border border-slate-200 dark:border-slate-800">
                <div class="text-2xl mb-2">🗳️</div>
                <div class="font-bold text-sm text-slate-700 dark:text-zinc-300">Aucune assemblée générale enregistrée</div>
                <p class="text-xs text-slate-400 mt-1">Cliquez sur le bouton ci-dessus pour consigner une AGO ou une AGE.</p>
            </div>
        <?php } else { ?>
            <!-- Parcours et affichage des assemblées tenues -->
            <?php foreach ($assemblees as $a) { ?>
                <?php
                    $isAGE = ($a['type'] === 'extraordinaire');
                $hasPassation = ! empty($a['changement_syndic']);
                ?>
                <div class="p-5 rounded-2xl bg-white dark:bg-[#131927] border border-slate-200 dark:border-slate-800 shadow-sm space-y-4 transition-colors">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="px-2.5 py-0.5 rounded-lg text-[10px] font-bold uppercase font-mono tracking-wider <?= $isAGE ? 'bg-purple-500/15 text-purple-600 dark:text-purple-400 border border-purple-500/30' : 'bg-blue-500/15 text-blue-600 dark:text-blue-400 border border-blue-500/30' ?>">
                                    <?= $isAGE ? 'AGE (Extraordinaire)' : 'AGO (Ordinaire)' ?>
                                </span>
                                <span class="text-xs text-slate-500 dark:text-slate-400 font-mono">
                                    <?= formatDateFR($a['date']) ?>
                                </span>
                            </div>
                            <h3 class="font-bold text-sm text-slate-900 dark:text-white mt-1">
                                <?= htmlspecialchars($a['description'] ?: ('AG '.($isAGE ? 'Extraordinaire' : 'Ordinaire'))) ?>
                            </h3>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                Lieu : <?= htmlspecialchars($a['lieu'] ?: 'Hall de l\'immeuble') ?>
                            </p>
                        </div>

                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-500/10 text-emerald-600 border border-emerald-500/20 whitespace-nowrap">
                            <?= strtoupper($a['statut']) ?>
                        </span>
                    </div>

                    <!-- Quorum légal constaté selon l'Article 19 -->
                    <div class="p-2.5 rounded-xl bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 text-xs flex items-center justify-between">
                        <span class="text-slate-500 dark:text-slate-400">Quorum Légal Constaté (Art. 19) :</span>
                        <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400">
                            <?= $a['tantiemes_presents'] ?? 8500 ?> / <?= $totalTantiemes ?> tantièmes
                        </span>
                    </div>

                    <!-- Budget annuel prévisionnel voté -->
                    <?php if (! empty($a['budget_annuel_vote']) && (float) $a['budget_annuel_vote'] > 0) { ?>
                        <div class="p-2.5 rounded-xl bg-blue-50/70 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800/60 text-xs flex items-center justify-between">
                            <span class="text-blue-700 dark:text-blue-300 font-semibold">Budget Prévisionnel Voté :</span>
                            <span class="font-mono font-bold text-blue-700 dark:text-blue-300">
                                <?= formatMAD((float) $a['budget_annuel_vote']) ?>
                                <span class="text-[10px] font-normal text-slate-500">(<?= htmlspecialchars($a['frequence_appels'] ?? 'trimestrielle') ?> &bull; <?= $a['exercice'] ?? date('Y') ?>)</span>
                            </span>
                        </div>
                    <?php } ?>

                    <!-- Ordre du jour et résolutions -->
                    <?php if (! empty($a['ordre_du_jour'])) { ?>
                        <div class="text-xs text-slate-600 dark:text-slate-300">
                            <strong class="text-slate-800 dark:text-slate-200 block text-[11px] mb-1">Points Débattus & Résolutions :</strong>
                            <p class="line-clamp-2 text-[11px] text-slate-500 dark:text-slate-400">
                                <?= htmlspecialchars($a['ordre_du_jour']) ?>
                            </p>
                        </div>
                    <?php } ?>

                    <!-- BANNIÈRE SPÉCIALE EN CAS DE CHANGEMENT DE SYNDIC VOTÉ -->
                    <?php if ($hasPassation) { ?>
                        <div class="p-3 rounded-xl bg-emerald-50/70 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/60 space-y-1.5 text-xs text-emerald-900 dark:text-emerald-200">
                            <div class="font-bold flex items-center gap-1.5 text-[11px] text-emerald-700 dark:text-emerald-400 uppercase tracking-wider">
                                <span>🔄</span>
                                <span>Élection & Passation d'Exercice (Art. 20)</span>
                            </div>
                            <div>
                                Nouveau Syndic Élu : <strong><?= htmlspecialchars($a['nouveau_syndic_nom']) ?></strong>
                            </div>
                            <div class="text-[11px] text-emerald-700/80 dark:text-emerald-300/80">
                                Email officiel : <span class="font-mono"><?= htmlspecialchars($a['nouveau_syndic_email']) ?></span> &bull; Trésorerie : <?= formatMAD((float) ($a['tresorerie_arretee'] ?? 0)) ?>
                            </div>
                        </div>
                    <?php } ?>

                    <!-- Bouton ouvrant le Procès-Verbal officiel imprimable -->
                    <div class="pt-2 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between text-xs">
                        <span class="text-[11px] text-slate-400">Conforme Loi 18-00</span>
                        <a
                            href="pv_assemblee.php?tenant=<?= urlencode($guid) ?>&id=<?= urlencode($a['id']) ?>"
                            target="_blank"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200 font-bold transition text-xs shadow-sm"
                        >
                            <span>📄 Procès-Verbal Officiel ↗</span>
                        </a>
                    </div>
                </div>
            <?php } ?>
        <?php } ?>
    </div>
</div>

<!-- MODALE DE TENUE D'UNE ASSEMBLÉE GÉNÉRALE (AVEC SUPPORT PASSATION ARTICLE 20) -->
<?php if (! $isReadOnly) { ?>
<div id="modal-add-ag" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-md">
    <div class="w-full max-w-2xl max-h-[92vh] overflow-y-auto rounded-3xl bg-white dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 shadow-2xl p-6 space-y-5 text-slate-900 dark:text-zinc-100">
        <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-zinc-800">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-2xl bg-blue-600 text-white flex items-center justify-center font-bold">
                    🗳️
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white tracking-tight">Enregistrer une Assemblée Générale</h3>
                    <p class="text-xs text-slate-500 dark:text-zinc-400">AGO Annuelle ou AGE Extraordinaire &bull; Conformité Loi 18-00</p>
                </div>
            </div>
            <button onclick="document.getElementById('modal-add-ag').classList.add('hidden')" class="p-1 text-slate-400 hover:text-slate-700 dark:hover:text-white rounded-xl transition text-lg">
                &times;
            </button>
        </div>

        <form action="actions/add_assemblee.php" method="POST" class="space-y-4 text-xs">
            <input type="hidden" name="tenant" value="<?= htmlspecialchars($guid) ?>">

            <!-- 1. Caractéristiques de la Réunion -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block font-semibold text-slate-700 dark:text-zinc-300 mb-1">Type d'Assemblée *</label>
                    <select
                        name="type"
                        id="ag-type-select"
                        required
                        class="w-full px-3 py-2 bg-slate-50 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-xl font-bold focus:border-blue-500 focus:outline-none"
                    >
                        <option value="ordinaire">AGO - Assemblée Générale Ordinaire (Annuelle)</option>
                        <option value="extraordinaire">AGE - Assemblée Générale Extraordinaire</option>
                    </select>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-zinc-300 mb-1">Date de Réunion *</label>
                    <input
                        type="date"
                        name="date"
                        required
                        value="<?= date('Y-m-d') ?>"
                        class="w-full px-3 py-2 bg-slate-50 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-xl focus:border-blue-500 focus:outline-none"
                    />
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-zinc-300 mb-1">Lieu de la Réunion *</label>
                    <input
                        type="text"
                        name="lieu"
                        required
                        value="Hall d'entrée de la résidence"
                        placeholder="Ex: Hall de l'immeuble, Salle de réunion..."
                        class="w-full px-3 py-2 bg-slate-50 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-xl focus:border-blue-500 focus:outline-none"
                    />
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-zinc-300 mb-1">Tantièmes Présents ou Représentés (Quorum) *</label>
                    <input
                        type="number"
                        name="tantiemes_presents"
                        required
                        value="<?= $defaultQuorum ?>"
                        min="1"
                        max="<?= $totalTantiemes ?>"
                        class="w-full px-3 py-2 bg-slate-50 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-xl font-mono font-bold focus:border-blue-500 focus:outline-none"
                    />
                </div>
            </div>

            <!-- 2. Vote du Budget Prévisionnel Annuel (Article 18) -->
            <div class="p-4 rounded-2xl bg-blue-50/50 dark:bg-blue-950/20 border border-blue-200 dark:border-blue-800/60 space-y-3">
                <div class="flex items-center justify-between pb-2 border-b border-blue-200/80 dark:border-blue-800/60">
                    <div class="flex items-center gap-2">
                        <span class="text-base">💰</span>
                        <div>
                            <span class="font-bold text-slate-900 dark:text-white text-xs">Vote du Budget Prévisionnel Annuel & Cotisations</span>
                            <p class="text-[11px] text-slate-500">Base légale obligatoire pour l'émission des appels de fonds</p>
                        </div>
                    </div>
                    <span class="px-2 py-0.5 rounded-lg bg-blue-500/10 text-blue-600 font-mono text-[10px] font-bold">Loi 18-00</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-zinc-300 mb-1">Exercice Concerné *</label>
                        <input
                            type="number"
                            name="exercice"
                            required
                            value="<?= $ex ?>"
                            class="w-full px-3 py-2 bg-white dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-xl font-mono font-bold focus:border-blue-500 focus:outline-none"
                        />
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-zinc-300 mb-1">Budget Annuel Voté (DH TTC) *</label>
                        <input
                            type="number"
                            step="0.01"
                            name="budget_annuel_vote"
                            placeholder="Ex: 120000.00"
                            class="w-full px-3 py-2 bg-white dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-xl font-mono font-bold text-blue-600 focus:border-blue-500 focus:outline-none"
                        />
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-zinc-300 mb-1">Périodicité des Appels *</label>
                        <select
                            name="frequence_appels"
                            class="w-full px-3 py-2 bg-white dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-xl font-bold focus:border-blue-500 focus:outline-none"
                        >
                            <option value="trimestrielle" selected>Trimestrielle (4 fois/an - standard)</option>
                            <option value="mensuelle">Mensuelle (12 fois/an)</option>
                            <option value="semestrielle">Semestrielle (2 fois/an)</option>
                            <option value="annuelle">Annuelle (1 fois/an)</option>
                        </select>
                    </div>
                </div>

                <!-- Ventilation estimative par rubriques comptables -->
                <div class="pt-2">
                    <label class="block font-semibold text-slate-700 dark:text-zinc-300 mb-1 text-[11px] uppercase tracking-wider">
                        Ventilation Estimative par Rubriques (Facultatif mais recommandé pour les annexes)
                    </label>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        <div>
                            <span class="text-[10px] text-slate-500">Gardiennage & Sécurité</span>
                            <input type="number" step="0.01" name="budget_rubriques[gardiennage]" placeholder="0.00" class="w-full px-2.5 py-1.5 bg-white dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-lg text-xs font-mono">
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-500">Nettoyage & Hygiène</span>
                            <input type="number" step="0.01" name="budget_rubriques[nettoyage]" placeholder="0.00" class="w-full px-2.5 py-1.5 bg-white dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-lg text-xs font-mono">
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-500">Électricité Communs</span>
                            <input type="number" step="0.01" name="budget_rubriques[electricite]" placeholder="0.00" class="w-full px-2.5 py-1.5 bg-white dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-lg text-xs font-mono">
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-500">Eau & Arrosage</span>
                            <input type="number" step="0.01" name="budget_rubriques[eau]" placeholder="0.00" class="w-full px-2.5 py-1.5 bg-white dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-lg text-xs font-mono">
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-500">Maintenance Ascenseur</span>
                            <input type="number" step="0.01" name="budget_rubriques[ascenseur]" placeholder="0.00" class="w-full px-2.5 py-1.5 bg-white dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-lg text-xs font-mono">
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-500">Assurance Multirisque</span>
                            <input type="number" step="0.01" name="budget_rubriques[assurance]" placeholder="0.00" class="w-full px-2.5 py-1.5 bg-white dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-lg text-xs font-mono">
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-500">Frais de Gestion Syndic</span>
                            <input type="number" step="0.01" name="budget_rubriques[gestion]" placeholder="0.00" class="w-full px-2.5 py-1.5 bg-white dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-lg text-xs font-mono">
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-500">Fonds Travaux (Art. 18)</span>
                            <input type="number" step="0.01" name="budget_rubriques[reserve_travaux]" placeholder="0.00" class="w-full px-2.5 py-1.5 bg-white dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-lg text-xs font-mono">
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Bureau de Séance Élu -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block font-semibold text-slate-700 dark:text-zinc-300 mb-1">Président de Séance Élu</label>
                    <input
                        type="text"
                        name="president_seance"
                        placeholder="Ex: M. Ahmed ALAOUI"
                        class="w-full px-3 py-2 bg-slate-50 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-xl focus:border-blue-500 focus:outline-none"
                    />
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 dark:text-zinc-300 mb-1">Secrétaire de Séance</label>
                    <input
                        type="text"
                        name="secretaire_seance"
                        placeholder="Ex: Mme Fatima ZAHRA"
                        class="w-full px-3 py-2 bg-slate-50 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-xl focus:border-blue-500 focus:outline-none"
                    />
                </div>
            </div>

            <!-- 4. Délibérations et Résolutions de l'AG -->
            <div>
                <label class="block font-semibold text-slate-700 dark:text-zinc-300 mb-1">Ordre du Jour & Résolutions Soumises au Vote *</label>
                <textarea
                    name="ordre_du_jour"
                    rows="2"
                    required
                    class="w-full p-2.5 bg-slate-50 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-xl focus:border-blue-500 focus:outline-none"
                >1. Approbation du rapport moral et financier de l'exercice écoulé et quitus.
2. Vote du budget prévisionnel et montant des appels de fonds.
3. Travaux de rénovation et d'entretien des parties communes.</textarea>
            </div>

            <div>
                <label class="block font-semibold text-slate-700 dark:text-zinc-300 mb-1">Texte des Délibérations & Décisions Adoptées</label>
                <textarea
                    name="pv_texte"
                    rows="2"
                    placeholder="Détail des résolutions adoptées à la majorité..."
                    class="w-full p-2.5 bg-slate-50 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-xl focus:border-blue-500 focus:outline-none"
                >Les comptes de l'exercice ont été adoptés à l'unanimité des présents. Le quitus de gestion a été accordé.</textarea>
            </div>

            <!-- 5. OPTION PASSATION D'EXERCICE / CHANGEMENT DE SYNDIC (ARTICLE 20) -->
            <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <input
                            type="checkbox"
                            name="changement_syndic"
                            id="toggle-changement-syndic"
                            value="1"
                            onchange="document.getElementById('passation-form').classList.toggle('hidden', !this.checked)"
                            class="w-4 h-4 rounded text-blue-600 border-amber-400 focus:ring-blue-500 cursor-pointer"
                        />
                        <label for="toggle-changement-syndic" class="font-bold text-amber-900 dark:text-amber-200 text-xs cursor-pointer flex items-center gap-1">
                            <span>🔄</span>
                            <span>Élection d'un Nouveau Syndic & Passation de Mandat (Art. 20 Loi 18-00)</span>
                        </label>
                    </div>
                    <span class="text-[10px] text-amber-700 dark:text-amber-400 font-mono">Changement de mandat</span>
                </div>

                <!-- Sous-formulaire conditionnel de passation de mandat -->
                <div id="passation-form" class="hidden space-y-3 pt-3 border-t border-amber-500/20">
                    <p class="text-[11px] text-amber-800 dark:text-amber-300">
                        Cette assemblée a élu un nouveau syndic. Remplissez les coordonnées du nouveau syndic ci-dessous. Le compte administrateur sera créé et les fiches de la résidence seront automatiquement mises à jour.
                    </p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-zinc-300 mb-1">Nom & Prénom du Nouveau Syndic *</label>
                            <input
                                type="text"
                                name="nouveau_syndic_nom"
                                placeholder="Ex: M. Yassine BENNANI"
                                class="w-full px-3 py-2 bg-white dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-xl font-bold focus:border-blue-500 focus:outline-none"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-zinc-300 mb-1">Email Officiel (Nouvel Identifiant) *</label>
                            <input
                                type="email"
                                name="nouveau_syndic_email"
                                placeholder="nouveau.syndic@gmail.com"
                                class="w-full px-3 py-2 bg-white dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-xl font-mono focus:border-blue-500 focus:outline-none"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-zinc-300 mb-1">Téléphone Portable du Nouveau Syndic</label>
                            <input
                                type="text"
                                name="nouveau_syndic_tel"
                                value="+212 6 "
                                class="w-full px-3 py-2 bg-white dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-xl focus:border-blue-500 focus:outline-none"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-zinc-300 mb-1">Mot de Passe Initial *</label>
                            <input
                                type="text"
                                name="nouveau_syndic_password"
                                value="syndic2026"
                                class="w-full px-3 py-2 bg-white dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-xl font-mono focus:border-blue-500 focus:outline-none"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-zinc-300 mb-1">Date de Prise d'Effet du Mandat</label>
                            <input
                                type="date"
                                name="date_effet_mandat"
                                value="<?= date('Y-m-d') ?>"
                                class="w-full px-3 py-2 bg-white dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-xl focus:border-blue-500 focus:outline-none"
                            />
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-zinc-300 mb-1">Trésorerie Arrêtée Transmise (MAD)</label>
                            <input
                                type="number"
                                step="0.01"
                                name="tresorerie_arretee"
                                value="<?= $cockpit['tresorerieDisponible'] ?>"
                                class="w-full px-3 py-2 bg-white dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 rounded-xl font-mono font-bold focus:border-blue-500 focus:outline-none"
                            />
                        </div>
                    </div>

                    <!-- Bordereau contradictoire de décharge légale -->
                    <div class="p-3 bg-white dark:bg-zinc-950 rounded-xl border border-amber-200 dark:border-amber-900/50 space-y-1.5 text-[11px] text-slate-600 dark:text-zinc-400">
                        <div class="font-bold text-slate-800 dark:text-zinc-200">Inventaire et bordereau de remise des archives (Art. 20) :</div>
                        <div class="grid grid-cols-2 gap-1 text-[10.5px]">
                            <div>✅ Registres des PV et convocations</div>
                            <div>✅ Pièces comptables & quittances</div>
                            <div>✅ Contrats d'assurance & maintenance</div>
                            <div>✅ Clés & badges d'accès communs</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Boutons de validation du formulaire d'AG -->
            <div class="pt-2 flex items-center justify-end gap-2 border-t border-slate-200 dark:border-zinc-800">
                <button
                    type="button"
                    onclick="document.getElementById('modal-add-ag').classList.add('hidden')"
                    class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:text-slate-900 dark:text-zinc-400 dark:hover:text-white bg-slate-100 dark:bg-zinc-900 transition"
                >
                    Annuler
                </button>
                <button
                    type="submit"
                    class="px-5 py-2 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-500 transition shadow-md flex items-center gap-1.5"
                >
                    <span>Enregistrer le Procès-Verbal & Délibérations</span>
                </button>
            </div>
        </form>
    </div>
</div>
<?php } ?>
