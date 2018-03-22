<?php

/**
 * Default values for Decorations extension.
 * @ingroup Extensions
 * @author Josef Martiňák
 * @license MIT
 * @file
 */

global $wgDecorationsHome, $wgDecorations;
// Default values
if( !isset($wgDecorationsHome) ) $wgDecorationsHome = "WikiSkripta:Vyznamenání";
if( !isset($wgDecorations) ) {
    $wgDecorations = array(
        array('Wiki4lístek.png', 'WikiČtyřlístek', 'Vyznamenání udílené obvykle za zvláštní či dlouhodobý přínos WikiSkriptům.'),
        array('WikiSlunicko.png', 'WikiSluníčko', 'Spíše než o vyznamenání se jedná o pozdrav a vyjádření podpory v další činnosti. V praxi lze udělit uživateli prostě proto, že jste si na něj vzpoměli, a chcete mu třeba popřát hezký den.'),
        array('Kava.png', 'Řád černé kávy', 'Jedná se o ocenění noční práce redaktora/uživatele. Prostě, když uvidíte někoho editovat ve 2 hodiny ráno, proč ho neocenit za toto nasazení?'),
        array('Řád_bílého_jednorožce2.png', 'Řád bílého jednorožce', 'Jedná se o ocenění výjimečného přínosu redaktora/uživatele. Když v úžasu stanete nad prací kolegy/ně, neváhejte jim udělením tohoto ocenění sdělit, že vás to ohromilo.')
    );
}

$wikipath = DecorationsHooks::getWikipath();


?>