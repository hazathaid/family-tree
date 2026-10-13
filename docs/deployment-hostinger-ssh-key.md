# Menyiapkan SSH Key untuk Auto-Deploy ke Hostinger

Panduan ini melengkapi `.github/workflows/deploy.yml`. Tujuannya membuat SSH key
khusus deploy, memasang public key di server Hostinger, lalu mengisi secret di
GitHub.

## 1. Generate pasangan kunci di komputer pengembang

```bash
ssh-keygen -t ed25519 -C "github-actions-deploy" -f ~/.ssh/hostinger_deploy -N ""
```

Perintah ini menghasilkan dua file:

| File | Isi | Dipakai untuk |
| --- | --- | --- |
| `~/.ssh/hostinger_deploy` | private key | secret `SSH_PRIVATE_KEY` |
| `~/.ssh/hostinger_deploy.pub` | public key | dipasang di server Hostinger |

Jangan commit kedua file tersebut. Keduanya berada di `~/.ssh` sehingga aman di
luar repository.

## 2. Pasang public key di Hostinger

Server ini dipakai beberapa project, dan `~/.ssh/authorized_keys` sudah berisi
key lain. Aman menambah key baru asalkan **append**, bukan menimpa. Jangan pernah
memakai `>` (menimpa) — gunakan `>>` atau hPanel. Backup dulu sebelum mengubah:

```bash
cp ~/.ssh/authorized_keys ~/.ssh/authorized_keys.bak
```

Pilih salah satu cara.

### Cara A — hPanel (paling aman)

1. Buka **Advanced → SSH Access → SSH Keys**.
2. Klik **Add SSH Key**.
3. Tempel seluruh isi `~/.ssh/hostinger_deploy.pub`.
4. Simpan.

### Cara B — SSH manual (tanpa copy-paste)

Dari laptop, kirim public key langsung ke server tanpa menyalin:

```bash
cat ~/.ssh/hostinger_deploy.pub | ssh -p PORT_SSH_ANDA SSH_USER@SSH_HOST \
  'cat >> ~/.ssh/authorized_keys && chmod 600 ~/.ssh/authorized_keys'
```

Atau, bila bisa login dengan password, cukup:

```bash
ssh-copy-id -i ~/.ssh/hostinger_deploy.pub -p PORT_SSH_ANDA SSH_USER@SSH_HOST
```

Untuk membatalkan, kembalikan backup:

```bash
cp ~/.ssh/authorized_keys.bak ~/.ssh/authorized_keys
```


## 3. Catat kredensial SSH dari hPanel

Buka **Advanced → SSH Access**. Catat tiga nilai berikut:

| Nilai hPanel | Secret GitHub |
| --- | --- |
| SSH Host | `SSH_HOST` |
| SSH Port | `SSH_PORT` |
| SSH Username | `SSH_USER` |

## 4. Isi secret di GitHub

Buka **Settings → Secrets and variables → Actions → New repository secret**, lalu
isi:

| Secret | Wajib | Keterangan |
| --- | --- | --- |
| `SSH_HOST` | ya | hostname SSH dari hPanel |
| `SSH_PORT` | ya | port SSH dari hPanel |
| `SSH_USER` | ya | username SSH |
| `SSH_PRIVATE_KEY` | ya | seluruh isi file `~/.ssh/hostinger_deploy` |
| `APP_DIR` | ya | path absolut source Laravel di server |
| `PUBLIC_HTML` | tidak | path `public_html` agar aset ikut tersalin |
| `PHP_BIN` | tidak | override binary PHP 8.3 (default `/opt/alt/php83/usr/bin/php`) |
| `COMPOSER_BIN` | tidak | override Composer (default `composer2`) |

Untuk `SSH_PRIVATE_KEY`, salin **seluruh** isi file termasuk baris pembuka dan
penutup:

```text
-----BEGIN OPENSSH PRIVATE KEY-----
...
-----END OPENSSH PRIVATE KEY-----
```

Cara menyalin isi private key:

- macOS: `pbcopy < ~/.ssh/hostinger_deploy`
- Linux: `xclip -selection clipboard < ~/.ssh/hostinger_deploy`
- atau buka file `~/.ssh/hostinger_deploy` dengan editor teks lalu salin semua.

### Cara tanpa copy-paste (disarankan)

Jika kesulitan menyalin dari terminal, gunakan GitHub CLI agar isi file langsung
dikirim sebagai secret tanpa perlu menempel:

```bash
# sekali saja
brew install gh        # macOS; gunakan paket manager lain bila perlu
gh auth login

# kirim tiap secret langsung dari file/perintah
gh secret set SSH_HOST --body "SSH_HOST_DARI_HPANEL"
gh secret set SSH_PORT --body "SSH_PORT_DARI_HPANEL"
gh secret set SSH_USER --body "SSH_USER_DARI_HPANEL"
gh secret set APP_DIR  --body "/path/absolut/source-laravel/di/server"
gh secret set SSH_PRIVATE_KEY < ~/.ssh/hostinger_deploy
```

`gh secret set NAMA < file` membaca langsung isi file, jadi private key tidak
pernah perlu disalin manual. Cek hasilnya:

```bash
gh secret list
```

## 5. Uji koneksi

```bash
ssh -i ~/.ssh/hostinger_deploy -p PORT_SSH_ANDA SSH_USER@SSH_HOST
```

Jika berhasil masuk tanpa diminta password, berarti public key sudah terpasang
benar. Jika masih meminta password, ulangi langkah 2.

## 6. Verifikasi server siap menerima deploy

Pastikan `APP_DIR` adalah git clone dengan remote `origin` yang benar, karena
`scripts/deploy-hostinger.sh` menjalankan `git pull --ff-only origin main`:

```bash
cd /path/ke/app
git remote -v
git status
```

Setelah secret terisi dan perubahan di-push ke branch `main`, workflow
`deploy.yml` akan berjalan otomatis setelah `CI` sukses. Deploy juga dapat
dipicu manual dari tab **Actions → Deploy → Run workflow**.
