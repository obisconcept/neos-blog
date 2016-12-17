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


use TYPO3\Flow\Annotations as Flow;
use ObisConcept\NeosBlog\Domain\Model\Category;
<<<<<<< HEAD
use ObisConcept\NeosBlog\Domain\Service\BlogService;
use ObisConcept\NeosBlog\Domain\Service\ContentService;
use ObisConcept\NeosBlog\Domain\Service\PostService;
=======
use ObisConcept\NeosBlog\Service\Domain\BlogService;
use ObisConcept\NeosBlog\Service\Domain\ContentService;
use ObisConcept\NeosBlog\Service\Domain\PostService;
>>>>>>> master
use TYPO3\Neos\Controller\Module\ManagementController;
use TYPO3\Neos\Domain\Service\ContentContextFactory;
use TYPO3\Neos\Domain\Service\UserService;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Model\NodeTemplate;
use TYPO3\TYPO3CR\Domain\Model\Workspace;
use TYPO3\TYPO3CR\Domain\Repository\ContentDimensionRepository;
use TYPO3\TYPO3CR\Domain\Service\NodeTypeManager;


/**
 * Class BlogController
 * @package ObisConcept\NeosBlog\Controller
 * @Flow\Scope("singleton")
 */

class BlogController extends ManagementController {


    /**
     * @Flow\Inject
     * @var BlogService
     */

    protected $blogService;


    /**
     * @Flow\Inject
     * @var PostService
     */

    protected $postService;

    /**
     * @Flow\Inject
     * @var ContentService
     */

    protected $contentService;

    /**
     * @Flow\Inject
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @Flow\Inject
     * @var ContentContextFactory
     */
    protected $contentContextFactory;

    /**
     * @Flow\Inject
     * @var UserService
     */
    protected $userService;

    /**
     * @Flow\Inject
     * @var ContentDimensionRepository
     */
    protected $contentDimensionsRepository;


    /**
     * Shows a list of post nodes which are accessible by the current user
     * based on the personal workspaces and the default dimension
     *
     * @param NodeInterface $blogNode
     * @param Workspace $workspaceObject
     * @param array $authorName
     * @param Category $categoryObject
     * @param array $dimension
     *
     * ToDo: Get the all types of filtered and sorted Posts directly from the database for performance reasons
     */


    public function indexAction(NodeInterface $blogNode = null, Workspace $workspaceObject = null, array $authorName = null, Category $categoryObject = null, $dimension = array()) {

        $this->view->assign('activeDimension', $dimension);

        if (!$workspaceObject == null) {
            $personalPosts = $this->postService->getPostsFilteredByWorkspace($workspaceObject, $dimension);
        } elseif (!$authorName == null) {
            $personalPosts = $this->postService->getPersonalPosts($authorName[0], $dimension);
        } elseif (!$categoryObject == null) {
            $personalPosts = $this->postService->getPostsWithCategoryRelation($categoryObject, $dimension);
        } elseif (!$blogNode == null) {
            $personalPosts = $blogNode->getChildNodes('ObisConcept.NeosBlog:Post');
        } else {
            $personalPosts = $this->postService->getPersonalPosts(' ',$dimension);
        }

<<<<<<< HEAD
        $sortedPosts = array();
        
=======
>>>>>>> master
        /** @var NodeInterface $post */
        foreach($personalPosts as $post) {
            $sortedPosts[$post->getProperty('publishedAt')->format('d.m.Y H:i:s')] = $post;
        }

<<<<<<< HEAD
        if ($sortedPosts !== null) {
            usort($sortedPosts, function($postA, $postB) {
                return ($postA->getProperty('publishedAt') > $postB->getProperty('publishedAt')) ? -1 : 1;
            });
        }
=======
        usort($sortedPosts, function($postA, $postB) {
            return ($postA->getProperty('publishedAt') > $postB->getProperty('publishedAt')) ? -1 : 1;
        });
>>>>>>> master

        /** @var NodeInterface $a */
        $nodes = $sortedPosts;

        $workspacesInArray = array();
        $authorsInArray = array();
        $blogsInArray = array();
        $categoryInArray = array();

        /** @var NodeInterface $post */
        foreach ($personalPosts as $post) {
            $workspacesInArray[$post->getWorkspace()->getName()] = $post->getWorkspace();
            $authorsInArray[] = $post->getProperty('author');

            $blogName = ($post->getParent() !== null ? $post->getParent()->getProperty('title'): null);
            $blogNode = ($post->getParent() !== null ? $post->getParent() : null);
            if (!$blogName == null) $blogsInArray[$blogName] = $blogNode;

            $categoryName = ($post->getProperty('categories') !== null ? $post->getProperty('categories')->getName(): null);
            $category = ($post->getProperty('categories') !== null ? $post->getProperty('categories'): null);
            if (!$categoryName == null) $categoryInArray[$categoryName] = $category;
        }

        $workspaceFilterMenu = $workspacesInArray;
        $authorFilterMenu = array_unique($authorsInArray);
        $categoryFilterMenu = $categoryInArray;
        $blogFilterMenu = $blogsInArray;

        $this->view->assign('blogFilterMenu', $blogFilterMenu);
        $this->view->assign('workspaceFilterMenu', $workspaceFilterMenu);
        $this->view->assign('authorFilterMenu', $authorFilterMenu);
        $this->view->assign('categoryFilterMenu', $categoryFilterMenu);


        $blogNodes = $this->blogService->getPersonalBlogs();
        $this->view->assign('blogs', $blogNodes);

        $userWorkspace = $this->_userService->getPersonalWorkspaceName();
        $this->view->assign('personalWorkspace', $userWorkspace);

        if (!$nodes == null){
            $this->view->assign('posts', $nodes);
        }
        $languageDimensions = $this->postService->getLanguageDimensions();
        $this->view->assign('dimensions', $languageDimensions);

        $postCount = count($nodes);
        $this->view->assign('postCount', $postCount);
    }

    /**
     * Shows the details of one post node
     * @param NodeInterface $post
     */
    
    public function showAction(NodeInterface $post) {

        if(!$post == Null) {
            $imageRessource = $this->contentService->getPostImageResourceObject($post);
            $teaserText = $this->contentService->getPostTextTeaser($post);

            if (!$teaserText == null) {
                $this->view->assign('postTextTeaser', $teaserText);
                $this->view->assign('postImage', $imageRessource[0]);
            }

            /** @var NodeInterface $personalPosts */
            $properties =  $post->getProperties();

            //make each property available in the template with it's propertyname
            foreach ($properties as $propertyName => $property) {
                $this->view->assign($propertyName, $property);
            }

            $this->view->assign('post', $post);
        }
    }

    /**
     * @param string $title
     * @param string $blogIdentifier
     * @throws \TYPO3\TYPO3CR\Exception\NodeTypeNotFoundException
     */
    public function createAction(string $title, string $blogIdentifier) {

        if($title == null) {
            $title = 'Unnamed';
        }

        $userWorkspace = $this->_userService->getPersonalWorkspaceName();

        /** @var NodeInterface $blogNode */
        $blogNode = $this->getBlogNode($userWorkspace, $blogIdentifier);

        $author = $this->userService->getCurrentUser()->getName()->getFullName();

        $nodeTemplate = new NodeTemplate();
        $nodeTemplate->setNodeType($this->nodeTypeManager->getNodeType('ObisConcept.NeosBlog:Post'));
        $nodeTemplate->setProperty('title', $title);
        $nodeTemplate->setProperty('author', $author);
        $nodeTemplate->setProperty('__hiddenInIndex', true);

        $published = new \DateTime();

        $nodeTemplate->setProperty('publishedAt', $published );

        $slug = strtolower(str_replace(array(' ', ',', ':', 'ü', 'à', 'é', '?', '!', '[', ']', '.', '\''), array('-', '', '', 'u', 'a', 'e', '', '', '', '', '-', ''), $title));

        \TYPO3\Flow\var_dump($slug);

        $blogNode->createNodeFromTemplate($nodeTemplate, $slug);

        if ($this->request->getHttpRequest()->isMethodSafe() === false) {
            $this->persistenceManager->persistAll();
        }

        
        $this->redirect('index');


    }

    /**
     * Deletes the specified node and all of its sub nodes
     *
     * @param $postNode
     */
    public function deleteAction(NodeInterface $postNode) {

        if ($this->request->getHttpRequest()->isMethodSafe() === false) {
            $this->persistenceManager->persistAll();
        }

        /** @var NodeInterface $node */
        $postNode->remove();
        $this->redirect('index');
    }


    protected function getBlogNode(string $workspace, string $blogIdentifier){
        $context = $this->contentContextFactory->create(['workspaceName' => $workspace]);

        $blogNode = $context->getNodeByIdentifier($blogIdentifier);

        if (!($blogNode instanceof NodeInterface)) {
           return;
        }

        return $blogNode;
    }

    /**
     * @param array $unfilteredPosts
     * @param string $workspaceName
     * @return array|bool
     */
    protected function filterPostsBySelectedWorkspace(array $unfilteredPosts, string $workspaceName) {

        $filteredPosts = array();
        /** @var NodeInterface $post */
        foreach ($unfilteredPosts as $post) {

            if ($post->getWorkspace()->getName() == $workspaceName) {
                $filteredPosts[] = $post;
            }

        }

        return $filteredPosts;
    }

}