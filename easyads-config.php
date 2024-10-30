<?php
	require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'easyads-shared-config.php';

	// MARKTFEED_LINK_URL
	// Endpoint for the call to link to Marktfeed
	define('MARKTFEED_LINK_URL', 'http://www.marktfeed.nl/link/woocommerce');
	
	// MARKTFEED_UNLINK_URL
	// Endpoint for the call to unlink from Marktfeed
	define('MARKTFEED_UNLINK_URL', 'http://www.marktfeed.nl/unlink/woocommerce');
	
	
	
	// MARKTFEED_HEADER_TEXT
	// Text to be displayed in the header of the plugin
	define('MARKTFEED_HEADER_TEXT', __('Publish, manage, analyze your ads and sell your products on <a href="http://www.marktplaats.nl" style="color:white" target="_blank">www.marktplaats.nl</a>. Marktfeed is an easy to use single channel tool for shop owners and marketing professionals who like to advertise their products on Marktplaats.nl, is the biggest marketplace in The Netherlands. Try Marktfeed <strong>30 days for free!</strong>','marktfeed'));