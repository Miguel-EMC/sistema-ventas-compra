#!/bin/sh
set -eu

cat > /usr/share/nginx/html/env.js <<EOF
window.__env = {
  API_ORIGIN: "${API_ORIGIN:-http://localhost:8001}"
};
EOF
