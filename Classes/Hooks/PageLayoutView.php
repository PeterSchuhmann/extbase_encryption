<?php
namespace PS\ExtbaseEncryption\Hooks;

use TYPO3\CMS\Core\Utility\GeneralUtility;

class PageLayoutView implements \TYPO3\CMS\Backend\View\PageLayoutViewDrawItemHookInterface
{

    /**
     * Preprocesses the preview rendering of a content element.
     *
     * @param  \TYPO3\CMS\Backend\View\PageLayoutView $parentObject  Calling parent object
     * @param  boolean                                $drawItem      Whether to draw the item using the default functionalities
     * @param  string                                 $headerContent Header content
     * @param  string                                 $itemContent   Item content
     * @param  array                                  $row           Record row of tt_content
     * @return void
     */
    public function preProcess(\TYPO3\CMS\Backend\View\PageLayoutView &$parentObject, &$drawItem, &$headerContent, &$itemContent, array &$row)
    {

    }

}