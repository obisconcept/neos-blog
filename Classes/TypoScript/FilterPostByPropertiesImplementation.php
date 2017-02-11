<?php

namespace ObisConcept\NeosBlog\TypoScript;

use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Model\User;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Fusion\FusionObjects\AbstractFusionObject;

class FilterPostByPropertiesImplementation extends AbstractFusionObject {


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