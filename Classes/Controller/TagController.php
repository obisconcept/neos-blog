<?php

namespace ObisConcept\NeosBlog\Controller;

    /*
     * This file is part of the ObisConcept.NeosBlog package.
     *
     * (c) Dennis Schröder
     *
     * This package is Open Source Software. For the full copyright and license
     * information, please view the LICENSE file which was distributed with this
     * source code.
     */


use Neos\Flow\Annotations as Flow;
use Neos\Neos\Controller\Module\ManagementController;



/**
 * Class TagController
 * @package ObisConcept\NeosBlog\Controller
 * @Flow\Scope("singleton")
 */

class TagController extends ManagementController {


    /**
     * Shows a list of tags
     *
     * @return string
     * @throws \Neos\Flow\Mvc\Exception\StopActionException
     */


    public function indexAction() {
        

    }

    /**
     * Shows the details of one Tag
     * @param array $identifier
     */
    
    public function showAction($identifier) {


    }

    /**
     * Create an new Tag
     * @param string $name
     * @param string $description
     */
    public function createAction(string $name, string $description) {


    }

    /**
     * Deletes a Tag
     *
     * @param $identifier
     */
    public function deleteAction($identifier) {


    }

}