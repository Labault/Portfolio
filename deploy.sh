#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")"
# Site statique : Caddy sert /srv/portfolio directement.
# Le git reset est déjà fait par dispatch.sh avant l'appel à ce script.
echo "[$(date '+%T')] [portfolio] Déploiement terminé ✓"
