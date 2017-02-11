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

use Neos\Flow\Annotations as Flow;
use ObisConcept\NeosBlog\Domain\Repository\CategoryRepository;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Utility\TypeHandling;
use Neos\Neos\Service\DataSource\AbstractDataSource;
use Neos\ContentRepository\Domain\Model\NodeInterface;

class CategorySource extends AbstractDataSource {

    /**
     * @var CategoryRepository
     * @Flow\Inject
     */
    protected $categoryRepository;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @var string
     */
    protected static $identifier = 'post-categories';

    /**
     * @inheritDoc
     */
    public function getData(NodeInterface $node = null, array $arguments)
    {
        $options = [];

        $categories = $this->categoryRepository->findAll();
        foreach ($categories as $category) {
            $options[] = [
                'label' => $category->getName(),
                // Yes, we actually need to encode the value to match EntityToIdentityConverter that is used to encode the serialized node property!
                'value' => json_encode([
                    '__identity' => $this->persistenceManager->getIdentifierByObject($category),
                    '__type' => TypeHandling::getTypeForValue($category)
                ])
            ];
        }

       return $options;
    }

}