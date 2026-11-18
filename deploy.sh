#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")"

# Site statique : le Caddy partagé sert /srv/portfolio directement (file_server).
# Le git reset est déjà fait par dispatch.sh avant l'appel à ce script ; il n'y
# a donc rien à builder. On vérifie tout de même que le site répond vraiment.
#
# Et puisqu'on le vérifie, on l'écrit : la troisième ligne du journal de
# déploiement, sous le hero, affiche le code HTTP et le temps de réponse
# réellement mesurés ici. build.php, lui, ne peut pas les connaître : il tourne
# sur ma machine, aucune requête n'est émise. Il laisse donc un tiret, que ce
# script remplace. Le reset --hard du prochain déploiement remet le tiret, et
# ce script le remplit à nouveau : rien à nettoyer.
HEALTH_URL="https://labault.dev/"
HEALTH_RETRIES=10
HOME_PAGE="index.html"

log() { echo "[$(date '+%T')] [portfolio] $*"; }

# Réécrit la ligne health du journal de déploiement avec des valeurs mesurées.
# Ne fait jamais échouer le déploiement : le site est sain, c'est l'essentiel.
write_health_line() {
  local code="$1" ms="$2"

  if [ ! -f "$HOME_PAGE" ]; then
    log "AVERTISSEMENT : $HOME_PAGE introuvable, ligne health non écrite"
    return 0
  fi

  local pattern='(<span class="deploy-log-name">health GET /</span><span class="deploy-log-ok">)[^<]*(</span><span class="deploy-log-value">)[^<]*(</span>)'

  if ! grep -qE "$pattern" "$HOME_PAGE"; then
    log "AVERTISSEMENT : ligne health absente de $HOME_PAGE, rien à écrire"
    return 0
  fi

  # Fichier temporaire puis mv : pas de -i, dont la syntaxe diffère entre
  # GNU sed et BSD sed, et le remplacement reste atomique pour Caddy.
  if sed -E "s|$pattern|\\1${code}\\2${ms}ms\\3|" "$HOME_PAGE" > "$HOME_PAGE.tmp"; then
    mv "$HOME_PAGE.tmp" "$HOME_PAGE"
    log "Journal de déploiement : health GET / $code ${ms}ms"
  else
    rm -f "$HOME_PAGE.tmp"
    log "AVERTISSEMENT : réécriture de la ligne health échouée"
  fi
}

log "Healthcheck → $HEALTH_URL"
for i in $(seq 1 "$HEALTH_RETRIES"); do
  # -f : un code >= 400 est un échec, on retente. Le code écrit dans la page
  # est donc toujours celui d'une réponse réellement servie.
  if measure=$(curl -fsS -o /dev/null -w '%{http_code} %{time_total}' "$HEALTH_URL"); then
    code="${measure%% *}"
    seconds="${measure##* }"
    ms=$(awk -v s="$seconds" 'BEGIN { printf "%d", (s * 1000) + 0.5 }')

    log "Healthy ✓ ($code en ${ms}ms, tentative $i)"
    write_health_line "$code" "$ms"
    log "Déploiement terminé ✓"
    exit 0
  fi
  sleep 2
done

log "ÉCHEC : le site ne répond pas ✗"
exit 1
