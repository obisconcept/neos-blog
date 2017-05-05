<?php

namespace ObisConcept\NeosBlog\Domain\Service;

/*
     * This file is part of the ObisConcept.NeosBlog package.
     *
     * (c) Dennis Schröder
     *
     * This package is Open Source Software. For the full copyright and license
     * information, please view the LICENSE file which was distributed with this
     * source code.
     */

use Neos\ContentRepository\Domain\Factory\NodeFactory;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Domain\Service\ContentContext;
use Neos\Neos\Domain\Service\ContentContextFactory;
use Neos\Neos\Service\UserService;
use ObisConcept\NeosBlog\Domain\Repository\PostNodeDataRepository;

/**
 * Class BlogService
 * @package ObisConcept\NeosBlog\Service
 * @Flow\Scope("singleton")
 */

class BlogService {

    const BLOG_NODETYPE = 'ObisConcept.NeosBlog:Blog';

    /**
     * @Flow\Inject
     * @var PostNodeDataRepository
     */
    protected $postNodeDataRepository;

    /**
     * @Flow\Inject
     * @var ContentContextFactory
     */
    protected $contextFactory;

    /**
     * @Flow\Inject
     * @var DomainRepository
     */
    protected $domainRepository;

    /**
     * @Flow\Inject
     * @var SiteRepository
     */
    protected $siteRepository;

    /**
     * @Flow\Inject
     * @var UserService
     */
    protected $userService;

    /**
     * @Flow\Inject
     * @var NodeFactory
     */
    protected $nodeFactory;


    /**
     * Returns a list of blognodes in the users workspace
     * @param array $dimensions optional
     * @return array
     */

    public function getPersonalBlogs(array $dimensions= array()) {
        // to show only user related blogs get the personal Workspace
        $workspaceName = $this->userService->getPersonalWorkspace();

        // get the blogNodes
        $blogNodeData = $this->postNodeDataRepository->getPostNodeData($dimensions, $workspaceName, self::BLOG_NODETYPE );

      return $this->postNodeCreator($blogNodeData, $dimensions);

    }

  /**
   * Create a Node
   *
   * @param array $nodeDataRecords
   * @param $dimension
   * @return mixed
   */
  public function postNodeCreator(array $nodeDataRecords, $dimension) {

    $userWorkspace = $this->userService->getPersonalWorkspace();

    $context = $this->createContentContext($userWorkspace->getName(), $dimension);

    foreach ($nodeDataRecords as $nodeData) {
      $node = $this->nodeFactory->createFromNodeData($nodeData, $context);
      if ($node !== null && $node->getNodeType() == self::BLOG_NODETYPE) {
        $posts[$node->getPath()] = $node;
      }
    }

    return $posts;
  }


  /**
     * Create a ContentContext based on the given workspace name
     *
     * @param string $workspaceName Name of the workspace to set for the context
     * @param array $dimensions Optional list of dimensions and their values which should be set
     * @return ContentContext
     */

    protected function createContentContext($workspaceName, array $dimensions = array()) {
        $contextProperties = array(
            'workspaceName' => $workspaceName,
            'invisibleContentShown' => true,
            'inaccessibleContentShown' => true
        );

        if ($dimensions !== array()) {
            $contextProperties['dimensions'] = $dimensions;
            $contextProperties['targetDimensions'] = array_map(function ($dimensionValues) {
                return array_shift($dimensionValues);
            }, $dimensions);
        }

        $currentDomain = $this->domainRepository->findOneByActiveRequest();
        if ($currentDomain !== null) {
            $contextProperties['currentSite'] = $currentDomain->getSite();
            $contextProperties['currentDomain'] = $currentDomain;
        } else {
            $contextProperties['currentSite'] = $this->siteRepository->findFirstOnline();
        }

        return $this->contextFactory->create($contextProperties);
    }
}