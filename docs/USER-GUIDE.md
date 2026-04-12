# Training Translation Tracker – Anleitung für Übersetzer

Diese Anleitung erklärt, wie du ein GitHub-Issue ausfüllst,
damit deine Übersetzung korrekt im Dashboard angezeigt wird.

---

## Was zeigt das Dashboard?

Das Dashboard zeigt alle Übersetzungen von learn.wordpress.org.
Jede Zeile entspricht einem GitHub-Issue. Du siehst auf einen Blick:

- Den Titel des Originals und der Übersetzung (mit Link)
- Den Fortschritt pro Komponente (Text, Untertitel, Quiz usw.)
- Wer die Übersetzung erstellt und wer sie geprüft hat
- Den Gesamtfortschritt als Balken

Die Übersetzungen sind nach Lernpfad → Kurs → Abschnitt
gruppiert, genau wie auf learn.wordpress.org.

---

## Was musst du im GitHub-Issue ausfüllen?

Jedes Issue hat zwei Teile:

1. **Die vorgegebene WordPress-Vorlage** — nicht verändern
2. **Den Translation-Tracker-Block** — dieser Teil steuert das Dashboard

### Der Translation-Tracker-Block

Füge diesen Block in dein Issue ein (nach dem Abschnitt «# Details»):

```markdown
<!-- Training Translation Tracker: fill in the fields below to display this lesson in the dashboard. -->

<!-- Short title of the original content (without "Learn WordPress:" prefix) -->
<!-- Example: Original title: What is WordPress -->
- Original title: 

<!-- Fill in once the translation is published on learn.wordpress.org -->
<!-- Example: https://learn.wordpress.org/lesson/what-is-wordpress/ -->
- Link to translated content: 

<!-- Short title of the translated content -->
<!-- Example: Translation title: Was ist WordPress -->
- Translation title: 

<!-- Fill in once available the URL on WordPress.tv -->
<!-- Example: https://wordpress.tv/2099/12/31/what-is-wordpress/ -->
- Link to WordPress.tv recording: 

<!-- Fill in once available -->
<!-- Example: https://www.youtube.com/watch?v=XyzxYzxyZ -->
- Link to YouTube recording: 

<!-- Optional. Order within the course section (number). Omit for lesson plans / tutorials. -->
- Order: 

## Progress of the translation

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

---

## Die Felder im Überblick

| Feld | Was eintragen | Wann |
| ---- | ------------- | ---- |
| `Original title` | Kurztitel des Originals, ohne «Learn WordPress:» | Beim Erstellen |
| `Link to translated content` | URL der Übersetzung auf learn.wordpress.org | Sobald veröffentlicht |
| `Translation title` | Kurztitel der Übersetzung | Sobald veröffentlicht |
| `Link to WordPress.tv recording` | URL des Recordings auf wordpress.tv | Sobald verfügbar |
| `Link to YouTube recording` | URL des Videos auf YouTube | Sobald verfügbar |
| `Order` | Zahl für die Reihenfolge im Kursabschnitt (z.B. `10`) | Optional |
| Statustabelle | Status pro Komponente | Laufend aktualisieren |

> Nicht ausgefüllte Felder einfach leer lassen — sie können später
> ergänzt werden.

---

## Die Statustabelle

Die Tabelle zeigt den Fortschritt pro Komponente.

| Status | Bedeutung |
| ------ | --------- |
| `open` | Noch nicht begonnen |
| `wip` | In Arbeit |
| `review` | Fertig, wartet auf Prüfung |
| `done` | Abgeschlossen |
| `na` | Nicht zutreffend (wird nicht gezählt) |

**Creator** und **Reviewer** sind GitHub-Usernamen **ohne** `@`.

### Beispiel einer ausgefüllten Tabelle

```markdown
| Component  | Status | Creator  | Reviewer |
| ---------- | ------ | -------- | -------- |
| thumbnails | done   | rfluethi | Ursha-wp |
| text       | done   | rfluethi | Ursha-wp |
| subtitles  | wip    | rfluethi |          |
| exercise   | na     |          |          |
| quiz       | open   |          |          |
| audio      | open   |          |          |
| video      | open   |          |          |
```

---

## Komponenten erklärt

| Komponente | Was ist gemeint |
| ---------- | --------------- |
| `thumbnails` | Vorschaubild |
| `text` | Haupttext / Inhalt |
| `subtitles` | Untertitel des Videos |
| `exercise` | Übungsaufgaben |
| `quiz` | Quiz-Fragen |
| `audio` | Audio-Aufnahme |
| `video` | Video |

---

## Häufige Fragen

**Die Übersetzung erscheint unter «Other» statt unter dem Kurs.**
Das passiert, wenn `Link to original content` in der WordPress-Vorlage
fehlt oder die URL nicht mit `https://learn.wordpress.org/` beginnt.

**Mein Name erscheint nicht im Dashboard.**
Prüfe, ob der GitHub-Username ohne `@` eingetragen ist und korrekt
geschrieben ist.

**Ich habe den Status aktualisiert, aber das Dashboard zeigt noch
den alten Wert.**
Das Dashboard wird automatisch nach dem konfigurierten Intervall
aktualisiert (Standard: 4 Stunden). Der Administrator kann den Cache
unter **Settings → Training Translation Tracker → Clear Cache** sofort leeren.

**Der Link zur Übersetzung wird nicht angezeigt.**
Fülle `Link to translated content` aus, sobald die Übersetzung auf
learn.wordpress.org veröffentlicht ist.

---

## Vollständiges Beispiel

```markdown
<!--
The steps to translating content on Learn WordPress can be found at
https://make.wordpress.org/training/handbook/content-localization/.
-->

# Details
- Link to original content: https://learn.wordpress.org/lesson/what-is-wordpress/
- Link to original content's GitHub issue (optional):
- Language you'll be translating to: German
- Have you arranged for someone to review this translation?: Yes
- Reviewer's GitHub username: Ursha-wp
- Other info:

# Next Steps
Once translated, please link or upload your translated files in a
comment on this issue, and request a translation review.

<!-- Training Translation Tracker: fill in the fields below to display this lesson in the dashboard. -->

<!-- Short title of the original content (without "Learn WordPress:" prefix) -->
- Original title: What is WordPress

<!-- Fill in once the translation is published on learn.wordpress.org -->
- Link to translated content: https://learn.wordpress.org/lesson/TRANSLATION-SLUG/

<!-- Short title of the translated content -->
- Translation title: Was ist WordPress

<!-- Fill in once available -->
- Link to WordPress.tv recording: https://wordpress.tv/2025/01/15/was-ist-wordpress/

<!-- Fill in once available -->
- Link to YouTube recording: https://www.youtube.com/watch?v=EXAMPLE

<!-- Optional. Order within the course section (number). Omit for lesson plans / tutorials. -->
- Order: 1

<!-- TRANSLATION-STATUS-START -->
<!-- Status values: open | wip | review | done | na. Creator and Reviewer: GitHub username without @. -->
| Component  | Status | Creator  | Reviewer |
| ---------- | ------ | -------- | -------- |
| thumbnails | done   | rfluethi | Ursha-wp |
| text       | done   | rfluethi | Ursha-wp |
| subtitles  | done   | rfluethi | Ursha-wp |
| exercise   | na     |          |          |
| quiz       | done   | rfluethi | Ursha-wp |
| audio      | na     |          |          |
| video      | done   | rfluethi | Ursha-wp |
<!-- TRANSLATION-STATUS-END -->
```
