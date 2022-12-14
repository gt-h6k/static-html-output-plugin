<?php
$ajax_action   = isset( $_POST['ajax_action'] ) ? $_POST['ajax_action'] : '';
$deployers_dir = dirname( __FILE__ ) . '/../deployers';
if ( $ajax_action === 'crawl_site' || $ajax_action === 'crawl_again' ) {
	require_once dirname( __FILE__ ) . '/WP2Static.php';
	require_once dirname( __FILE__ ) . '/SiteCrawler.php';
	wp_die();

	return null;
} elseif ( $ajax_action == 'bitbucket_prepare_export' ) {
	require_once dirname( __FILE__ ) . '/SitePublisher.php';
	require_once $deployers_dir . '/Bitbucket.php';
	wp_die();

	return null;
} elseif ( $ajax_action == 'bitbucket_upload_files' ) {
	require_once dirname( __FILE__ ) . '/SitePublisher.php';
	require_once $deployers_dir . '/Bitbucket.php';
	wp_die();

	return null;
} elseif ( $ajax_action == 'github_prepare_export' ) {
	require_once dirname( __FILE__ ) . '/SitePublisher.php';
	require_once $deployers_dir . '/GitHub.php';
	wp_die();

	return null;
} elseif ( $ajax_action == 'github_upload_files' ) {
	require_once dirname( __FILE__ ) . '/SitePublisher.php';
	require_once $deployers_dir . '/GitHub.php';
	wp_die();

	return null;
} elseif ( $ajax_action == 'test_github' ) {
	require_once dirname( __FILE__ ) . '/SitePublisher.php';
	require_once $deployers_dir . '/GitHub.php';
	wp_die();

	return null;
} elseif ( $ajax_action == 'gitlab_prepare_export' ) {
	require_once dirname( __FILE__ ) . '/SitePublisher.php';
	require_once $deployers_dir . '/GitLab.php';
	wp_die();

	return null;
} elseif ( $ajax_action == 'gitlab_upload_files' ) {
	require_once dirname( __FILE__ ) . '/SitePublisher.php';
	require_once $deployers_dir . '/GitLab.php';
	wp_die();

	return null;
} elseif ( $ajax_action == 'test_gitlab' ) {
	require_once dirname( __FILE__ ) . '/SitePublisher.php';
	require_once $deployers_dir . '/GitLab.php';
	wp_die();

	return null;
} elseif ( $ajax_action == 'test_bitbucket' ) {
	require_once dirname( __FILE__ ) . '/SitePublisher.php';
	require_once $deployers_dir . '/Bitbucket.php';
	wp_die();

	return null;
} elseif ( $ajax_action == 'test_netlify' ) {
	require_once dirname( __FILE__ ) . '/SitePublisher.php';
	require_once $deployers_dir . '/Netlify.php';
	wp_die();

	return null;
} elseif ( $ajax_action == 'netlify_do_export' ) {
	require_once dirname( __FILE__ ) . '/SitePublisher.php';
	require_once $deployers_dir . '/Netlify.php';
	wp_die();

	return null;
} elseif ( $ajax_action == 'test_s3' ) {
	require_once dirname( __FILE__ ) . '/SitePublisher.php';
	require_once $deployers_dir . '/S3.php';
	wp_die();

	return null;
} elseif ( $ajax_action == 's3_prepare_export' ) {
	require_once dirname( __FILE__ ) . '/SitePublisher.php';
	require_once $deployers_dir . '/S3.php';
	wp_die();

	return null;
} elseif ( $ajax_action == 's3_transfer_files' ) {
	require_once dirname( __FILE__ ) . '/SitePublisher.php';
	require_once $deployers_dir . '/S3.php';
	wp_die();

	return null;
} elseif ( $ajax_action == 'cloudfront_invalidate_all_items' ) {
	require_once dirname( __FILE__ ) . '/SitePublisher.php';
	require_once $deployers_dir . '/S3.php';
	wp_die();

	return null;
} elseif ( $ajax_action == 'test_ftp' ) {
	require_once dirname( __FILE__ ) . '/SitePublisher.php';
	require_once $deployers_dir . '/FTP.php';
	wp_die();

	return null;
} elseif ( $ajax_action == 'ftp_prepare_export' ) {
	require_once dirname( __FILE__ ) . '/SitePublisher.php';
	require_once $deployers_dir . '/FTP.php';
	wp_die();

	return null;
} elseif ( $ajax_action == 'ftp_transfer_files' ) {
	require_once dirname( __FILE__ ) . '/SitePublisher.php';
	require_once $deployers_dir . '/FTP.php';
	wp_die();

	return null;
} elseif ( $ajax_action == 'test_bunny' ) {
	require_once dirname( __FILE__ ) . '/SitePublisher.php';
	require_once $deployers_dir . '/BunnyCDN.php';
	wp_die();

	return null;
} elseif ( $ajax_action == 'bunnycdn_prepare_export' ) {
	require_once dirname( __FILE__ ) . '/SitePublisher.php';
	require_once $deployers_dir . '/BunnyCDN.php';
	wp_die();

	return null;
} elseif ( $ajax_action == 'bunnycdn_transfer_files' ) {
	require_once dirname( __FILE__ ) . '/SitePublisher.php';
	require_once $deployers_dir . '/BunnyCDN.php';
	wp_die();

	return null;
} elseif ( $ajax_action == 'bunnycdn_purge_cache' ) {
	require_once dirname( __FILE__ ) . '/SitePublisher.php';
	require_once $deployers_dir . '/BunnyCDN.php';
	wp_die();

	return null;
}