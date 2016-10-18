<?php


namespace WMDE\PageRetriever\Tests;

use Mediawiki\Api\ApiUser;
use Mediawiki\Api\MediawikiApi;
use Mediawiki\Api\SimpleRequest;
use Mediawiki\Api\UsageException;
use WMDE\PageRetriever\ApiBasedPageRetriever;
use WMDE\PsrLogTestDoubles\LoggerSpy;

/**
 * @covers WMDE\PageRetriever\ApiBasedPageRetriever
 *
 * @licence GNU GPL v2+
 * @author Gabriel Birke < gabriel.birke@wikimedia.de >
 */
class ApiBasedPageRetrieverTest extends \PHPUnit_Framework_TestCase {

	const PAGE_PREFIX = 'Web:SpendenseiteTestSkin/';

	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject|MediawikiApi
	 */
	private $api;
	private $apiUser;

	/**
	 * @var LoggerSpy
	 */
	private $logger;

	/**
	 * @var ApiBasedPageRetriever
	 */
	private $pageRetriever;

	public function setUp() {
		$this->api = $this->getMockBuilder( MediawikiApi::class )->disableOriginalConstructor()->getMock();
		$this->apiUser = $this->getMockBuilder( ApiUser::class )->disableOriginalConstructor()->getMock();
		$this->logger = new LoggerSpy();
		$this->pageRetriever = new ApiBasedPageRetriever( $this->api, $this->apiUser, $this->logger, self::PAGE_PREFIX );
	}

	public function testRetrieverReturnsApiResultInRenderMode() {
		$this->api->method( 'isLoggedin' )->willReturn( true );
		$this->api->method( 'postRequest' )->willReturn( $this->getJsonTestData( 'mwApiUnicornsPage.json' ) );

		$expectedContent = '<p>Pink fluffy unicorns dancing on rainbows</p>';
		$this->assertSame( $expectedContent, $this->pageRetriever->fetchPage( 'Unicorns' ) );
	}

	private function getJsonTestData( string $fileName ) {
		return json_decode(
			file_get_contents( __DIR__ . '/data/' . $fileName ),
			true
		);
	}

	public function testRetrieverReturnsApiResultInRawMode() {
		$this->api->method( 'isLoggedin' )->willReturn( true );
		$this->api->method( 'postRequest' )->willReturn( $this->getJsonTestData( 'mwApiNo_CatsQuery.json' ) );

		$expectedContent = "Nyan\nGarfield\nFelix da House";
		$pageName = 'Web:Spendenseite-HK2013/test/No Cats';

		$this->pageRetriever = new ApiBasedPageRetriever(
			$this->api,
			$this->apiUser,
			$this->logger,
			self::PAGE_PREFIX,
			ApiBasedPageRetriever::MODE_RAW
		);

		$this->assertSame( $expectedContent, $this->pageRetriever->fetchPage( $pageName ) );
	}

	/**
	 * The Mediawiki API does a json_decode which will return null if the request is not valid JSON
	 */
	public function testGivenApiReturnsNull_failureIsLogged() {
		$this->api->method( 'isLoggedin' )->willReturn( true );
		$this->api->method( 'postRequest' )->willReturn( null );

		$this->pageRetriever->fetchPage( 'test page' );

		$expectedLogMessage = 'Failed fetching page via MW API';
		$this->assertCalledWithMessage( $expectedLogMessage );
	}

	public function assertCalledWithMessage( string $expectedMessage ) {
		$this->assertContains(
			$expectedMessage,
			array_map(
				function( array $logCall ) {
					return $logCall['message'];
				},
				$this->logger->getLogCalls()
			),
			'Should be called with expected message'
		);
	}

	public function testGivenApiThrowsUsageException_failureIsLogged() {
		$this->api->method( 'isLoggedin' )->willReturn( true );
		$this->api->method( 'postRequest' )->will( $this->throwException( new UsageException() ) );

		$this->pageRetriever->fetchPage( 'test page' );

		$expectedLogMessage = 'Failed fetching page via MW API';
		$this->assertCalledWithMessage( $expectedLogMessage );
	}

	public function testMediaWikiPerformanceCommentsAreRemoved() {
		$this->api->method( 'isLoggedin' )->willReturn( true );
		$this->api->method( 'postRequest' )->willReturn( $this->getJsonTestData( 'mwApiPerformanceCommentPage.json' ) );

		$expectedContent = '<p>Pink fluffy unicorns dancing on rainbows</p>';
		$this->assertSame( $expectedContent, $this->pageRetriever->fetchPage( 'PerformanceComment' ) );
	}

	public function testGivenPagenameWithSpaces_theyAreTrimmedAndReplacedWithUnderscores() {
		$this->api->method( 'isLoggedin' )->willReturn( true );
		$this->api->expects( $this->once() )
			->method( 'postRequest' )
			->with( new SimpleRequest( 'parse', [
				'page' => self::PAGE_PREFIX . 'No_Spaces_Allowed',
				'prop' => 'text'
			] ) );

		$this->pageRetriever->fetchPage( 'No Spaces Allowed ' );
	}

	public function testWhenMultiplePagesAreRetrieved_apiAuthenticatesOnlyForFirstPage() {
		$this->api->method( 'isLoggedin' )->will( $this->onConsecutiveCalls( false, true, true ) );
		$this->api->expects( $this->once() )
			->method( 'login' )
			->with( $this->apiUser );
		$this->api->method( 'postRequest' )->willReturn( $this->getJsonTestData( 'mwApiUnicornsPage.json' ) );

		$this->pageRetriever->fetchPage( 'Unicorns' );
		$this->pageRetriever->fetchPage( 'Lollipops' );
		$this->pageRetriever->fetchPage( 'Rainbows' );
	}

	public function testWhenConstructingWithInvalidFetchMode_exceptionIsThrown() {
		$this->expectException( \InvalidArgumentException::class );

		$this->pageRetriever = new ApiBasedPageRetriever(
			$this->api,
			$this->apiUser,
			$this->logger,
			self::PAGE_PREFIX,
			'~=[,,_,,]:3'
		);
	}

}
