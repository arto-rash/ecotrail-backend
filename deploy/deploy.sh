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
  # Bash keeps running the old copy of this file after the pull replaces it; restart with the new one.
  SKIP_PULL=1 exec "$0" "$@"
fi

echo "==> build + up"
"${COMPOSE[@]}" up -d --build --remove-orphans

echo "==> wait for the API (migrations run on boot)"
for i in $(seq 1 60); do
  [ "$(docker inspect -f '{{.State.Health.Status}}' "$(${COMPOSE[@]} ps -q api)")" = healthy ] && break
  sleep 3
done

echo "==> sync open-data catalogue"
"${COMPOSE[@]}" exec -T api php artisan catalogue:sync

echo "==> ensure reverse-proxy route for $API_HOST"
# The route is committed to the proxy project's Caddyfile; this is a fallback in case it goes missing.
MARKER="# >>> ecotrail-api (managed by ecotrail-backend/deploy/deploy.sh)"
if ! grep -qE "^${API_HOST//./\\.} \{" "$PROXY_CADDYFILE"; then
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
