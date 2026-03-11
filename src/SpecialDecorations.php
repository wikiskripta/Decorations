<?php
declare(strict_types=1);

namespace MediaWiki\Extension\Decorations\SpecialPage;

use Html;
use MediaWiki\MediaWikiServices;
use MediaWiki\SpecialPage\SpecialPage;

/**
 * Special page: Special:Decorations
 *
 * MediaWiki 1.45+ compatible rewrite.
 */
class SpecialDecorations extends SpecialPage {

	public function __construct() {
		parent::__construct( 'Decorations' );
	}

	public function execute( $subPage ): void {
		$this->setHeaders();
		$out = $this->getOutput();

		$config = $this->getConfig();
		$decorationsHome = (string)$config->get( 'decorationsHome' );
		$decorationsList = (array)$config->get( 'decorationsList' );

		$out->addHTML( Html::rawElement(
			'p',
			[],
			Html::escape( $this->msg( 'decorations-desc' )->text() )
		) );

		$out->addHTML( Html::rawElement(
			'p',
			[],
			Html::escape( $this->msg( 'decorations-home' )->text() ) . ' ' .
			$this->getLinkRenderer()->makeKnownLink(
				$this->getTitleFactory()->newFromText( $decorationsHome ),
				$decorationsHome
			)
		) );

		if ( $decorationsList === [] ) {
			$out->addHTML( Html::rawElement( 'div', [ 'class' => 'errorbox' ],
				Html::escape( 'Decorations list is empty (check configuration).' )
			) );
			$out->addHTML( Html::element( 'div', [ 'class' => 'visualClear' ] ) );
			return;
		}

		$idx = $this->normalizeIndex( $subPage, count( $decorationsList ) );

		$out->addHTML( $this->buildSelectForm( $idx, $decorationsList ) );
		$out->addHTML( Html::element( 'hr' ) );

		$imageName = (string)($decorationsList[$idx][0] ?? '');
		$label = (string)($decorationsList[$idx][1] ?? '');

		if ( $imageName === '' ) {
			$out->addHTML( Html::rawElement( 'div', [ 'class' => 'errorbox' ], 'Invalid configuration.' ) );
			$out->addHTML( Html::element( 'div', [ 'class' => 'visualClear' ] ) );
			return;
		}

		$usageTitles = $this->fetchImageUsageTitles( "File:$imageName" );

		$counts = [];
		foreach ( $usageTitles as $pageTitle ) {
			$user = $this->extractUsername( $pageTitle );
			if ( $user === null ) {
				continue;
			}
			$counts[$user] = ($counts[$user] ?? 0) + 1;
		}
		ksort( $counts, SORT_NATURAL | SORT_FLAG_CASE );

		$out->addHTML( Html::element( 'h2', [], $label ) );
		$out->addHTML( $this->buildResultsTable( $counts ) );
	}

	private function normalizeIndex( $subPage, int $max ): int {
		if ( $subPage === null || $subPage === '' ) {
			return 0;
		}
		if ( !is_string( $subPage ) || !preg_match( '/^\d+$/', $subPage ) ) {
			return 0;
		}
		$i = (int)$subPage;
		return ($i >= 0 && $i < $max) ? $i : 0;
	}

	private function buildSelectForm( int $selected, array $decorationsList ): string {
		$baseUrl = $this->getPageTitle()->getLocalURL();

		$optionsHtml = '';
		foreach ( $decorationsList as $i => $row ) {
			$label = (string)($row[1] ?? (string)$i);
			$optionsHtml .= Html::element(
				'option',
				[
					'value' => (string)$i,
					'selected' => ($i === $selected) ? 'selected' : null
				],
				$label
			);
		}

		$select = Html::rawElement(
			'select',
			[
				'id' => 'decMenu',
				'onchange' => 'location.href=' . json_encode( $baseUrl . '/' ) . '+this.value;'
			],
			$optionsHtml
		);

		return Html::rawElement(
			'form',
			[ 'id' => 'decMenuForm', 'method' => 'get', 'action' => '' ],
			$select
		);
	}

	private function fetchImageUsageTitles( string $fileTitleText ): array {
		$services = MediaWikiServices::getInstance();
		$http = $services->getHttpRequestFactory();

		$apiUrl = wfScript( 'api' ); // relative is fine for server-side HTTP if canonical server is configured

		// Prefer canonical server if set (avoids relative URL issues)
		$mainConfig = $services->getMainConfig();
		$server = (string)( $mainConfig->get( 'CanonicalServer' ) ?: $mainConfig->get( 'Server' ) );
		$fullApiUrl = rtrim( $server, '/' ) . $apiUrl;

		$paramsBase = [
			'action' => 'query',
			'format' => 'json',
			'list' => 'imageusage',
			'iulimit' => '500',
			'iutitle' => $fileTitleText,
		];

		$titles = [];
		$continue = null;

		for ( $guard = 0; $guard < 50; $guard++ ) {
			$params = $paramsBase;
			if ( $continue !== null ) {
				$params['iucontinue'] = $continue;
			}

			$url = $fullApiUrl . '?' . wfArrayToCgi( $params );

			$res = $http->get( $url, [
				'timeout' => 20,
				'connectTimeout' => 10,
				'userAgent' => 'Decorations/1.45 (Special:Decorations)',
			] );

			if ( $res === null || $res === '' ) {
				break;
			}

			$data = json_decode( $res, true );
			if ( !is_array( $data ) ) {
				break;
			}

			$list = $data['query']['imageusage'] ?? [];
			if ( is_array( $list ) ) {
				foreach ( $list as $row ) {
					if ( isset( $row['title'] ) && is_string( $row['title'] ) ) {
						$titles[] = $row['title'];
					}
				}
			}

			$continue = $data['continue']['iucontinue'] ?? null;
			if ( !$continue ) {
				break;
			}
		}

		return $titles;
	}

	private function extractUsername( string $pageTitle ): ?string {
		if ( preg_match(
			'/^(Thread:)?(User:|User talk:|Uživatel:|Uživatelka:|Diskuse s uživatelem:|Diskuse s uživatelem:|Diskuse s uživatelkou:)([^\/]*).*$/u',
			$pageTitle,
			$m
		) ) {
			$user = trim( $m[3] );
			return $user !== '' ? $user : null;
		}
		return null;
	}

	private function buildResultsTable( array $counts ): string {
		$header = Html::rawElement( 'tr', [],
			Html::element( 'th', [], $this->msg( 'decorations-username' )->text() ) .
			Html::element( 'th', [], $this->msg( 'decorations-count' )->text() )
		);

		$rows = '';
		foreach ( $counts as $username => $count ) {
			$userTitle = $this->getTitleFactory()->newFromText( "User:$username" );
			$link = $this->getLinkRenderer()->makeKnownLink( $userTitle, $username );

			$rows .= Html::rawElement( 'tr', [],
				Html::rawElement( 'td', [], $link ) .
				Html::element( 'td', [], (string)$count )
			);
		}

		return Html::rawElement(
			'table',
			[ 'class' => 'wikitable sortable' ],
			$header . $rows
		);
	}
}
