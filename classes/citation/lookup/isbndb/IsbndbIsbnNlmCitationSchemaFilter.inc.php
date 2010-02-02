<?php

/**
 * @file classes/citation/IsbndbIsbnNlmCitationSchemaFilter.inc.php
 *
 * Copyright (c) 2000-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class IsbndbIsbnNlmCitationSchemaFilter
 * @ingroup citation_lookup_isbndb
 *
 * @brief Filter that uses the ISBNdb web service to look up
 *  an ISBN and create a NLM citation description from the result.
 */

// $Id$

import('citation.lookup.isbndb.IsbndbNlmCitationSchemaFilter');

class IsbndbIsbnNlmCitationSchemaFilter extends IsbndbNlmCitationSchemaFilter {
	/*
	 * Constructor
	 */
	function IsbndbIsbnNlmCitationSchemaFilter($apiKey) {
		parent::IsbndbNlmCitationSchemaFilter($apiKey);
	}

	//
	// Implement template methods from Filter
	//
	/**
	 * @see Filter::supports()
	 * @param $input mixed
	 * @return boolean
	 */
	function supports(&$input) {
		return $this->isValidIsbn($input);
	}

	/**
	 * @see Filter::process()
	 * @param $isbn string
	 * @return MetadataDescription a looked up citation description
	 *  or null if the filter fails
	 */
	function &process($isbn) {
		// Instantiate the web service request
		$lookupParams = array(
			'access_key' => $this->getApiKey(),
			'index1' => 'isbn',
			'results' => 'details,authors',
			'value1' => $isbn
		);

		// Call the web service
		$resultDOM =& $this->callWebService(ISBNDB_WEBSERVICE_URL, $lookupParams);

		// Handle web service errors
		if (is_null($resultDOM)) return $resultDOM;

		// Transform and pre-process the web service result
		$metadata =& $this->transformWebServiceResults($resultDOM, dirname(__FILE__).DIRECTORY_SEPARATOR.'isbndb.xsl');

		// Handle transformation errors
		if (is_null($metadata)) return $metadata;

		// Extract place and publisher from the combined entry.
		$metadata['publisher-loc'] = String::regexp_replace('/^(.+):.*/', '\1', $metadata['place-publisher']);
		$metadata['publisher-name'] = String::regexp_replace('/.*:([^,]+),?.*/', '\1', $metadata['place-publisher']);
		unset($metadata['place-publisher']);

		// Reformat the publication date
		$metadata['date'] = String::regexp_replace('/^[^\d{4}]+(\d{4}).*/', '\1', $metadata['date']);

		// Clean non-numerics from ISBN
		$metadata['isbn'] = String::regexp_replace('/[^\dX]*/', '', $isbn);

		// Set the publicationType
		$metadata['[@publication-type]'] = 'book';

		return $this->createNlmCitationDescriptionFromArray($metadata);
	}
}
?>