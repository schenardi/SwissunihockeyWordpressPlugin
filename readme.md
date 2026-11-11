# Wordpress Plugin zur Nutzung der Web Components von Swissunihockey

Bindet die [Swissunihockey Web Components](https://github.com/swissunihockey/swissunihockey-webcomponents/tree/master) per Shortcode ein. JS/CSS werden extern (jsDelivr) geladen; URLs unter Einstellungen > Swissunihockey änderbar.

Für die Nutzung ist eine [Registrierung](https://www.swissunihockey.ch/de/administration/services/webmaster/) bei Swissunihockey Pflicht. 

## Installation

1. Ordner swissunihockey in den plugins Ordner von Wordpress kopieren (/wp-content/plugins/)
2. Wordpress-Admin: Plugin aktivieren
3. Unter Einstellungen > Swissunihockey kann die Standardsaison übersteuert werden.
4. Web Component gemäss [Doku von Swissunihockey](https://github.com/swissunihockey/swissunihockey-webcomponents) per Shortcode einbinden, zum Beispiel `[swissunihockey component="uniho-ranking" league="3" game_class="21" group="Gruppe 1"]`. Das `component`-Attribut enthält den Namen der Web Component; weitere Attribute werden an diese weitergegeben. Die Season kann weggelassen werden, falls die konfigurierte genutzt werden soll.

## Nutzungsbedingungen

Bitte immer die Vorgaben von Swissunihockey beachten. [Registrierung](https://www.swissunihockey.ch/de/administration/services/webmaster/) ist obligatorisch.
Dieses Plugin kann von allen Vereinen frei genutzt werden. 
Feedbacks oder auch Pullrequest gerne via Github.

Erstellt mit KI - ich bin kein PHP Entwickler. Getestet mit Wordpress 7.

Ich bin Markus Schenardi. Selbständiger Berater für Geo-IT mit [Schenardi Consulting](https://schenardi.github.io/), Leiter [Vereinsbüro](https://vereinsbuero.ch) (die Geschäftsstelle für Vereine), Präsident von [Floorball Zurich Lioness](https://lioness.ch).