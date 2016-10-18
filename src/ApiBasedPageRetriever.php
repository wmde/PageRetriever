<?php

declare( strict_types = 1 );

namespace WMDE\PageRetriever;

use Mediawiki\Api\ApiUser;
use Mediawiki\Api\MediawikiApi;
use Mediawiki\Api\SimpleRequest;
use Mediawiki\Api\UsageException;
use Psr\Log\LoggerInterface;

/**
 * @licence GNU GPL v2+
 * @author Kai Nissen
 * @author Gabriel Birke < gabriel.birke@wikimedia.de >
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ApiBasedPageRetriever implements PageRetriever {

	/* public */ const MODE_RAW = 'raw';
	/* public */ const MODE_RENDERED = 'render';

	/* private */ const MW_COMMENT_PATTERNS = [
		'/<!--\s*NewPP limit report.*?-->/s' => '',
		'/<!--\s*Transclusion expansion time report.*?-->/s' => '',
		'/<!--\s*Saved in parser cache with key.*?-->/s' => ''
	];

	private $api;
	private $apiUser;
	private $logger;
	private $pageTitlePrefix;
	private $retrievalMode;

	public function __construct( MediawikiApi $api, ApiUser $apiUser, LoggerInterface $logger,
		string $pageTitlePrefix, string $retrievalMode = self::MODE_RENDERED ) {

		$this->api = $api;
		$this->apiUser = $apiUser;
		$this->logger = $logger;
		$this->pageTitlePrefix = $pageTitlePrefix;

		if ( !in_array( $retrievalMode, [ self::MODE_RENDERED, self::MODE_RAW ] ) ) {
			throw new \InvalidArgumentException( 'Invalid value for $retrievalMode' );
		}

		$this->retrievalMode = $retrievalMode;
	}

	/**
	 * @param string $pageTitle
	 * @throws \RuntimeException if the value of $action is not supported
	 * @return string
	 */
	public function fetchPage( string $pageTitle ): string {
		$normalizedPageName = $this->normalizePageName( $this->getPrefixedPageTitle( $pageTitle ) );

		$this->logger->info( 'Fetching page via MW API', [ 'normalizedPageName' => $normalizedPageName ] );

		if ( !$this->api->isLoggedin() ) {
			$this->doLogin();
		}

		$content = $this->retrieveContent( $normalizedPageName );

		if ( $content === false ) {
			$this->logger->notice(
				'Failed fetching page via MW API',
				[ 'normalizedPageName' => $normalizedPageName ]
			);

			return '';
		}

		return $content;
	}

	private function doLogin() {
		$this->api->login( $this->apiUser );
	}

	/**
	 * @param string $pageTitle
	 * @return string|bool retrieved content or false on error
	 */
	private function retrieveContent( string $pageTitle ) {
		switch ( $this->retrievalMode ) {
			case self::MODE_RAW:
				return $this->retrieveWikiText( $pageTitle );
			case self::MODE_RENDERED:
				return $this->retrieveRenderedPage( $pageTitle );
			default:
				throw new \LogicException( 'Action "' . $this->retrievalMode . '" not supported' );
				break;
		}
	}

	private function retrieveRenderedPage( $pageTitle ) {
		$params = [
			'page' => $pageTitle,
			'prop' => 'text'
		];

		try {
			$response = $this->api->postRequest( new SimpleRequest( 'parse', $params ) );
		} catch ( UsageException $e ) {
			return false;
		}

		if ( !empty( $response['parse']['text']['*'] ) ) {
			return $this->cleanupWikiHtml( $response['parse']['text']['*'] );
		}

		return false;
	}

	private function retrieveWikiText( $pageTitle ) {
		$params = [
			'titles' => $pageTitle,
			'prop' => 'revisions',
			'rvprop' => 'content'
		];

		try {
			$response = $this->api->postRequest( new SimpleRequest( 'query', $params ) );
		} catch ( UsageException $e ) {
			return false;
		}

		if ( !is_array( $response['query']['pages'] ) ) {
			return false;
		}

		$page = reset( $response['query']['pages'] );

		return $page['revisions'][0]['*'];
	}

	private function cleanupWikiHtml( string $text ): string {
		return rtrim(
			preg_replace(
				array_keys( self::MW_COMMENT_PATTERNS ),
				array_values( self::MW_COMMENT_PATTERNS ),
				$text
			)
		);
	}

	private function normalizePageName( string $title ): string {
		return ucfirst( str_replace( ' ', '_', trim( $title ) ) );
	}

	private function getPrefixedPageTitle( string $pageTitle ): string {
		return $this->pageTitlePrefix . $pageTitle;
	}

}
