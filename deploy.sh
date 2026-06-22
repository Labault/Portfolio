#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")"

# Site statique : le Caddy partagé sert /srv/portfolio directement (file_server).
# Le git reset est déjà fait par dispatch.sh avant l'appel à ce script ; il n'y
# a donc rien à builder. On vérifie tout de même que le site répond vraiment.
HEALTH_URL="https://labault.dev/"
HEALTH_RETRIES=10

log() { echo "[$(date '+%T')] [portfolio] $*"; }

log "Healthcheck → $HEALTH_URL"
for i in $(seq 1 "$HEALTH_RETRIES"); do
  if curl -fsS -o /dev/null "$HEALTH_URL"; then
    log "Healthy ✓"
    log "Déploiement terminé ✓"
    exit 0
  fi
  sleep 2
done

log "ÉCHEC : le site ne répond pas ✗"
exit 1
