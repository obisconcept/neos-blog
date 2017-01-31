<?php
/*
     * This file is part of the ObisConcept.NeosBlog package.
     *
     * (c) Dennis Schröder
     *
     * This package is Open Source Software. For the full copyright and license
     * information, please view the LICENSE file which was distributed with this
     * source code.
*/

namespace ObisConcept\NeosBlog\ViewHelpers;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;

class AuthorDetailsViewHelper extends AbstractViewHelper {

  /**
   * @Flow\Inject
   * @var PersistenceManagerInterface
   */
  protected $persistenceManager;


  /**
   * Render the choosen property of an user object
   *
   * @param string $identifier
   * @return string the users full name
   */

  public function render(string $identifier) {
    $user = $this->persistenceManager->getObjectByIdentifier($identifier, 'TYPO3\Neos\Domain\Model\User');

    return $user->getLabel();
  }
}