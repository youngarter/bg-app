<#
.SYNOPSIS
    SyndicPro Maroc - Packaging et Deploiement distant vers VPS OVH
.DESCRIPTION
    Compresse l'application, les bases SQLite partitionnees et les assets,
    puis televerse et deploie automatiquement sur un VPS OVH via SSH/SCP.
.PARAMETER VpsIp
    L'adresse IP publique ou nom d'hote du VPS OVH (ex: 51.178.10.20).
.PARAMETER SshUser
    L'utilisateur SSH sur le VPS (defaut : root).
.PARAMETER SshPort
    Le port SSH du VPS (defaut : 22).
.PARAMETER Domain
    Nom de domaine optionnel pour configurer le SSL HTTPS (ex: syndic.domaine.ma).
.PARAMETER PrivateKeyPath
    Chemin optionnel vers la cle privee SSH.
#>

param(
    [string]$VpsIp = "",
    [string]$SshUser = "root",
    [int]$SshPort = 22,
    [string]$Domain = "",
    [string]$PrivateKeyPath = ""
)

$ErrorActionPreference = "Stop"

$appRoot = "C:\xampp\htdocs\Syndic"
$deployDir = Join-Path $appRoot "deploy"
$tarFile = Join-Path $deployDir "syndic_production.tar.gz"
$setupScript = Join-Path $deployDir "setup_ovh_vps.sh"

Write-Host "==============================================================================" -ForegroundColor Magenta
Write-Host "    SYNDICPRO MAROC - PACKAGING ET DEPLOIEMENT VPS OVH" -ForegroundColor Magenta
Write-Host "==============================================================================" -ForegroundColor Magenta

# 1. Verification de tar
if (-not (Get-Command tar -ErrorAction SilentlyContinue)) {
    Write-Error "La commande tar est introuvable sur ce systeme Windows."
    exit 1
}

# 2. Creation de l'archive tar.gz
Write-Host "`n[1/3] Creation de l'archive de production (syndic_production.tar.gz)..." -ForegroundColor Cyan

if (Test-Path $tarFile) {
    Remove-Item -Force $tarFile
}

Push-Location $appRoot
try {
    # Exclusion des fichiers temporaires, tests scratch et developpement
    tar.exe -czf $tarFile `
        --exclude="deploy/syndic_production.tar.gz" `
        --exclude="*.disabled" `
        --exclude="node_modules" `
        --exclude=".git" `
        --exclude=".vscode" `
        --exclude="scratch" `
        .
}
finally {
    Pop-Location
}

if (Test-Path $tarFile) {
    $fileSize = (Get-Item $tarFile).Length / 1MB
    Write-Host "   OK : Archive generee : $tarFile ($([math]::Round($fileSize, 2)) Mo)" -ForegroundColor Green
} else {
    Write-Error "Echec lors de la creation de l'archive tar.gz."
    exit 1
}

# 3. Mode Packaging Seul ou Deploiement Distant
if ([string]::IsNullOrWhiteSpace($VpsIp)) {
    Write-Host "`n[INFO] Aucun parametre -VpsIp specifie." -ForegroundColor Yellow
    Write-Host "L'archive de production est prete dans : $tarFile" -ForegroundColor Cyan
    Write-Host "`nPour deployer automatiquement vers votre VPS OVH, relancez :" -ForegroundColor Yellow
    Write-Host "  .\pack_and_deploy.ps1 -VpsIp <IP_VPS>" -ForegroundColor White
    Write-Host "  .\pack_and_deploy.ps1 -VpsIp <IP_VPS> -Domain <domaine.ma>" -ForegroundColor White
    Write-Host "`nOu pour deployer manuellement :" -ForegroundColor Yellow
    Write-Host "  scp `"$tarFile`" `"$setupScript`" ${SshUser}@<IP_VPS>:/tmp/" -ForegroundColor White
    Write-Host "  ssh ${SshUser}@<IP_VPS> `"sudo bash /tmp/setup_ovh_vps.sh -a /tmp/syndic_production.tar.gz`"" -ForegroundColor White
    exit 0
}

# 4. Transfert vers le VPS OVH
Write-Host "`n[2/3] Televersement des fichiers vers le VPS OVH ($($VpsIp):$($SshPort))..." -ForegroundColor Cyan

$sshKeyArg = ""
if (-not [string]::IsNullOrWhiteSpace($PrivateKeyPath) -and (Test-Path $PrivateKeyPath)) {
    $sshKeyArg = "-i `"$PrivateKeyPath`""
}

$portArgScp = "-P $SshPort"
$portArgSsh = "-p $SshPort"

# Transfert de l'archive et du script d'installation
$scpCmd = "scp.exe $portArgScp $sshKeyArg `"$tarFile`" `"$setupScript`" ${SshUser}@${VpsIp}:/tmp/"
Write-Host "   Execution : $scpCmd" -ForegroundColor DarkGray
Invoke-Expression $scpCmd

Write-Host "   OK : Fichiers transferes vers /tmp/ sur le VPS." -ForegroundColor Green

# 5. Execution distante du script d'installation
Write-Host "`n[3/3] Execution de l'installation automatisee sur le VPS OVH..." -ForegroundColor Cyan

$domainArg = ""
if (-not [string]::IsNullOrWhiteSpace($Domain)) {
    $domainArg = "-d $Domain"
}

$remoteCmd = "chmod +x /tmp/setup_ovh_vps.sh && sudo /tmp/setup_ovh_vps.sh -a /tmp/syndic_production.tar.gz $domainArg"
$sshCmd = "ssh.exe $portArgSsh $sshKeyArg ${SshUser}@${VpsIp} `"$remoteCmd`""

Write-Host "   Connexion SSH et execution de setup_ovh_vps.sh..." -ForegroundColor DarkGray
Invoke-Expression $sshCmd

Write-Host "`nDeploiement OVH termine avec succes !" -ForegroundColor Green
