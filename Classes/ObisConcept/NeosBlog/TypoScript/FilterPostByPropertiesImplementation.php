<?php

namespace ObisConcept\NeosBlog\TypoScript;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Neos\Domain\Model\User;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TypoScript\TypoScriptObjects\AbstractTypoScriptObject;

class FilterPostByPropertiesImplementation extends AbstractTypoScriptObject {


  /**
   * Evaluate this TypoScript object and return the result
   *
   * @return mixed
   */
  
  public function evaluate() {
    $postCollection = $this->tsValue('postCollection');
    $categoryFilter = $this->tsValue('categoryFilter');

    /** @var User $authorFilter */
    $authorFilter = $this->tsValue('authorFilter');

    $filteredByCategoryPostCollection = array();

    /** @var NodeInterface $post */
    foreach ($postCollection as $post) {

      if ($post->getProperty('categories') == $categoryFilter) {
        $filteredByCategoryPostCollection[] = $post;
      }

    }


    return $filteredByCategoryPostCollection;

  }
}