<?php

class SiteCrawler extends WP2Static {
	public function __construct() {
		$this->loadSettings( array( 'wpenv', 'crawling', 'processing', 'advanced', ) );
		if ( isset( $this->settings['crawl_delay'] ) ) {
			sleep( $this->settings['crawl_delay'] );
		}
		$this->processed_file             = '';
		$this->file_type                  = '';
		$this->response                   = '';
		$this->content_type               = '';
		$this->url                        = '';
		$this->full_url                   = '';
		$this->extension                  = '';
		$this->archive_dir                = '';
		$this->list_of_urls_to_crawl_path = '';
		$this->urls_to_crawl              = '';
		if ( ! defined( 'WP_CLI' ) ) {
			if ( $_POST['ajax_action'] === 'crawl_again' ) {
				$this->crawl_discovered_links();
			} elseif ( $_POST['ajax_action'] === 'crawl_site' ) {
				$this->crawl_site();
			}
		}
	}

	public function generate_discovered_links_list() {
		$second_crawl_file_path  = $this->settings['wp_uploads_path'] . '/WP-STATIC-2ND-CRAWL-LIST.txt';
		$already_crawled         = file( $this->settings['wp_uploads_path'] . '/WP-STATIC-INITIAL-CRAWL-LIST.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
		$unique_discovered_links = array();
		$discovered_links_file   = $this->settings['wp_uploads_path'] . '/WP-STATIC-DISCOVERED-URLS.txt';
		if ( is_file( $discovered_links_file ) ) {
			$discovered_links        = file( $discovered_links_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
			$unique_discovered_links = array_unique( $discovered_links );
			sort( $unique_discovered_links );
		}
		file_put_contents( $this->settings['wp_uploads_path'] . '/WP-STATIC-DISCOVERED-URLS-LOG.txt', implode( PHP_EOL, $unique_discovered_links ) );
		chmod( $this->settings['wp_uploads_path'] . '/WP-STATIC-DISCOVERED-URLS-LOG.txt', 0664 );
		file_put_contents( $this->settings['wp_uploads_path'] . '/WP-STATIC-DISCOVERED-URLS-TOTAL.txt', count( $unique_discovered_links ) );
		chmod( $this->settings['wp_uploads_path'] . '/WP-STATIC-DISCOVERED-URLS-TOTAL.txt', 0664 );
		$discovered_links = array_diff( $unique_discovered_links, $already_crawled );
		file_put_contents( $second_crawl_file_path, implode( PHP_EOL, $discovered_links ) );
		chmod( $second_crawl_file_path, 0664 );
		copy( $second_crawl_file_path, $this->settings['wp_uploads_path'] . '/WP-STATIC-FINAL-2ND-CRAWL-LIST.txt' );
		chmod( $this->settings['wp_uploads_path'] . '/WP-STATIC-FINAL-2ND-CRAWL-LIST.txt', 0664 );
	}

	public function crawl_discovered_links() {
		if ( defined( 'WP_CLI' ) && ! defined( 'CRAWLING_DISCOVERED' ) ) {
			define( 'CRAWLING_DISCOVERED', true );
		}
		$second_crawl_file_path = $this->settings['wp_uploads_path'] . '/WP-STATIC-2ND-CRAWL-LIST.txt';
		if ( ! is_file( $second_crawl_file_path ) ) {
			$this->generate_discovered_links_list();
		}
		$this->list_of_urls_to_crawl_path = $this->settings['wp_uploads_path'] . '/WP-STATIC-FINAL-2ND-CRAWL-LIST.txt';
		if ( ! is_file( $this->list_of_urls_to_crawl_path ) ) {
			require_once dirname( __FILE__ ) . '/../WP2Static/WsLog.php';
			WsLog::l( 'ERROR: LIST OF URLS TO CRAWL NOT FOUND AT: ' . $this->list_of_urls_to_crawl_path );
			die();
		} else {
			if ( filesize( $this->list_of_urls_to_crawl_path ) ) {
				$this->crawlABitMore();
			} else {
				if ( ! defined( 'WP_CLI' ) ) {
					echo 'SUCCESS';
				}
			}
		}
	}

	public function crawl_site() {
		$this->list_of_urls_to_crawl_path = $this->settings['wp_uploads_path'] . '/WP-STATIC-FINAL-2ND-CRAWL-LIST.txt';
		if ( is_file( $this->list_of_urls_to_crawl_path ) ) {
			$this->crawl_discovered_links();

			return;
		}
		$this->list_of_urls_to_crawl_path = $this->settings['wp_uploads_path'] . '/WP-STATIC-FINAL-CRAWL-LIST.txt';
		if ( ! is_file( $this->list_of_urls_to_crawl_path ) ) {
			require_once dirname( __FILE__ ) . '/../WP2Static/WsLog.php';
			WsLog::l( 'ERROR: LIST OF URLS TO CRAWL NOT FOUND AT: ' . $this->list_of_urls_to_crawl_path );
			die();
		} else {
			if ( filesize( $this->list_of_urls_to_crawl_path ) ) {
				$this->crawlABitMore();
			} else {
				if ( ! defined( 'WP_CLI' ) ) {
					echo 'SUCCESS';
				}
			}
		}
	}

	public function crawlABitMore() {
		$batch_of_links_to_crawl = array();
		$this->urls_to_crawl     = file( $this->list_of_urls_to_crawl_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
		$total_links             = count( $this->urls_to_crawl );
		if ( $total_links < 1 ) {
			require_once dirname( __FILE__ ) . '/../WP2Static/WsLog.php';
			WsLog::l( 'ERROR: LIST OF URLS TO CRAWL NOT FOUND AT: ' . $this->list_of_urls_to_crawl_path );
			die();
		}
		if ( $this->settings['crawl_increment'] > $total_links ) {
			$this->settings['crawl_increment'] = $total_links;
		}
		for ( $i = 0; $i < $this->settings['crawl_increment']; $i ++ ) {
			$link_from_crawl_list = array_shift( $this->urls_to_crawl );
			if ( $link_from_crawl_list ) {
				$batch_of_links_to_crawl[] = $link_from_crawl_list;
			}
		}
		$this->remaining_urls_to_crawl = count( $this->urls_to_crawl );
		file_put_contents( $this->list_of_urls_to_crawl_path, implode( "\r\n", $this->urls_to_crawl ) );
		chmod( $this->list_of_urls_to_crawl_path, 0664 );
		$handle            = fopen( $this->settings['wp_uploads_path'] . '/WP2STATIC-CURRENT-ARCHIVE.txt', 'r' );
		$this->archive_dir = stream_get_line( $handle, 0 );
		$total_urls_path   = $this->settings['wp_uploads_path'] . '/WP-STATIC-INITIAL-CRAWL-TOTAL.txt';
		if ( defined( 'CRAWLING_DISCOVERED' ) || ( isset( $_POST['ajax_action'] ) && $_POST['ajax_action'] == 'crawl_again' ) ) {
			$total_urls_path = $this->settings['wp_uploads_path'] . '/WP-STATIC-DISCOVERED-URLS-TOTAL.txt';
		}
		$total_urls_to_crawl = file_get_contents( $total_urls_path );
		$batch_index         = 0;
		$exclusions          = array( 'wp-json' );
		if ( isset( $this->settings['excludeURLs'] ) ) {
			$user_exclusions = explode( "\n", str_replace( "\r", '', $this->settings['excludeURLs'] ) );
			$exclusions      = array_merge( $exclusions, $user_exclusions );
		}
		$this->logAction( 'Exclusion rules ' . implode( PHP_EOL, $exclusions ) );
		foreach ( $batch_of_links_to_crawl as $link_to_crawl ) {
			$this->url      = $link_to_crawl;
			$this->full_url = $this->settings['wp_site_url'] . ltrim( $this->url, '/' );
			foreach ( $exclusions as $exclusion ) {
				$exclusion = trim( $exclusion );
				if ( $exclusion != '' ) {
					if ( false !== strpos( $this->url, $exclusion ) ) {
						$this->logAction( 'Excluding ' . $this->url . ' because of rule ' . $exclusion );
						continue 2;
					}
				}
			}
			$this->file_extension = $this->getExtensionFromURL();
			if ( $this->loadFileForProcessing() ) {
				$this->saveFile();
			}
			$batch_index ++;
			$completed_urls = $total_urls_to_crawl - $this->remaining_urls_to_crawl - count( $batch_of_links_to_crawl ) + $batch_index;
			require_once dirname( __FILE__ ) . '/../WP2Static/ProgressLog.php';
			ProgressLog::l( $completed_urls, $total_urls_to_crawl );
			$this->logAction( 'Memory allocated by crawl script: ' . round( memory_get_usage( true ) / 1024 ) );
		}
		$this->checkIfMoreCrawlingNeeded();
		$url_reponse = null;
		unset( $url_reponse );
	}

	public function loadFileForProcessing() {
		$this->logAction( "Crawling {$this->url}" );
		$ch = curl_init();
		if ( isset( $this->settings['crawlPort'] ) ) {
			curl_setopt( $ch, CURLOPT_PORT, $this->settings['crawlPort'] );
		}
		curl_setopt( $ch, CURLOPT_URL, $this->full_url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
		curl_setopt( $ch, CURLOPT_USERAGENT, 'WP2Static.com' );
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 0 );
		curl_setopt( $ch, CURLOPT_TIMEOUT, 600 );
		curl_setopt( $ch, CURLOPT_HEADER, 0 );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
		if ( isset( $this->settings['useBasicAuth'] ) ) {
			curl_setopt( $ch, CURLOPT_USERPWD, $this->settings['basicAuthUser'] . ':' . $this->settings['basicAuthPassword'] );
		}
		$output = curl_exec( $ch );
		$this->checkForCurlErrors( $output, $ch );
		$status_code             = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		$this->curl_content_type = curl_getinfo( $ch, CURLINFO_CONTENT_TYPE );
		curl_close( $ch );
		$this->response           = $output;
		$this->crawled_links_file = $this->settings['wp_uploads_path'] . '/WP-STATIC-CRAWLED-LINKS.txt';
		$good_response_codes      = array( '200', '201', '301', '302', '304' );
		if ( ! in_array( $status_code, $good_response_codes ) ) {
			$this->logAction( 'BAD RESPONSE STATUS (' . $status_code . '): ' . $this->url );

			return false;
			file_put_contents( $this->settings['wp_uploads_path'] . '/WP-STATIC-404-LOG.txt', $status_code . ':' . $this->url . PHP_EOL, FILE_APPEND | LOCK_EX );
			chmod( $this->settings['wp_uploads_path'] . '/WP-STATIC-404-LOG.txt', 0664 );
		} else {
			file_put_contents( $this->crawled_links_file, $this->url . PHP_EOL, FILE_APPEND | LOCK_EX );
			chmod( $this->crawled_links_file, 0664 );
		}
		$base_url = $this->settings['baseUrl'];
		$this->detectFileType( $this->full_url );
		switch ( $this->file_type ) {
			case 'html':
				require_once dirname( __FILE__ ) . '/../WP2Static/WP2Static.php';
				require_once dirname( __FILE__ ) . '/../WP2Static/HTMLProcessor.php';
				$processor            = new HTMLProcessor();
				$this->processed_file = $processor->processHTML( $this->response, $this->full_url );
				if ( $this->processed_file ) {
					$this->processed_file = $processor->getHTML();
				}
				break;
			case 'css':
				require_once dirname( __FILE__ ) . '/../WP2Static/WP2Static.php';
				require_once dirname( __FILE__ ) . '/../WP2Static/CSSProcessor.php';
				$processor            = new CSSProcessor();
				$this->processed_file = $processor->processCSS( $this->response, $this->full_url );
				if ( $this->processed_file ) {
					$this->processed_file = $processor->getCSS();
				}
				break;
			case 'txt':
			case 'js':
			case 'json':
			case 'xml':
				require_once dirname( __FILE__ ) . '/../WP2Static/WP2Static.php';
				require_once dirname( __FILE__ ) . '/../WP2Static/TXTProcessor.php';
				$processor            = new TXTProcessor();
				$this->processed_file = $processor->processTXT( $this->response, $this->full_url );
				if ( $this->processed_file ) {
					$this->processed_file = $processor->getTXT();
				}
				break;
			default:
				$this->processed_file = $this->response;
				break;
		}

		return true;
	}

	public function checkIfMoreCrawlingNeeded() {
		if ( $this->remaining_urls_to_crawl > 0 ) {
			if ( ! defined( 'WP_CLI' ) ) {
				echo $this->remaining_urls_to_crawl;
			} else {
				$this->crawl_site();
			}
		} else {
			if ( ! defined( 'WP_CLI' ) ) {
				echo 'SUCCESS';
			}
		}
	}

	public function saveFile() {
		require_once dirname( __FILE__ ) . '/../WP2Static/FileWriter.php';
		$file_writer = new FileWriter( $this->url, $this->processed_file, $this->file_type, $this->content_type );
		$file_writer->saveFile( $this->archive_dir );
	}

	public function getExtensionFromURL() {
		$url_path  = parse_url( $this->url, PHP_URL_PATH );
		$extension = pathinfo( $url_path, PATHINFO_EXTENSION );
		if ( ! $extension ) {
			return '';
		}

		return $extension;
	}

	public function detectFileType() {
		if ( $this->file_extension ) {
			$this->file_type = $this->file_extension;
		} else {
			$type = $this->content_type = $this->curl_content_type;
			if ( stripos( $type, 'text/html' ) !== false ) {
				$this->file_type = 'html';
			} elseif ( stripos( $type, 'rss+xml' ) !== false ) {
				$this->file_type = 'xml';
			} elseif ( stripos( $type, 'text/xml' ) !== false ) {
				$this->file_type = 'xml';
			} elseif ( stripos( $type, 'application/xml' ) !== false ) {
				$this->file_type = 'xml';
			} elseif ( stripos( $type, 'application/json' ) !== false ) {
				$this->file_type = 'json';
			} else {
				require_once dirname( __FILE__ ) . '/../WP2Static/WsLog.php';
				WsLog::l( 'no filetype inferred from content-type: ' . $this->curl_content_type . ' url: ' . $this->url );
			}
		}
	}

	public function logAction( $action ) {
		if ( ! isset( $this->settings['debug_mode'] ) ) {
			return;
		}
		require_once dirname( __FILE__ ) . '/../WP2Static/WsLog.php';
		WsLog::l( $action );
	}

	public function checkForCurlErrors( $response, $curl_handle ) {
		if ( $response === false ) {
			$response = curl_error( $curl_handle );
			$this->logAction( 'cURL error:' . stripslashes( $response ) );
		}
	}
}

$site_crawler = new SiteCrawler();