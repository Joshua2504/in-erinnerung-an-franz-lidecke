# In Erinnerung an Franz Lidecke

Gedenkwebsite mit öffentlichem Kondolenzbuch unter [franz-lidecke.de](https://franz-lidecke.de).

## Stack

- PHP 8.3 + Apache (Docker)
- SQLite mit WAL-Modus
- PHPMailer für E-Mail-Benachrichtigungen
- GLightbox für die Bildergalerie
- Google Analytics (mit Cookie-Consent-Banner)

## Lokale Entwicklung

```sh
cp .env.example .env.local   # Werte anpassen
docker compose up --build
# → http://localhost:8997
```

## Deployment

Push auf `main` löst GitHub Actions aus (rsync → `krakatau.treudler.net`).

**Benötigte GitHub Secrets & Variables:**

| Name | Typ | Wert |
|---|---|---|
| `SSH_KEY` | Secret | Privater SSH-Schlüssel |
| `SSH_HOST` | Variable | `krakatau.treudler.net` |
| `SSH_USER` | Variable | `root` |
| `SSH_PATH` | Variable | `/root/docker/franz` |

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

- Seitenaufruf-Statistiken (gesamt, letzte 7 Tage, heute)
- Wartende Einträge freigeben oder ablehnen
- Freigegebene Einträge nachträglich zurückziehen oder löschen
- Bei Freigabe erhält der Einsender eine Bestätigungs-E-Mail (falls angegeben)
- Bei Löschung werden auch alle hochgeladenen Fotos entfernt

## Datenpersistenz

Die Verzeichnisse `data/` (SQLite-Datenbank) und `uploads/` (Fotos) werden als Docker-Volumes gemountet und beim Deployment **nicht** überschrieben.
