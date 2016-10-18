<?php

declare( strict_types = 1 );

namespace WMDE\PageRetriever;

use FileFetcher\FileFetcher;
use FileFetcher\FileFetchingException;
use Psr\Log\LoggerInterface;

/**
 * @licence GNU GPL v2+
 * @author Kai Nissen
 */
class LocalFilePageRetriever implements PageRetriever {

	private $logger;
	private $fetcher;

	public function __construct( FileFetcher $fetcher, LoggerInterface $logger ) {
		$this->logger = $logger;
		$this->fetcher = $fetcher;
	}

	public function fetchPage( string $pageName ): string {
		$this->logger->info( 'Fetching local page', [ 'pageName' => $pageName ] );

		try {
			$content = $this->fetcher->fetchFile( $pageName );
		}
		catch ( FileFetchingException $ex ) {
			$this->logger->notice(
				'Failed fetching local page',
				[ 'pageName' => $pageName, 'exception' => $ex->getMessage() ]
			);

			return '';
		}

		return $content;
	}
}
