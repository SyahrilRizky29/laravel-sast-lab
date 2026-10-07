#!/bin/bash

# ==============================================================================
# scan.sh — Script otomatis untuk menjalankan SAST scan dengan SonarQube
#
# Penggunaan:
#   chmod +x scan.sh
#   ./scan.sh
#
# Prasyarat:
#   - Docker & Docker Compose sudah terinstall
#   - SonarQube sudah berjalan (jalankan: docker compose up -d)
#   - Ganti TOKEN di bawah atau atur di sonar-project.properties
# ==============================================================================

set -e

# ─── Konfigurasi ──────────────────────────────────────────────────────────────
SONAR_URL="http://localhost:9000"
PROJECT_KEY="laravel-sast-lab"
PROJECT_NAME="Laravel SAST Lab"
SONAR_TOKEN="${SONAR_TOKEN:-}"   # Set via environment variable atau parameter

# ─── Banner ───────────────────────────────────────────────────────────────────
echo "============================================================"
echo "  SAST Scan — Laravel SAST Lab"
echo "  DevSecOps Praktikum — STT Nurul Fikri"
echo "============================================================"
echo ""

# ─── Cek apakah token sudah diset ────────────────────────────────────────────
if [ -z "$SONAR_TOKEN" ]; then
    # Coba ambil dari sonar-project.properties
    if grep -q "sonar.token=<TOKEN_ANDA>" sonar-project.properties 2>/dev/null; then
        echo "❌ ERROR: Token SonarQube belum diset!"
        echo ""
        echo "   Cara 1 — Set via environment variable:"
        echo "     export SONAR_TOKEN=your_token_here"
        echo "     ./scan.sh"
        echo ""
        echo "   Cara 2 — Edit sonar-project.properties:"
        echo "     Ganti <TOKEN_ANDA> dengan token dari SonarQube Web UI"
        echo ""
        echo "   Cara membuat token:"
        echo "     1. Buka http://localhost:9000"
        echo "     2. My Account → Security → Generate Token"
        echo "     3. Pilih 'Project Analysis Token', pilih project 'laravel-sast-lab'"
        echo ""
        exit 1
    fi
fi

# ─── Cek apakah SonarQube sudah berjalan ─────────────────────────────────────
echo "⏳ Mengecek koneksi ke SonarQube di $SONAR_URL ..."
if ! curl -sf "${SONAR_URL}/api/system/status" > /dev/null 2>&1; then
    echo "❌ SonarQube tidak dapat dijangkau di $SONAR_URL"
    echo ""
    echo "   Jalankan SonarQube terlebih dahulu:"
    echo "     docker compose up -d"
    echo ""
    echo "   Tunggu ~1-2 menit lalu coba lagi."
    exit 1
fi

SONAR_STATUS=$(curl -sf "${SONAR_URL}/api/system/status" | grep -o '"status":"[^"]*"' | cut -d'"' -f4)
if [ "$SONAR_STATUS" != "UP" ]; then
    echo "❌ SonarQube belum siap. Status: $SONAR_STATUS"
    echo "   Tunggu beberapa saat lagi dan coba lagi."
    exit 1
fi
echo "✅ SonarQube berjalan dan siap!"
echo ""

# ─── Update token di sonar-project.properties jika diberikan via env ─────────
if [ -n "$SONAR_TOKEN" ] && grep -q "<TOKEN_ANDA>" sonar-project.properties 2>/dev/null; then
    echo "📝 Mengupdate token di sonar-project.properties ..."
    sed -i "s|sonar.token=<TOKEN_ANDA>|sonar.token=${SONAR_TOKEN}|g" sonar-project.properties
fi

# ─── Jalankan SonarScanner via Docker ─────────────────────────────────────────
echo "🔍 Memulai SAST scan ..."
echo "   Project: $PROJECT_NAME ($PROJECT_KEY)"
echo "   Source:  $(pwd)"
echo ""

docker run --rm \
    --network host \
    -v "$(pwd):/usr/src" \
    sonarsource/sonar-scanner-cli \
    -Dsonar.projectBaseDir=/usr/src \
    ${SONAR_TOKEN:+-Dsonar.token=$SONAR_TOKEN}

SCAN_EXIT=$?

echo ""
if [ $SCAN_EXIT -eq 0 ]; then
    echo "✅ Scan selesai!"
    echo ""
    echo "   Lihat hasil di: ${SONAR_URL}/dashboard?id=${PROJECT_KEY}"
    echo ""
    echo "   Yang perlu diperiksa:"
    echo "   1. Quality Gate Status (PASSED / FAILED)"
    echo "   2. Vulnerabilities — SQL Injection, Hardcoded Credentials"
    echo "   3. Security Hotspots — Log dengan data sensitif"
    echo "   4. Bugs & Code Smells"
else
    echo "❌ Scan gagal dengan kode error: $SCAN_EXIT"
    echo "   Periksa output di atas untuk detail error."
fi
