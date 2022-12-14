<?php

namespace Sabberworm\CSS\Value;

use Sabberworm\CSS\Renderable;

abstract class Value implements Renderable {
	protected $iLineNo;

	public function __construct( $iLineNo = 0 ) {
		$this->iLineNo = $iLineNo;
	}

	public function getLineNo() {
		return $this->iLineNo;
	}
}