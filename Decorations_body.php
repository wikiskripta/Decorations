<?php

/**
 * SpecialPage for Decorations extension
 * @ingroup Extensions
 * @author Josef Martiňák
 */

class Decorations extends SpecialPage {
	function __construct() {
		parent::__construct( 'Decorations' );
	}

	function execute($param) {

		global $wgServer;
		$config = $this->getConfig();
		$decorationsHome = $config->get( 'decorationsHome' );
		$decorationsList = $config->get( 'decorationsList' );

		$this->setHeaders();
		$out = $this->getOutput();
		// display header				
		$out->addWikiTextAsInterface($this->msg( 'decorations-desc' )->plain() . '<br>' . $this->msg( 'decorations-home' )->plain() . "[[" . $decorationsHome . "]]\n");

		// defaults
		$plist = array();
		for($i=0;$i<sizeof($decorationsList);$i++) array_push($plist,$i);
		if ( empty( $param ) || !preg_match( "/^(" . implode('|',$plist) . ")$/", $param ) ) {
			$param = 0;
		}
		$param = (int)$param;

		// Dropdown list for selecting particular list
		$output = "<form id='decMenuForm' name='decMenuForm' method='get' action=''>\n";
		$url = $wgServer . "/index.php?title=Special:Decorations";
		$output .= "<select id='decMenu' onchange='location.href=\"$url/\" +";
		$output .= "this.options[this.selectedIndex].value'>\n";

		for($i=0;$i<sizeof($decorationsList);$i++) {
			$output .= "<option value='$i' ";
			if($i == $param) $output .= "selected='selected'";
			$output .= ">" . $decorationsList[$i][1] . "</option>\n";
		}
		$output .= "</select>\n";
		$output .= "</form>\n";
		$out->addHTML($output . '<br>');

		// Get decoration counts
		$json = json_decode( file_get_contents($wgServer . "/api.php?action=query&format=json&list=imageusage&iulimit=500&iutitle=File:" . $decorationsList[$param][0]), true );
		$json = $json['query']['imageusage'];

		$results = array();
		foreach($json as $item) {
			if( preg_match("/^(Thread:)?(User:|User talk:|Uživatel:|Uživatelka:|Diskuse s uživatelem:|Diskuse s uživatelkou:)([^\/]*).*$/", $item['title'], $m) ) {
				if( array_key_exists($m[3],$results) ) {
					$results[$m[3]]++; 
				}
				else $results[$m[3]] = 1; 
			}
		}

		// Display table with results
		ksort($results);
		$output = "{| class='wikitable sortable'\n";
		$output .= '! ' . $this->msg( 'decorations-username' )->text() . ' !! ';
		$output .= $this->msg( 'decorations-count' )->text() . "\n";
		foreach($results as $key=>$value) {
			$output .= "|-\n";
			$output .= '|[[User:' . $key . '|' . $key . ']] || ' . $value . "\n";
		}
		$output .= "|}\n";
		$out->addWikiTextAsInterface("==" . $decorationsList[$param][1] . "==\n");
		$out->addWikiTextAsInterface($output);
	}		
}