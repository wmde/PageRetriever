<?php

declare( strict_types = 1 );

namespace WMDE\PageRetriever;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Kai Nissen
 * @author Christoph Fischer
 */
interface PageRetriever {

	/**
	 * Should return an empty string on error.
	 *
	 * @param string $pageName
	 *
	 * @return string
	 */
	public function fetchPage( string $pageName ): string;

}
