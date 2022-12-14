<?php

namespace Sabberworm\CSS\CSSList;

use Sabberworm\CSS\Renderable;
use Sabberworm\CSS\RuleSet\DeclarationBlock;
use Sabberworm\CSS\RuleSet\RuleSet;
use Sabberworm\CSS\Property\Selector;
use Sabberworm\CSS\Comment\Commentable;

abstract class CSSList implements Renderable, Commentable {
	protected $aComments;
	protected $aContents;
	protected $iLineNo;

	public function __construct( $iLineNo = 0 ) {
		$this->aComments = array();
		$this->aContents = array();
		$this->iLineNo   = $iLineNo;
	}

	public function getLineNo() {
		return $this->iLineNo;
	}

	public function append( $oItem ) {
		$this->aContents[] = $oItem;
	}

	public function remove( $oItemToRemove ) {
		$iKey = array_search( $oItemToRemove, $this->aContents, true );
		if ( $iKey !== false ) {
			unset( $this->aContents[ $iKey ] );

			return true;
		}

		return false;
	}

	public function setContents( array $aContents ) {
		$this->aContents = array();
		foreach ( $aContents as $content ) {
			$this->append( $content );
		}
	}

	public function removeDeclarationBlockBySelector( $mSelector, $bRemoveAll = false ) {
		if ( $mSelector instanceof DeclarationBlock ) {
			$mSelector = $mSelector->getSelectors();
		}
		if ( ! is_array( $mSelector ) ) {
			$mSelector = explode( ',', $mSelector );
		}
		foreach ( $mSelector as $iKey => &$mSel ) {
			if ( ! ( $mSel instanceof Selector ) ) {
				$mSel = new Selector( $mSel );
			}
		}
		foreach ( $this->aContents as $iKey => $mItem ) {
			if ( ! ( $mItem instanceof DeclarationBlock ) ) {
				continue;
			}
			if ( $mItem->getSelectors() == $mSelector ) {
				unset( $this->aContents[ $iKey ] );
				if ( ! $bRemoveAll ) {
					return;
				}
			}
		}
	}

	public function __toString() {
		return $this->render( new \Sabberworm\CSS\OutputFormat() );
	}

	public function render( \Sabberworm\CSS\OutputFormat $oOutputFormat ) {
		$sResult    = '';
		$bIsFirst   = true;
		$oNextLevel = $oOutputFormat;
		if ( ! $this->isRootList() ) {
			$oNextLevel = $oOutputFormat->nextLevel();
		}
		foreach ( $this->aContents as $oContent ) {
			$sRendered = $oOutputFormat->safely( function () use ( $oNextLevel, $oContent ) {
				return $oContent->render( $oNextLevel );
			} );
			if ( $sRendered === null ) {
				continue;
			}
			if ( $bIsFirst ) {
				$bIsFirst = false;
				$sResult  .= $oNextLevel->spaceBeforeBlocks();
			} else {
				$sResult .= $oNextLevel->spaceBetweenBlocks();
			}
			$sResult .= $sRendered;
		}
		if ( ! $bIsFirst ) {
			$sResult .= $oOutputFormat->spaceAfterBlocks();
		}

		return $sResult;
	}

	public abstract function isRootList();

	public function getContents() {
		return $this->aContents;
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