# Laravel SAST Lab 🛡️

**Panduan Praktikum DevSecOps — SAST dengan SonarQube**  
STT Terpadu Nurul Fikri · Mata Kuliah DevSecOps

---

> ⚠️ **PERINGATAN**: Project ini mengandung **kerentanan keamanan yang disengaja** untuk tujuan pembelajaran.  
> **JANGAN** gunakan kode ini di environment production!

---

## 📋 Ringkasan Kerentanan

Project ini berisi **11 kerentanan** yang terdistribusi di 4 controller:

| File | Kerentanan | OWASP | Severity |
|------|-----------|-------|----------|
| `UserController.php` | SQL Injection (3x) | A03:2021 | 🔴 CRITICAL |
| `UserController.php` | Mass Assignment | A01:2021 | 🟠 MAJOR |
| `UserController.php` | IDOR (Insecure Direct Object Reference) | A01:2021 | 🟠 MAJOR |
| `AuthController.php` | Hardcoded Credentials (4 secrets) | A02:2021 | 🔴 CRITICAL |
| `AuthController.php` | Password di Plaintext Log | A09:2021 | 🟠 MAJOR |
| `AuthController.php` | No Password Hashing | A02:2021 | 🔴 CRITICAL |
| `AuthController.php` | Weak Reset Token (md5) | A02:2021 | 🟠 MAJOR |
| `FileController.php` | Path Traversal | A01:2021 | 🔴 CRITICAL |
| `FileController.php` | Unrestricted File Upload | A04:2021 | 🔴 CRITICAL |
| `FileController.php` | Command Injection (2x) | A03:2021 | 🔴 CRITICAL |
| `PaymentController.php` | IDOR pada Transaksi | A01:2021 | 🟠 MAJOR |
| `PaymentController.php` | Missing Authorization | A01:2021 | 🟠 MAJOR |
| `PaymentController.php` | Sensitive Data Exposure (CVV) | A02:2021 | 🔴 CRITICAL |
| `PaymentController.php` | Open Redirect | A01:2021 | 🟠 MAJOR |
| `PaymentController.php` | Missing Webhook Signature Verification | A02:2021 | 🟠 MAJOR |
| `routes/web.php` | CSRF Protection Disabled | A01:2021 | 🔴 CRITICAL |
| `routes/web.php` | Missing Authentication pada Route Kritis | A07:2021 | 🟠 MAJOR |
| `.env.example` | Sensitive Data in Config (API Keys) | A02:2021 | 🟠 MAJOR |

---

## 🚀 Cara Penggunaan

### Prasyarat
- Docker Desktop (v24+)
- Docker Compose (v2.x)

### Langkah 1 — Jalankan SonarQube

```bash
# Clone / ekstrak project ini
cd laravel-sast-lab

# Jalankan SonarQube + PostgreSQL
docker compose up -d

# Tunggu sekitar 1-2 menit, lalu cek status
docker compose ps
```

SonarQube akan tersedia di: **http://localhost:9000**  
Login default: `admin` / `admin` (akan diminta ganti saat pertama login)

> **Troubleshooting**: Jika SonarQube gagal start, jalankan:
> ```bash
> sudo sysctl -w vm.max_map_count=262144
> ```

### Langkah 2 — Buat Project di SonarQube

1. Buka **http://localhost:9000**
2. Klik **"Create project"** → **"Local project"**
3. Isi:
   - Project key: `laravel-sast-lab`
   - Display name: `Laravel SAST Lab`
4. Klik **"Set up"** → **"Locally"**
5. Buat token baru → **copy token tersebut**

### Langkah 3 — Konfigurasi Token

```bash
# Opsi A: Via environment variable (direkomendasikan)
export SONAR_TOKEN=your_token_here

# Opsi B: Edit sonar-project.properties
# Ganti baris: sonar.token=<TOKEN_ANDA>
# Dengan:      sonar.token=your_actual_token
```

### Langkah 4 — Jalankan Scan

```bash
# Cara termudah — gunakan script otomatis
chmod +x scan.sh
./scan.sh

# Atau jalankan SonarScanner langsung via Docker
docker run --rm \
  --network host \
  -v "$(pwd):/usr/src" \
  sonarsource/sonar-scanner-cli \
  -Dsonar.projectBaseDir=/usr/src \
  -Dsonar.token=$SONAR_TOKEN
```

### Langkah 5 — Lihat Hasil

Buka: **http://localhost:9000/dashboard?id=laravel-sast-lab**

---

## 📁 Struktur Project

```
laravel-sast-lab/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── UserController.php     ← SQL Injection, Mass Assignment, IDOR
│   │       ├── AuthController.php     ← Hardcoded Credentials, No Password Hash
│   │       ├── FileController.php     ← Path Traversal, File Upload, Cmd Injection
│   │       └── PaymentController.php  ← IDOR, Open Redirect, Missing Auth
│   └── Models/
│       └── User.php                   ← Mass Assignment (no $fillable)
├── routes/
│   ├── web.php                        ← CSRF disabled, Missing Auth
│   └── api.php                        ← No Rate Limiting
├── database/
│   └── migrations/                    ← Skema dengan kolom sensitif
├── tests/
│   └── Feature/
│       └── VulnerabilityTest.php      ← Test case dokumentasi kerentanan
├── docker-compose.yml                 ← SonarQube + PostgreSQL setup
├── sonar-project.properties           ← Konfigurasi SonarQube scanner
├── scan.sh                            ← Script scan otomatis
└── .env.example                       ← Contoh env dengan kerentanan
```

---

## 📚 Tugas Kelompok

Setelah scan selesai, dokumentasikan:

1. **Screenshot** Quality Gate dashboard
2. **Tabel findings** — file, baris, kategori, severity, deskripsi
3. **Root cause analysis** — mengapa kode tersebut rentan?
4. **Remediasi** — kode sebelum dan sesudah perbaikan
5. **Laporan** — BAB I s.d. BAB VI (lihat modul praktikum)

---

## 🔗 Referensi

- [SonarQube Documentation](https://docs.sonarqube.org)
- [Laravel Security Best Practices](https://laravel.com/docs/security)
- [OWASP Top 10 2021](https://owasp.org/Top10)
- [PHP Security Guide](https://phptherightway.com/#security)
- [CWE Top 25](https://cwe.mitre.org/top25/)

---

*DevSecOps — STT Terpadu Nurul Fikri*
