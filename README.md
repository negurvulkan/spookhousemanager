🕯️ SPOOKHOUSE MANAGER – ein Multiplayer Browsergame
🎭 Grundidee
Der Spieler ist der Hausmeister/die Hausmeisterin eines alten Spukhauses, das von echten Geistern bewohnt wird.
Die Aufgabe: Das Gleichgewicht zwischen Energiefluss, Bewohnern, und dem Wohlbefinden der Geister aufrechterhalten – und verhindern, dass paranormale Katastrophen oder Geisterjäger das Haus ruinieren.

🏚️ SPIELSTRUKTUR
1. Das Spukhaus
Das Haus wird prozedural generiert (Stockwerke, Räume, Wände, Türen, Portale).


Darstellung als 2.5D-Grundrissansicht, ähnlich wie alte Simulationsspiele (z. B. „Theme Hospital“).


Räume haben:


id
floor_id
wall_ids[]
style_id (Tapeten, Böden)
energy_level
temperature, humidity (später optional)
haunting_intensity (Grad der Spukaktivität)
occupants[] (Geister + Lebende)

Wände:

besitzen start_x, start_y, end_x, end_y
Typ A (bestimmt den Typ und das Aussehen der Seite A der Wand)
Typ B (bestimmt den Typ und das Aussehen der Seite B der Wand)
Status (normal, damaged, ripped_open, sealed)
Optional: linked_rooms[]

Vorteil: Das Haus kann dynamisch umgebaut oder durch Ereignisse (Risse, Portalöffnungen, Explosionen, ectoplasmische Blasen) verändert werden.
Räume können einfach rechteckig sein oder kompliziertere Formen haben (L-förmig, T-förmig etc.)


2. Entitäten
👻 Geister
Haben Persönlichkeitstypen (z. B. „Poltergeist“, „Trauerschatten“, „Kindergeist“, „Wächtergeist“)


Attribute:

energy (benötigt Angst oder Portalenergie)
mood (Zufriedenheit)
preferred_activities
visibility (0–100 %, abhängig von Aktivität und Ritualen)

Können sich durch je nach Typ durch Wände bewegen aber immer durch Türen bewegen. Räume können aber gegen Geister zeitweise versiegelt werden.

🧍 Bewohner
Temporär anwesend: z. B. Touristen, Erben, Forscher, Geisterjäger, Medien

Charakter und Verhalten beeinflusst Hausenergie:

Angst = mehr Geisterenergie 😱
Rituale = Portale stabilisieren 🔮
Geisterjäger = Schaden 👎

🔮 Portale

Zufällig auftretende Energiequellen zwischen Dimensionen.
Geben „Portalenergie“, wenn stabilisiert.
Überlastung → Risse im Raumgefüge → neue Räume entstehen (prozedural).

3. Ressourcen
Symbol
Name
Beschreibung
🕯 Ritualgegenstände (Artefakte)
zum Stabilisieren, Bannen oder Beschwören, können auf einem Onlineflohmarkt gekauft werden
😱 Angstenergie
entsteht durch erschreckte Lebende
🔮 Portalenergie
erzeugt durch Portale
💰 Geld
durch „Haustouren“, und Vermietung von Zimmern verdient


🎮 GAMEPLAY LOOP
Tag/Nacht-Zyklus


Tagsüber: Wartung, Ausbau, Rituale planen.
Nachts: Geister aktiv, Bewohner verängstigen, Portale setzen Energie frei.


Hausmanagement


Räume einrichten
Energieflüsse lenken Portale stabilisieren (Rituale durchführen)
Wände umbauen
Zimmer einrichten
Reparaturen durchführen


Ereignisse


Zufällige Besucher (z. B. „Fernsehteam“, „Geisterjäger“, „Medium“)
Paranormale Störungen (Energieüberladung, Dimensionsriss)
Emotionale Ereignisse (ein Geist erinnert sich an sein Leben)


Ziel


Langfristig: Das Haus im Gleichgewicht halten.
Später evtl. Metaprogression: Neue Häuser freischalten, „Geisterfamilien“ managen.


🧱 TECHNISCHES KONZEPT
Backend
PHP 8 + MySQL
Frontend Rendering
HTML Canvas / SVG Hybrid
Canvas für Grundrisse, Texturen, Sprites
SVG / HTML für Overlays (Tooltips, UI)


Rendering-Schichten (Je Stockwerk:


Floorplan (Wände, Böden)
Türen / Furniture / Portale
Entities (Sprites, animiert)
Overlays (Tooltips, Energie-Icons)


Assets: PNG/WebP + Spritesheets
Animationen per JS/Tweening (GSAP oder Vanilla)


Events
Clientseitig (JS) → WebSocket/Polling (optional)
Für asynchrone Ereignisse, z. B. „Geist hat Bewohner erschreckt“ oder „Portal öffnet sich“.

🖼️ UI-VORSCHLAG
+-------------------------------------------------------------+
| [🕯124] [👻84] [😱34] [🔮12] [💰580]             [⏰ Nacht] |
+-------------------------------------------------------------+
|                         Hausansicht                         |
|   ▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒   |
|   ▒  💀 Flur            😨 Bewohnerin (ängstlich)       ▒   |
|   ▒-----------------------------------------------------▒   |
|   ▒ 👻 Poltergeist: "Lust auf Spuk!"                     ▒   |
+-------------------------------------------------------------+
| Menü: Räume | Geister | Rituale | Ereignisse | Archiv       |
+-------------------------------------------------------------+


🌑 Ästhetik & Stil
Farbpalette: Dunkelviolett, Grau, Gold, Türkis, Blutrot


Beleuchtung: sanftes Kerzenlicht-Rendering, leichte Schatten, Parallax-Effekte


UI-Stil: leicht verschnörkelt, gotisch-modern (ähnlich „Don’t Starve“ oder „Darkest Dungeon“)

Was sagst du zu diesem Konzept?
