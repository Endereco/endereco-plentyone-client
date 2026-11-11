# Release Notes für EnderecoPlentyOneClient

## v1.0.1 (2026-11-11)

### Changed

- Die Tags „Address validation pending" und „Address validation failed"
  heißen jetzt „Pending address validation" und „Failed address
  validation". In der Auftragsübersicht werden lange Tag-Namen gekürzt,
  und beide alten Namen wurden zu „Address valid..." - mit dem
  Unterscheidungswort am Anfang bleiben sie auseinanderzuhalten.
- Das Plugin gibt PHP 8.0 als Mindestversion an und wird damit als „PHP 8
  Kompatibilität vom Entwickler geprüft" gekennzeichnet.

### Fixed

- Der veraltete Aufruf `curl_close()` wurde entfernt (deprecated ab PHP
  8.5, seit PHP 8.0 ohne Wirkung). Am Verhalten ändert sich nichts.
- Das Plugin meldet sich beim endereco-Service mit der richtigen Version
  statt mit 0.1.0.

### TODO

- Plugin-Set nach dem Update bereitstellen.
- Die neuen Tags legt das Plugin beim ersten Bedarf selbst an. Die Tags
  mit den alten Namen nimmt es nicht mehr ab: Aufträge, die beim Update
  noch „Address validation pending" tragen, behalten diesen Tag auch nach
  der Prüfung - hier den alten Tag von Hand entfernen.
- Trägt kein Auftrag mehr die alten Tags, können sie gelöscht werden.

## v1.0.0 (2026-09-24)

### Added

- Erstveröffentlichung.
- Internationale Adressprüfung für Auftragsadressen.
- Automatische Übernahme sicherer, eindeutiger Korrekturen an Straße,
  Ort, Postleitzahl, Zusatzzeile und Bundesland.
- Automatische Auftragsstatus-Umstellung nach der Prüfung auf einen
  konfigurierbaren Zielstatus.
- Tags am Auftrag, an denen sich das Prüfergebnis in der
  Auftragsübersicht ablesen lässt.
- Kommentar am Auftrag mit dem Ergebnis der Prüfung im Klartext,
  optional abschaltbar.
- Vollständiges Prüfergebnis in den Auftragseigenschaften, auch als
  Rückweg, um eine Korrektur zurückzunehmen.
- Ein Auftrag kann automatisch bei Eingang zur Prüfung vorgemerkt
  werden.
- Alternativ per Flow-Baustein, mit dem du selbst bestimmst, wann
  geprüft wird, etwa erst nach Zahlungseingang oder um einen bereits
  geprüften Auftrag gezielt erneut prüfen zu lassen.

### TODO

- Plugin-Set nach der Installation zweimal bereitstellen - erst beim
  zweiten Durchgang legt PlentyONE die Tabelle und Auftragseigenschaften
  des Plugins an.
- Im Konfigurationstab „endereco-Schnittstelle" den eigenen API-Schlüssel
  eintragen und speichern.
- Prüfen, ob das Plugin-Set mit einem Mandanten verknüpft ist - ohne
  diese Verknüpfung läuft kein Plugin-Code.
