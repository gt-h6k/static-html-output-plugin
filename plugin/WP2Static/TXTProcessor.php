<?php

class TXTProcessor extends WP2Static {
	public function __construct() {
		$this->loadSettings( array( 'crawling', 'wpenv', 'processing', 'advanced', ) );
	}

	public function processTXT( $txt_document, $page_url ) {
		if ( $txt_document == '' ) {
			return false;
		}
		$this->txt_doc              = $txt_document;
		$this->destination_protocol = $this->getTargetSiteProtocol( $this->settings['baseUrl'] );
		$this->placeholder_url      = $this->destination_protocol . 'PLACEHOLDER.wpsho/';
		$this->rewriteSiteURLsToPlaceholder();

		return true;
	}

	public function getTXT() {
		$processed_txt = $this->txt_doc;
		$processed_txt = $this->detectEscapedSiteURLs( $processed_txt );
		$processed_txt = $this->detectUnchangedURLs( $processed_txt );

		return $processed_txt;
	}

	public function detectEscapedSiteURLs( $processed_txt ) {
		$escaped_site_url = addcslashes( $this->placeholder_url, '/' );
		if ( strpos( $processed_txt, $escaped_site_url ) !== false ) {
			return $this->rewriteEscapedURLs( $processed_txt );
		}

		return $processed_txt;
	}

	public function detectUnchangedURLs( $processed_txt ) {
		$site_url = $this->placeholder_url;
		if ( strpos( $processed_txt, $site_url ) !== false ) {
			return $this->rewriteUnchangedURLs( $processed_txt );
		}

		return $processed_txt;
	}

	public function rewriteUnchangedURLs( $processed_txt ) {
		if ( ! isset( $this->settings['rewrite_rules'] ) ) {
			$this->settings['rewrite_rules'] = '';
		}
		$this->settings['rewrite_rules'] .= PHP_EOL . $this->placeholder_url . ',' . $this->settings['baseUrl'];
		$this->settings['rewrite_rules'] .= PHP_EOL . $this->getProtocolRelativeURL( $this->placeholder_url ) . ',' . $this->getProtocolRelativeURL( $this->settings['baseUrl'] );
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
		$rewritten_source = str_replace( $rewrite_from, $rewrite_to, $processed_txt );

		return $rewritten_source;
	}

	public function rewriteEscapedURLs( $processed_txt ) {
		$processed_txt   = str_replace( '%5C/', '\\/', $processed_txt );
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
		$rewritten_source = str_replace( $rewrite_from, $rewrite_to, $processed_txt );

		return $rewritten_source;
	}

	public function rewriteSiteURLsToPlaceholder() {
		$patterns     = array(
			$this->settings['wp_site_url'],
			$this->getProtocolRelativeURL( $this->settings['wp_site_url'] ),
			$this->getProtocolRelativeURL( rtrim( $this->settings['wp_site_url'], '/' ) ),
			$this->getProtocolRelativeURL( $this->settings['wp_site_url'] . '//' ),
			$this->getProtocolRelativeURL( addcslashes( $this->settings['wp_site_url'], '/' ) ),
		);
		$replacements = array(
			$this->placeholder_url,
			$this->getProtocolRelativeURL( $this->placeholder_url ),
			$this->getProtocolRelativeURL( $this->placeholder_url ),
			$this->getProtocolRelativeURL( $this->placeholder_url . '/' ),
			$this->getProtocolRelativeURL( addcslashes( $this->placeholder_url, '/' ) ),
		);
		if ( $this->destination_protocol === 'https' ) {
			$patterns[]     = str_replace( 'http:', 'https:', $this->settings['wp_site_url'] );
			$replacements[] = $this->placeholder_url;
		}
		$rewritten_source = str_replace( $patterns, $replacements, $this->txt_doc );
		$this->txt_doc    = $rewritten_source;
	}

	public function getTargetSiteProtocol( $url ) {
		$protocol = '//';
		if ( strpos( $url, 'https://' ) !== false ) {
			$protocol = 'https://';
		} elseif ( strpos( $url, 'http://' ) !== false ) {
			$protocol = 'http://';
		} else {
			$protocol = '//';
		}

		return $protocol;
	}

	public function getProtocolRelativeURL( $url ) {
		$this->destination_protocol_relative_url = str_replace( array( 'https:', 'http:', ), array( '', '', ), $url );

		return $this->destination_protocol_relative_url;
	}
}