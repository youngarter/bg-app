<?php
/**
 * ============================================================================
 * SYNDIC CONNECT - PORTAIL SYNDIC : PIED DE PAGE & MODALES D'ACTIONS RAPIDES
 * ============================================================================
 *
 * @author     ZetaAdmin / Syndic Development Team
 *
 * @version    2.4.0
 *
 * @license    Proprietary / Strict Dahir n° 1-02-298 compliance
 *
 * RÔLE DU MODULE ET SÉCURITÉ DE SAISIE :
 * ----------------------------------------------------------------------------
 * Ce fichier clôture la mise en page HTML du portail syndic et embarque les
 * modales d'action globale pour un enregistrement fluide des écritures comptables
 * courantes depuis n'importe quelle vue.
 *
 * Sécurisation et intégrité :
 * - Si le tenant est en mode lecture seule ($isReadOnly = true), les modales
 *   d'enregistrement (paiements et dépenses) ne sont absolument pas générées
 *   dans le DOM, empêchant toute tentative de contournement par injection script.
 * - Formulaire d'encaissement : Génère immédiatement la quittance légale (Art. 25).
 * - Formulaire de dépense : Enregistre l'imputation sur le compte de charges.
 */

declare(strict_types=1);
?>
        </main>
    </div>

<?php if (empty($isReadOnly)) { ?>
    <!-- ===================================================================== -->
    <!-- 1. MODALE GLOBALE D'ENCAISSEMENT RAPIDE D'UNE COTISATION              -->
    <!-- ===================================================================== -->
    <div id="modal-paiement" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md">
        <div class="w-full max-w-md rounded-3xl bg-white dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 shadow-2xl p-6 space-y-4 text-slate-900 dark:text-zinc-100">
            <!-- En-tête de la modale de paiement -->
            <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-zinc-800">
                <h3 class="font-bold text-sm">Encaisser une Cotisation / Paiement</h3>
                <button onclick="document.getElementById('modal-paiement').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 dark:hover:text-white" aria-label="Fermer la modale">&times;</button>
            </div>

            <!-- Formulaire d'encaissement avec cible add_paiement.php -->
            <form action="actions/add_paiement.php" method="POST" class="space-y-3 text-xs">
                <!-- Paramètre d'isolation multi-tenant -->
                <input type="hidden" name="tenant" value="<?= htmlspecialchars($guid) ?>">

                <!-- Sélection du copropriétaire débiteur -->
                <div>
                    <label class="block font-semibold mb-1">Copropriétaire *</label>
                    <select name="coproprietaire_id" required class="w-full p-2.5 bg-slate-50 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-xl">
                        <?php foreach (TenantDB::getCoproprietaires() as $c) { ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nom'].' '.$c['prenom']) ?></option>
                        <?php } ?>
                    </select>
                </div>

                <!-- Montant de la cotisation encaissée en MAD -->
                <div>
                    <label class="block font-semibold mb-1">Montant Encaissé (MAD) *</label>
                    <input type="number" step="0.01" name="montant" required value="3500" class="w-full p-2.5 bg-slate-50 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-xl font-bold">
                </div>

                <!-- Modalité et date de règlement -->
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block font-semibold mb-1">Mode de Règlement *</label>
                        <select name="mode_paiement" class="w-full p-2.5 bg-slate-50 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-xl">
                            <option value="virement">Virement Bancaire</option>
                            <option value="cheque">Chèque Bancaire</option>
                            <option value="versement">Versement Espèces Banque</option>
                            <option value="especes">Espèces en Caisse</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold mb-1">Date Encaissement *</label>
                        <input type="date" name="date_paiement" required value="<?= date('Y-m-d') ?>" class="w-full p-2.5 bg-slate-50 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-xl">
                    </div>
                </div>

                <!-- Numéro de pièce comptable / chèque / bordereau -->
                <div>
                    <label class="block font-semibold mb-1">Référence Transaction</label>
                    <input type="text" name="reference" placeholder="Ex: VIR-ATTIJARI-891" class="w-full p-2.5 bg-slate-50 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-xl">
                </div>

                <!-- Boutons d'annulation et de validation -->
                <div class="pt-2 flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('modal-paiement').classList.add('hidden')" class="px-4 py-2 bg-slate-100 dark:bg-zinc-900 rounded-xl">Annuler</button>
                    <button type="submit" class="px-5 py-2 bg-blue-600 text-white font-bold rounded-xl shadow">Enregistrer & Émettre Quittance</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- 2. MODALE GLOBALE DE SAISIE RAPIDE D'UNE DÉPENSE OU FACTURE           -->
    <!-- ===================================================================== -->
    <div id="modal-depense" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md">
        <div class="w-full max-w-md rounded-3xl bg-white dark:bg-zinc-950 border border-slate-200 dark:border-zinc-800 shadow-2xl p-6 space-y-4 text-slate-900 dark:text-zinc-100">
            <!-- En-tête de la modale de dépense -->
            <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-zinc-800">
                <h3 class="font-bold text-sm">Saisir une Dépense / Facture</h3>
                <button onclick="document.getElementById('modal-depense').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 dark:hover:text-white" aria-label="Fermer la modale">&times;</button>
            </div>

            <!-- Formulaire d'engagement de dépense -->
            <form action="actions/add_depense.php" method="POST" class="space-y-3 text-xs">
                <!-- Paramètre d'isolation multi-tenant -->
                <input type="hidden" name="tenant" value="<?= htmlspecialchars($guid) ?>">

                <!-- Nom ou raison sociale du prestataire -->
                <div>
                    <label class="block font-semibold mb-1">Fournisseur / Prestataire *</label>
                    <input type="text" name="fournisseur_nom" required placeholder="Ex: Atlas Ascenseurs SARL" class="w-full p-2.5 bg-slate-50 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-xl">
                </div>

                <!-- Objet de la dépense -->
                <div>
                    <label class="block font-semibold mb-1">Description Prestation *</label>
                    <input type="text" name="description" required placeholder="Maintenance mensuelle" class="w-full p-2.5 bg-slate-50 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-xl">
                </div>

                <!-- Rubrique d'imputation comptable et montant TTC -->
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block font-semibold mb-1">Catégorie *</label>
                        <select name="categorie" class="w-full p-2.5 bg-slate-50 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-xl">
                            <option value="Ascenseur">Ascenseur</option>
                            <option value="Nettoyage / Sécurité">Nettoyage / Sécurité</option>
                            <option value="Électricité">Électricité</option>
                            <option value="Eau">Eau</option>
                            <option value="Jardinage">Jardinage</option>
                            <option value="Assurance">Assurance Immeuble</option>
                            <option value="Travaux">Travaux & Rénovation</option>
                            <option value="Autre">Autre</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold mb-1">Montant TTC (MAD) *</label>
                        <input type="number" step="0.01" name="montant_ttc" required placeholder="1500" class="w-full p-2.5 bg-slate-50 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-xl font-bold">
                    </div>
                </div>

                <!-- Date d'exigibilité de la facture -->
                <div>
                    <label class="block font-semibold mb-1">Date Facture *</label>
                    <input type="date" name="date" required value="<?= date('Y-m-d') ?>" class="w-full p-2.5 bg-slate-50 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800 rounded-xl">
                </div>

                <!-- Actions de soumission -->
                <div class="pt-2 flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('modal-depense').classList.add('hidden')" class="px-4 py-2 bg-slate-100 dark:bg-zinc-900 rounded-xl">Annuler</button>
                    <button type="submit" class="px-5 py-2 bg-blue-600 text-white font-bold rounded-xl shadow">Enregistrer Dépense</button>
                </div>
            </form>
        </div>
    </div>
<?php } ?>

</body>
</html>
