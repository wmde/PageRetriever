# Page Retriever

[![Build Status](https://secure.travis-ci.org/wmde/PageRetriever.png?branch=master)](http://travis-ci.org/wmde/PageRetriever)
[![Latest Stable Version](https://poser.pugx.org/wmde/page-retriever/version.png)](https://packagist.org/packages/wmde/page-retriever)
[![Download count](https://poser.pugx.org/wmde/page-retriever/d/total.png)](https://packagist.org/packages/wmde/page-retriever)

Interface and implementations for fetching MediaWiki page content.

## Release notes

### 1.1.0 (2016-12-14)

* Added `MODE_RAW_EXPANDED` mode to `ApiBasedPageRetriever`.

### 1.0.0 (2016-10-18)

* Initial release with
	* `PageRetriever` interface
	* `ApiBasedPageRetriever` implementation that uses the MediaWiki API
	* `LocalFilePageRetriever` implementation that uses (typically local) files
	* `CachingPageRetriever` implementation that is a caching decorator

