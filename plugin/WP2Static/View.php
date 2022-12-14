<?php

class WP2Static_View {
	protected $variables = array();
	protected $path = null;
	protected $directory = 'views';
	protected $extension = '.phtml';
	protected $template = null;

	public function __construct() {
		list( $plugin_dir ) = explode( '/', plugin_basename( __FILE__ ) );
		$path_array = array( WP_PLUGIN_DIR, $plugin_dir, $this->directory );
		$this->path = implode( '/', $path_array );
	}

	public function setTemplate( $tpl ) {
		$this->template  = $tpl;
		$this->variables = array();

		return $this;
	}

	public function __set( $name, $value ) {
		$this->variables[ $name ] = $value;

		return $this;
	}

	public function assign( $name, $value ) {
		return $this->__set( $name, $value );
	}

	public function __get( $name ) {
		$value = array_key_exists( $name, $this->variables ) ? $this->variables[ $name ] : null;

		return $value;
	}

	public function render() {
		$file = $this->path . '/' . $this->template . $this->extension;
		if ( ! is_readable( $file ) ) {
			error_log( 'Can\'t find view template: ' . $file );
		}
		include $file;

		return $this;
	}

	public function fetch() {
		ob_start();
		$this->render();
		$contents = ob_get_contents();
		ob_end_clean();

		return $contents;
	}
}