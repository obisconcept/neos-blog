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

use ObisConcept\NeosBlog\Domain\Model\Category;
use ObisConcept\NeosBlog\Domain\Repository\CategoryRepository;
use ObisConcept\NeosBlog\Domain\Service\PostService;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Neos\Controller\Module\ManagementController;
use TYPO3\Neos\Domain\Service\UserService;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;
use TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository;


/**
 * Class CategoryController
 * @package ObisConcept\NeosBlog\Controller
 * @Flow\Scope("singleton")
 */

class CategoryController extends ManagementController {

    /**
     * @Flow\Inject
     * @var CategoryRepository
     */

    protected $categoryRepository;

    /**
     * @Flow\Inject
     * @var WorkspaceRepository
     */

    protected $workspaceRepository;

    /**
     * @Flow\Inject
     * @var NodeDataRepository
     */

    protected $nodeDataRepository;

    /**
     * @Flow\Inject
     * @var UserService
     */
    protected $userService;

    /**
     * @Flow\Inject
     * @var PostService
     */

    protected $postService;

    /**
     * Shows a list of categories
     *
     * @return string
     * @throws \TYPO3\Flow\Mvc\Exception\StopActionException
     */


    public function indexAction() {
        $categories = $this->categoryRepository->findAll();


        $this->view->assign('categories', $categories);

    }

    /**
     * Edit one category
     * @param Category $category
     */

    public function editAction(Category $category) {

        $this->categoryRepository->update($category);

        $this->redirect('index');
    }

    /**
     * Create an new Category
     * @param string $name
     * @param string $description
     */
    public function createAction(string $name, string $description) {
        $category = new Category();
        $category->setName($name);
        $category->setDescription($description);
        $author = $this->userService->getCurrentUser()->getName()->getFullName();
        $category->setAuthor($author);

        $this->categoryRepository->add($category);

        $this->redirect('index');
    }

    /**
     * Deletes a Category
     *
     * @param Category $category
     * @throws \TYPO3\Flow\Persistence\Exception\IllegalObjectTypeException
     */
    public function deleteAction(Category $category) {

        //check first if the to be deleted category is connected with any posts
        $categoryID = $this->categoryRepository->getCategoryIdentifier($category);

        $objectTypeMap = array(
            'ObisConcept\NeosBlog\Domain\Model\Category' => array($categoryID)
        );

        $posts = $this->nodeDataRepository->findNodesByRelatedEntities($objectTypeMap);

        if (empty($posts)) {
            $this->categoryRepository->remove($category);
        } else {
            
        }

        $this->redirect('index');
    }

}