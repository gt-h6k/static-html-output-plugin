<?php

class WP2Static_CLI {
	public function diagnostics() {
		WP_CLI::line( PHP_EOL . 'WP2Static' . PHP_EOL );
		$environmental_info = array(
			array( 'key' => 'PLUGIN VERSION', 'value' => WP2Static_Controller::VERSION, ),
			array( 'key' => 'PHP_VERSION', 'value' => phpversion(), ),
			array( 'key' => 'PHP MAX EXECUTION TIME', 'value' => ini_get( 'max_execution_time' ), ),
			array( 'key' => 'OS VERSION', 'value' => php_uname(), ),
			array( 'key' => 'WP VERSION', 'value' => get_bloginfo( 'version' ), ),
			array( 'key' => 'WP URL', 'value' => get_bloginfo( 'url' ), ),
			array( 'key' => 'WP SITEURL', 'value' => get_option( 'siteurl' ), ),
			array( 'key' => 'WP HOME', 'value' => get_option( 'home' ), ),
			array( 'key' => 'WP ADDRESS', 'value' => get_bloginfo( 'wpurl' ), ),
		);
		WP_CLI\Utils\format_items( 'table', $environmental_info, array( 'key', 'value' ) );
		$active_plugins = get_option( 'active_plugins' );
		WP_CLI::line( PHP_EOL . 'Active plugins:' . PHP_EOL );
		foreach ( $active_plugins as $active_plugin ) {
			WP_CLI::line( $active_plugin );
		}
		WP_CLI::line( PHP_EOL );
		WP_CLI::line( 'There are a total of ' . count( $active_plugins ) . ' active plugins on this site.' . PHP_EOL );
	}

	public function microtime_diff( $start, $end = null ) {
		if ( ! $end ) {
			$end = microtime();
		}
		list( $start_usec, $start_sec ) = explode( ' ', $start );
		list( $end_usec, $end_sec ) = explode( ' ', $end );
		$diff_sec  = intval( $end_sec ) - intval( $start_sec );
		$diff_usec = floatval( $end_usec ) - floatval( $start_usec );

		return floatval( $diff_sec ) + $diff_usec;
	}

	public function generate() {
		$start_time = microtime();
		$plugin     = WP2Static_Controller::getInstance();
		$plugin->generate_filelist_preview();
		$plugin->prepare_for_export();
		require_once dirname( __FILE__ ) . '/WP2Static/WP2Static.php';
		require_once dirname( __FILE__ ) . '/WP2Static/SiteCrawler.php';
		$site_crawler->crawl_site();
		$site_crawler->crawl_discovered_links();
		$plugin->post_process_archive_dir();
		$end_time = microtime();
		$duration = $this->microtime_diff( $start_time, $end_time );
		WP_CLI::success( "Generated static site archive in $duration seconds" );
	}

	public function deploy( $args, $assoc_args ) {
		$test = false;
		if ( ! empty( $assoc_args['test'] ) ) {
			$test = true;
		}
		if ( ! empty( $assoc_args['selected_deployment_option'] ) ) {
			switch ( $assoc_args['selected_deployment_option'] ) {
				case 'zip':
					break;
			}
		}
		require_once dirname( __FILE__ ) . '/WP2Static/Deployer.php';
		$deployer = new Deployer();
		$deployer->deploy( $test );
	}
}

function wp2static_options( $args, $assoc_args ) {
	$action                  = isset( $args[0] ) ? $args[0] : null;
	$option_name             = isset( $args[1] ) ? $args[1] : null;
	$value                   = isset( $args[2] ) ? $args[2] : null;
	$reveal_sensitive_values = false;
	if ( empty( $action ) ) {
		WP_CLI::error( 'Missing required argument: <get|set|list>' );
	}
	$plugin = WP2Static_Controller::getInstance();
	if ( $action === 'get' ) {
		if ( empty( $option_name ) ) {
			WP_CLI::error( 'Missing required argument: <option-name>' );
		}
		if ( ! $plugin->options->optionExists( $option_name ) ) {
			WP_CLI::error( 'Invalid option name' );
		} else {
			$option_value = $plugin->options->getOption( $option_name );
			WP_CLI::line( $option_value );
		}
	}
	if ( $action === 'set' ) {
		if ( empty( $option_name ) ) {
			WP_CLI::error( 'Missing required argument: <option-name>' );
		}
		if ( empty( $value ) ) {
			WP_CLI::error( 'Missing required argument: <value>' );
		}
		if ( ! $plugin->options->optionExists( $option_name ) ) {
			WP_CLI::error( 'Invalid option name' );
		} else {
			$plugin->options->setOption( $option_name, $value );
			$plugin->options->save();
			$result = $plugin->options->getOption( $option_name );
			if ( ! $result === $value ) {
				WP_CLI::error( 'Option not able to be updated' );
			}
		}
	}
	if ( $action === 'list' ) {
		if ( isset( $assoc_args['reveal-sensitive-values'] ) ) {
			$reveal_sensitive_values = true;
		}
		$options = $plugin->options->getAllOptions( $reveal_sensitive_values );
		WP_CLI\Utils\format_items( 'table', $options, array( 'Option name', 'Value' ) );
	}
}

WP_CLI::add_command( 'wp2static', 'wp2static_cli' );
WP_CLI::add_command( 'wp2static options', 'wp2static_options' );