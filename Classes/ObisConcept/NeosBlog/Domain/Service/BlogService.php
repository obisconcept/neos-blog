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

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Neos\Domain\Repository\DomainRepository;
use TYPO3\Neos\Domain\Repository\SiteRepository;
use TYPO3\Neos\Domain\Service\ContentContext;
use TYPO3\Neos\Domain\Service\ContentContextFactory;
use TYPO3\Neos\Domain\Service\NodeSearchServiceInterface;
use TYPO3\Neos\Service\UserService;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Model\NodeType;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;
use TYPO3\TYPO3CR\Domain\Service\NodeTypeManager;

/**
 * Class BlogService
 * @package ObisConcept\NeosBlog\Service
 * @Flow\Scope("singleton")
 */

class BlogService {

    const BLOGNODETYPE = 'ObisConcept.NeosBlog:Blog';

    /**
     * @Flow\Inject
     * @var NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * @Flow\Inject
     * @var NodeSearchServiceInterface
     */
    protected $nodeSearchService;

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
     * @var NodeTypeManager
     */
    protected $nodeTypeManager;

    /**
     * @Flow\Inject
     * @var UserService
     */
    protected $userService;


    /**
     * Returns a list of postnodes in the users workspace
     * @param array $dimensions optional
     * @return array
     */
    public function getPersonalBlogs(array $dimensions= array()) {
        $workspaceName = $this->userService->getPersonalWorkspaceName();
        
        $blogNodes = $this->getBlogsByTerm(' ', $workspaceName, $dimensions);
        
        return $blogNodes;

    }


    /**
     * Returns a list of blognodes filtered by a search term
     *
     * @param string $searchTerm An optional search term used for filtering the list of nodes
     * @param string $workspaceName Name of the workspace to search in, "live" by default
     * @param array $dimensions Optional list of dimensions and their values which should be used for querying
     * @param NodeInterface $contextNode a node to use as context for the search
     * @return string
     * @throws \TYPO3\Flow\Mvc\Exception\StopActionException
     */

    public function getBlogsByTerm($searchTerm = '', $workspaceName = 'live', array $dimensions = array(), NodeInterface $contextNode = null) {

        $nodeTypes = array(self::BLOGNODETYPE);

        $searchableNodeTypeNames = array();
        foreach ($nodeTypes as $nodeTypeName) {
            if (!$this->nodeTypeManager->hasNodeType($nodeTypeName)) {
                $this->throwStatus(400, sprintf('Unknown node type "%s"', $nodeTypeName));
            }

            $searchableNodeTypeNames[$nodeTypeName] = $nodeTypeName;
            /** @var NodeType $subNodeType */
            foreach ($this->nodeTypeManager->getSubNodeTypes($nodeTypeName, false) as $subNodeTypeName => $subNodeType) {
                $searchableNodeTypeNames[$subNodeTypeName] = $subNodeTypeName;
            }
        }

        $contentContext = $this->createContentContext($workspaceName, $dimensions);

        $nodes = $this->nodeSearchService->findByProperties($searchTerm, $searchableNodeTypeNames, $contentContext, $contextNode);

        return $nodes;

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