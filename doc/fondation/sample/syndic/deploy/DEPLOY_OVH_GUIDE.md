# 🚀 Guide de Déploiement sur VPS OVH — SyndicPro Maroc
### Plateforme Multi-Tenant de Gestion de Copropriétés (Conforme Loi 18-00)

Ce guide détaille les procédures pour déployer l'application sur un **VPS OVH** tournant sous **Ubuntu 22.04/24.04 LTS** ou **Debian 11/12**.

---

## 📑 Sommaire
1. [Méthode 1 : Déploiement Automatisé depuis Windows (Recommandé)](#méthode-1--déploiement-automatisé-depuis-windows-recommandé)
2. [Méthode 2 : Déploiement Manuel sur le VPS via SSH](#méthode-2--déploiement-manuel-sur-le-vps-via-ssh)
3. [Méthode 3 : Déploiement Conteneurisé via Docker](#méthode-3--déploiement-conteneurisé-via-docker)
4. [Configuration du Nom de Domaine OVH (DNS) & SSL HTTPS](#configuration-du-nom-de-domaine-ovh-dns--ssl-https)
5. [Sécurité, Permissions Linux & Sauvegardes SQLite](#sécurité-permissions-linux--sauvegardes-sqlite)
6. [Contrôle Qualité Post-Déploiement (Checklist)](#contrôle-qualité-post-déploiement-checklist)

---

## Méthode 1 : Déploiement Automatisé depuis Windows (Recommandé)

Un script PowerShell dédié [`pack_and_deploy.ps1`](file:///c:/Users/ZetaAdmin/syndic/deploy/pack_and_deploy.ps1) compresse l'application, l'envoie sur votre VPS par `scp` et lance l'installation par `ssh` en une seule commande.

### Étape 1 : Ouvrir PowerShell dans le dossier du projet
```powershell
cd C:\xampp\htdocs\Syndic\deploy
```

### Étape 2 : Lancer le déploiement vers l'IP de votre VPS OVH
```powershell
# Exemple avec adresse IP directe
.\pack_and_deploy.ps1 -VpsIp 51.178.xx.xx

# Exemple avec un nom de domaine (active le certificat SSL HTTPS Let's Encrypt)
.\pack_and_deploy.ps1 -VpsIp 51.178.xx.xx -Domain syndic.votredomaine.ma
```

*Si vous utilisez une clé SSH privée au lieu d'un mot de passe :*
```powershell
.\pack_and_deploy.ps1 -VpsIp 51.178.xx.xx -PrivateKeyPath "$HOME\.ssh\id_rsa"
```

Le script s'occupe de tout :
* Compression de l'application (`syndic_production.tar.gz`) en excluant les fichiers de développement.
* Téléversement sécurisé vers `/tmp/` sur le VPS OVH.
* Installation de Apache 2.4, PHP 8.2, extensions SQLite/GD/CURL/MBSTRING.
* Configuration des permissions `www-data` et protection `.htaccess`.
* Configuration du Pare-Feu UFW (ports 22, 80, 443).
* Mise en place du cron journalier de sauvegarde des bases SQLite.

---

## Méthode 2 : Déploiement Manuel sur le VPS via SSH

Si vous préférez exécuter les commandes directement sur votre serveur VPS :

### Étape 1 : Générer l'archive de production en local
Sous PowerShell :
```powershell
cd C:\xampp\htdocs\Syndic\deploy
.\pack_and_deploy.ps1
```
*(Cela génère `C:\xampp\htdocs\Syndic\deploy\syndic_production.tar.gz`)*

### Étape 2 : Copier l'archive et le script d'installation sur le VPS
```bash
scp C:\xampp\htdocs\Syndic\deploy\syndic_production.tar.gz root@<VOTRE_IP_VPS>:/tmp/
scp C:\xampp\htdocs\Syndic\deploy\setup_ovh_vps.sh root@<VOTRE_IP_VPS>:/tmp/
```

### Étape 3 : Se connecter en SSH et lancer l'installation
```bash
ssh root@<VOTRE_IP_VPS>
chmod +x /tmp/setup_ovh_vps.sh
sudo bash /tmp/setup_ovh_vps.sh -a /tmp/syndic_production.tar.gz
```

*(Optionnel avec nom de domaine)* :
```bash
sudo bash /tmp/setup_ovh_vps.sh -a /tmp/syndic_production.tar.gz -d syndic.votredomaine.ma
```

---

## Méthode 3 : Déploiement Conteneurisé via Docker

Si votre VPS dispose de Docker et Docker Compose :

```bash
# 1. Transférer le dossier de l'application sur le VPS
scp -r C:\xampp\htdocs\Syndic root@<VOTRE_IP_VPS>:/var/www/syndic

# 2. Se connecter sur le VPS
ssh root@<VOTRE_IP_VPS>
cd /var/www/syndic/deploy

# 3. Lancer le conteneur
docker compose up -d --build
```
L'application sera immédiatement accessible sur `http://<VOTRE_IP_VPS>/Syndic/`.

---

## Configuration du Nom de Domaine OVH (DNS) & SSL HTTPS

Pour associer votre nom de domaine enregistré chez OVH :

1. Rendez-vous dans votre **Espace Client OVH** &gt; **Web Cloud** &gt; **Noms de domaine** &gt; **Zone DNS**.
2. Ajoutez ou modifiez un enregistrement de type **A** :
   * **Sous-domaine :** `syndic` (ou laissez vide pour la racine `@`)
   * **Cible :** L'adresse IPv4 de votre VPS OVH (ex: `51.178.xx.xx`)
   * **TTL :** Par défaut (ex: 3600)
3. Patientez pendant la propagation DNS (environ 5 à 15 minutes).
4. Sur le VPS, lancez la commande Certbot pour activer le HTTPS :
   ```bash
   sudo certbot --apache -d syndic.votredomaine.ma
   ```
5. Certbot configure automatiquement le renouvellement automatique (tous les 90 jours).

---

## Sécurité, Permissions Linux & Sauvegardes SQLite

### 1. Permissions Linux (Air-Gap)
Dans l'environnement Linux, le serveur Apache s'exécute sous l'utilisateur `www-data`.
Pour que SQLite puisse écrire dans les bases et gérer les fichiers de verrouillage (`-wal`, `-shm`) :
```bash
chown -R www-data:www-data /var/www/html/Syndic
chmod -R 775 /var/www/html/Syndic/data
chmod -R 775 /var/www/html/Syndic/uploads
```

### 2. Protection contre le téléchargement direct des bases `.sqlite`
Le script installe automatiquement un fichier `.htaccess` bloquant dans `/var/www/html/Syndic/data/.htaccess` :
```apache
<IfModule mod_authz_core.c>
    Require all denied
</IfModule>
```
Tout accès direct du type `http://<IP>/Syndic/data/master.sqlite` renvoie une erreur **HTTP 403 Forbidden**.

### 3. Sauvegardes à Chaud Automatisées (Cron Journalier)
Le script installe une tâche cron dans `/etc/cron.d/syndic_backup` qui s'exécute chaque nuit à **02h00** :
* Archive toutes les bases SQLite dans `/var/backups/syndic/syndic_sqlite_YYYY-MM-DD_HH-MM-SS.tar.gz`.
* Supprime automatiquement les sauvegardes de plus de 30 jours.

Pour tester manuellement la sauvegarde :
```bash
sudo /usr/local/bin/syndic_backup.sh
ls -lh /var/backups/syndic/
```

Pour restaurer une sauvegarde :
```bash
sudo tar -xzf /var/backups/syndic/syndic_sqlite_XXXXX.tar.gz -C /var/www/html/Syndic/data/
sudo chown -R www-data:www-data /var/www/html/Syndic/data
```

---

## Contrôle Qualité Post-Déploiement (Checklist)

| Point de Contrôle | Commande de Test / URL | Résultat Attendu |
|---|---|---|
| **Portail Central** | `http://<IP_VPS>/Syndic/` | Code HTTP 200, sélecteur de résidence affiché |
| **Console Super-Admin** | `http://<IP_VPS>/Syndic/MgmtConsole/` | Connexion avec `admin@syndicpro.ma` / `admin2026` |
| **Cockpit Syndic** | `http://<IP_VPS>/Syndic/MgmtResidence/` | Connexion avec `syndic.yassine.bennani@gmail.com` / `syndic2026` |
| **Espace Résident** | `http://<IP_VPS>/Syndic/MgmtResident/` | Connexion avec `mehdi.elamrani@atlas` / `resident2026` |
| **Sécurité SQLite** | `curl -I http://<IP_VPS>/Syndic/data/master.sqlite` | **HTTP 403 Forbidden** (Accès refusé) |
| **Pare-feu UFW** | `sudo ufw status` | Ports 22, 80, 443 autorisés |
| **Temps de Réponse** | `curl -w "%{time_total}s\n" -o /dev/null -s http://<IP_VPS>/Syndic/` | **< 0.05s (< 50 ms)** |
