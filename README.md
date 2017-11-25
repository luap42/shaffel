# shaffel 1.0
> **`SHAFFEL`** -- Die freie und liebliche Template-Engine für PHP

## Was ist `shaffel`?
`shaffel` ist eine kostenlose Open-Source Template-Engine für PHP. Diese soll möglichst einfach einzubinden und zu benutzen sein. Um `shaffel` zu benutzen braucht man nur eine moderne PHP-Installation und einen Webserver, über den die Webseite verfügbar sein soll. 

## Wie installiert man `shaffel`?
Die Installation von `shaffel` ist extrem einfach. Dazu lädt man einfach die letzte Version von `shaffel` herunter und kopiert die `engine.php` in das PHP-Bibliotheken-Verzeichnis.

Dann bindet man diese Datei über den PHP-`require`-Befehl ein und ...

**... FERTIG**

## Wie benutzt man `shaffel`?

Um ein Template zu laden, muss mindestens der folgende Boilerplate-Code verwendet werden:

```php
<?php
require "pfad/zur/shaffel.php";
$template = new Engine();
$template->load("pfad/zum/template.shaffel");
$template->show();
?>
```

Dabei sind die beiden Pfade entsprechend zu setzen.

### Template-Syntax
Die Template-Syntax ist relativ einfach. Sie besteht aus verschiedenen 'Template-Expressions', die jeweils in eckige Klammern (`[]`) gesetzt werden.

Es gibt folgende Befehle:


Befehl | Syntax | Erklärung
-------|--------|-----------
Variable ausgeben | ```[$variablenname]```|Gibt die Variable mit dem Namen `variablenname` aus
Variable filtern | ```[$variablenname|filter+filter]```|Gibt die Variable mit dem Namen `variablenname` aus, nachdem sie durch die Filter geleitet wurde
Master-Template-Import|```[@master "filename"]```|Macht das Template in der Datei `filename` (relativ zum aktuellen Template) zum Haupttemplate. **Darf nur einmal pro Datei vorkommen**
Platzhaler erstellen|```[@area "name"]```|Erstellt einen Platzhalter mit dem Namen `name`
Platzhalter füllen|```[@extend "name"]...[/@extend]```|Füllt den Platzhalter `name` mit `...`
||
Bedingungen|```[@if(bedingung1)] ... [@elseif(bedingung2)] ... [@else] ... [/@if]```|Zeigt Text abhängig von den Bedingungen an
||
Schleife über eine Liste|```[@each($liste -> $eintrag)] ... [/@each]```|Wiederholt `...` für jedes Element von `$liste`. Das aktuelle Element ist unter `$eintrag` verfügbar.

Wenn eine Liste in einer Variable gespeichert ist, erhält man ein spezielles Element durch den `.`-Operator. Dabei ist zu beachten, dass Listen ab 0 indiziert werden:
```html
<!--
    Gibt das 2. Element der Liste aus (Index=1)
-->
[$liste.1]
<!--
    Gibt das Element mit dem Index `baum` aus
-->
[$liste.baum]
```


### Dem Template Inalte hinzufügen

Um Inhalte zum Template hinzuzufügen, nutzt man das `Engine`-Objekt. Dieses bietet einem insgesamt zwei Möglichkeiten, die Ausgabe des Templates zu beeinflussen.

#### Variablen setzen
Variablen können mithilfe der Funktion 
```php
$template->set("name", wert);
```
gesetzt werden. Dabei kann `wert` vom Typ `string`, `int` oder `boolean` sein oder aus einem Array bestehen, der nur solche Werte enthält.

Wenn eine bereits vorhandene Variable erneut gesetzt werden soll, wird der ursprüngliche Wert überschrieben.

#### Eigene Filter erstellen
Standardmäßig gibt es nur zwei Filter: `html` zum Entschärfen von HTML und `url` um Werte URL-sicher zu machen.

Diesse Filter können vom Backend-Programmierer überschrieben werden. Dazu muss dieser die eine Kindklasse der `Engine`-Klasse erstellen, in dieser eine `on_init`-Methode definieren. In dieser wird dann der Liste `$this->filters` ein Element mit einem Schlüssel, der dem Namen entspricht, der im Template verwendet werden soll, und einer Funktion, die aufgerufen werden soll, hinzugefügt.

Wollte man zum Beispiel einen `italic`-Filter hinzufügen, der einen Text in Kursivschrift setzt, geht man so vor:

```php
function filter_italic($val) {
    return "<i>".$val."</i>";
}
class CustomEngine extends Engine
{
    protected function on_init() {
        $this->filters["italic"] = 'filter_italic';
    }
}


$page = new CustomEngine();
// ...
```

Diesen Filter würde man mit folgendem Template-Code aufrufen:

```
[$variable|italic]
```

## Features
In der Version 1.0 hat `shaffel` folgende Features:
- Templates laden und ausgeben
- Template-Variablen ausfüllen
- Im Template: über Listen iterieren
- Im Template: If-Elseif-Else-Blöcke
- Master-Template und Platzhalter definieren

## TODO
Natürlich gibt es noch ein paar Erweiterungsmöglichkeiten:
- Template-Plugins (Syntax: `[[pluginname schluessel=wert]]`) einbinden
- Kommentare
- Template-Include (An die aktuelle Position)
- HTML Minifier
...