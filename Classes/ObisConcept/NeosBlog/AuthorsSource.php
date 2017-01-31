<?php

namespace ObisConcept\NeosBlog;

    /*
     * This file is part of the ObisConcept.NeosBlog package.
     *
     * (c) Dennis Schröder
     *
     * This package is Open Source Software. For the full copyright and license
     * information, please view the LICENSE file which was distributed with this
     * source code.
     */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Flow\Utility\TypeHandling;
use TYPO3\Neos\Service\DataSource\AbstractDataSource;
use TYPO3\Neos\Domain\Service\UserService;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

class AuthorsSource extends AbstractDataSource {

    /**
     * @Flow\Inject
     * @var UserService
     */
    protected $userService;


    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @var string
     */
    protected static $identifier = 'post-authors';

    /**
     * @inheritDoc
     */
    public function getData(NodeInterface $node = null, array $arguments)
    {
        $options = [];

        $authors = $this->userService->getUsers();
        
        
        foreach ($authors as $author) {
            $options[] = [
                'label' => $author->getLabel(),
                // Yes, we actually need to encode the value to match EntityToIdentityConverter that is used to encode the serialized node property!
                'value' => json_encode([
                    '__identity' => $this->persistenceManager->getIdentifierByObject($author),
                    '__type' => TypeHandling::getTypeForValue($author)
                ])
            ];
        }

       return $options;
    }

}