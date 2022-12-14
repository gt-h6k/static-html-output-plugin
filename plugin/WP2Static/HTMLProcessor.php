<?php

class HTMLProcessor extends WP2Static {
	public function __construct() {
		$this->loadSettings( array( 'github', 'wpenv', 'processing', 'advanced', ) );
		$this->processed_urls = array();
	}

	public function processHTML( $html_document, $page_url ) {
		if ( $html_document == '' ) {
			return false;
		}
		$this->xml_doc              = new DOMDocument();
		$this->destination_protocol = $this->getTargetSiteProtocol( $this->settings['baseUrl'] );
		$this->placeholder_url      = $this->destination_protocol . 'PLACEHOLDER.wpsho/';
		$this->raw_html             = $this->rewriteSiteURLsToPlaceholder( $html_document );
		$this->base_tag_exists      = false;
		require_once dirname( __FILE__ ) . '/../URL2/URL2.php';
		$this->page_url = new Net_url2( $page_url );
		$this->detectIfURLsShouldBeHarvested();
		$this->discovered_urls = array();
		libxml_use_internal_errors( true );
		$this->xml_doc->loadHTML( $this->raw_html );
		libxml_use_internal_errors( false );
		$elements = iterator_to_array( $this->xml_doc->getElementsByTagName( '*' ) );
		foreach ( $elements as $element ) {
			switch ( $element->tagName ) {
				case 'meta':
					$this->processMeta( $element );
					break;
				case 'a':
					$this->processAnchor( $element );
					break;
				case 'img':
					$this->processImage( $element );
					$this->processImageSrcSet( $element );
					break;
				case 'head':
					$this->processHead( $element );
					break;
				case 'link':
					$this->processLink( $element );
					break;
				case 'script':
					$this->processScript( $element );
					break;
			}
		}
		if ( $this->base_tag_exists ) {
			$base_element = $this->xml_doc->getElementsByTagName( 'base' )->item( 0 );
			if ( $this->shouldCreateBaseHREF() ) {
				$base_element->setAttribute( 'href', $this->settings['baseHREF'] );
			} else {
				$base_element->parentNode->removeChild( $base_element );
			}
		} elseif ( $this->shouldCreateBaseHREF() ) {
			$base_element = $this->xml_doc->createElement( 'base' );
			$base_element->setAttribute( 'href', $this->settings['baseHREF'] );
			$head_element = $this->xml_doc->getElementsByTagName( 'head' )->item( 0 );
			if ( $head_element ) {
				$first_head_child = $head_element->firstChild;
				$head_element->insertBefore( $base_element, $first_head_child );
			} else {
				require_once dirname( __FILE__ ) . '/../WP2Static/WsLog.php';
				WsLog::l( 'WARNING: no valid head elemnent to attach base to: ' . $this->page_url );
			}
		}
		$this->stripHTMLComments();
		$this->writeDiscoveredURLs();

		return true;
	}

	public function detectIfURLsShouldBeHarvested() {
		if ( ! defined( 'WP_CLI' ) ) {
			$this->harvest_new_urls = ( $_POST['ajax_action'] === 'crawl_site' );
		} else {
			if ( defined( 'CRAWLING_DISCOVERED' ) ) {
				return;
			} else {
				$this->harvest_new_urls = true;
			}
		}
	}

	public function processLink( $element ) {
		$this->normalizeURL( $element, 'href' );
		$this->removeQueryStringFromInternalLink( $element );
		$this->addDiscoveredURL( $element->getAttribute( 'href' ) );
		$this->rewriteWPPaths( $element );
		$this->rewriteBaseURL( $element );
		$this->convertToRelativeURL( $element );
		$this->convertToOfflineURL( $element );
		if ( isset( $this->settings['removeWPLinks'] ) ) {
			$relative_links_to_rm = array(
				'shortlink',
				'pingback',
				'alternate',
				'EditURI',
				'wlwmanifest',
				'index',
				'profile',
				'prev',
				'next',
				'wlwmanifest',
			);
			$link_rel             = $element->getAttribute( 'rel' );
			if ( in_array( $link_rel, $relative_links_to_rm ) ) {
				$element->parentNode->removeChild( $element );
			} elseif ( strpos( $link_rel, '.w.org' ) !== false ) {
				$element->parentNode->removeChild( $element );
			}
		}
	}

	public function isValidURL( $url ) {
		$url = trim( $url );
		if ( $url == '' ) {
			return false;
		}
		if ( strpos( $url, '.php' ) !== false ) {
			return false;
		}
		if ( strpos( $url, ' ' ) !== false ) {
			return false;
		}
		if ( $url[0] == '#' ) {
			return false;
		}

		return true;
	}

	public function addDiscoveredURL( $url ) {
		$url = strtok( $url, '#' );
		$url = strtok( $url, '?' );
		if ( in_array( $url, $this->processed_urls ) ) {
			return;
		}
		if ( trim( $url ) === '' ) {
			return;
		}
		$this->processed_urls[] = $url;
		if ( isset( $this->harvest_new_urls ) ) {
			if ( ! $this->isValidURL( $url ) ) {
				return;
			}
			if ( $this->isInternalLink( $url ) ) {
				$discovered_url_without_site_url = str_replace( rtrim( $this->placeholder_url, '/' ), '', $url );
				$this->logAction( 'Adding discovered URL: ' . $discovered_url_without_site_url );
				$this->discovered_urls[] = $discovered_url_without_site_url;
			}
		}
	}

	public function processImageSrcSet( $element ) {
		if ( ! $element->hasAttribute( 'srcset' ) ) {
			return;
		}
		$new_src_set   = array();
		$src_set       = $element->getAttribute( 'srcset' );
		$src_set_lines = explode( ',', $src_set );
		foreach ( $src_set_lines as $src_set_line ) {
			$all_pieces = explode( ' ', $src_set_line );
			$pieces     = array_filter( $all_pieces );
			$pieces     = array_values( $pieces );
			$url        = $pieces[0];
			$dimension  = $pieces[1];
			if ( $this->isInternalLink( $url ) ) {
				$url = $this->page_url->resolve( $url );
				$url = strtok( $url, '?' );
				$this->addDiscoveredURL( $url );
				$url = $this->rewriteWPPathsSrcSetURL( $url );
				$url = $this->rewriteBaseURLSrcSetURL( $url );
				$url = $this->convertToRelativeURLSrcSetURL( $url );
				$url = $this->convertToOfflineURLSrcSetURL( $url );
			}
			$new_src_set[] = "{$url} {$dimension}";
		}
		$element->setAttribute( 'srcset', implode( ',', $new_src_set ) );
	}

	public function processImage( $element ) {
		$this->normalizeURL( $element, 'src' );
		$this->removeQueryStringFromInternalLink( $element );
		$this->addDiscoveredURL( $element->getAttribute( 'src' ) );
		$this->rewriteWPPaths( $element );
		$this->rewriteBaseURL( $element );
		$this->convertToRelativeURL( $element );
		$this->convertToOfflineURL( $element );
	}

	public function stripHTMLComments() {
		if ( isset( $this->settings['removeHTMLComments'] ) ) {
			$xpath = new DOMXPath( $this->xml_doc );
			foreach ( $xpath->query( '//comment()' ) as $comment ) {
				$comment->parentNode->removeChild( $comment );
			}
		}
	}

	public function processHead( $element ) {
		$head_elements = iterator_to_array( $element->childNodes );
		foreach ( $head_elements as $node ) {
			if ( $node instanceof DOMComment ) {
				if ( isset( $this->settings['removeConditionalHeadComments'] ) ) {
					$node->parentNode->removeChild( $node );
				}
			} elseif ( isset( $node->tagName ) ) {
				if ( $node->tagName === 'base' ) {
					$this->base_tag_exists = true;
				}
			}
		}
	}

	public function processScript( $element ) {
		$this->normalizeURL( $element, 'src' );
		$this->removeQueryStringFromInternalLink( $element );
		$this->addDiscoveredURL( $element->getAttribute( 'src' ) );
		$this->rewriteWPPaths( $element );
		$this->rewriteBaseURL( $element );
		$this->convertToRelativeURL( $element );
		$this->convertToOfflineURL( $element );
	}

	public function processAnchor( $element ) {
		$url = $element->getAttribute( 'href' );
		if ( $url[0] === '#' ) {
			return;
		}
		if ( substr( $url, 0, 7 ) == 'mailto:' ) {
			return;
		}
		if ( ! $this->isInternalLink( $url ) ) {
			return;
		}
		$this->normalizeURL( $element, 'href' );
		$this->removeQueryStringFromInternalLink( $element );
		$this->addDiscoveredURL( $url );
		$this->rewriteWPPaths( $element );
		$this->rewriteBaseURL( $element );
		$this->convertToRelativeURL( $element );
		$this->convertToOfflineURL( $element );
	}

	public function processMeta( $element ) {
		if ( isset( $this->settings['removeWPMeta'] ) ) {
			$meta_name = $element->getAttribute( 'name' );
			if ( strpos( $meta_name, 'generator' ) !== false ) {
				$element->parentNode->removeChild( $element );

				return;
			}
			if ( strpos( $meta_name, 'robots' ) !== false ) {
				$content = $element->getAttribute( 'content' );
				if ( strpos( $content, 'noindex' ) !== false ) {
					$element->parentNode->removeChild( $element );
				}
			}
		}
		$url = $element->getAttribute( 'content' );
		$this->normalizeURL( $element, 'content' );
		$this->removeQueryStringFromInternalLink( $element );
		$this->addDiscoveredURL( $url );
		$this->rewriteWPPaths( $element );
		$this->rewriteBaseURL( $element );
		$this->convertToRelativeURL( $element );
		$this->convertToOfflineURL( $element );
	}

	public function writeDiscoveredURLs() {
		if ( isset( $_POST['ajax_action'] ) && $_POST['ajax_action'] === 'crawl_again' ) {
			return;
		}
		if ( defined( 'WP_CLI' ) ) {
			if ( defined( 'CRAWLING_DISCOVERED' ) ) {
				return;
			}
		}
		file_put_contents( $this->settings['wp_uploads_path'] . '/WP-STATIC-DISCOVERED-URLS.txt', PHP_EOL . implode( PHP_EOL, array_unique( $this->discovered_urls ) ), FILE_APPEND | LOCK_EX );
		chmod( $this->settings['wp_uploads_path'] . '/WP-STATIC-DISCOVERED-URLS.txt', 0664 );
	}

	public function normalizeURL( $element, $attribute ) {
		$original_link = $element->getAttribute( $attribute );
		if ( $this->isInternalLink( $original_link ) ) {
			$abs = $this->page_url->resolve( $original_link );
			$element->setAttribute( $attribute, $abs );
		}
	}

	public function isInternalLink( $link, $domain = false ) {
		if ( ! $domain ) {
			$domain = $this->placeholder_url;
		}
		$is_internal_link = parse_url( $link, PHP_URL_HOST ) === parse_url( $domain, PHP_URL_HOST );

		return $is_internal_link;
	}

	public function removeQueryStringFromInternalLink( $element ) {
		$attribute_to_change = '';
		$url_to_change       = '';
		if ( $element->hasAttribute( 'href' ) ) {
			$attribute_to_change = 'href';
		} elseif ( $element->hasAttribute( 'src' ) ) {
			$attribute_to_change = 'src';
		} elseif ( $element->hasAttribute( 'content' ) ) {
			$attribute_to_change = 'content';
		} else {
			return;
		}
		$url_to_change = $element->getAttribute( $attribute_to_change );
		if ( $this->isInternalLink( $url_to_change ) ) {
			$element->setAttribute( $attribute_to_change, strtok( $url_to_change, '?' ) );
		}
	}

	public function detectEscapedSiteURLs( $processed_html ) {
		$escaped_site_url = addcslashes( $this->placeholder_url, '/' );
		if ( strpos( $processed_html, $escaped_site_url ) !== false ) {
			return $this->rewriteEscapedURLs( $processed_html );
		}

		return $processed_html;
	}

	public function detectUnchangedPlaceholderURLs( $processed_html ) {
		$placeholder_url = $this->placeholder_url;
		if ( strpos( $processed_html, $placeholder_url ) !== false ) {
			return $this->rewriteUnchangedPlaceholderURLs( $processed_html );
		}

		return $processed_html;
	}

	public function rewriteUnchangedPlaceholderURLs( $processed_html ) {
		if ( ! isset( $this->settings['rewrite_rules'] ) ) {
			$this->settings['rewrite_rules'] = '';
		}
		$placeholder_url                 = rtrim( $this->placeholder_url, '/' );
		$destination_url                 = rtrim( $this->settings['baseUrl'], '/' );
		$this->settings['rewrite_rules'] .= PHP_EOL . $placeholder_url . ',' . $destination_url;
		$rewrite_from                    = array();
		$rewrite_to                      = array();
		$rewrite_rules                   = explode( "\n", str_replace( "\r", '', $this->settings['rewrite_rules'] ) );
		foreach ( $rewrite_rules as $rewrite_rule_line ) {
			if ( $rewrite_rule_line ) {
				list( $from, $to ) = explode( ',', $rewrite_rule_line );
				$rewrite_from[] = $from;
				$rewrite_to[]   = $to;
			}
		}
		$rewritten_source = str_replace( $rewrite_from, $rewrite_to, $processed_html );

		return $rewritten_source;
	}

	public function rewriteEscapedURLs( $processed_html ) {
		$processed_html  = str_replace( '%5C/', '\\/', $processed_html );
		$site_url        = addcslashes( $this->placeholder_url, '/' );
		$destination_url = addcslashes( $this->settings['baseUrl'], '/' );
		if ( ! isset( $this->settings['rewrite_rules'] ) ) {
			$this->settings['rewrite_rules'] = '';
		}
		$this->settings['rewrite_rules'] .= PHP_EOL . $site_url . ',' . $destination_url;
		$rewrite_from                    = array();
		$rewrite_to                      = array();
		$rewrite_rules                   = explode( "\n", str_replace( "\r", '', $this->settings['rewrite_rules'] ) );
		foreach ( $rewrite_rules as $rewrite_rule_line ) {
			if ( $rewrite_rule_line ) {
				list( $from, $to ) = explode( ',', $rewrite_rule_line );
				$rewrite_from[] = addcslashes( $from, '/' );
				$rewrite_to[]   = addcslashes( $to, '/' );
			}
		}
		$rewritten_source = str_replace( $rewrite_from, $rewrite_to, $processed_html );

		return $rewritten_source;
	}

	public function rewriteWPPathsSrcSetURL( $url_to_change ) {
		if ( ! isset( $this->settings['rewrite_rules'] ) ) {
			return $url_to_change;
		}
		$rewrite_from  = array();
		$rewrite_to    = array();
		$rewrite_rules = explode( "\n", str_replace( "\r", '', $this->settings['rewrite_rules'] ) );
		foreach ( $rewrite_rules as $rewrite_rule_line ) {
			list( $from, $to ) = explode( ',', $rewrite_rule_line );
			$rewrite_from[] = $from;
			$rewrite_to[]   = $to;
		}
		$rewritten_url = str_replace( $rewrite_from, $rewrite_to, $url_to_change );

		return $rewritten_url;
	}

	public function rewriteWPPaths( $element ) {
		if ( ! isset( $this->settings['rewrite_rules'] ) ) {
			return;
		}
		$rewrite_from  = array();
		$rewrite_to    = array();
		$rewrite_rules = explode( "\n", str_replace( "\r", '', $this->settings['rewrite_rules'] ) );
		foreach ( $rewrite_rules as $rewrite_rule_line ) {
			list( $from, $to ) = explode( ',', $rewrite_rule_line );
			$rewrite_from[] = $from;
			$rewrite_to[]   = $to;
		}
		$attribute_to_change = '';
		$url_to_change       = '';
		if ( $element->hasAttribute( 'href' ) ) {
			$attribute_to_change = 'href';
		} elseif ( $element->hasAttribute( 'src' ) ) {
			$attribute_to_change = 'src';
		} elseif ( $element->hasAttribute( 'content' ) ) {
			$attribute_to_change = 'content';
		} else {
			return;
		}
		$url_to_change = $element->getAttribute( $attribute_to_change );
		if ( $this->isInternalLink( $url_to_change ) ) {
			$rewritten_url = str_replace( $rewrite_from, $rewrite_to, $url_to_change );
			$element->setAttribute( $attribute_to_change, $rewritten_url );
		}
	}

	public function getHTML() {
		$processed_html = $this->xml_doc->saveHtml();
		$processed_html = $this->detectEscapedSiteURLs( $processed_html );
		$processed_html = $this->detectUnchangedPlaceholderURLs( $processed_html );
		$processed_html = html_entity_decode( $processed_html, ENT_QUOTES, 'UTF-8' );
		$processed_html = html_entity_decode( $processed_html, ENT_QUOTES, 'UTF-8' );

		return $processed_html;
	}

	public function convertToRelativeURLSrcSetURL( $url_to_change ) {
		if ( ! $this->shouldUseRelativeURLs() ) {
			return $url_to_change;
		}
		$site_root    = '';
		$relative_url = str_replace( $this->settings['baseUrl'], $site_root, $url_to_change );

		return $relative_url;
	}

	public function convertToRelativeURL( $element ) {
		if ( ! $this->shouldUseRelativeURLs() ) {
			return;
		}
		if ( $element->hasAttribute( 'href' ) ) {
			$attribute_to_change = 'href';
		} elseif ( $element->hasAttribute( 'src' ) ) {
			$attribute_to_change = 'src';
		} elseif ( $element->hasAttribute( 'content' ) ) {
			$attribute_to_change = 'content';
		} else {
			return;
		}
		$url_to_change = $element->getAttribute( $attribute_to_change );
		$site_root     = '';
		if ( $this->isInternalLink( $url_to_change, $this->settings['baseUrl'] ) ) {
			$rewritten_url = str_replace( $this->settings['baseUrl'], $site_root, $url_to_change );
			$element->setAttribute( $attribute_to_change, $rewritten_url );
		}
	}

	public function convertToOfflineURLSrcSetURL( $url_to_change ) {
		if ( ! $this->shouldCreateOfflineURLs() ) {
			return $url_to_change;
		}
		$current_page_path_to_root  = '';
		$current_page_path          = parse_url( $this->page_url, PHP_URL_PATH );
		$number_of_segments_in_path = explode( '/', $current_page_path );
		$num_dots_to_root           = count( $number_of_segments_in_path ) - 2;
		for ( $i = 0; $i < $num_dots_to_root; $i ++ ) {
			$current_page_path_to_root .= '../';
		}
		if ( ! $this->isInternalLink( $url_to_change ) ) {
			return false;
		}
		$rewritten_url = str_replace( $this->placeholder_url, '', $url_to_change );
		$offline_url   = $current_page_path_to_root . $rewritten_url;
		if ( substr( $offline_url, - 1 ) === '/' ) {
			$offline_url .= 'index.html';
		}

		return $offline_url;
	}

	public function convertToOfflineURL( $element ) {
		if ( ! $this->shouldCreateOfflineURLs() ) {
			return;
		}
		if ( $element->hasAttribute( 'href' ) ) {
			$attribute_to_change = 'href';
		} elseif ( $element->hasAttribute( 'src' ) ) {
			$attribute_to_change = 'src';
		} elseif ( $element->hasAttribute( 'content' ) ) {
			$attribute_to_change = 'content';
		} else {
			return;
		}
		$url_to_change              = $element->getAttribute( $attribute_to_change );
		$current_page_path_to_root  = '';
		$current_page_path          = parse_url( $this->page_url, PHP_URL_PATH );
		$number_of_segments_in_path = explode( '/', $current_page_path );
		$num_dots_to_root           = count( $number_of_segments_in_path ) - 2;
		for ( $i = 0; $i < $num_dots_to_root; $i ++ ) {
			$current_page_path_to_root .= '../';
		}
		if ( ! $this->isInternalLink( $url_to_change ) ) {
			return false;
		}
		$rewritten_url = str_replace( $this->placeholder_url, '', $url_to_change );
		$offline_url   = $current_page_path_to_root . $rewritten_url;
		if ( substr( $offline_url, - 1 ) === '/' ) {
			$offline_url .= 'index.html';
		}
		$element->setAttribute( $attribute_to_change, $offline_url );
	}

	public function getProtocolRelativeURL( $url ) {
		$this->destination_protocol_relative_url = str_replace( array( 'https:', 'http:', ), array( '', '', ), $url );

		return $this->destination_protocol_relative_url;
	}

	public function rewriteBaseURLSrcSetURL( $url_to_change ) {
		$rewritten_url = str_replace( $this->getBaseURLRewritePatterns(), $this->getBaseURLRewritePatterns(), $url_to_change );

		return $rewritten_url;
	}

	public function rewriteBaseURL( $element ) {
		if ( $element->hasAttribute( 'href' ) ) {
			$attribute_to_change = 'href';
		} elseif ( $element->hasAttribute( 'src' ) ) {
			$attribute_to_change = 'src';
		} elseif ( $element->hasAttribute( 'content' ) ) {
			$attribute_to_change = 'content';
		} else {
			return;
		}
		$url_to_change = $element->getAttribute( $attribute_to_change );
		if ( $this->isInternalLink( $url_to_change ) ) {
			$rewritten_url = str_replace( $this->getBaseURLRewritePatterns(), $this->getBaseURLRewritePatterns(), $url_to_change );
			$element->setAttribute( $attribute_to_change, $rewritten_url );
		}
	}

	public function getTargetSiteProtocol( $url ) {
		$this->destination_protocol = '//';
		if ( strpos( $url, 'https://' ) !== false ) {
			$this->destination_protocol = 'https://';
		} elseif ( strpos( $url, 'http://' ) !== false ) {
			$this->destination_protocol = 'http://';
		} else {
			$this->destination_protocol = '//';
		}

		return $this->destination_protocol;
	}

	public function rewriteSiteURLsToPlaceholder( $raw_html ) {
		$site_url         = rtrim( $this->settings['wp_site_url'], '/' );
		$placeholder_url  = rtrim( $this->placeholder_url, '/' );
		$patterns         = array(
			$site_url,
			addcslashes( $site_url, '/' ),
			$this->getProtocolRelativeURL( $site_url ),
			$this->getProtocolRelativeURL( $site_url . '//' ),
			$this->getProtocolRelativeURL( addcslashes( $site_url, '/' ) ),
		);
		$replacements     = array(
			$placeholder_url,
			addcslashes( $placeholder_url, '/' ),
			$this->getProtocolRelativeURL( $placeholder_url ),
			$this->getProtocolRelativeURL( $placeholder_url . '/' ),
			$this->getProtocolRelativeURL( addcslashes( $placeholder_url, '/' ) ),
		);
		$rewritten_source = str_replace( $patterns, $replacements, $raw_html );

		return $rewritten_source;
	}

	public function shouldUseRelativeURLs() {
		if ( ! isset( $this->settings['useRelativeURLs'] ) ) {
			return false;
		}
		if ( isset( $this->settings['allowOfflineUsage'] ) ) {
			return false;
		}
	}

	public function shouldCreateBaseHREF() {
		if ( empty( $this->settings['baseHREF'] ) ) {
			return false;
		}
		if ( isset( $this->settings['allowOfflineUsage'] ) ) {
			return false;
		}

		return true;
	}

	public function shouldCreateOfflineURLs() {
		if ( ! isset( $this->settings['allowOfflineUsage'] ) ) {
			return false;
		}
		if ( $this->settings['selected_deployment_option'] != 'zip' ) {
			return false;
		}

		return true;
	}

	public function getBaseURLRewritePatterns() {
		$patterns = array(
			$this->placeholder_url,
			addcslashes( $this->placeholder_url, '/' ),
			$this->getProtocolRelativeURL( $this->placeholder_url ),
			$this->getProtocolRelativeURL( $this->placeholder_url ),
			$this->getProtocolRelativeURL( $this->placeholder_url . '/' ),
			$this->getProtocolRelativeURL( addcslashes( $this->placeholder_url, '/' ) ),
		);

		return $patterns;
	}

	public function getBaseURLRewriteReplacements() {
		$replacements = array(
			$this->settings['baseUrl'],
			addcslashes( $this->settings['baseUrl'], '/' ),
			$this->getProtocolRelativeURL( $this->settings['baseUrl'] ),
			$this->getProtocolRelativeURL( rtrim( $this->settings['baseUrl'], '/' ) ),
			$this->getProtocolRelativeURL( $this->settings['baseUrl'] . '//' ),
			$this->getProtocolRelativeURL( addcslashes( $this->settings['baseUrl'], '/' ) ),
		);

		return $replacements;
	}

	public function logAction( $action ) {
		if ( ! isset( $this->settings['debug_mode'] ) ) {
			return;
		}
		require_once dirname( __FILE__ ) . '/../WP2Static/WsLog.php';
		WsLog::l( $action );
	}
}