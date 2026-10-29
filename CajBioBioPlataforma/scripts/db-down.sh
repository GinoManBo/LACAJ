#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."

if command -v docker >/dev/null 2>&1 && docker compose version >/dev/null 2>&1; then
  docker compose down
  exit 0
fi

ENGINE=""
if command -v docker >/dev/null 2>&1; then ENGINE="docker"
elif command -v podman >/dev/null 2>&1; then ENGINE="podman"
else exit 0
fi

$ENGINE stop caj-mysql caj-adminer >/dev/null 2>&1 || true
