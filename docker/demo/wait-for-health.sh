#!/bin/sh
set -eu

service_name="${1:?service name is required}"
timeout_seconds="${2:-120}"
profile="${COMPOSE_PROFILE:-demo}"
started_at="$(date +%s)"

printf "Waiting for %s" "$service_name"

while :; do
  container_id="$(docker compose --profile "$profile" ps -q "$service_name" 2>/dev/null || true)"

  if [ -n "$container_id" ]; then
    status="$(docker inspect -f '{{if .State.Health}}{{.State.Health.Status}}{{else}}{{.State.Status}}{{end}}' "$container_id" 2>/dev/null || true)"

    case "$status" in
      healthy|running)
        printf "\n%s is %s.\n" "$service_name" "$status"
        exit 0
        ;;
      unhealthy|exited|dead)
        printf "\n%s is %s.\n" "$service_name" "$status" >&2
        docker compose --profile "$profile" logs --tail=40 "$service_name" >&2 || true
        exit 1
        ;;
    esac
  fi

  now="$(date +%s)"

  if [ $((now - started_at)) -ge "$timeout_seconds" ]; then
    printf "\nTimed out while waiting for %s.\n" "$service_name" >&2
    docker compose --profile "$profile" logs --tail=40 "$service_name" >&2 || true
    exit 1
  fi

  printf "."
  sleep 2
done
