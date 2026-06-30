#!/bin/bash
cd "$(dirname "$0")"
ROOT="$(pwd)"
DB="$ROOT/data/inventory.db"
JAVA_PORT=8081
PHP_PORT=8888

# find php
PHP=""
for p in php "$HOME/Downloads/php" /usr/local/bin/php /opt/homebrew/bin/php; do
  if command -v "$p" >/dev/null 2>&1; then PHP="$p"; break; fi
done

# find java
JAVA=""
for j in java "$HOME/oracleJdk-25.jdk/Contents/Home/bin/java" /usr/local/bin/java /opt/homebrew/bin/java; do
  if command -v "$j" >/dev/null 2>&1; then JAVA="$j"; break; fi
done

echo ""
echo "  Nextgen Shield — Stationery Management"
echo "  Stack: PHP + Java + HTML + CSS + JavaScript"
echo ""

# compile & start Java API
if [ -n "$JAVA" ]; then
  mkdir -p "$ROOT/java/build"
  "$JAVA" -version 2>&1 | head -1
  CP="$ROOT/java/lib/sqlite-jdbc.jar:$ROOT/java/lib/gson.jar:$ROOT/java/lib/slf4j-api.jar:$ROOT/java/lib/slf4j-nop.jar"
  javac -cp "$CP" -d "$ROOT/java/build" "$ROOT/java/src/"*.java 2>/dev/null
  if [ $? -eq 0 ]; then
    "$JAVA" -cp "$ROOT/java/build:$CP" InventoryServer "$DB" "$JAVA_PORT" &
    JAVA_PID=$!
    echo "  Java API:  http://127.0.0.1:$JAVA_PORT"
    sleep 1
  else
    echo "  Java: compile failed — install JDK to enable inventory API"
  fi
else
  echo "  Java: not found — install JDK (inventory API needs Java)"
fi

# start PHP web server
if [ -n "$PHP" ]; then
  echo "  Web app:   http://127.0.0.1:$PHP_PORT/index.php"
  echo "  Login:     manager / manager123"
  echo ""
  "$PHP" -S "127.0.0.1:$PHP_PORT" -t "$ROOT/public"
else
  echo "  PHP not found."
  echo "  Install PHP:  brew install php"
  echo "  Or use MAMP/XAMPP and point document root to: $ROOT/public"
  echo ""
  # fallback to python if available
  if command -v python3 >/dev/null 2>&1 && [ -f "$ROOT/app.py" ]; then
    echo "  Falling back to Python server (legacy)..."
    python3 "$ROOT/app.py"
  fi
fi

[ -n "$JAVA_PID" ] && kill $JAVA_PID 2>/dev/null