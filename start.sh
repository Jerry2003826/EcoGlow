#!/usr/bin/env bash
# Eco Glow one-click launcher for macOS and Linux.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")" && pwd)"
cd "$ROOT"

say() { printf '\n==> %s\n' "$*"; }
have() { command -v "$1" >/dev/null 2>&1; }

export PATH="/opt/homebrew/bin:/opt/homebrew/sbin:/usr/local/bin:/usr/local/sbin:$PATH"

find_php() {
  local candidate
  for candidate in \
    php \
    /opt/homebrew/bin/php \
    /usr/local/bin/php \
    /opt/homebrew/opt/php@8.4/bin/php \
    /opt/homebrew/opt/php@8.3/bin/php \
    /opt/homebrew/opt/php@8.2/bin/php \
    /Applications/MAMP/bin/php/php8.4.*/bin/php \
    /Applications/MAMP/bin/php/php8.3.*/bin/php \
    /Applications/MAMP/bin/php/php8.2.*/bin/php
  do
    # shellcheck disable=SC2086
    for candidate in $candidate; do
      if [[ -x "$candidate" ]] && "$candidate" -r 'exit(PHP_VERSION_ID >= 80400 ? 0 : 1);' 2>/dev/null; then
        printf '%s' "$candidate"
        return 0
      fi
    done
  done
  return 1
}

install_macos_stack() {
  if ! have brew; then
    say "Installing Homebrew (this may ask for your Mac password)"
    /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
    if [[ -x /opt/homebrew/bin/brew ]]; then
      eval "$(/opt/homebrew/bin/brew shellenv)"
    elif [[ -x /usr/local/bin/brew ]]; then
      eval "$(/usr/local/bin/brew shellenv)"
    fi
  fi
  say "Installing PHP, Composer and MySQL with Homebrew"
  brew list php >/dev/null 2>&1 || brew install php
  brew list composer >/dev/null 2>&1 || brew install composer
  if ! brew list mysql >/dev/null 2>&1 && ! brew list mysql@8.4 >/dev/null 2>&1 && ! brew list mariadb >/dev/null 2>&1; then
    brew install mysql
  fi
}

start_mysql() {
  if have mysqladmin && mysqladmin --user=root ping >/dev/null 2>&1; then
    return 0
  fi
  if have brew; then
    brew services start mysql >/dev/null 2>&1 || true
    brew services start mysql@8.4 >/dev/null 2>&1 || true
    brew services start mariadb >/dev/null 2>&1 || true
  fi
  if [[ -x /Applications/MAMP/Library/bin/mysql ]]; then
    open -a MAMP >/dev/null 2>&1 || true
  fi
  local i
  for i in 1 2 3 4 5 6 7 8 9 10; do
    if have mysqladmin && mysqladmin --user=root ping >/dev/null 2>&1; then
      return 0
    fi
    # PDO probe is the real check; mysqladmin may be missing.
    if find_php >/dev/null && "$(find_php)" -r '
      try {
        new PDO("mysql:host=127.0.0.1;port=3306", "root", "");
        exit(0);
      } catch (Throwable $e) {
        try { new PDO("mysql:host=127.0.0.1;port=3306", "root", "root"); exit(0); }
        catch (Throwable $e2) { exit(1); }
      }
    ' >/dev/null 2>&1; then
      return 0
    fi
    sleep 2
  done
  return 0
}

PHP_BIN="$(find_php || true)"
if [[ -z "${PHP_BIN}" ]]; then
  if [[ "$(uname -s)" == "Darwin" ]]; then
    install_macos_stack
    export PATH="/opt/homebrew/bin:/usr/local/bin:$PATH"
    PHP_BIN="$(find_php || true)"
  fi
fi

if [[ -z "${PHP_BIN}" ]]; then
  echo "PHP 8.4+ was not found."
  echo "macOS: install Homebrew, then run this script again."
  echo "Linux: sudo apt install php php-cli php-mysql php-mbstring php-xml php-intl unzip"
  exit 1
fi

say "Using PHP $($PHP_BIN -r 'echo PHP_VERSION;')"
start_mysql
export ECOGLOW_ALLOW_DEV_BOOTSTRAP=1
if have mysqladmin && mysqladmin --user=root ping >/dev/null 2>&1; then
  export ECOGLOW_DB_ADMIN_USER="${ECOGLOW_DB_ADMIN_USER:-root}"
  export MYSQL_ROOT_PASSWORD="${MYSQL_ROOT_PASSWORD:-}"
elif have mysqladmin && mysqladmin --user=root --password=root ping >/dev/null 2>&1; then
  export ECOGLOW_DB_ADMIN_USER="${ECOGLOW_DB_ADMIN_USER:-root}"
  export MYSQL_ROOT_PASSWORD="${MYSQL_ROOT_PASSWORD:-root}"
fi
exec "$PHP_BIN" "$ROOT/bin/dev_up.php" "$@"
