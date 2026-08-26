#!/bin/bash
# BerryIO installer and updater
# Date: 2026-08-26 | Revision: 3

set -u

REPOSITORY_URL="https://github.com/witai2212/berryio.git"
INSTALL_DIR="/usr/share/berryio"
CONFIG_DIR="/etc/berryio"
BACKUP_DIR="${INSTALL_DIR}.update-backup"

fail()
{
  echo "BerryIO installation failed: $1" 1>&2
  exit 1
}

if [[ ${EUID} -ne 0 ]]; then
  fail "run this script as root (for example: sudo $0)"
fi

if [[ ! -r /etc/os-release ]]; then
  fail "cannot determine the operating system"
fi

. /etc/os-release
OS_CODENAME="${VERSION_CODENAME:-}"
if [[ "${OS_CODENAME}" != "bullseye" && "${OS_CODENAME}" != "bookworm" ]]; then
  fail "Raspberry Pi OS Bullseye or Bookworm is required (detected: ${OS_CODENAME:-unknown})"
fi
if [[ "${ID:-}" != "raspbian" && "${ID:-}" != "debian" ]]; then
  fail "Raspberry Pi OS Bullseye or Bookworm is required (detected: ${ID:-unknown})"
fi

INSTALL_MODE="install"
if [[ -d "${INSTALL_DIR}" ]]; then
  INSTALL_MODE="update"
fi

echo
echo "BerryIO Installer"
echo "-----------------"
echo "Operating system: ${PRETTY_NAME:-${OS_CODENAME}}"
echo "Mode: ${INSTALL_MODE}"

echo
echo "Updating package information..."
apt-get update || fail "apt update failed"

echo
echo "Installing prerequisites..."
DEBIAN_FRONTEND=noninteractive apt-get -y install \
  apache2 ethtool git libapache2-mod-authnz-external libapache2-mod-php \
  msmtp php pwauth wireless-tools || fail "prerequisite installation failed"

echo
echo "Retrieving BerryIO from GitHub..."
TEMP_DIR=$(mktemp -d) || fail "could not create a temporary directory"
trap 'rm -rf "${TEMP_DIR}"' EXIT
git clone --depth 1 --branch master "${REPOSITORY_URL}" "${TEMP_DIR}/berryio" \
  || fail "GitHub download failed"

rm -rf "${BACKUP_DIR}"
if [[ -d "${INSTALL_DIR}" ]]; then
  mv "${INSTALL_DIR}" "${BACKUP_DIR}" || fail "could not prepare the existing installation for update"
fi
if ! mv "${TEMP_DIR}/berryio" "${INSTALL_DIR}"; then
  if [[ -d "${BACKUP_DIR}" ]]; then
    mv "${BACKUP_DIR}" "${INSTALL_DIR}"
  fi
  fail "could not activate the downloaded version"
fi
rm -rf "${BACKUP_DIR}"

echo
echo "Installing system configuration..."
mkdir -p "${CONFIG_DIR}" /var/log/berryio /var/log/msmtp

for CONFIG_FILE in "${INSTALL_DIR}"/default_config/berryio/*.php; do
  CONFIG_NAME=$(basename "${CONFIG_FILE}")
  case "${CONFIG_NAME}" in
    gpio.*.example.php)
      continue
      ;;
  esac
  if [[ ! -e "${CONFIG_DIR}/${CONFIG_NAME}" ]]; then
    cp "${CONFIG_FILE}" "${CONFIG_DIR}/${CONFIG_NAME}" || fail "could not install ${CONFIG_NAME}"
  fi
done

cp "${INSTALL_DIR}/default_config/apache2/sites-available/berryio.conf" \
  /etc/apache2/sites-available/berryio.conf || fail "could not install the Apache configuration"
cp "${INSTALL_DIR}/default_config/sudoers.d/berryio" \
  /etc/sudoers.d/berryio || fail "could not install the sudo configuration"
chmod 440 /etc/sudoers.d/berryio || fail "could not secure the sudo configuration"

if [[ ! -f /etc/msmtprc ]]; then
  cp "${INSTALL_DIR}/default_config/msmtprc" /etc/msmtprc || fail "could not install the email configuration"
fi
chmod 640 /etc/msmtprc || fail "could not secure the email configuration"
chgrp www-data /etc/msmtprc || fail "could not grant email configuration access"

if [[ -d /etc/network/if-up.d ]]; then
  cp "${INSTALL_DIR}/default_config/network/if-up.d/berryio_email_ip" \
    /etc/network/if-up.d/berryio_email_ip || fail "could not install the network hook"
  chmod 755 /etc/network/if-up.d/berryio_email_ip
fi

PHP_VERSION=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;') \
  || fail "could not determine the PHP version"
for PHP_SAPI in apache2 cli; do
  PHP_CONFIG_DIR="/etc/php/${PHP_VERSION}/${PHP_SAPI}/conf.d"
  if [[ -d "${PHP_CONFIG_DIR}" ]]; then
    printf '%s\n' '; BerryIO PHP settings' 'short_open_tag = On' \
      > "${PHP_CONFIG_DIR}/99-berryio.ini" || fail "could not configure PHP"
    printf '%s\n' '; BerryIO msmtp settings' 'sendmail_path = /usr/bin/msmtp -t' \
      > "${PHP_CONFIG_DIR}/99-berryio-msmtp.ini" || fail "could not configure PHP email"
  fi
done

echo
echo "Granting the web server access to GPIO..."
getent group gpio > /dev/null || groupadd --system gpio || fail "could not create the gpio group"
usermod -a -G gpio www-data || fail "could not add www-data to the gpio group"

echo
echo "Enabling the Apache configuration..."
a2enmod rewrite authnz_external || fail "could not enable the Apache modules"
for SITE in default default-ssl 000-default default.conf default-ssl.conf 000-default.conf; do
  a2dissite "${SITE}" > /dev/null 2>&1 || true
done
a2ensite berryio.conf || fail "could not enable the BerryIO site"
rm -f /etc/apache2/sites-available/berryio

echo
echo "Setting up the BerryIO command line..."
ln -sfn "${INSTALL_DIR}/scripts/berryio.php" /usr/bin/berryio \
  || fail "could not create the BerryIO command"

if [[ "${INSTALL_MODE}" == "install" ]]; then
  echo
  echo "Configuring email settings"
  echo "--------------------------"
  DEFAULT_MAIL_TO="pi@localhost"
  DEFAULT_MAIL_FROM="pi@localhost"
  EMAIL_CONFIGURED="N"
  until [[ "${EMAIL_CONFIGURED}" =~ ^[yY]$ ]]; do
    read -r -p "Email address messages should be sent to [${DEFAULT_MAIL_TO}]: " MAIL_TO
    read -r -p "Email address messages should be sent from [${DEFAULT_MAIL_FROM}]: " MAIL_FROM
    MAIL_TO="${MAIL_TO:-${DEFAULT_MAIL_TO}}"
    MAIL_FROM="${MAIL_FROM:-${DEFAULT_MAIL_FROM}}"
    echo
    echo "Mail To:   ${MAIL_TO}"
    echo "Mail From: ${MAIL_FROM}"
    read -r -p "Is this correct? [y/N]: " EMAIL_CONFIGURED
  done
  cat > "${CONFIG_DIR}/email.php" <<EOF
<?php
/* BerryIO Email Settings */
define('EMAIL_FROM', '${MAIL_FROM}');
define('EMAIL_TO', '${MAIL_TO}');
EOF

  echo
  echo "Configuring GPIO settings"
  echo "-------------------------"
  GPIO_CONFIG="rev2.0"
  PI_REVISION=$(awk -F ': ' '/^Revision/ { print tolower($2) }' /proc/cpuinfo | tail -n 1)
  case "${PI_REVISION}" in
    900092|920092|900093|920093|9000c1) GPIO_CONFIG="zero" ;;
    0002|0003) GPIO_CONFIG="rev1.0" ;;
    0012|0015|900021) GPIO_CONFIG="a_plus" ;;
    0010|0013|900032) GPIO_CONFIG="b_plus" ;;
    a01040|a01041|a21041|a22042) GPIO_CONFIG="2b" ;;
    a02082|a22082|a32082|a52082|a020d3) GPIO_CONFIG="3b" ;;
    0011|0014|a020a0) GPIO_CONFIG="compute_module" ;;
  esac
  echo "Detected GPIO configuration: ${GPIO_CONFIG}"
  read -r -p "Use this configuration? [Y/n]: " GPIO_CONFIRM
  if [[ "${GPIO_CONFIRM:-Y}" =~ ^[nN]$ ]]; then
    while true; do
      read -r -p "Pi variant [rev1.0|rev2.0|a_plus|b_plus|2b|3b|zero|compute_module]: " GPIO_CONFIG
      [[ "${GPIO_CONFIG}" =~ ^(rev1\.0|rev2\.0|a_plus|b_plus|2b|3b|zero|compute_module)$ ]] && break
    done
  fi
  cp "${INSTALL_DIR}/default_config/berryio/gpio.${GPIO_CONFIG}.example.php" \
    "${CONFIG_DIR}/gpio.php" || fail "could not install the GPIO configuration"
fi

chgrp www-data "${CONFIG_DIR}/gpio.php" || fail "could not grant GPIO configuration access"
chmod 664 "${CONFIG_DIR}/gpio.php" || fail "could not secure the GPIO configuration"

echo
echo "Checking and restarting Apache..."
apache2ctl configtest || fail "the Apache configuration test failed"
systemctl restart apache2 || fail "could not restart Apache"

echo
echo "BerryIO ${INSTALL_MODE} successful."
echo "Existing files in ${CONFIG_DIR} were preserved during updates."
echo "Further information: ${INSTALL_DIR}/INSTALL.README.txt"
