<?php

class FileCopier {
	public function __construct( $url, $wp_site_url, $wp_site_path ) {
		$this->url          = $url;
		$this->wp_site_url  = $wp_site_url;
		$this->wp_site_path = $wp_site_path;
	}

	public function getLocalFileForURL() {
		$local_file = str_replace( $this->wp_site_url, $this->wp_site_path, $this->url );
		if ( is_file( $local_file ) ) {
			return $local_file;
		} else {
			require_once dirname( __FILE__ ) . '/../WP2Static/WsLog.php';
			WsLog::l( 'ERROR: trying to copy local file: ' . $local_file . ' for URL: ' . $this->url . ' (FILE NOT FOUND/UNREADABLE)' );
		}
	}

	public function copyFile( $archive_dir ) {
		$url_info   = parse_url( $this->url );
		$path_info  = array();
		$local_file = $this->getLocalFileForURL();
		if ( ! isset( $url_info['path'] ) ) {
			return false;
		}
		$path_info            = pathinfo( $url_info['path'] );
		$directory_in_archive = isset( $path_info['dirname'] ) ? $path_info['dirname'] : '';
		if ( ! empty( $this->settings['wp_site_subdir'] ) ) {
			$directory_in_archive = str_replace( $this->settings['wp_site_subdir'], '', $directory_in_archive );
		}
		$file_dir = $archive_dir . ltrim( $directory_in_archive, '/' );
		if ( ! file_exists( $file_dir ) ) {
			wp_mkdir_p( $file_dir );
		}
		$file_extension = $path_info['extension'];
		$basename       = $path_info['filename'] . '.' . $file_extension;
		$filename       = $file_dir . '/' . $basename;
		$filename       = str_replace( '//', '/', $filename );
		if ( is_file( $local_file ) ) {
			copy( $local_file, $filename );
		} else {
			require_once dirname( __FILE__ ) . '/../WP2Static/WsLog.php';
			WsLog::l( 'ERROR: trying to copy local file: ' . $local_file . ' to: ' . $filename . ' in archive dir: ' . $archive_dir . ' (FILE NOT FOUND/UNREADABLE)' );
		}
	}
}