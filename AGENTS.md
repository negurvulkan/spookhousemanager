# AGENTS.md
*Guidelines for code-generating agents (Codex) working on the SPOOKHOUSE MANAGER / PHP stack*

## 1. Ziel dieses Dokuments
Dieses Dokument legt fest, **welche Technologien** zu verwenden sind und **wie** neue Features, Seiten, Templates und Datenbankänderungen im Projekt anzulegen sind. Alle Code-Generatoren (z. B. Codex, GPT-Agents) müssen sich daran halten, damit das Projekt konsistent bleibt.

---

## 2. Verwendeter Stack (verbindlich)

- **Backend:** PHP 8.x (prozedural oder modulare Struktur, keine großen Frameworks wie Laravel/Symfony)
- **Templating:** **Smarty** (alle HTML-Ausgaben sollen über Smarty laufen)
- **Frontend:** HTML5, CSS3, **Bootstrap** (bestehende Version im Projekt verwenden), JavaScript (Vanilla oder minimal jQuery, falls bereits vorhanden)
- **Datenbank:** MySQL/MariaDB
- **Rendering-Prinzip:** PHP → Daten sammeln → Smarty-Template befüllen → HTML ausgeben

**WICHTIG:**  
- Keine neuen Frameworks oder Build-Tools einführen (kein React, kein Vue, kein Node-Frontend-Build), sofern nicht ausdrücklich verlangt.  
- CSS möglichst über vorhandene Bootstrap-Klassen und projektspezifische CSS-Dateien lösen.

---

## 3. Projektstruktur (Richtlinie)

Eine typische Seitenanforderung besteht aus:

1. **Controller/Entry-PHP**  
   - Holt Daten aus der DB oder Services  
   - Validiert Request  
   - Weist Variablen dem Smarty-Template zu  
   - Ruft `$smarty->display()` auf

2. **Smarty-Template** (`.tpl`)  
   - Enthält nur Präsentationslogik (Schleifen, Ifs, Ausgaben)  
   - Verwendet Bootstrap für Layout  
   - Keine DB-Aufrufe im Template

3. **Optionale JS/CSS**  
   - In separaten Dateien oder in bestehenden Bundles einhängen  
   - Inline-JS nur für sehr kleine UI-Funktionen

**Beispielstruktur:**
- `/public/` – aufrufbare PHP-Skripte
- `/templates/` – Smarty-Templates
- `/templates/_partials/` – wiederverwendbare Bausteine (Header, Nav, Modals)
- `/lib/` oder `/inc/` – Hilfsfunktionen, DB-Wrapper
- `/updates/` – Update-/Migrationslogik (siehe unten)
- `/setup/` – Setup-Skript

---

## 4. Datenbankänderungen (SEHR WICHTIG)

**Grundregel:**  
> *Keine direkte, manuelle Änderung an der Datenbankstruktur im laufenden Code.*  
> *Jede Änderung muss über ein definierbares Update-/Migrationssystem gehen.*

### 4.1 Update.php
- Es existiert (oder wird erstellt) eine zentrale `update.php` bzw. ein vergleichbares Skript, das **alle DB-Migrationen in Reihenfolge** ausführt.
- Neue Tabellen, neue Spalten, Indexe, Defaults o. Ä. werden **immer** als neuer Schritt hinzugefügt.
- Jeder Schritt muss:
  1. prüfbar sein (existiert die Spalte schon?)
  2. idempotent sein (mehrfaches Ausführen darf nicht zerstören)
  3. geloggt werden können

**Agenten-Aufgabe:**  
Wenn du Code erzeugst, der eine neue Tabelle oder Spalte benötigt, **muss** du:
1. die SQL-Anweisung nennen,
2. sie als neuen Migrations-Schritt formulieren,
3. sie so schreiben, dass sie nur ausgeführt wird, wenn die Änderung noch fehlt.

5. Setup-Skript (Initialinstallation)

Von Anfang an muss ein Setup/Installer vorhanden sein.

Anforderungen an das Setup:

Abfrage der DB-Zugangsdaten (Host, DB-Name, User, Passwort)

Schreiben einer Konfigurationsdatei (z. B. config.php oder .ini)

Ausführen der Basis-Schema-SQLs (Core-Tabellen)

Optional: Erstellen eines ersten Admin-Users

Prüfen der PHP-Version und nötigen Extensions

Prüfen der Schreibrechte für wichtige Verzeichnisse

Agenten-Aufgabe:
Wenn neue Module hinzukommen (z. B. „Portale“, „Geister“, „Räume“), musst du prüfen:

Gehört etwas in das Basisschema (Setup)?

Oder ist es eine Erweiterung (Update/Migration)?

Basis-Tabellen, die für den Start immer gebraucht werden, gehören ins Setup. Optionale Features in die Updates.

6. Smarty-Richtlinien

Templates kommen unter /templates/...

Template-Dateien bekommen sprechende Namen: house_list.tpl, ghost_detail.tpl, portal_editor.tpl

Layout/Design: Erst Bootstrap-Grundlayout, dann projekt-spezifische Klassen

Kein HTML im Controller zurückgeben, stattdessen:

$smarty->assign('ghosts', $ghosts);
$smarty->display('ghosts/list.tpl');

7. Security & Requests

Formulare über POST

CSRF-Schutz falls Projektstandard vorhanden – verwenden

User-Input immer validieren/escapen, vor allem bevor er an Smarty geht oder in SQL landet

DB-Zugriffe über vorhandenen DB-Layer/Wrapper, nicht wild neue PDO-Instanzen anlegen

8. Naming & Style

PHP-Dateien: snake_case oder projektspezifisch beibehalten

Tabellen: sm_-Prefix oder vorhandenen Prefix übernehmen (nicht ändern)

Spaltennamen englisch, kurz, eindeutig: created_at, updated_at, room_id

Kein Mischmasch aus deutsch/englisch in der DB

9. Feature-Workflow für Agenten

Wenn ein Agent ein neues Feature „Rituale verwalten“ bauen soll, dann:

DB prüfen/erweitern

Gibt es eine Tabelle rituals? Wenn nein: Migrations-Skript erstellen.

PHP-Controller anlegen

rituals.php mit Aktionen: list, create, edit, delete

Smarty-Templates anlegen

templates/rituals/list.tpl
templates/rituals/form.tpl

UI in bestehendes Menü einhängen
Über vorhandene Navbar/Sidebar-Template
Falls neue Settings nötig
In Setup oder in eine zentrale Konfigseite einbauen

10. Was Agenten NICHT tun sollen

Keine fremden Frameworks einführen
Keine Inline-SQLs mitten in Templates
Keine DB-Struktur „on the fly“ im Live-Code anlegen
Keine Passwort/Config-Daten hardcoden
Keine separaten Build-Prozesse annehmen (wir bleiben bei PHP/HTML/CSS)

11. Zielbild

Am Ende soll Codex jederzeit:
wissen, welchen Stack es benutzen muss,
neue Features über Smarty andocken,
DB-Änderungen reproduzierbar machen (über update.php/Migrations),
und bei einer frischen Installation alles über das Setup-Skript aufsetzen können.
Dadurch bleibt das Projekt wartbar und mehrere Agenten/Menschen können parallel arbeiten, ohne die Installation jedes Mal manuell reparieren zu müssen.
