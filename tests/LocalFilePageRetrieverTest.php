<?php

declare( strict_types = 1 );

namespace WMDE\PageRetriever\Tests;

use FileFetcher\FileFetcher;
use Psr\Log\LoggerInterface;
use WMDE\PageRetriever\LocalFilePageRetriever;

/**
 * @covers WMDE\PageRetriever\LocalFilePageRetriever
 *
 * @licence GNU GPL v2+
 * @author Kai Nissen
 */
class LocalFilePageRetrieverTest extends \PHPUnit_Framework_TestCase {

	private function createPageRetriever( $fileName, $expectedReturnValue ) {
		$debugger = $this->createMock( LoggerInterface::class );
		$fetcher = $this->createMock( FileFetcher::class );

		$fetcher->expects( $this->once() )
			->method( 'fetchFile' )
			->with( $fileName )
			->will( $this->returnValue( $expectedReturnValue ) );

		return new LocalFilePageRetriever( $fetcher, $debugger );
	}

	public function testLocalFilePageRetrieverReturnsContent() {
		$pageRetriever = $this->createPageRetriever( 'foo', 'some string' );
		$this->assertSame( 'some string', $pageRetriever->fetchPage( 'foo' ) );
	}

}
