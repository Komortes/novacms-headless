#!/bin/sh
set -eu

cd /app

if [ ! -f .env ]; then
  cp .env.example .env
fi

shared_dir="${NOVACMS_DEMO_SHARED_DIR:-/demo-shared}"
key_file="${shared_dir}/app_key"

mkdir -p "$shared_dir"

if [ ! -s "$key_file" ]; then
  umask 077
  generated_key="$(php -r 'echo "base64:".base64_encode(random_bytes(32));')"
  ( set -C; printf '%s\n' "$generated_key" > "$key_file" ) 2>/dev/null || true
fi

until [ -s "$key_file" ]; do
  echo "Waiting for shared demo APP_KEY..."
  sleep 1
done

APP_KEY="$(tr -d '\r\n' < "$key_file")"
export APP_KEY

php -r '
$path = ".env";
$key = getenv("APP_KEY");
$content = file_exists($path) ? file_get_contents($path) : "";

if ($content === false) {
    fwrite(STDERR, "Failed to read .env\n");
    exit(1);
}

if (preg_match("/^APP_KEY=.*/m", $content) === 1) {
    $content = preg_replace("/^APP_KEY=.*/m", "APP_KEY=".$key, $content, 1);
} else {
    $content .= (str_ends_with($content, PHP_EOL) ? "" : PHP_EOL)."APP_KEY=".$key.PHP_EOL;
}

file_put_contents($path, $content);
'

if [ "${NOVACMS_DEMO_BOOTSTRAP:-false}" = "true" ]; then
  echo "Bootstrapping NovaCMS demo data..."

  until php artisan migrate --force --no-interaction; do
    echo "Waiting for database..."
    sleep 2
  done

  php artisan db:seed --class=DemoEnvironmentSeeder --force --no-interaction
  php artisan view:cache >/dev/null 2>&1 || true

  echo "NovaCMS demo bootstrap complete."
  echo "Landing page: http://localhost:8000"
  echo "Admin: http://localhost:8000/admin"
  echo "Admin credentials: admin@novacms.test / password"
  echo "Run 'make demo-models' on the host if you want live Ollama generation."
else
  echo "Skipping demo bootstrap in this container; demo-app owns the seeded state."
fi

exec "$@"
