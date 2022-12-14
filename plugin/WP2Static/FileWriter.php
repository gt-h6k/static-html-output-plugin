<?php

class FileWriter extends WP2Static {
	public function __construct( $url, $content, $file_type, $content_type ) {
		$this->url          = $url;
		$this->content      = $content;
		$this->file_type    = $file_type;
		$this->content_type = $content_type;
		$this->loadSettings( array( 'wpenv', ) );
	}

	public function saveFile( $archive_dir ) {
		$url_info  = parse_url( $this->url );
		$path_info = array();
		if ( ! isset( $url_info['path'] ) ) {
			return false;
		}
		if ( $url_info['path'] != '/' ) {
			$path_info = pathinfo( $url_info['path'] );
		} else {
			$path_info = pathinfo( 'index.html' );
		}
		$directory_in_archive = isset( $path_info['dirname'] ) ? $path_info['dirname'] : '';
		if ( ! empty( $this->settings['wp_site_subdir'] ) ) {
			$directory_in_archive = str_replace( $this->settings['wp_site_subdir'], '', $directory_in_archive );
		}
		$file_dir = $archive_dir . ltrim( $directory_in_archive, '/' );
		if ( empty( $path_info['extension'] ) && $path_info['basename'] === $path_info['filename'] ) {
			$file_dir              .= '/' . $path_info['basename'];
			$path_info['filename'] = 'index';
		}
		if ( ! file_exists( $file_dir ) ) {
			wp_mkdir_p( $file_dir );
		}
		$file_extension = '';
		if ( isset( $path_info['extension'] ) ) {
			$file_extension = $path_info['extension'];
		} elseif ( $this->file_type == 'html' ) {
			$file_extension = 'html';
		} elseif ( $this->file_type == 'xml' ) {
			$file_extension = 'html';
		}
		$filename = '';
		if ( $url_info['path'] == '/' ) {
			$filename = rtrim( $file_dir, '.' ) . 'index.html';
		} else {
			if ( ! empty( $this->settings['wp_site_subdir'] ) ) {
				$file_dir = str_replace( '/' . $this->settings['wp_site_subdir'], '/', $file_dir );
			}
			$filename = $file_dir . '/' . $path_info['filename'] . '.' . $file_extension;
		}
		$file_contents = $this->content;
		if ( $file_contents ) {
			$this->logAction( 'SAVING ' . $this->url . ' to ' . $filename );
			file_put_contents( $filename, $file_contents );
			chmod( $filename, 0664 );
		} else {
			$this->logAction( 'NOT SAVING EMTPY FILE ' . $this->url );
		}
	}

	public function logAction( $action ) {
		if ( ! isset( $this->settings['debug_mode'] ) ) {
			return;
		}
		require_once dirname( __FILE__ ) . '/WsLog.php';
		WsLog::l( $action );
	}
}