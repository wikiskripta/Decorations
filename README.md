# Decorations

Mediawiki extension.

## Description

* Let's say we encourage users in their work by putting an particular image (with thanks) into their discussion page. 
* This extension shows the lists of honored users at special page.
* Version 1.0

## SpecialPage

_SpecialPage:Decorations_ searches through the discussion pages/user pages/threads of all users for decorations' images and shows the lists of honored ones.


## Installation

* Make sure you have MediaWiki 1.28+ installed.
* Download and place the extension's folder to your /extensions/ folder.
* Add the following code to your LocalSettings.php: 
* Set CRON job for _CRON/update_decorations.php_.
```
wfLoadExtension( 'Decorations' );
```

## Configuration

You have to put following array into the _LocalSettings.php_:
```
$wgDecorationsHome = "pagename"; 
# Wikipage with description of decorations
# Default: "WikiSkripta:Vyznamenání"

$wgDecorations = array(
                    array('decoration_image_name', 'decoration_name', 'decoration_description'),
                    array('decoration_image_name2', 'decoration_name2', 'decoration_description2'),
                    ....
                 );

# Default
$wgDecorationsHome = "WikiSkripta:Vyznamenání";
$wgDecorations = array(
			    	array('Wiki4lístek.png', 'WikiČtyřlístek', 'Vyznamenání udílené obvykle za zvláštní či dlouhodobý přínos WikiSkriptům.'),
                    array('WikiSlunicko.png', 'WikiSluníčko', 'Spíše než o vyznamenání se jedná o pozdrav a vyjádření podpory v další činnosti. V praxi lze udělit uživateli prostě proto, že jste si na něj vzpoměli, a chcete mu třeba popřát hezký den.'),
				    array('Kava.png', 'Řád černé kávy', 'Jedná se o ocenění noční práce redaktora/uživatele. Prostě, když uvidíte někoho editovat ve 2 hodiny ráno, proč ho neocenit za toto nasazení?'),
				    array('Řád_bílého_jednorožce2.png', 'Řád bílého jednorožce', 'Jedná se o ocenění výjimečného přínosu redaktora/uživatele. Když v úžasu stanete nad prací kolegy/ně, neváhejte jim udělením tohoto ocenění sdělit, že vás to ohromilo.')
                );
```


## Internationalization

This extension is available in English and Czech language. For other languages, just edit files in /i18n/ folder.


## Authors and license

* [Josef Martiňák](https://bitbucket.org/josmart/)
* MIT License, Copyright (c) 2018 First Faculty of Medicine, Charles University
