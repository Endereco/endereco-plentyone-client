# EnderecoPlentyOneClient

## Version 1.0.0 - 2026-09-24

### Added

* Erstveröffentlichung.
* Internationale Adressprüfung für Auftragsadressen.
* Automatische Übernahme sicherer, eindeutiger Korrekturen an Straße,
  Ort, Postleitzahl, Zusatzzeile und Bundesland.
* Automatische Auftragsstatus-Umstellung nach der Prüfung auf einen
  konfigurierbaren Zielstatus.
* Tags am Auftrag, an denen sich das Prüfergebnis in der
  Auftragsübersicht ablesen lässt.
* Kommentar am Auftrag mit dem Ergebnis der Prüfung im Klartext,
  optional abschaltbar.
* Vollständiges Prüfergebnis in den Auftragseigenschaften, auch als
  Rückweg, um eine Korrektur zurückzunehmen.
* Ein Auftrag kann automatisch bei Eingang zur Prüfung vorgemerkt
  werden.
* Alternativ per Flow-Baustein, mit dem du selbst bestimmst, wann
  geprüft wird, etwa erst nach Zahlungseingang oder um einen bereits
  geprüften Auftrag gezielt erneut prüfen zu lassen.

### TODO

* Plugin-Set nach der Installation zweimal bereitstellen - erst beim
  zweiten Durchgang legt PlentyONE die Tabelle und Auftragseigenschaften
  des Plugins an.
* Im Konfigurationstab „endereco-Schnittstelle" den eigenen API-Schlüssel
  eintragen und speichern.
* Prüfen, ob das Plugin-Set mit einem Mandanten verknüpft ist - ohne
  diese Verknüpfung läuft kein Plugin-Code.
