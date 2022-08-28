<?php

namespace Sabberworm\CSS\Comment;

use Sabberworm\CSS\Renderable;

class Comment implements Renderable {
	protected $iLineNo;
	protected $sComment;

	public function __construct( $sComment = '', $iLineNo = 0 ) {
		$this->sComment = $sComment;
		$this->iLineNo  = $iLineNo;
	}

	public function getComment() {
		return $this->sComment;
	}

	public function getLineNo() {
		return $this->iLineNo;
	}

	public function setComment( $sComment ) {
		$this->sComment = $sComment;
	}

	public function __toString() {
		return $this->render( new \Sabberworm\CSS\OutputFormat() );
	}

	public function render( \Sabberworm\CSS\OutputFormat $oOutputFormat ) {
		return '/*' . $this->sComment . '*/';
	}
}