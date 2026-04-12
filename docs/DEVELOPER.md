# Training Translation Tracker – WordPress Plugin

## Dokumentation

**Plugin Version:** 0.1.4-beta  
**Mindestanforderung:** WordPress 5.8, PHP 7.4  
**Lizenz:** GPL-2.0-or-later

---

## Inhaltsverzeichnis

1. [Übersicht](#1-übersicht)
2. [ZIP erstellen & Installation](#2-zip-erstellen)
3. [Konfiguration](#3-konfiguration)
4. [Shortcode verwenden](#4-shortcode-verwenden)
5. [GitHub Issues korrekt befüllen](#5-github-issues-korrekt-befüllen)
   - [5.6 Issue-Vorlage: Vorgegebener Teil](#56-issue-vorlage-vorgegebener-teil-nicht-ändern)
   - [5.7 Issue-Vorlage: Zusatz für den Training Translation Tracker](#57-issue-vorlage-zusatz-für-den-training-translation-tracker)
6. [Funktionsweise im Detail](#6-funktionsweise-im-detail)
7. [Caching und Auto-Refresh](#7-caching-und-auto-refresh)
8. [GitHub Token einrichten](#8-github-token-einrichten)
9. [Sprache / Übersetzung](#9-sprache--übersetzung)
10. [Dateistruktur](#10-dateistruktur)
11. [Technische Referenz](#11-technische-referenz)
12. [Fehlerbehebung](#12-fehlerbehebung)

---

## 1. Übersicht

Das Plugin **Training Translation Tracker** zeigt den Fortschritt von Übersetzungen auf learn.wordpress.org als interaktives Dashboard direkt auf einer WordPress-Seite an.

**Kernfunktionen:**

- Shortcode `[translation_tracker]` für jede Seite oder jeden Beitrag
- Liest Status-Tabellen direkt aus GitHub-Issues (via GitHub Project V2 GraphQL API oder REST API)
- Filtert Issues nach dem konfigurierbaren Projekt-Feld `Locale` (z.B. `German` für das DACH-Team)
- Zeigt Original-URL und Übersetzungs-URL als separate Spalten
- Sortierbare Spalten (Klick auf Spaltentitel)
- Filter und Suchfeld
- Auto-Refresh nach konfigurierbarem Intervall (stündlich bis 72h)
- GitHub Token wird sicher im Backend gespeichert
- Mehrsprachig (Englisch/Deutsch via WordPress-Übersetzungssystem)
- Alle CSS-Klassen mit `tt-`-Prefix (keine Konflikte mit Themes)
- Hierarchische Gruppierung nach Kursstruktur (Lernpfad → Kurs → Abschnitt → Lektion)
- Kursstruktur wird automatisch via learn.wordpress.org REST API ermittelt (24h gecacht)

---

## 2. ZIP erstellen

Die ZIP-Datei heisst immer **`training-translation-tracker.zip`**.

### Manuell (lokal)

```bash
cd /pfad/zu/training-training-translation-tracker

git archive --format=zip --prefix=training-training-translation-tracker/ HEAD:plugin \
  -o training-translation-tracker.zip
```

Der Befehl archiviert ausschliesslich den Inhalt des `plugin/`-Verzeichnisses.
Das Ergebnis ist ein sauberer ZIP ohne Entwicklungs-, Dokumentations- oder
GitHub-Dateien. Die Datei kann danach direkt in WordPress importiert werden.

### Automatisch via GitHub Actions

Das Repository enthält einen Workflow unter
`.github/workflows/release.yml`, der `training-translation-tracker.zip` automatisch
baut und bereitstellt:

| Auslöser | Was passiert |
| -------- | ------------ |
| **Manuell** (Actions → Run workflow) | ZIP wird als Workflow-Artefakt hochgeladen (30 Tage verfügbar) |
| **GitHub Release erstellen** | ZIP wird automatisch dem Release als Asset angehängt |

---

## 3. Installation

### Variante A: ZIP-Upload (empfohlen)

1. Erstelle `training-translation-tracker.zip` (siehe Abschnitt 2)
2. Gehe in WordPress zu **Plugins → Installieren → Plugin hochladen**
3. Wähle die ZIP-Datei aus und klicke **Jetzt installieren**
4. Aktiviere das Plugin

### Variante B: Manuell per FTP/SFTP

1. Lade den Ordner `training-training-translation-tracker` nach `wp-content/plugins/` hoch
2. Aktiviere das Plugin unter **Plugins** im WordPress-Admin

### Nach der Aktivierung

Das Plugin erscheint unter **Settings → Training Translation Tracker** im Admin-Menü.

---

## 3. Konfiguration

Die Einstellungsseite findest du unter **Settings → Training Translation Tracker**.

### 3.1 GitHub Project V2 – Locale Filter (empfohlen)

Dies ist der Hauptmodus. Er liest direkt aus dem GitHub-Projekt und filtert nach dem konfigurierbaren Feld `Locale`.

| Feld | Standard | Beschreibung |
| ------ | ---------- | -------------- |
| **GitHub Organisation** | `WordPress` | Die GitHub-Organisation des Projekts |
| **Project Number** | `104` | Die Nummer aus der Projekt-URL: `github.com/orgs/WordPress/projects/`**`104`** |
| **Locale Filter** | `German` | Wert des Projekt-Felds «Locale». Leer lassen = alle Locales anzeigen. |

> **Wichtig:** Dieser Modus erfordert zwingend einen GitHub Token mit `project`-Scope (siehe Abschnitt 8).

### 3.2 GitHub REST API – Label Filter (Fallback)

Wird verwendet wenn **Project Number = 0**. Liest alle Issues eines Repositories nach Label.

| Feld | Standard | Beschreibung |
| ------ | ---------- | -------------- |
| **GitHub Repository** | `WordPress/Learn` | Format: `owner/repo` |
| **Issue Label** | `[Content] Translation` | Nur Issues mit diesem Label werden geladen |

### 3.3 Allgemein

| Feld | Standard | Beschreibung |
| ------ | ---------- | -------------- |
| **GitHub Token** | (leer) | Personal Access Token (siehe Abschnitt 8) |
| **Auto-Refresh Interval** | `4h` | Nach diesem Intervall werden die GitHub-Daten beim nächsten Seitenaufruf neu geladen. Optionen: 1h / 2h / 4h / 6h / 12h / 24h / 48h / 72h |

### 3.4 Cache leeren

Unter **Data Status & Refresh** auf der Einstellungsseite:
- Zeigt wann die Daten zuletzt geladen wurden
- Button **«Clear Cache & Reload Data»** löscht den GitHub-Cache sofort – beim nächsten Seitenaufruf werden die Daten frisch von GitHub geladen
- Button **«Clear Course Structure Cache»** löscht nur den learn.wordpress.org API-Cache (Kursstruktur) – nützlich wenn sich Kurs- oder Abschnittsnamen geändert haben

---

## 4. Shortcode verwenden

### 4.1 Grundform

```
[translation_tracker]
```

Verwendet die Einstellungen aus der Konfigurationsseite.

### 4.2 Parameter überschreiben

**Project-Modus:**
```
[translation_tracker org="WordPress" project="104" locale="German"]
```

**REST-Modus:**
```
[translation_tracker repo="WordPress/Learn" label="[Content] Translation"]
```

| Parameter | Beschreibung |
| ----------- | -------------- |
| `org` | GitHub Organisation |
| `project` | Projekt-Nummer (0 = REST-Modus) |
| `locale` | Locale-Filter (z.B. `German`) |
| `repo` | GitHub Repository (REST-Modus) |
| `label` | Issue Label (REST-Modus) |

### 4.3 Tipps zur Einbettung

- Verwende eine **Seite mit voller Breite** – das Plugin bricht automatisch aus dem Theme-Container aus (`100vw`)
- Pro Seite nur **einen** Shortcode einfügen

---

## 5. GitHub Issues korrekt befüllen

Damit das Plugin die Daten korrekt darstellt, müssen die GitHub-Issues zwei Dinge enthalten:

### 5.1 Status-Tabelle (für Fortschrittsanzeige)

Füge folgende Tabelle in den Issue-Body ein (zwischen den HTML-Kommentaren):

```markdown
<!-- TRANSLATION-STATUS-START -->
| Component  | Status | Creator   | Reviewer  |
| ---------- | ------ | --------- | --------- |
| thumbnails | done   | @rfluethi | @Ursha-wp |
| text       | done   | @rfluethi | @Ursha-wp |
| subtitles  | wip    | @rfluethi |           |
| exercise   | na     |           |           |
| quiz       | open   |           |           |
| audio      | open   |           |           |
| video      | open   |           |           |
<!-- TRANSLATION-STATUS-END -->
```

**Gültige Werte für `Status`:**

| Wert | Bedeutung | Farbe |
| ------ | ----------- | ------- |
| `done` | Fertig / übersetzt und geprüft | Grün |
| `review` | Im Review | Violett |
| `wip` | In Arbeit | Orange |
| `open` | Noch nicht begonnen | Grau |
| `na` | Nicht zutreffend (wird nicht gezählt) | — |

**Gültige Werte für `Component`:**

| Component | Beschreibung |
| ----------- | -------------- |
| `thumbnails` | Vorschaubilder (Thumbnails) |
| `text` | Haupttext der Lektion |
| `subtitles` | Untertitel |
| `exercise` | Übungsaufgaben |
| `quiz` | Quiz |
| `audio` | Audio-Datei |
| `video` | Video |

> Die Kommentare `<!-- TRANSLATION-STATUS-START -->` und `<!-- TRANSLATION-STATUS-END -->` sind zwingend erforderlich – ohne sie findet das Plugin die Tabelle nicht.

### 5.2 Link zur englischen Lektion (für «English Lesson»-Spalte)

Das Plugin erkennt automatisch eine Zeile in diesem Format:

```markdown
- Link to original content: https://learn.wordpress.org/lesson/what-is-wordpress/
```

Die URL muss mit `https://learn.wordpress.org/lesson/` beginnen.

### 5.3 Link zur Übersetzung (für «Translation»-Spalte)

Das Plugin sucht nach einer Zeile die das Wort «translated», «translation» oder ähnliches enthält, gefolgt von einer URL:

```markdown
- Link to translated content: https://learn.wordpress.org/lesson/was-ist-wordpress/
```

**Weitere erkannte Formate:**

```markdown
- German lesson: https://learn.wordpress.org/lesson/was-ist-wordpress/
- Deutsche Lektion: https://learn.wordpress.org/lesson/was-ist-wordpress/
- Translation URL: https://learn.wordpress.org/lesson/was-ist-wordpress/
- DE URL: https://learn.wordpress.org/lesson/was-ist-wordpress/
```

Alternativ erkennt das Plugin automatisch URLs die `/de/` oder `?lang=de` enthalten.

> Falls kein passendes Format im Issue vorhanden ist, zeigt die Spalte «Translation» einen Gedankenstrich (—).

### 5.4 WordPress.tv-Link (unter dem Lektionsnamen)

Das Plugin zeigt einen **WordPress.tv**-Link unterhalb des Lektionsnamens, wenn im Issue eine wordpress.tv-URL angegeben ist.

**Empfohlenes Format:**

```markdown
- Link to WordPress.tv recording: https://wordpress.tv/2024/01/15/what-is-wordpress/
```

Alternativ erkennt das Plugin automatisch jede `wordpress.tv`-URL im Issue-Body.

> Der Link erscheint nur wenn diese Zeile vorhanden und befüllt ist.

### 5.5 YouTube-Link (für «YouTube»-Link unter der Lektion)

Das Plugin zeigt einen **YouTube**-Link unterhalb des Lektionstitels, wenn im Issue eine YouTube-URL angegeben ist.

**Empfohlenes Format** (Feldname «Link to YouTube recording»):

```markdown
- Link to YouTube recording: https://www.youtube.com/watch?v=VIDEOID
```

Alternativ erkennt das Plugin automatisch `youtube.com`- und `youtu.be`-URLs im Issue-Body.

> TV- und YouTube-Links erscheinen sowohl unter dem Original als auch unter der Übersetzung.

### 5.6 Issue-Vorlage: Vorgegebener Teil (nicht ändern)

Die folgende Vorlage ist von WordPress.org vorgegeben und **darf nicht verändert werden**. Sie ist bereits in jedem neuen Übersetzungs-Issue enthalten:

```markdown
<!--
The steps to translating content on Learn WordPress can be found at
https://make.wordpress.org/training/handbook/content-localization/.

Remember to update the title of this issue by replacing the capitalized words.
Example: Greek translation for Lesson Plan "Introduction To Common Plugins"
-->

# Details
- Link to original content: 
- Link to original content's GitHub issue (optional): 
- Language you'll be translating to: 
- Have you arranged for someone to review this translation?: Yes or No
- Reviewer's GitHub username: 
- Other info: 

# Next Steps
Once translated, please link or upload your translated files in a comment on this issue, and request a [translation review](https://make.wordpress.org/training/handbook/content-localization/#translation-review).
```

### 5.7 Issue-Vorlage: Zusatz für den Training Translation Tracker

Die folgenden Abschnitte **ergänzen** die obige Vorlage. Sie werden nach dem Abschnitt «# Details» eingefügt und müssen manuell hinzugefügt werden, damit das Dashboard die Lektion korrekt darstellen kann.

```markdown
<!-- Training Translation Tracker: fill in the fields below to display this lesson in the dashboard. -->

<!-- Short title of the original content (without "Learn WordPress:" prefix) -->
<!-- Example: Original title: What is WordPress -->
- Original title:

<!-- Fill in once the translation is published on learn.wordpress.org -->
<!-- Example: https://learn.wordpress.org/lesson/what-is-wordpress/ -->
- Link to translated content: https://learn.wordpress.org/lesson/TRANSLATION-SLUG/

<!-- Fill in once available title of the translated content -->
<!-- Translation title: Was ist WordPress -->
- Translation title: Was ist WordPress

<!-- Fill in once available the URL on WordPress.tv -->
<!-- Example: https://wordpress.tv/2099/12/31/what-is-wordpress/ -->
- Link to WordPress.tv recording: https://wordpress.tv/YEAR/MONTH/DAY/SLUG/

<!-- Fill in once available like https://www.youtube.com/watch?v=VIDEOID -->
<!-- Example: https://www.youtube.com/watch?v=XyzxYzxyZ -->
- Link to YouTube recording: https://www.youtube.com/watch?v=VIDEOID

<!-- Optional. Order within the course section (number). Omit for lesson plans / tutorials. -->
- Order: 10

<!-- TRANSLATION-STATUS-START -->
<!-- Status values: open | wip | review | done | na. Creator and Reviewer: GitHub username without @. -->
| Component  | Status | Creator | Reviewer |
| ---------- | ------ | ------- | -------- |
| thumbnails | open   |         |          |
| text       | open   |         |          |
| subtitles  | open   |         |          |
| exercise   | open   |         |          |
| quiz       | open   |         |          |
| audio      | open   |         |          |
| video      | open   |         |          |
<!-- TRANSLATION-STATUS-END -->
```

**Hinweise zu den Feldern:**

| Feld | Pflicht | Wann befüllen |
| ---- | ------- | ------------- |
| `English lesson name` | Empfohlen | Beim Erstellen des Issues |
| `Link to translated content` | Optional | Sobald die Übersetzung veröffentlicht ist |
| `German lesson name` | Empfohlen | Sobald die Übersetzung veröffentlicht ist (Legacy-Feld) |
| `Link to WordPress.tv recording` | Optional | Sobald das Recording auf wordpress.tv verfügbar ist |
| `Link to YouTube recording` | Optional | Sobald das Video auf YouTube verfügbar ist |
| `Order` | Optional | Wenn die Reihenfolge im Kursabschnitt festgelegt ist |
| Status-Tabelle | Empfohlen | Laufend aktualisieren |

**Kursstruktur wird automatisch ermittelt** – folgende Felder müssen **nicht** manuell eingetragen werden:

- **Lernpfad** und **Kurs**: automatisch via learn.wordpress.org REST API (aus `Link to original content`)
- **Abschnitt**: automatisch aus der API
- Issues ohne `Link to original content`: erscheinen in der Gruppe **«Other»** ganz unten

**Verhalten wenn Felder fehlen oder leer sind:**

- Kein `English lesson name` → Dashboard zeigt den GitHub-Issue-Titel kursiv, ohne Link
- Kein `German lesson name` → Dashboard zeigt den GitHub-Issue-Titel kursiv, ohne Link
- Kein Link zu WordPress.tv / YouTube → der jeweilige Link wird nicht angezeigt
- Keine `Order`-Angabe → Lektion erscheint am Ende des Abschnitts

### 5.8 Reihenfolge innerhalb eines Abschnitts (Order)

Das Dashboard gruppiert Lektionen automatisch nach Lernpfad → Kurs → Abschnitt. Die Reihenfolge der Lektionen **innerhalb** eines Abschnitts wird mit dem `Order:`-Feld gesteuert:

- Lektionen werden aufsteigend nach dem `Order:`-Wert sortiert
- Bei gleicher Zahl: alphabetisch nach Lektionsname
- Ohne `Order:`-Angabe erscheint die Lektion am Ende des Abschnitts

> **Für flache Strukturen** (Lesson Plans, Tutorials) ist kein `Order:`-Feld nötig – diese Lektionen werden alphabetisch sortiert.

---

## 6. Funktionsweise im Detail

### 6.1 Ablauf beim Seitenaufruf

```
Besucher öffnet Seite mit Shortcode
        │
        ▼
Plugin prüft: Gibt es gecachte Daten?
        │
  ┌─────┴──────┐
  │ JA         │ NEIN / abgelaufen
  │ Cache      │ GitHub API aufrufen
  │ verwenden  │ (GraphQL oder REST)
  └─────┬──────┘
        │
        ▼
Daten werden gefiltert (Locale = German)
und Status-Tabellen geparst
        │
        ▼
PHP rendert HTML-Gerüst + übergibt
Daten als JSON an JavaScript
        │
        ▼
JavaScript rendert Tabelle, Sortierung,
Filter und Stats im Browser
```

### 6.2 Datenfluss (GraphQL-Modus)

```
GitHub Project #104
        │
        ▼ GraphQL API (benötigt Token)
Alle Items des Projekts laden
        │
        ▼ Filter: Locale = "German"
Nur gefilterte Übersetzungs-Issues
        │
        ▼ Parser: Status-Tabelle + URLs
Strukturierte Lektionsdaten
        │
        ▼ WordPress Transient
Cache für konfigurierten Zeitraum
```

---

## 7. Caching und Auto-Refresh

### 7.1 Wie funktioniert das Caching?

Das Plugin nutzt **WordPress Transients**. Die Daten werden nach dem ersten Laden für die konfigurierte Zeit in der Datenbank gespeichert. Kein Besucher verursacht während dieser Zeit GitHub-API-Anfragen.

### 7.2 Auto-Refresh Interval

Einstellbar unter **Settings → Training Translation Tracker → Auto-Refresh Interval**:

| Intervall | Empfohlen für |
| ----------- | --------------- |
| 1h – 2h | Aktive Arbeitsphase (Status ändert sich oft) |
| 4h – 6h | Normaler Betrieb (Standard: 4h) |
| 12h – 24h | Öffentliche Statusseite |
| 48h – 72h | Archiv / selten aktualisiert |

Nach Ablauf des Intervalls werden die Daten beim **nächsten Seitenaufruf** automatisch neu von GitHub geladen.

### 7.3 Cache manuell leeren

Unter **Settings → Training Translation Tracker → Data Status & Refresh** → Button «Clear Cache & Reload Data».

Alternativ per WP-CLI:
```bash
wp transient delete --all
```

---

## 8. GitHub Token einrichten

### 8.1 Welcher Token für welchen Modus?

| Modus | Benötigter Scope | Pflicht? |
| ------- | ----------------- | --------- |
| **Project V2 (GraphQL)** | `project` (read) | **Ja – ohne Token kein Zugriff** |
| **REST API** | `public_repo` | Nein (erhöht aber Rate Limit) |

### 8.2 Token erstellen (Classic Token)

1. Gehe zu [github.com/settings/tokens](https://github.com/settings/tokens)
2. Klicke **Generate new token (classic)**
3. Vergib einen Namen, z.B. `Training Translation Tracker`
4. Wähle folgende Scopes:
   - `read:org` (für GraphQL Organisationszugriff)
   - `project` (für GitHub Projects)
5. Setze das Ablaufdatum (z.B. 1 Jahr)
6. Klicke **Generate token** und kopiere den Token sofort

### 8.3 Token eintragen

**Option A — Umgebungsvariable (empfohlen):**

Den Token als Server-Umgebungsvariable setzen, z. B. in `wp-config.php`:

```php
putenv( 'TT_GITHUB_TOKEN=ghp_xxxxxxxxxxxx' );
```

Oder in der Serverkonfiguration (Apache `.htaccess` / Nginx `fastcgi_param`):

```apacheconf
SetEnv TT_GITHUB_TOKEN ghp_xxxxxxxxxxxx
```

Das Plugin liest die Variable automatisch — das Feld im Backend bleibt leer.

**Option B — Backend-Feld:**

1. Gehe zu **Settings → Training Translation Tracker**
2. Füge den Token ins Feld **GitHub Token** ein
3. Klicke **Save Settings**

### 8.4 Sicherheit

- Token via Umgebungsvariable (`TT_GITHUB_TOKEN`) speichern,
  damit er nicht in der Datenbank liegt
- Wird ein DB-Token verwendet, liegt er in `wp_options`
  und ist für WordPress-Admins lesbar
- Der Token wird **nie** an das Frontend oder Besucher übermittelt
- Empfehlung: Minimale Scopes wählen (`project` read-only)
- Bei Verdacht auf Kompromittierung: Token sofort auf GitHub widerrufen

---

## 9. Sprache / Übersetzung

Das Plugin ist auf **Englisch** verfasst und enthält eine fertige **deutsche Übersetzung**.

### 9.1 Deutsche Übersetzung aktivieren

1. Gehe in WordPress zu **Einstellungen → Allgemein**
2. Setze **Website-Sprache** auf **Deutsch**
3. Speichern – das Plugin erscheint nun auf Deutsch

### 9.2 Übersetzungsdateien

Die Dateien liegen im Ordner `languages/`:

| Datei | Beschreibung |
| ------- | -------------- |
| `training-translation-tracker.pot` | Template mit allen englischen Strings |
| `training-translation-tracker-de_DE.po` | Deutsche Übersetzung (Quelltext) |
| `training-translation-tracker-de_DE.mo` | Kompilierte Binärdatei (von WordPress geladen) |

### 9.3 .mo-Datei neu kompilieren

Nach Änderungen an der `.po`-Datei:

```bash
cd languages/
msgfmt training-translation-tracker-de_DE.po -o training-translation-tracker-de_DE.mo
```

---

## 10. Dateistruktur

```
training-training-translation-tracker/          Repository-Root (GitHub)
│
├── plugin/                      NUR diese Dateien landen im ZIP
│   ├── training-translation-tracker.php  Hauptdatei: Plugin-Header, Settings,
│   │                             GitHub API (GraphQL + REST), Shortcode, AJAX
│   ├── readme.txt               WordPress.org Plugin-Verzeichnis Format
│   ├── assets/
│   │   ├── dashboard.css        Styles (Light Theme, tt-Prefix, Viewport-Breakout)
│   │   └── dashboard.js         Frontend-Rendering, Sortierung, Filter
│   └── languages/
│       ├── training-translation-tracker.pot          String-Template
│       ├── training-translation-tracker-de_DE.po     Deutsche Übersetzung (Quelle)
│       └── training-translation-tracker-de_DE.mo     Kompilierte Übersetzung
│
├── wporg-assets/                WP.org SVN-Assets (nicht im ZIP)
│   ├── banner-1544x500.png
│   ├── banner-772x250.png
│   ├── icon-128x128.png
│   └── icon-256x256.png
│
├── docs/                        Dokumentation (nur GitHub)
│   ├── DEVELOPER.md             Diese Datei – technische Dokumentation
│   └── USER-GUIDE.md            Anleitung für Endanwender
│
├── .github/                     GitHub-Konfiguration
│   ├── SECURITY.md              Anleitung für vertrauliche Sicherheitsmeldungen
│   ├── ISSUE_TEMPLATE/
│   │   ├── bug_report.md        Issue-Template für Fehlerberichte
│   │   └── feature_request.md   Issue-Template für Feature-Wünsche
│   └── workflows/
│       └── release.yml          GitHub Actions: ZIP-Build und Release-Anhang
│
├── CHANGELOG.md                 Versionshistorie (nur GitHub)
├── CONTRIBUTING.md              Anleitung für Beitragende (nur GitHub)
├── LICENSE                      GPL-2.0-or-later (nur GitHub)
└── README.md                    GitHub-Projektbeschreibung
```

---

## 11. Technische Referenz

### 11.1 WordPress Options (wp_options)

| Option Key | Typ | Standard | Beschreibung |
| ------------ | ----- | ---------- | -------------- |
| `tt_github_org` | string | `WordPress` | GitHub Organisation |
| `tt_project_number` | integer | `104` | GitHub Projekt-Nummer (0 = REST-Modus) |
| `tt_locale_filter` | string | `German` | Locale-Filter für Project-Modus |
| `tt_github_repo` | string | `WordPress/Learn` | GitHub Repository (REST-Modus) |
| `tt_github_label` | string | `[Content] Translation` | Issue Label (REST-Modus) |
| `tt_github_token` | string | (leer) | GitHub Personal Access Token |
| `tt_refresh_hours` | integer | `4` | Auto-Refresh Intervall in Stunden |
| `tt_lwp_cache_hours` | integer | `24` | Cache-Dauer für learn.wordpress.org Kursstruktur in Stunden |
| `tt_last_fetched` | string | (leer) | Zeitstempel des letzten Datenabrufs |

### 11.2 Transients

| Transient Key | Inhalt | Ablauf |
| ------------- | ------ | ------ |
| `tt_proj_{md5}` | `lessons`, `fetched`, `count` | `tt_refresh_hours` |
| `tt_issues_{md5}` | `lessons`, `fetched`, `count` | `tt_refresh_hours` |
| `tt_lwp_{slug}` | `pathway`, `course`, `section` | `tt_lwp_cache_hours` |

### 11.3 PHP-Funktionen

| Funktion | Beschreibung |
| ---------- | -------------- |
| `tt_fetch_project_issues()` | Lädt Issues via GitHub GraphQL API (Project V2) |
| `tt_fetch_issues()` | Lädt Issues via GitHub REST API |
| `tt_build_lesson()` | Erstellt ein Lesson-Array aus Issue-Daten |
| `tt_parse_status_table()` | Parst die Markdown-Tabelle aus dem Issue-Body |
| `tt_extract_lesson_url()` | Extrahiert die englische Lektions-URL |
| `tt_extract_lesson_de_url()` | Extrahiert die Übersetzungs-URL |
| `tt_slug_from_url()` | Extrahiert den Slug aus einer learn.wordpress.org URL |
| `tt_fetch_lesson_structure()` | Fragt Kursstruktur (Lernpfad, Kurs, Abschnitt) via learn.wordpress.org API ab |
| `tt_extract_order()` | Liest den `Order:`-Wert aus dem Issue-Body |
| `tt_load_data()` | Wählt automatisch GraphQL oder REST je nach Einstellung |
| `tt_ajax_refresh()` | AJAX-Endpoint für Cache-Clearing |
| `tt_shortcode_render()` | Rendert das Dashboard-HTML |

### 11.4 Kursstruktur API (learn.wordpress.org)

Das Plugin ermittelt Lernpfad, Kurs und Abschnitt automatisch aus der englischen Lektions-URL:

```text
en_url: https://learn.wordpress.org/lesson/intro-to-wordpress/
        → Slug: intro-to-wordpress

GET https://learn.wordpress.org/wp/v2/lessons?slug=intro-to-wordpress
    → _lesson_course: 12345
    → show: [42]           (Abschnitts-Term-ID)

GET https://learn.wordpress.org/wp/v2/show/42
    → name: "Getting Started with WordPress"

GET https://learn.wordpress.org/wp/v2/courses/12345
    → title: "Beginner WordPress User"
    → learning-pathway: [7]

GET https://learn.wordpress.org/wp/v2/learning-pathway/7
    → name: "User"
```

Das Ergebnis wird als `tt_lwp_{slug}` gecacht (Standard: 24h). Bei Issues ohne `en_url` bleiben alle drei Felder leer und die Lektion landet in der Gruppe **«Other»**.

### 11.5 JavaScript-Objekt `ttData`

```javascript
ttData = {
  lessons: [{
    title:        "Lesson title",
    issue_number: 1234,
    issue_url:    "https://github.com/...",
    issue_state:  "open" | "closed",
    en_name:      "What is WordPress",
    de_name:      "Was ist WordPress",
    en_url:       "https://learn.wordpress.org/lesson/english-slug/",
    de_url:       "https://learn.wordpress.org/lesson/deutsch-slug/",
    tv_url:       "https://wordpress.tv/...",
    youtube_url:  "https://www.youtube.com/...",
    pathway:      "User",                          // aus API
    course:       "Beginner WordPress User",       // aus API
    section:      "Getting Started with WordPress",// aus API
    order:        10,                              // aus Issue-Feld (Standard: 9999)
    hasTable:     true | false,
    thumbnails:   { status: "done", creator: "rfluethi", reviewer: "Ursha-wp" },
    text:         { status: "wip",  creator: "rfluethi", reviewer: "" },
    subtitles:    { status: "open", creator: "", reviewer: "" },
    exercise:     { status: "na",   creator: "", reviewer: "" },
    quiz:         { status: "open", creator: "", reviewer: "" },
    audio:        { status: "open", creator: "", reviewer: "" },
    video:        { status: "open", creator: "", reviewer: "" }
  }],
  error:   "",          // Fehlermeldung (leer wenn OK)
  ajaxUrl: "...",       // admin-ajax.php URL
  project: 104,         // Projekt-Nummer
  org:     "WordPress",
  locale:  "German",
  repo:    "WordPress/Learn",
  label:   "[Content] Translation",
  i18n:    { ... }      // Übersetzbare Strings für JS
}
```

### 11.5 Shortcode-Parameter

| Parameter | Standard | Beschreibung |
| ----------- | ---------- | -------------- |
| `org` | Einstellung | GitHub Organisation |
| `project` | Einstellung | Projekt-Nummer (0 = REST-Modus) |
| `locale` | Einstellung | Locale-Filter |
| `repo` | Einstellung | GitHub Repository (REST-Modus) |
| `label` | Einstellung | Issue Label (REST-Modus) |

---

## 12. Fehlerbehebung

### Dashboard zeigt keine Daten

1. Prüfe unter **Settings → Training Translation Tracker** ob Org, Projekt-Nummer und Token korrekt sind
2. Klicke **«Clear Cache & Reload Data»** um den Cache zu leeren
3. Öffne die Seite mit dem Shortcode neu

### "A GitHub Token is required for Project mode"

Der GraphQL-Modus benötigt zwingend einen Token mit `project`-Scope. Token unter **Settings → Training Translation Tracker → GitHub Token** eintragen.

### "GraphQL: Could not resolve to a ProjectV2"

Die Projekt-Nummer ist falsch. Prüfe die Zahl in der GitHub-Projekt-URL: `github.com/orgs/WordPress/projects/`**`104`**.

### «Translation»-Spalte zeigt nur —

Das Issue enthält keinen Link zur Übersetzung. Ergänze den Issue-Body mit:
```markdown
**Link to translated content:** https://learn.wordpress.org/lesson/DEUTSCH-SLUG/
```

Siehe [Abschnitt 5.3](#53-link-zur-übersetzung-für-translation-spalte) für alle erkannten Formate.

### "GitHub API 403" Fehler (REST-Modus)

Rate Limit erreicht. Lösung: GitHub Token eintragen (Abschnitt 8).

### "GitHub API 404" Fehler (REST-Modus)

Repository nicht gefunden. Prüfe die Schreibweise: `WordPress/Learn`.

### Plugin-Texte erscheinen auf Englisch statt Deutsch

Die WordPress-Sprache muss auf Deutsch eingestellt sein:  
**Einstellungen → Allgemein → Website-Sprache → Deutsch**

### Styles sehen komisch aus / Konflikte mit Theme

Das Plugin verwendet ausschliesslich `tt-`-prefixed CSS-Klassen. Falls trotzdem Konflikte bestehen, prüfe ob das Theme `!important`-Regeln auf generische Elemente wie `table` oder `button` setzt.
