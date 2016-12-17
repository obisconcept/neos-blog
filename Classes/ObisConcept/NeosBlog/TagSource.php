<?php
/**
 * Created by PhpStorm.
 * User: dschroder
 * Date: 03.12.16
 * Time: 12:26
 */

namespace ObisConcept\NeosBlog;

use TYPO3\Flow\Annotations as Flow;
use ObisConcept\NeosBlog\Domain\Repository\TagRepository;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Flow\Utility\TypeHandling;
use TYPO3\Neos\Service\DataSource\AbstractDataSource;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;


class TagSource extends AbstractDataSource {

    /**
     * @var TagRepository
     * @Flow\Inject
     */
    protected $tagRepository;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @var string
     */
    protected static $identifier = 'post-tags';

    /**
     * @inheritDoc
     */
    public function getData(NodeInterface $node = null, array $arguments)
    {
        $options = [];

        $tags = $this->tagRepository->findAll();

        foreach ($tags as $tag) {
            $options[] = [
                'label' => $tag->getName(),
                // Yes, we actually need to encode the value to match EntityToIdentityConverter that is used to encode the serialized node property!
                'value' => json_encode([
                    '__identity' => $this->persistenceManager->getIdentifierByObject($tag),
                    '__type' => TypeHandling::getTypeForValue($tag)
                ])
            ];
        }

        return $options;
    }

}