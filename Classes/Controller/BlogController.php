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


use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Service\UserService;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\Neos\Domain\Service\ContentContextFactory;
use ObisConcept\NeosBlog\Domain\Service\BlogService;
use ObisConcept\NeosBlog\Domain\Service\PostService;
use Neos\Neos\Controller\Module\ManagementController;
use Neos\ContentRepository\Domain\Model\NodeTemplate;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use ObisConcept\NeosBlog\Domain\Service\ContentService;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\ContentRepository\Domain\Repository\ContentDimensionRepository;

/**
 * Class BlogController
 * @package ObisConcept\NeosBlog\Controller
 * @Flow\Scope("singleton")
 */

class BlogController extends ManagementController
{


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
     * @param bool $showArchived
     * @param string $dimension
     * @param array $dimensionLabel
     * @param string $searchTerm
     * @param bool $searchSubmitted
     */

    public function indexAction(bool $showArchived = false, string $dimension = '', $dimensionLabel = array(), $searchTerm = '', $searchSubmitted = false)
    {

    // convert json string to array
        ($dimension == '') ? $dimension = array() : $dimension = json_decode($dimension, true);

        // we need a little logic here to control no posts status
        if ($showArchived == true && $searchSubmitted == false) {
            $this->view->assign('showArchivedNoResults', true);
            $this->view->assign('showSearchNoResults', false);
            $this->view->assign('showCreateNewForm', false);
        }

        if ($searchSubmitted == true && $showArchived == false) {
            $this->view->assign('showSearchNoResults', true);
            $this->view->assign('showArchivedNoResults', false);
            $this->view->assign('showCreateNewForm', false);
        }

        if ($searchSubmitted == false && $showArchived == false) {
            $this->view->assign('showSearchNoResults', false);
            $this->view->assign('showArchivedNoResults', false);
            $this->view->assign('showCreateNewForm', true);
        }
    

        // pass the search was submitted flag to the view
        $this->view->assign('searchSubmitted', $searchSubmitted);

        // pass the showArchived flag to the view
        $this->view->assign('showArchived', $showArchived);

        // get the posts filtered by searchterm if defined
        $posts = $this->postService->getPersonalPosts($dimension, $searchTerm, $showArchived);

        // pass the posts to the view
        if (!$posts == null) {
            $this->view->assign('posts', $posts);
            $this->view->assign('searchTerm', $searchTerm);
        }

        // pass all blogs to the view to create new posts
        $blogNodes = $this->blogService->getPersonalBlogs();
        $this->view->assign('blogs', $blogNodes);

        // pass the personalWorkspace to the view to create new posts
        $userWorkspace = $this->_userService->getPersonalWorkspaceName();
        $this->view->assign('personalWorkspace', $userWorkspace);

        // get all language Dimensions and the defaultLanguage
        $languageDimensions = $this->postService->getLanguageDimensions();
        $defaultLanguage = $this->postService->getDefaultLanguage();

        // passs the language Dimensions and the defaultLanguage to view
        if (empty($dimension) == false) {
            $this->view->assign('dimensionLabel', $dimensionLabel[0]);
            unset($languageDimensions[$dimensionLabel[0]]);
        } else {
            unset($languageDimensions[$defaultLanguage]);
            $this->view->assign('defaultLanguage', $defaultLanguage);
        }

        $this->view->assign('dimensions', $languageDimensions);
    }

    /**
     * Shows the details of one post node
     * @param NodeInterface $post
     */

    public function showAction(NodeInterface $post)
    {
        if (!$post == null) {
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
     * @throws \Neos\ContentRepository\Exception\NodeTypeNotFoundException
     */
    public function createAction(string $title, string $blogIdentifier)
    {
        if ($title == null) {
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
        $nodeTemplate->setProperty('postType', 'news');
        $nodeTemplate->setHiddenInIndex(true);

        $published = new \DateTime();

        $nodeTemplate->setProperty('publishedAt', $published);

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
    public function deleteAction(NodeInterface $postNode)
    {
        if ($this->request->getHttpRequest()->isMethodSafe() === false) {
            $this->persistenceManager->persistAll();
        }

        /** @var NodeInterface $node */
        $postNode->remove();
        $this->redirect('index');
    }


    protected function getBlogNode(string $workspace, string $blogIdentifier)
    {
        $context = $this->contentContextFactory->create(['workspaceName' => $workspace]);

        $blogNode = $context->getNodeByIdentifier($blogIdentifier);

        if (!($blogNode instanceof NodeInterface)) {
            return;
        }

        return $blogNode;
    }
}
