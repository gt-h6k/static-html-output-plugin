<?php

namespace Sabberworm\CSS\RuleSet;

use Sabberworm\CSS\Rule\Rule;
use Sabberworm\CSS\Renderable;
use Sabberworm\CSS\Comment\Commentable;

abstract class RuleSet implements Renderable, Commentable {
	private $aRules;
	protected $iLineNo;
	protected $aComments;

	public function __construct( $iLineNo = 0 ) {
		$this->aRules    = array();
		$this->iLineNo   = $iLineNo;
		$this->aComments = array();
	}

	public function getLineNo() {
		return $this->iLineNo;
	}

	public function addRule( Rule $oRule, Rule $oSibling = null ) {
		$sRule = $oRule->getRule();
		if ( ! isset( $this->aRules[ $sRule ] ) ) {
			$this->aRules[ $sRule ] = array();
		}
		$iPosition = count( $this->aRules[ $sRule ] );
		if ( $oSibling !== null ) {
			$iSiblingPos = array_search( $oSibling, $this->aRules[ $sRule ], true );
			if ( $iSiblingPos !== false ) {
				$iPosition = $iSiblingPos;
			}
		}
		array_splice( $this->aRules[ $sRule ], $iPosition, 0, array( $oRule ) );
	}

	public function getRules( $mRule = null ) {
		if ( $mRule instanceof Rule ) {
			$mRule = $mRule->getRule();
		}
		$aResult = array();
		foreach ( $this->aRules as $sName => $aRules ) {
			if ( ! $mRule || $sName === $mRule || ( strrpos( $mRule, '-' ) === strlen( $mRule ) - strlen( '-' ) && ( strpos( $sName, $mRule ) === 0 || $sName === substr( $mRule, 0, - 1 ) ) ) ) {
				$aResult = array_merge( $aResult, $aRules );
			}
		}

		return $aResult;
	}

	public function setRules( array $aRules ) {
		$this->aRules = array();
		foreach ( $aRules as $rule ) {
			$this->addRule( $rule );
		}
	}

	public function getRulesAssoc( $mRule = null ) {
		$aResult = array();
		foreach ( $this->getRules( $mRule ) as $oRule ) {
			$aResult[ $oRule->getRule() ] = $oRule;
		}

		return $aResult;
	}

	public function removeRule( $mRule ) {
		if ( $mRule instanceof Rule ) {
			$sRule = $mRule->getRule();
			if ( ! isset( $this->aRules[ $sRule ] ) ) {
				return;
			}
			foreach ( $this->aRules[ $sRule ] as $iKey => $oRule ) {
				if ( $oRule === $mRule ) {
					unset( $this->aRules[ $sRule ][ $iKey ] );
				}
			}
		} else {
			foreach ( $this->aRules as $sName => $aRules ) {
				if ( ! $mRule || $sName === $mRule || ( strrpos( $mRule, '-' ) === strlen( $mRule ) - strlen( '-' ) && ( strpos( $sName, $mRule ) === 0 || $sName === substr( $mRule, 0, - 1 ) ) ) ) {
					unset( $this->aRules[ $sName ] );
				}
			}
		}
	}

	public function __toString() {
		return $this->render( new \Sabberworm\CSS\OutputFormat() );
	}

	public function render( \Sabberworm\CSS\OutputFormat $oOutputFormat ) {
		$sResult  = '';
		$bIsFirst = true;
		foreach ( $this->aRules as $aRules ) {
			foreach ( $aRules as $oRule ) {
				$sRendered = $oOutputFormat->safely( function () use ( $oRule, $oOutputFormat ) {
					return $oRule->render( $oOutputFormat->nextLevel() );
				} );
				if ( $sRendered === null ) {
					continue;
				}
				if ( $bIsFirst ) {
					$bIsFirst = false;
					$sResult  .= $oOutputFormat->nextLevel()->spaceBeforeRules();
				} else {
					$sResult .= $oOutputFormat->nextLevel()->spaceBetweenRules();
				}
				$sResult .= $sRendered;
			}
		}
		if ( ! $bIsFirst ) {
			$sResult .= $oOutputFormat->spaceAfterRules();
		}

		return $oOutputFormat->removeLastSemicolon( $sResult );
	}

	public function addComments( array $aComments ) {
		$this->aComments = array_merge( $this->aComments, $aComments );
	}

	public function getComments() {
		return $this->aComments;
	}

	public function setComments( array $aComments ) {
		$this->aComments = $aComments;
	}
}