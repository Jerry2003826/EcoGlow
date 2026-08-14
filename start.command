#!/bin/bash
cd "$(dirname "$0")"
chmod +x ./start.sh ./bin/dev_up.php 2>/dev/null || true
exec ./start.sh
