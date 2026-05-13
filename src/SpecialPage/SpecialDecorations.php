<?php
declare(strict_types=1);

namespace MediaWiki\Extension\Decorations\SpecialPage;

use MediaWiki\Html\Html;
use MediaWiki\MediaWikiServices;
use MediaWiki\SpecialPage\SpecialPage;

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
			$this->msg( 'decorations-desc' )->escaped()
		) );

		$out->addHTML( Html::rawElement(
			'p',
			[],
			$this->msg( 'decorations-home' )->escaped() . ' ' .
			$this->getLinkRenderer()->makeKnownLink(
				MediaWikiServices::getInstance()->getTitleFactory()->newFromText( $decorationsHome ),
				$decorationsHome
			)
		) );

		if ( $decorationsList === [] ) {
			$out->addHTML( Html::rawElement(
				'div',
				[ 'class' => 'errorbox' ],
				htmlspecialchars( 'Decorations list is empty (check configuration).', ENT_QUOTES )
			) );
			$out->addHTML( Html::element( 'div', [ 'class' => 'visualClear' ] ) );
			return;
		}

		$idx = $this->normalizeIndex( $subPage, count( $decorationsList ) );
		$out->addHTML( $this->buildSelectForm( $idx, $decorationsList ) );
		$out->addHTML( Html::element( 'hr' ) );

		$imageName = trim( (string)( $decorationsList[$idx][0] ?? '' ) );
		$label = (string)( $decorationsList[$idx][1] ?? '' );

		$imageTitle = $this->makeImageTitle( $imageName );
		if ( $imageTitle === null ) {
			$out->addHTML( Html::rawElement(
				'div',
				[ 'class' => 'errorbox' ],
				Html::element( 'strong', [], 'Invalid image title.' ) . ' ' .
				Html::element( 'span', [], 'Configured value: ' . $imageName )
			) );
			$out->addHTML( Html::element( 'div', [ 'class' => 'visualClear' ] ) );
			return;
		}

		$usagePages = $this->fetchImageUsagePages( $imageTitle->getPrefixedText() );

		$counts = [];
		foreach ( $usagePages as $page ) {
			$titleText = (string)( $page['title'] ?? '' );
			$content = (string)( $page['content'] ?? '' );

			$user = $this->extractUsername( $titleText );
			if ( $user === null || $content === '' ) {
				continue;
			}

			$count = $this->countImageOccurrences( $content, $imageTitle );
			if ( $count <= 0 ) {
				continue;
			}

			$counts[$user] = ( $counts[$user] ?? 0 ) + $count;
		}
		ksort( $counts, SORT_NATURAL | SORT_FLAG_CASE );

		$out->addHTML( Html::element( 'h2', [], $label ) );
		$out->addHTML( $this->buildResultsTable( $counts ) );
	}


	/**
	 * Builds a valid file title from configuration. The value may be written either
	 * as a bare file name (Wiki4lístek.png) or with a file namespace prefix
	 * (File:Wiki4lístek.png / Soubor:Wiki4lístek.png).
	 */
	private function makeImageTitle( string $configuredName ) {
		$configuredName = trim( $configuredName );
		if ( $configuredName === '' ) {
			return null;
		}

		$titleFactory = MediaWikiServices::getInstance()->getTitleFactory();

		// First try the value exactly as configured, using NS_FILE as default only
		// when the value has no namespace prefix. This avoids creating invalid
		// titles such as File:Soubor:Example.png.
		$title = $titleFactory->newFromText( $configuredName, NS_FILE );
		if ( $title !== null && $title->getNamespace() === NS_FILE ) {
			return $title;
		}

		// Fallback for installations where localized/canonical aliases were not
		// recognized in this context: strip a known file namespace manually.
		$prefixes = $this->getFileNamespacePrefixes();
		foreach ( $prefixes as $prefix ) {
			$prefix = trim( (string)$prefix );
			if ( $prefix === '' ) {
				continue;
			}
			$pattern = '/^' . preg_quote( $prefix, '/' ) . '\s*:\s*/iu';
			if ( preg_match( $pattern, $configuredName ) ) {
				$bareName = trim( preg_replace( $pattern, '', $configuredName, 1 ) );
				$title = $titleFactory->newFromText( $bareName, NS_FILE );
				if ( $title !== null && $title->getNamespace() === NS_FILE ) {
					return $title;
				}
			}
		}

		return null;
	}

	private function normalizeIndex( $subPage, int $max ): int {
		if ( $subPage === null || $subPage === '' ) {
			return 0;
		}
		if ( !is_string( $subPage ) || !preg_match( '/^\d+$/', $subPage ) ) {
			return 0;
		}
		$i = (int)$subPage;
		return ( $i >= 0 && $i < $max ) ? $i : 0;
	}

	private function buildSelectForm( int $selected, array $decorationsList ): string {
		$baseUrl = $this->getPageTitle()->getLocalURL();

		$optionsHtml = '';
		foreach ( $decorationsList as $i => $row ) {
			$label = (string)( $row[1] ?? (string)$i );
			$optionsHtml .= Html::element(
				'option',
				[
					'value' => (string)$i,
					'selected' => ( $i === $selected ) ? 'selected' : null,
				],
				$label
			);
		}

		$select = Html::rawElement(
			'select',
			[
				'id' => 'decMenu',
				'onchange' => 'location.href=' . json_encode( $baseUrl . '/' ) . '+this.value;',
			],
			$optionsHtml
		);

		return Html::rawElement(
			'form',
			[ 'id' => 'decMenuForm', 'method' => 'get', 'action' => '' ],
			$select
		);
	}

	/**
	 * Returns pages using the selected file together with their current wikitext.
	 * The previous implementation used list=imageusage only, which can only tell
	 * that a page uses the image. It cannot tell how many times the image appears
	 * on that page.
	 */
	private function fetchImageUsagePages( string $fileTitleText ): array {
		$services = MediaWikiServices::getInstance();
		$http = $services->getHttpRequestFactory();

		$mainConfig = $services->getMainConfig();
		$server = (string)( $mainConfig->get( 'CanonicalServer' ) ?: $mainConfig->get( 'Server' ) );
		$apiPath = wfScript( 'api' );
		$apiUrl = rtrim( $server, '/' ) . $apiPath;

		$paramsBase = [
			'action' => 'query',
			'format' => 'json',
			'formatversion' => '2',
			'generator' => 'imageusage',
			'giulimit' => '500',
			'giutitle' => $fileTitleText,
			'prop' => 'revisions',
			'rvprop' => 'content',
			'rvslots' => 'main',
		];

		$pages = [];
		$continueParams = [];

		for ( $guard = 0; $guard < 100; $guard++ ) {
			$params = $paramsBase + $continueParams;
			$url = $apiUrl . '?' . wfArrayToCgi( $params );

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

			$queryPages = $data['query']['pages'] ?? [];
			if ( is_array( $queryPages ) ) {
				foreach ( $queryPages as $page ) {
					if ( !is_array( $page ) || !isset( $page['title'] ) ) {
						continue;
					}

					$content = '';
					$revision = $page['revisions'][0] ?? null;
					if ( is_array( $revision ) ) {
						$content = (string)( $revision['slots']['main']['content'] ?? $revision['content'] ?? '' );
					}

					$pages[] = [
						'title' => (string)$page['title'],
						'content' => $content,
					];
				}
			}

			$continue = $data['continue'] ?? null;
			if ( !is_array( $continue ) ) {
				break;
			}

			unset( $continue['continue'] );
			if ( $continue === [] ) {
				break;
			}

			$continueParams = $continue;
		}

		return $pages;
	}

	private function extractUsername( string $pageTitle ): ?string {
		$title = MediaWikiServices::getInstance()
			->getTitleFactory()
			->newFromText( $pageTitle );

		if ( $title === null ) {
			return null;
		}

		$namespace = $title->getNamespace();
		if ( $namespace !== NS_USER && $namespace !== NS_USER_TALK ) {
			return null;
		}

		$dbKey = $title->getDBkey();
		$rootPart = explode( '/', $dbKey, 2 )[0];
		$user = trim( str_replace( '_', ' ', $rootPart ) );

		return $user !== '' ? $user : null;
	}

	private function countImageOccurrences( string $content, $imageTitle ): int {
		$fileDbKey = $imageTitle->getDBkey();
		$fileText = str_replace( '_', '[ _]', preg_quote( $fileDbKey, '~' ) );
		$fileText = str_replace( '\ ', '[ _]', $fileText );

		$namespaceAliases = $this->getFileNamespacePrefixes();
		$count = 0;

		foreach ( $namespaceAliases as $prefix ) {
			$prefixPattern = preg_quote( $prefix, '~' );
			$pattern = '~\[\[\s*' . $prefixPattern . '\s*:\s*' . $fileText . '(?=\s*(?:[\]|#]))~iu';
			$count += preg_match_all( $pattern, $content );
		}

		return $count;
	}

	private function getFileNamespacePrefixes(): array {
		$services = MediaWikiServices::getInstance();
		$language = $services->getContentLanguage();

		$prefixes = [ 'File', 'Image', 'Soubor', 'Obrázek' ];

		$namespaceText = $language->getNsText( NS_FILE );
		if ( $namespaceText !== '' ) {
			$prefixes[] = $namespaceText;
		}

		$namespaceAliases = $services->getNamespaceInfo()->getCanonicalNamespaces();
		if ( isset( $namespaceAliases[NS_FILE] ) && $namespaceAliases[NS_FILE] !== '' ) {
			$prefixes[] = $namespaceAliases[NS_FILE];
		}

		return array_values( array_unique( array_filter( $prefixes, static function ( $prefix ) {
			return is_string( $prefix ) && $prefix !== '';
		} ) ) );
	}

	private function buildResultsTable( array $counts ): string {
		$header = Html::rawElement(
			'tr',
			[],
			Html::element( 'th', [], $this->msg( 'decorations-username' )->text() ) .
			Html::element( 'th', [], $this->msg( 'decorations-count' )->text() )
		);

		$rows = '';
		foreach ( $counts as $username => $count ) {
			$userTitle = MediaWikiServices::getInstance()
				->getTitleFactory()
				->newFromText( 'User:' . $username );
			$link = $this->getLinkRenderer()->makeKnownLink( $userTitle, $username );

			$rows .= Html::rawElement(
				'tr',
				[],
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
