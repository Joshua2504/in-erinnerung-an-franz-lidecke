# In Erinnerung an Franz Lidecke

Gedenkwebsite mit öffentlichem Kondolenzbuch unter franz-lidecke.de.

## Stack

- PHP 8.3 + Apache (Docker)
- SQLite mit WAL-Modus
- PHPMailer für E-Mail-Benachrichtigungen
- GLightbox für die Bildergalerie

## Lokale Entwicklung

```sh
docker compose up --build
# → http://localhost:8997
```

Für das Admin-Panel wird eine `.env.local` benötigt:

```sh
cp .env.example .env.local  # oder manuell anlegen, siehe unten
```

## Deployment

Push auf `main` löst GitHub Actions aus (rsync → `krakatau.treudler.net`).

**Benötigtes GitHub Secret:** `SSH_KEY` (privater SSH-Schlüssel für root@krakatau.treudler.net)

## Server-Setup (einmalig)

```sh
mkdir -p /root/docker/franz/data /root/docker/franz/uploads

cat > /root/docker/franz/.env.local << 'EOF'
ADMIN_TOKEN=<langer_zufaelliger_string>
MAILER=benutzer:passwort@mail.treudler.net
ADMIN_EMAIL=admin@example.com
EOF
```

Danach das erste Deployment anstoßen (Push auf `main`).

## Admin-Panel

```
https://franz-lidecke.de/admin.php?token=<ADMIN_TOKEN>
```

- **Wartende Einträge** freigeben oder löschen
- Bei Freigabe erhält der Einsender eine Bestätigungs-E-Mail (falls angegeben)
- Bei Löschung werden auch alle hochgeladenen Fotos entfernt

## Datenpersistenz

Die Verzeichnisse `data/` (SQLite-Datenbank) und `uploads/` (Fotos) werden als Docker-Volumes gemountet und beim Deployment **nicht** überschrieben.
