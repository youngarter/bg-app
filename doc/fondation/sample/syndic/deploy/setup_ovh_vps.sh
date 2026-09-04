#!/usr/bin/env bash
# ==============================================================================
# SyndicPro Maroc — Script de Déploiement Automatisé pour VPS OVH
# Système : Ubuntu 22.04 / 24.04 LTS ou Debian 11 / 12
# Conformité : Dahir n° 1-02-298 (Loi 18-00) & Architecture Tri-Tier
# ==============================================================================

set -euo pipefail

# --- Palette de Couleurs ---
C_RESET='\033[0m'
C_BOLD='\033[1m'
C_GREEN='\033[32m'
C_BLUE='\033[34m'
C_CYAN='\033[36m'
C_YELLOW='\033[33m'
C_RED='\033[31m'
C_MAGENTA='\033[35m'

echo -e "${C_MAGENTA}${C_BOLD}"
echo "=============================================================================="
echo "    🏢 SYNDICPRO MAROC — DÉPLOIEMENT AUTOMATISÉ VPS OVH"
echo "    Gestion Multi-Tenant de Copropriétés & Architecture Air-Gap SQLite"
echo "=============================================================================="
echo -e "${C_RESET}"

# --- Vérification des Droits Root ---
if [ "$(id -u)" -ne 0 ]; then
    echo -e "${C_RED}[ERREUR] Ce script doit être exécuté avec les privilèges root (sudo bash $0).${C_RESET}"
    exit 1
fi

# --- Variables de Configuration ---
DOMAIN=""
APP_SRC_ARCHIVE="${1:-}"
APP_DEST_DIR="/var/www/html/Syndic"
BACKUP_DIR="/var/backups/syndic"
PHP_VERSION="8.2"

# Lecture des options CLI éventuelles
while [[ $# -gt 0 ]]; do
    case $1 in
        -d|--domain)
            DOMAIN="$2"
            shift 2
            ;;
        -a|--archive)
            APP_SRC_ARCHIVE="$2"
            shift 2
            ;;
        -h|--help)
            echo "Usage: sudo bash setup_ovh_vps.sh [-d votredomaine.com] [-a syndic_production.tar.gz]"
            exit 0
            ;;
        *)
            if [ -z "$APP_SRC_ARCHIVE" ] && [ -f "$1" ]; then
                APP_SRC_ARCHIVE="$1"
            fi
            shift
            ;;
    esac
done

export DEBIAN_FRONTEND=noninteractive

echo -e "${C_BLUE}ℹ️  1. Détection du Système d'Exploitation...${C_RESET}"
if [ -f /etc/os-release ]; then
    . /etc/os-release
    OS_ID=$ID
    OS_VERSION=$VERSION_ID
    echo -e "   Système détecté : ${C_GREEN}${NAME} (${VERSION_ID})${C_RESET}"
else
    echo -e "${C_RED}[ERREUR] Impossible d'identifier le système d'exploitation Linux.${C_RESET}"
    exit 1
fi

echo -e "${C_BLUE}ℹ️  2. Mise à jour des paquets du système...${C_RESET}"
apt-get update -y
apt-get install -y software-properties-common curl wget git tar unzip ca-certificates lsb-release ufw fail2ban

echo -e "${C_BLUE}ℹ️  3. Configuration du dépôt PHP ${PHP_VERSION}...${C_RESET}"
if [ "$OS_ID" = "ubuntu" ]; then
    add-apt-repository -y ppa:ondrej/php
    apt-get update -y
elif [ "$OS_ID" = "debian" ]; then
    curl -sSLo /usr/share/keyrings/deb.sury.org-php.gpg https://packages.sury.org/php/apt.gpg
    echo "deb [signed-by=/usr/share/keyrings/deb.sury.org-php.gpg] https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list
    apt-get update -y
fi

echo -e "${C_BLUE}ℹ️  4. Installation de la pile Apache 2.4 & PHP ${PHP_VERSION}...${C_RESET}"
apt-get install -y \
    apache2 \
    php${PHP_VERSION} \
    libapache2-mod-php${PHP_VERSION} \
    php${PHP_VERSION}-cli \
    php${PHP_VERSION}-sqlite3 \
    php${PHP_VERSION}-gd \
    php${PHP_VERSION}-curl \
    php${PHP_VERSION}-mbstring \
    php${PHP_VERSION}-xml \
    php${PHP_VERSION}-zip \
    php${PHP_VERSION}-intl

echo -e "${C_BLUE}ℹ️  5. Activation des modules Apache requis...${C_RESET}"
a2enmod rewrite headers alias deflate ssl

echo -e "${C_BLUE}ℹ️  6. Optimisation de la configuration PHP...${C_RESET}"
PHP_INI="/etc/php/${PHP_VERSION}/apache2/php.ini"
if [ -f "$PHP_INI" ]; then
    sed -i 's/^upload_max_filesize = .*/upload_max_filesize = 16M/' "$PHP_INI"
    sed -i 's/^post_max_size = .*/post_max_size = 20M/' "$PHP_INI"
    sed -i 's/^memory_limit = .*/memory_limit = 256M/' "$PHP_INI"
    sed -i 's/^max_execution_time = .*/max_execution_time = 120/' "$PHP_INI"
fi

echo -e "${C_BLUE}ℹ️  7. Déploiement des fichiers de l'application dans ${APP_DEST_DIR}...${C_RESET}"
mkdir -p "$APP_DEST_DIR"

if [ -n "$APP_SRC_ARCHIVE" ] && [ -f "$APP_SRC_ARCHIVE" ]; then
    echo -e "   Extraction de l'archive source : ${C_CYAN}${APP_SRC_ARCHIVE}${C_RESET}"
    tar -xzf "$APP_SRC_ARCHIVE" -C "$APP_DEST_DIR"
elif [ -f "./syndic_production.tar.gz" ]; then
    echo -e "   Archive locale trouvée : ${C_CYAN}syndic_production.tar.gz${C_RESET}"
    tar -xzf "./syndic_production.tar.gz" -C "$APP_DEST_DIR"
elif [ -d "./MgmtResidence" ]; then
    echo -e "   Copie directe depuis le répertoire courant..."
    cp -r ./* "$APP_DEST_DIR"/
    cp -r ./.[!.]* "$APP_DEST_DIR"/ 2>/dev/null || true
else
    echo -e "${C_YELLOW}   [ATTENTION] Aucune archive ou code source direct détecté.${C_RESET}"
    echo -e "   Veuillez déposer l'archive de l'application dans ${APP_DEST_DIR}."
fi

# Création des sous-dossiers requis s'ils n'existent pas encore
mkdir -p "$APP_DEST_DIR/data/tenants"
mkdir -p "$APP_DEST_DIR/uploads/logos/presets"
mkdir -p "$APP_DEST_DIR/uploads/reclamations"

echo -e "${C_BLUE}ℹ️  8. Application des règles de sécurité .htaccess...${C_RESET}"
# Sécurisation data/.htaccess
cat << 'EOF' > "$APP_DEST_DIR/data/.htaccess"
# ==============================================================================
# SyndicPro Maroc — Sécurité des Données SQLite
# Dahir n° 1-02-298 (Loi 18-00) — Partitionnement Physique Air-Gap
# ==============================================================================
<IfModule mod_authz_core.c>
    Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
    Order Deny,Allow
    Deny from all
</IfModule>
EOF

# Sécurisation uploads/.htaccess
cat << 'EOF' > "$APP_DEST_DIR/uploads/.htaccess"
# ==============================================================================
# SyndicPro Maroc — Sécurité des Téléversements
# ==============================================================================
<FilesMatch "(?i)\.(php|phtml|php3|php4|php5|php7|php8|phps|cgi|pl|py|sh|bash)$">
    <IfModule mod_authz_core.c>
        Require all denied
    </IfModule>
    <IfModule !mod_authz_core.c>
        Order Deny,Allow
        Deny from all
    </IfModule>
</FilesMatch>
<IfModule mod_php.c>
    php_flag engine off
</IfModule>
<IfModule mod_php8.c>
    php_flag engine off
</IfModule>
EOF

echo -e "${C_BLUE}ℹ️  9. Attribution rigoureuse des permissions Linux (www-data)...${C_RESET}"
chown -R www-data:www-data "$APP_DEST_DIR"
find "$APP_DEST_DIR" -type d -exec chmod 755 {} +
find "$APP_DEST_DIR" -type f -exec chmod 644 {} +

# Permissions d'écriture pour SQLite (fichiers db, wal, shm) et uploads
chmod -R 775 "$APP_DEST_DIR/data"
chmod -R 775 "$APP_DEST_DIR/uploads"
find "$APP_DEST_DIR/data" -type f -exec chmod 664 {} + 2>/dev/null || true

echo -e "${C_BLUE}ℹ️  10. Configuration du VirtualHost Apache...${C_RESET}"
VHOST_CONF="/etc/apache2/sites-available/syndic.conf"

cat << EOF > "$VHOST_CONF"
<VirtualHost *:80>
    ServerAdmin contact@syndicpro.ma
    $( [ -n "$DOMAIN" ] && echo "ServerName $DOMAIN" )
    
    DocumentRoot /var/www/html
    Alias /Syndic $APP_DEST_DIR

    <Directory $APP_DEST_DIR>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    <Directory /var/www/html>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Journalisation
    ErrorLog \${APACHE_LOG_DIR}/syndic_error.log
    CustomLog \${APACHE_LOG_DIR}/syndic_access.log combined
</VirtualHost>
EOF

# Désactiver le site par défaut et activer syndic.conf
a2dissite 000-default.conf 2>/dev/null || true
a2ensite syndic.conf
systemctl restart apache2

echo -e "${C_BLUE}ℹ️  11. Configuration du Pare-Feu UFW & Protection Anti-Brute-Force...${C_RESET}"
ufw allow OpenSSH
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable

systemctl enable fail2ban
systemctl restart fail2ban

echo -e "${C_BLUE}ℹ️  12. Mise en place du Script & Cron de Sauvegarde Automatisée (Hot Backup)...${C_RESET}"
mkdir -p "$BACKUP_DIR"
chown root:root "$BACKUP_DIR"
chmod 700 "$BACKUP_DIR"

cat << 'EOF' > /usr/local/bin/syndic_backup.sh
#!/usr/bin/env bash
set -euo pipefail
BACKUP_DIR="/var/backups/syndic"
SOURCE_DATA="/var/www/html/Syndic/data"
TIMESTAMP=$(date +"%Y-%m-%d_%H-%M-%S")
TARGET_FILE="${BACKUP_DIR}/syndic_sqlite_${TIMESTAMP}.tar.gz"

mkdir -p "$BACKUP_DIR"
tar -czf "$TARGET_FILE" -C "$SOURCE_DATA" .
chmod 600 "$TARGET_FILE"

# Rétention : supprimer les sauvegardes de plus de 30 jours
find "$BACKUP_DIR" -type f -name "syndic_sqlite_*.tar.gz" -mtime +30 -delete
EOF

chmod +x /usr/local/bin/syndic_backup.sh

# Cron journalier à 02h00 du matin
cat << 'EOF' > /etc/cron.d/syndic_backup
0 2 * * * root /usr/local/bin/syndic_backup.sh > /dev/null 2>&1
EOF
chmod 644 /etc/cron.d/syndic_backup

echo -e "${C_BLUE}ℹ️  13. Vérification des tests de sécurité et d'accès...${C_RESET}"
LOCAL_STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1/Syndic/ || echo "000")
DATA_BLOCK_STATUS=$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1/Syndic/data/master.sqlite || echo "000")

echo -e "   Accès Web Portail : HTTP ${C_GREEN}${LOCAL_STATUS}${C_RESET}"
if [ "$DATA_BLOCK_STATUS" = "403" ]; then
    echo -e "   Protection Base SQLite : HTTP ${C_GREEN}403 Forbidden (PARFAIT — Données Étanches)${C_RESET}"
else
    echo -e "   Protection Base SQLite : HTTP ${C_YELLOW}${DATA_BLOCK_STATUS}${C_RESET}"
fi

# Option SSL avec Certbot si un nom de domaine valide a été fourni
if [ -n "$DOMAIN" ]; then
    echo -e "${C_BLUE}ℹ️  14. Configuration SSL HTTPS Let's Encrypt pour ${DOMAIN}...${C_RESET}"
    apt-get install -y certbot python3-certbot-apache
    certbot --apache -d "$DOMAIN" --non-interactive --agree-tos -m "admin@$DOMAIN" --redirect || {
        echo -e "${C_YELLOW}[AVERTISSEMENT] Certbot n'a pas pu émettre le certificat. Vérifiez que la zone DNS de $DOMAIN pointe bien vers l'IP du VPS.${C_RESET}"
    }
fi

SERVER_IP=$(curl -s -4 https://ifconfig.me || curl -s -4 https://icanhazip.com || echo "VOTRE_IP_VPS")

echo ""
echo -e "${C_GREEN}${C_BOLD}==============================================================================${C_RESET}"
echo -e "${C_GREEN}${C_BOLD}🎉 DÉPLOIEMENT SYNDICPRO MAROC TERMINÉ AVEC SUCCÈS SUR LE VPS OVH !${C_RESET}"
echo -e "${C_GREEN}${C_BOLD}==============================================================================${C_RESET}"
echo ""
echo -e "🌐 ${C_BOLD}URLs d'accès à l'application :${C_RESET}"
if [ -n "$DOMAIN" ]; then
    echo -e "   Portail d'Aiguillage : ${C_CYAN}https://${DOMAIN}/Syndic/${C_RESET}"
    echo -e "   Console Super-Admin  : ${C_CYAN}https://${DOMAIN}/Syndic/MgmtConsole/${C_RESET}"
    echo -e "   Cockpit Syndic       : ${C_CYAN}https://${DOMAIN}/Syndic/MgmtResidence/${C_RESET}"
    echo -e "   Portail Résidents    : ${C_CYAN}https://${DOMAIN}/Syndic/MgmtResident/${C_RESET}"
else
    echo -e "   Portail d'Aiguillage : ${C_CYAN}http://${SERVER_IP}/Syndic/${C_RESET}"
    echo -e "   Console Super-Admin  : ${C_CYAN}http://${SERVER_IP}/Syndic/MgmtConsole/${C_RESET}"
    echo -e "   Cockpit Syndic       : ${C_CYAN}http://${SERVER_IP}/Syndic/MgmtResidence/${C_RESET}"
    echo -e "   Portail Résidents    : ${C_CYAN}http://${SERVER_IP}/Syndic/MgmtResident/${C_RESET}"
fi
echo ""
echo -e "🔑 ${C_BOLD}Identifiants de démonstration :${C_RESET}"
echo -e "   • Super-Admin : ${C_BOLD}admin@syndicpro.ma${C_RESET} / Mot de passe : ${C_BOLD}admin2026${C_RESET}"
echo -e "   • Syndic Atlas : ${C_BOLD}syndic.yassine.bennani@gmail.com${C_RESET} / Mot de passe : ${C_BOLD}syndic2026${C_RESET}"
echo -e "   • Résidents   : ${C_BOLD}mehdi.elamrani@atlas${C_RESET} / Mot de passe : ${C_BOLD}resident2026${C_RESET}"
echo ""
echo -e "🛡️ ${C_BOLD}Sécurité & Maintenance :${C_RESET}"
echo -e "   • Firewall UFW actif (SSH 22, HTTP 80, HTTPS 443 autorisés)"
echo -e "   • Téléchargement direct des bases SQLite verrouillé (HTTP 403)"
echo -e "   • Sauvegarde automatique quotidienne configurée à 02:00 (${BACKUP_DIR})"
echo "=============================================================================="
