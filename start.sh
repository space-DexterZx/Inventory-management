#!/bin/bash
# No admin access needed — uses Python + Java from your user folder only.
cd "$(dirname "$0")"
ROOT="$(pwd)"
DB="$ROOT/data/inventory.db"
PORT=8888
JAVA_PORT=8081

mkdir -p "$ROOT/data" "$ROOT/static"
[ -f "$ROOT/inventory.db" ] && [ ! -f "$DB" ] && cp "$ROOT/inventory.db" "$DB"
[ -f "$ROOT/public/assets/img/logo.png" ] && cp "$ROOT/public/assets/img/logo.png" "$ROOT/static/logo.png"
[ -f "$ROOT/public/assets/css/style.css" ] && cp "$ROOT/public/assets/css/style.css" "$ROOT/static/style.css"

JAVA_BIN="$HOME/oracleJdk-25.jdk/Contents/Home/bin/java"
JAVAC_BIN="$HOME/oracleJdk-25.jdk/Contents/Home/bin/javac"
CP="$ROOT/java/lib/sqlite-jdbc.jar:$ROOT/java/lib/gson.jar:$ROOT/java/lib/slf4j-api.jar:$ROOT/java/lib/slf4j-nop.jar"

echo ""
echo "  Nextgen Shield — Stationery Management"
echo "  (no admin install required)"
echo ""

# Java inventory service (optional but running if JDK is in your home folder)
if [ -x "$JAVA_BIN" ] && [ -x "$JAVAC_BIN" ]; then
  mkdir -p "$ROOT/java/build"
  "$JAVAC_BIN" -cp "$CP" -d "$ROOT/java/build" "$ROOT/java/src/"*.java 2>/dev/null
  if [ $? -eq 0 ]; then
    "$JAVA_BIN" -cp "$ROOT/java/build:$CP" InventoryServer "$DB" "$JAVA_PORT" &
    echo "  Java API:  http://127.0.0.1:$JAVA_PORT"
    sleep 1
  fi
fi

LAN_IP=$(ipconfig getifaddr en0 2>/dev/null || ipconfig getifaddr en1 2>/dev/null || echo "")
echo "  On this Mac:   http://127.0.0.1:$PORT"
if [ -n "$LAN_IP" ]; then
  echo "  Other devices: http://$LAN_IP:$PORT"
  echo "  (same Wi-Fi / office network)"
fi
echo ""
echo "  Each staff member uses their own login."
echo "  Manager adds accounts under Members."
echo "  Default: manager / manager123"
echo ""
echo "  Press Ctrl+C to stop."
echo ""

python3 "$ROOT/app.py"