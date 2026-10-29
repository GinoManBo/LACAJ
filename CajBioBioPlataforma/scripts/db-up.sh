#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."

ROOT_PASSWORD="${MYSQL_ROOT_PASSWORD:-rootdev}"
DATABASE="${MYSQL_DATABASE:-caj_practicas}"
DB_USER="${MYSQL_USER:-caj}"
DB_PASSWORD="${MYSQL_PASSWORD:-cajdev}"

if command -v docker >/dev/null 2>&1 && docker compose version >/dev/null 2>&1; then
  echo "Usando docker compose…"
  docker compose up -d
  exit 0
fi

ENGINE=""
if command -v docker >/dev/null 2>&1; then
  ENGINE="docker"
elif command -v podman >/dev/null 2>&1; then
  ENGINE="podman"
else
  echo "No se encontró docker ni podman. Instala uno de los dos para continuar." >&2
  exit 1
fi

echo "docker compose no disponible — usando '$ENGINE run' directo…"

if ! $ENGINE volume inspect caj-mysql-data >/dev/null 2>&1; then
  $ENGINE volume create caj-mysql-data >/dev/null
fi

if $ENGINE ps -a --format '{{.Names}}' | grep -qx caj-mysql; then
  echo "Contenedor caj-mysql ya existe, iniciando…"
  $ENGINE start caj-mysql >/dev/null
else
  $ENGINE run -d \
    --name caj-mysql \
    -p 3307:3306 \
    -e MYSQL_ROOT_PASSWORD="$ROOT_PASSWORD" \
    -e MYSQL_DATABASE="$DATABASE" \
    -e MYSQL_USER="$DB_USER" \
    -e MYSQL_PASSWORD="$DB_PASSWORD" \
    -v caj-mysql-data:/var/lib/mysql \
    -v "$(pwd)/docker/mysql/init:/docker-entrypoint-initdb.d:ro,Z" \
    docker.io/library/mysql:8.4 \
    --character-set-server=utf8mb4 --collation-server=utf8mb4_0900_ai_ci >/dev/null
fi

if $ENGINE ps -a --format '{{.Names}}' | grep -qx caj-adminer; then
  $ENGINE start caj-adminer >/dev/null
else
  $ENGINE run -d --name caj-adminer -p 8081:8080 docker.io/library/adminer:5 >/dev/null
fi

echo -n "Esperando a que MySQL esté listo"
for _ in $(seq 1 40); do
  if $ENGINE exec caj-mysql mysqladmin ping -h 127.0.0.1 -p"$ROOT_PASSWORD" --silent >/dev/null 2>&1; then
    echo " — listo."
    exit 0
  fi
  echo -n "."
  sleep 2
done
echo
echo "MySQL no respondió a tiempo; revisa '$ENGINE logs caj-mysql'." >&2
exit 1
