# Decorations

Mediawiki extension.


## Description

* Let's say we encourage users in their work by putting an particular image (with thanks) into their discussion page. 
* This extension shows the lists of honored users at special page.


## SpecialPage

_SpecialPage:Decorations_ searches through the discussion pages/user pages/threads of all users for decorations' images and shows the lists of honored ones.


## Installation

* Make sure you have MediaWiki 1.29+ installed.
* Download and place the extension to your /extensions/ folder.
* Add the following code to your LocalSettings.php: 
```
wfLoadExtension( 'Decorations' );
```


## Configuration

Edit config section of _extension.json_.
```
// Wiki pagename informing about available wiki decorations
"decorationsHome": "WikiSkripta:Vyznamenání"
// Decorations info (decoration's image placed on wiki, title, description)
"decorationsList":
	[
		["Wiki4lístek.png", "WikiČtyřlístek", "Vyznamenání udílené obvykle za zvláštní či dlouhodobý přínos WikiSkriptům."],
		["WikiSlunicko.png", "WikiSluníčko", "Spíše než o vyznamenání se jedná o pozdrav a vyjádření podpory v další činnosti. V praxi lze udělit uživateli prostě proto, že jste si na něj vzpoměli, a chcete mu třeba popřát hezký den."],
		["Kava.png", "Řád černé kávy", "Jedná se o ocenění noční práce redaktora/uživatele. Prostě, když uvidíte někoho editovat ve 2 hodiny ráno, proč ho neocenit za toto nasazení?"],
		["Řád_bílého_jednorožce2.png", "Řád bílého jednorožce", "Jedná se o ocenění výjimečného přínosu redaktora/uživatele. Když v úžasu stanete nad prací kolegy/ně, neváhejte jim udělením tohoto ocenění sdělit, že vás to ohromilo."]
	]
```


## Internationalization

This extension is available in English and Czech language. For other languages, just edit files in /i18n/ folder.


## RELEASE NOTES

### 1.1

* manifest version 2
* MW 1.29+
* config moved to _extensions.json_


## Authors and license

* [Josef Martiňák](https://www.wikiskripta.eu/w/User:Josmart)
* MIT License, Copyright (c) 2018 First Faculty of Medicine, Charles University
