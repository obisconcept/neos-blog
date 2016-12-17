<?php

namespace ObisConcept\NeosBlog\Domain\Repository;

use ObisConcept\NeosBlog\Domain\Model\Category;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\Repository;

/**
 * Class CategoryRepository
 * @package ObisConcept\NeosBlog\Domain\Repository
 * @Flow\Scope("singleton")
 */
class CategoryRepository extends Repository {


    /**
     * @param Category $category
     * @return mixed
     */
    public function getCategoryIdentifier(Category $category){

        return $this->persistenceManager->getIdentifierByObject($category);
    }
}