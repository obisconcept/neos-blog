<?php namespace ObisConcept\NeosBlog\ViewHelpers\Widget;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\ViewHelpers\Widget\PaginateViewHelper as ContentRepositoryPaginateViewHelper;

class PaginateViewHelper extends ContentRepositoryPaginateViewHelper {

    /**
     * @Flow\Inject
     * @var \ObisConcept\NeosBlog\ViewHelpers\Widget\Controller\PaginateController
     */
    protected $controller;

}
