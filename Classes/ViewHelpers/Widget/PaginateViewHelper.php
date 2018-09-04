<?php

namespace ObisConcept\NeosBlog\ViewHelpers\Widget;

/*
     * This file is part of the ObisConcept.NeosBlog package.
     *
     * (c) Rene Zwinge
     *
     * This package is Open Source Software. For the full copyright and license
     * information, please view the LICENSE file which was distributed with this
     * source code.
     */

use Neos\Flow\Annotations as Flow;
use ObisConcept\NeosBlog\ViewHelpers\Widget\Controller\PaginateController;
use Neos\ContentRepository\ViewHelpers\Widget\PaginateViewHelper as ContentRepositoryPaginateViewHelper;

class PaginateViewHelper extends ContentRepositoryPaginateViewHelper
{

    /**
     * @Flow\Inject
     * @var PaginateController
     */
    protected $controller;
}
