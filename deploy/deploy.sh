#!/usr/bin/env bash
# Deploy the EcoTrail API on the shared server. Run from the repo root as a user in the docker group:
#   ./deploy/deploy.sh
# Requires .env with APP_*, DB_* and the proxy settings below.
set -euo pipefail
cd "$(dirname "$0")/.."

set -a; . ./.env; set +a
: "${PROXY_NETWORK:?}" "${PROXY_CADDYFILE:?}" "${PROXY_CONTAINER:?}" "${API_HOST:?}"

COMPOSE=(docker compose -f docker-compose.yml -f docker-compose.prod.yml -f docker-compose.server.yml)

if [ "${SKIP_PULL:-0}" != "1" ]; then
  echo "==> pull"
  git pull --ff-only
fi

echo "==> build + up"
"${COMPOSE[@]}" up -d --build --remove-orphans

echo "==> ensure reverse-proxy route for $API_HOST"
# The proxy belongs to another project whose deploys reset its Caddyfile, so re-add our block every time.
MARKER="# >>> ecotrail-api (managed by ecotrail-backend/deploy/deploy.sh)"
if ! grep -qF "$MARKER" "$PROXY_CADDYFILE"; then
  # Append in place (tee -a keeps the inode, so the container's bind mount sees the change).
  TEE=(tee -a); [ -w "$PROXY_CADDYFILE" ] || TEE=(sudo tee -a)
  printf '\n%s\n%s {\n\tencode zstd gzip\n\treverse_proxy ecotrail-api:8080\n}\n# <<< ecotrail-api\n' "$MARKER" "$API_HOST" \
    | "${TEE[@]}" "$PROXY_CADDYFILE" >/dev/null
  docker exec "$PROXY_CONTAINER" caddy validate --config /etc/caddy/Caddyfile --adapter caddyfile
  docker exec "$PROXY_CONTAINER" caddy reload --config /etc/caddy/Caddyfile --adapter caddyfile
  echo "    route added"
else
  echo "    route already present"
fi

echo "==> prune dangling images"
docker image prune -f >/dev/null

"${COMPOSE[@]}" ps
