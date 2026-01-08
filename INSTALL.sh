#!/bin/bash
set -euo pipefail

# Color output helpers
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

info() { echo -e "${GREEN}[INFO]${NC} $1"; }
warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
error() { echo -e "${RED}[ERROR]${NC} $1" >&2; }

# Detect package manager and set appropriate packages
detect_package_manager() {
    if command -v apt-get > /dev/null 2>&1; then
        PKG_MANAGER="apt-get"
        PKG_UPDATE="sudo apt-get update"
        PKG_INSTALL="sudo apt-get install -y"
        PACKAGES="apache2 libapache2-mod-php php php-xml php-mbstring php-mysql mariadb-server"
        WEB_SERVICE="apache2"
        DB_SERVICE="mariadb"
    elif command -v dnf > /dev/null 2>&1; then
        PKG_MANAGER="dnf"
        PKG_UPDATE="sudo dnf check-update || true"
        PKG_INSTALL="sudo dnf install -y"
        PACKAGES="httpd php php-xml php-mbstring php-mysqlnd mariadb-server"
        WEB_SERVICE="httpd"
        DB_SERVICE="mariadb"
    elif command -v yum > /dev/null 2>&1; then
        PKG_MANAGER="yum"
        PKG_UPDATE="sudo yum check-update || true"
        PKG_INSTALL="sudo yum install -y"
        PACKAGES="httpd php php-xml php-mbstring php-mysqlnd mariadb-server"
        WEB_SERVICE="httpd"
        DB_SERVICE="mariadb"
    elif command -v pacman > /dev/null 2>&1; then
        PKG_MANAGER="pacman"
        PKG_UPDATE="sudo pacman -Sy"
        PKG_INSTALL="sudo pacman -S --noconfirm"
        PACKAGES="apache php php-apache mariadb"
        WEB_SERVICE="httpd"
        DB_SERVICE="mariadb"
    else
        error "No supported package manager found (apt-get, dnf, yum, pacman)"
        exit 1
    fi
    info "Detected package manager: $PKG_MANAGER"
}

# Install system dependencies
install_dependencies() {
    info "Updating package lists..."
    $PKG_UPDATE

    info "Installing dependencies: $PACKAGES"
    $PKG_INSTALL $PACKAGES
}

# Enable PHP extensions (Debian/Ubuntu specific)
enable_php_extensions() {
    if command -v phpenmod > /dev/null 2>&1; then
        info "Enabling PHP extensions (mbstring, mysqli)..."
        sudo phpenmod -s apache2 mbstring || true
        sudo phpenmod -s apache2 mysqli || true
    else
        warn "phpenmod not found - PHP extensions may need manual configuration"
    fi
}

# Generate a random password
generate_password() {
    if command -v openssl > /dev/null 2>&1; then
        openssl rand -base64 16 | tr -d '/+=' | head -c 16
    else
        head -c 32 /dev/urandom | base64 | tr -d '/+=' | head -c 16
    fi
}

# Validate database name (alphanumeric and underscores only)
validate_db_name() {
    local name="$1"
    if [[ ! "$name" =~ ^[a-zA-Z_][a-zA-Z0-9_]*$ ]]; then
        error "Invalid database name. Use only letters, numbers, and underscores."
        return 1
    fi
    return 0
}

# Validate username (alphanumeric and underscores only)
validate_username() {
    local name="$1"
    if [[ ! "$name" =~ ^[a-zA-Z_][a-zA-Z0-9_]*$ ]]; then
        error "Invalid username. Use only letters, numbers, and underscores."
        return 1
    fi
    return 0
}

# Configure database credentials
configure_database_credentials() {
    echo
    info "LWT needs database access credentials. You can change these later in '.env'."
    echo

    host="localhost"

    # Database user
    while true; do
        read -rp "Database User Name [lwt]: " user
        user=${user:-lwt}
        if validate_username "$user"; then
            break
        fi
    done

    # Database password (hidden input)
    local default_passwd
    default_passwd=$(generate_password)
    while true; do
        read -rsp "Database Password [auto-generated]: " passwd
        echo
        passwd=${passwd:-$default_passwd}
        if [ ${#passwd} -lt 8 ]; then
            warn "Password should be at least 8 characters. Try again."
        else
            break
        fi
    done

    # Database name
    while true; do
        read -rp "Database Name [learning_with_texts]: " db_name
        db_name=${db_name:-learning_with_texts}
        if validate_db_name "$db_name"; then
            break
        fi
    done
}

# Create MySQL user and database securely
setup_database() {
    info "Creating MySQL user and database..."

    # Create temporary MySQL config file to avoid password on command line
    local mysql_config
    mysql_config=$(mktemp)
    chmod 600 "$mysql_config"

    # Cleanup on exit
    trap "rm -f '$mysql_config'" EXIT

    # Use root authentication (typically socket auth on modern systems)
    cat > "$mysql_config" << EOF
[client]
EOF

    # Create user if not exists
    if ! sudo mysql --defaults-extra-file="$mysql_config" -e \
        "CREATE USER IF NOT EXISTS '$user'@'$host' IDENTIFIED BY '$passwd';" 2>/dev/null; then
        warn "User '$user' may already exist, attempting to continue..."
    fi

    # Create database if not exists
    if ! sudo mysql --defaults-extra-file="$mysql_config" -e \
        "CREATE DATABASE IF NOT EXISTS \`$db_name\`;" 2>/dev/null; then
        error "Failed to create database '$db_name'"
        exit 1
    fi

    # Grant privileges
    sudo mysql --defaults-extra-file="$mysql_config" -e \
        "GRANT ALL PRIVILEGES ON \`$db_name\`.* TO '$user'@'$host';"
    sudo mysql --defaults-extra-file="$mysql_config" -e "FLUSH PRIVILEGES;"

    rm -f "$mysql_config"
    trap - EXIT

    info "Database setup complete"
}

# Save environment configuration
save_env_file() {
    info "Saving configuration to '.env'..."

    if [ -f .env ]; then
        warn "Existing .env file found. Backing up to .env.backup"
        cp .env .env.backup
    fi

    cat > .env << EOF
# LWT Database Configuration
# Generated by INSTALL.sh on $(date)

DB_HOST=$host
DB_USER=$user
DB_PASSWORD=$passwd
DB_NAME=$db_name
EOF

    chmod 600 .env
    info "Configuration saved (file permissions set to 600)"
}

# Copy files to web server directory
deploy_to_server() {
    echo
    info "Ready to deploy LWT to your web server."
    echo

    read -rp "Destination directory [/var/www/html/lwt]: " dest
    dest=${dest:-/var/www/html/lwt}

    # Validate destination path
    if [[ ! "$dest" =~ ^/ ]]; then
        error "Destination must be an absolute path"
        exit 1
    fi

    # Confirm before potentially destructive operation
    if [ -d "$dest" ]; then
        warn "Directory '$dest' already exists!"
        read -rp "Overwrite existing files? (y/N): " confirm
        if [[ ! "$confirm" =~ ^[Yy]$ ]]; then
            info "Aborted. You can manually copy files later."
            return 1
        fi
    fi

    # Create parent directory if needed
    sudo mkdir -p "$(dirname "$dest")"

    info "Copying files to '$dest'..."
    sudo cp -r . "$dest"
    sudo chmod -R 755 "$dest"

    # Secure the .env file in destination
    if [ -f "$dest/.env" ]; then
        sudo chmod 600 "$dest/.env"
    fi

    info "Files deployed successfully"
    return 0
}

# Restart services using systemctl or service command
restart_services() {
    info "Restarting services..."

    if command -v systemctl > /dev/null 2>&1; then
        sudo systemctl enable "$WEB_SERVICE" || true
        sudo systemctl enable "$DB_SERVICE" || true
        sudo systemctl restart "$WEB_SERVICE"
        sudo systemctl restart "$DB_SERVICE"
    else
        sudo service "$WEB_SERVICE" restart
        sudo service "$DB_SERVICE" restart || sudo service mysql restart
    fi

    info "Services restarted"
}

# Main installation flow
main() {
    echo "========================================"
    echo "  LWT (Learning With Texts) Installer"
    echo "========================================"
    echo

    detect_package_manager

    read -rp "Install system dependencies? (Y/n): " install_deps
    if [[ ! "$install_deps" =~ ^[Nn]$ ]]; then
        install_dependencies
        enable_php_extensions
    fi

    configure_database_credentials
    setup_database
    save_env_file

    if deploy_to_server; then
        restart_services

        echo
        echo "========================================"
        info "Installation complete!"
        echo "========================================"
        echo
        echo "You can access LWT at: http://localhost/${dest##*/}"
        echo
        echo "Database credentials have been saved to '.env'"
        echo "Keep this file secure and do not commit it to version control."
    else
        echo
        info "Partial installation complete."
        echo "Run the following to complete deployment manually:"
        echo "  sudo cp -r . /your/destination/path"
        echo "  sudo chmod -R 755 /your/destination/path"
    fi
}

main "$@"
