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


use ObisConcept\NeosBlog\Domain\Repository\PostNodeDataRepository;
use TYPO3\Flow\Annotations as Flow;
use ObisConcept\NeosBlog\Domain\Model\Category;
use ObisConcept\NeosBlog\Domain\Service\BlogService;
use ObisConcept\NeosBlog\Domain\Service\ContentService;
use ObisConcept\NeosBlog\Domain\Service\PostService;
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
   * @param string $dimension
   * @param array $dimensionLabel
   *
   * ToDo: Refactor indexAction / Move some of the code into separate Service classes and create own queries to get the data directly from the database
   */


  public function indexAction(NodeInterface $blogNode = null, Workspace $workspaceObject = null, array $authorName = null, Category $categoryObject = null, string $dimension = '' ,  $dimensionLabel = array()) {

//    \TYPO3\Flow\var_dump(json_decode($dimension, true));

    if ($dimension == '') {
      $dimension = array();
    } else {
      $dimension = json_decode($dimension, true);
    }

//    $posts = $this->postService->testFunction($dimension);
//
//    foreach ($posts as $post) {
//      \TYPO3\Flow\var_dump($post->getWorkspace()->getName());
//    }
//
//    die();




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

    // unset archived posts
    /** @var NodeInterface $personalPost */
    foreach ($personalPosts as $key => $personalPost) {

      if($personalPost->getProperty('archived') == true) {
        unset($personalPosts[$key]);
      }
    }

    $sortedPosts = array();

    /** @var NodeInterface $post */
    foreach($personalPosts as $post) {
      $sortedPosts[$post->getProperty('publishedAt')->format('d.m.Y H:i:s')] = $post;
    }

    if ($sortedPosts !== null) {
      usort($sortedPosts, function($postA, $postB) {
        return ($postA->getProperty('publishedAt') > $postB->getProperty('publishedAt')) ? -1 : 1;
      });
    }

    usort($sortedPosts, function($postA, $postB) {
      return ($postA->getProperty('publishedAt') > $postB->getProperty('publishedAt')) ? -1 : 1;
    });

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

    \TYPO3\Flow\var_dump($authorFilterMenu);
    die();

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
    $defaultLanguage = $this->postService->getDefaultLanguage();

    if(empty($dimension) == false) {

      $this->view->assign('dimensionLabel', $dimensionLabel[0]);
      unset($languageDimensions[$dimensionLabel[0]]);

    } else {

      unset($languageDimensions[$defaultLanguage]);
      $this->view->assign('defaultLanguage', $defaultLanguage);

    }

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
            $imageResource = $this->contentService->getPostImageResourceObject($post);
            $teaserText = $this->contentService->getPostTextTeaser($post);

            if (!$teaserText == null) {
                $this->view->assign('postTextTeaser', $teaserText);
                $this->view->assign('postImage', $imageResource[0]);
            }

            /** @var NodeInterface $personalPosts */
            $properties =  $post->getProperties();

            //make each property available in the template with it's property name
            foreach ($properties as $propertyName => $property) {
                $this->view->assign($propertyName, $property);
            }

            $this->view->assign('post', $post);
        } else {

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

        $author = $this->userService->getCurrentUser();

        $nodeTemplate = new NodeTemplate();
        $nodeTemplate->setNodeType($this->nodeTypeManager->getNodeType('ObisConcept.NeosBlog:Post'));
        $nodeTemplate->setProperty('title', $title);
        $nodeTemplate->setProperty('author', $author);
        $nodeTemplate->setProperty('archived', false);
        $nodeTemplate->setHiddenInIndex(true);

        $published = new \DateTime();

        $nodeTemplate->setProperty('publishedAt', $published );

        $slug = strtolower(str_replace(array(' ', ',', ':', 'ü', 'à', 'é', '?', '!', '[', ']', '.', '\''), array('-', '', '', 'u', 'a', 'e', '', '', '', '', '-', ''), $title));


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
}