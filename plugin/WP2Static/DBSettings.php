<?php

class WPSHO_DBSettings {
	public static function get( $sets = array() ) {
		$plugin                 = WP2Static_Controller::getInstance();
		$settings               = array();
		$key_sets               = array();
		$target_keys            = array();
		$key_sets['general']    = array( 'baseUrl', 'debug_mode', 'selected_deployment_option', );
		$key_sets['crawling']   = array(
			'additionalUrls',
			'excludeURLs',
			'useBasicAuth',
			'basicAuthPassword',
			'basicAuthUser',
			'detection_level',
			'crawl_delay',
			'crawlPort',
		);
		$key_sets['processing'] = array(
			'removeConditionalHeadComments',
			'allowOfflineUsage',
			'baseHREF',
			'rewrite_rules',
			'rename_rules',
			'removeWPMeta',
			'removeWPLinks',
			'useBaseHref',
			'useRelativeURLs',
			'removeConditionalHeadComments',
			'removeWPMeta',
			'removeWPLinks',
			'removeHTMLComments',
		);
		$key_sets['advanced']   = array(
			'crawl_increment',
			'completionEmail',
			'delayBetweenAPICalls',
			'deployBatchSize',
		);
		$key_sets['folder']     = array( 'baseUrl-folder', 'targetFolder', );
		$key_sets['zip']        = array( 'baseUrl-zip', 'allowOfflineUsage', );
		$key_sets['github']     = array(
			'baseUrl-github',
			'ghBranch',
			'ghPath',
			'ghToken',
			'ghRepo',
			'ghCommitMessage',
		);
		$key_sets['bitbucket']  = array( 'baseUrl-bitbucket', 'bbBranch', 'bbPath', 'bbToken', 'bbRepo', );
		$key_sets['gitlab']     = array( 'baseUrl-gitlab', 'glBranch', 'glPath', 'glToken', 'glProject', );
		$key_sets['ftp']        = array(
			'baseUrl-ftp',
			'ftpPassword',
			'ftpRemotePath',
			'ftpServer',
			'ftpPort',
			'ftpTLS',
			'ftpUsername',
			'useActiveFTP',
		);
		$key_sets['bunnycdn']   = array(
			'baseUrl-bunnycdn',
			'bunnycdnStorageZoneAccessKey',
			'bunnycdnPullZoneAccessKey',
			'bunnycdnPullZoneID',
			'bunnycdnStorageZoneName',
			'bunnycdnRemotePath',
		);
		$key_sets['s3']         = array(
			'baseUrl-s3',
			'cfDistributionId',
			's3Bucket',
			's3Key',
			's3Region',
			's3RemotePath',
			's3Secret',
		);
		$key_sets['netlify']    = array(
			'baseUrl-netlify',
			'netlifyHeaders',
			'netlifyPersonalAccessToken',
			'netlifyRedirects',
			'netlifySiteID',
		);
		$key_sets['wpenv']      = array(
			'wp_site_url',
			'wp_site_path',
			'wp_site_subdir',
			'wp_uploads_path',
			'wp_uploads_url',
			'wp_active_theme',
			'wp_themes',
			'wp_uploads',
			'wp_plugins',
			'wp_content',
			'wp_inc',
		);
		foreach ( $sets as $set ) {
			$target_keys = array_merge( $target_keys, $key_sets[ $set ] );
		}
		foreach ( $target_keys as $key ) {
			$settings[ $key ] = $plugin->options->{$key};
		}
		require_once dirname( __FILE__ ) . '/../WP2Static/WPSite.php';
		$wp_site = new WPSite();
		foreach ( $key_sets['wpenv'] as $key ) {
			$settings[ $key ] = $wp_site->{$key};
		}
		$settings['crawl_increment'] = isset( $plugin->options->crawl_increment ) ? (int) $plugin->options->crawl_increment : 1;
		$settings['baseUrl']         = rtrim( $plugin->options->baseUrl, '/' ) . '/';

		return array_filter( $settings );
	}
}