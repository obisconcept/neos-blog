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

use ObisConcept\NeosBlog\Domain\Repository\PostNodeDataRepository;
use TYPO3\Flow\Annotations as Flow;
use ObisConcept\NeosBlog\Domain\Model\Category;
use ObisConcept\NeosBlog\Domain\Repository\CategoryRepository;
use TYPO3\Flow\Configuration\ConfigurationManager;
use TYPO3\Neos\Domain\Repository\DomainRepository;
use TYPO3\Neos\Domain\Repository\SiteRepository;
use TYPO3\Neos\Domain\Service\ContentContext;
use TYPO3\Neos\Domain\Service\ContentContextFactory;
use TYPO3\Neos\Domain\Service\NodeSearchServiceInterface;
use TYPO3\Neos\Service\UserService;
use TYPO3\TYPO3CR\Domain\Factory\NodeFactory;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Model\NodeType;
use TYPO3\TYPO3CR\Domain\Model\Workspace;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;
use TYPO3\TYPO3CR\Domain\Service\NodeTypeManager;

/**
 * Class PostService
 * @package ObisConcept\NeosBlog\Service
 * @Flow\Scope("singleton")
 */

class PostService {

    const BLOGPOSTTYPE = 'ObisConcept.NeosBlog:Blog';
    const POSTNODETYPE = 'ObisConcept.NeosBlog:Post';

    /**
     * @Flow\Inject
     * @var NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * @Flow\Inject
     * @var PostNodeDataRepository
     */
    protected $postNodeDataRepository;

    /**
     * @Flow\Inject
     * @var CategoryRepository
     */
    protected $categoryRepository;

    /**
     * @Flow\Inject
     * @var NodeFactory
     */
    protected $nodeFactory;

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
     * @Flow\Inject
     * @var ConfigurationManager
     */

    protected $configurationManager;


    public function testFunction($dimension) {
        $userWorkspace = $this->userService->getPersonalWorkspace();
        $nodeData = $this->postNodeDataRepository->getPostNodeData($dimension, $userWorkspace);
        
        return $this->postNodeCreator($nodeData, $dimension);
    }
    
    public function postNodeCreator(array $nodeDataRecords, $dimension) {

        $userWorkspace = $this->userService->getPersonalWorkspace();

        $context = $this->createContentContext($userWorkspace->getName(), $dimension);

        foreach ($nodeDataRecords as $nodeData) {
            $node = $this->nodeFactory->createFromNodeData($nodeData, $context);
            if ($node !== null && $node->getNodeType() == self::POSTNODETYPE) {
                $posts[$node->getPath()] = $node;
            }
        }

        return $posts;
    }

    public function getPostsFilteredByBlog(string $path, array $dimension) {
        $nodeDataRecords = $this->nodeDataRepository->findByPath($path);
        $context = $this->createContentContext($workspace = 'live', $dimension);

        $blog = array();
        foreach ($nodeDataRecords as $nodeData) {
            $node = $this->nodeFactory->createFromNodeData($nodeData, $context);
            if ($node !== null && $node->getNodeType() == self::BLOGPOSTTYPE) {
                $categoryRelatedPosts[$node->getPath()] = $node;
            }
        }

        return $categoryRelatedPosts;
    }

    /**
     * @param Workspace $workspace
     * @param array $dimensions
     * @return array
     * @throws \TYPO3\TYPO3CR\Exception\NodeConfigurationException
     */
    public function getPostsFilteredByWorkspace(Workspace $workspace, array $dimension){

        $nodeDataRecords = $this->nodeDataRepository->findByWorkspace($workspace);
        $context = $this->createContentContext($workspace->getName(), $dimension);

        $categoryRelatedPosts = array();
        foreach ($nodeDataRecords as $nodeData) {
            $node = $this->nodeFactory->createFromNodeData($nodeData, $context);
            if ($node !== null && $node->getNodeType() == self::POSTNODETYPE) {
                $categoryRelatedPosts[$node->getPath()] = $node;
            }
        }

        return $categoryRelatedPosts;
        
    }
    
    
    /**
     * @param Category $category
     * @param array $dimensions
     * @param string $workspaceName
     * @return array
     * @throws \TYPO3\TYPO3CR\Exception\NodeConfigurationException
     */
    public function getPostsWithCategoryRelation(Category $category, array $dimensions, string $workspaceName = 'live' ) {


        $categoryIdentifier = $this->categoryRepository->getCategoryIdentifier($category);
        
        $objectTypeMap = array(
            'ObisConcept\NeosBlog\Domain\Model\Category' => array($categoryIdentifier)
        );

        $nodeDataRecords = $this->nodeDataRepository->findNodesByRelatedEntities($objectTypeMap);
        $context = $this->createContentContext($workspaceName, $dimensions);

        $categoryRelatedPosts = array();
        foreach ($nodeDataRecords as $nodeData) {
            $node = $this->nodeFactory->createFromNodeData($nodeData, $context);
            if ($node !== null) {
                $categoryRelatedPosts[$node->getPath()] = $node;
            }
        }

        return $categoryRelatedPosts;
    }

    /**
     * Returns a list of postnodes filtered by a search term
     *
     * @param string $searchTerm An optional search term used for filtering the list of nodes
     * @param string $workspaceName Name of the workspace to search in, "live" by default
     * @param array $dimensions Optional list of dimensions and their values which should be used for querying
     * @param NodeInterface $contextNode a node to use as context for the search
     * @return string
     * @throws \TYPO3\Flow\Mvc\Exception\StopActionException
     */

    public function getPostsByTerm($searchTerm = '', $workspaceName = 'live', array $dimensions = array(), NodeInterface $contextNode = null) {

        $nodeTypes = array(self::POSTNODETYPE);

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
     * Returns a list of filtered postnodes
     *
     * @param string $workspaceName Name of the workspace to search in, "live" by default
     * @param array $nodeIdentifiers One or a list of node identifiers
     * @param array $dimensions Optional list of dimensions and their values which should be used for querying
     * @return mixed
     * @throws \TYPO3\Flow\Mvc\Exception\StopActionException
     */

    public function getPostsByIdentifiers($nodeIdentifiers = array(), $workspaceName = 'live', array $dimensions = array()) {

        $contentContext = $this->createContentContext($workspaceName, $dimensions);

        $nodes = array_map(function ($identifier) use ($contentContext) {
            return $contentContext->getNodeByIdentifier($identifier);
        }, $nodeIdentifiers);

        return $nodes;
    }

    /**
     * Returns one postnode with a given identifier
     *
     * @param string $workspaceName Name of the workspace to search in, "live" by default
     * @param string $nodeIdentifier One or a list of node identifiers
     * @param array $dimensions Optional list of dimensions and their values which should be used for querying
     * @return mixed
     * @throws \TYPO3\Flow\Mvc\Exception\StopActionException
     */

    public function getPostsByIdentifier($nodeIdentifier, $workspaceName = 'live', array $dimensions = array()) {

        $contentContext = $this->createContentContext($workspaceName, $dimensions);

        $node = $contentContext->getNodeByIdentifier($nodeIdentifier);

        return $node;
    }


    /**
     * Returns a list of postnodes in the users workspace
     * @param string $term
     * @param array $dimensions
     * @return array
     */

    public function getPersonalPosts(string $term , array $dimensions = array()) {
        $userWorkspaceName = $this->userService->getPersonalWorkspaceName();

        $personalPosts = $this->getPostsByTerm($term, $userWorkspaceName, $dimensions);

        return $personalPosts;

    }

    /**
     * Returns a list of postnodes filtered by identifiers
     * @param array $identifiers
     * @param array $dimensions optional
     * @return array
     */

    public function getPersonalPostsByIdentifers($identifiers, array $dimensions = array()) {
        $userWorkspaceName = $this->userService->getPersonalWorkspaceName();

        $personalPosts = $this->getPostsByIdentifiers($identifiers, $userWorkspaceName, $dimensions);

        return $personalPosts;
    }

  /**
   * Get the default Language preset label from the settings.yaml
   * @return array
   */
    public function getDefaultLanguage() {
        $languageSettings = $this->getLanguageDimensionsSettings();

        $defaultLanguage = $languageSettings['defaultPreset'];

        $defaultLanguage = $languageSettings['presets'][$defaultLanguage]['label'];

        return $defaultLanguage;
    }

    /**
     * Get the language Dimensions from settings.yaml ordered by default language first
     * @return array
     */
    public function getLanguageDimensions() {
        // Get the settings from the settings.yaml
        $languageSettings = $this->getLanguageDimensionsSettings();

        $languageDimensions = array();
        $presets = $languageSettings['presets'];

        //Get the default language preset name from the setings.yaml
        $defaultLanguage = $languageSettings['defaultPreset'];

        //Put the default language in the array at first
        $languageDimensions[$presets[$defaultLanguage]['label']] = array(
            'language' => array(
                0 => $defaultLanguage
            )
        );

        unset($presets[$defaultLanguage]);

        //Put the other language dimensions into the array
        foreach ($presets as $key => $preset) {

            $languageDimensions[$preset['label']] = array(
                'language' => array(
                    0 => $key
                )
            );
        }

        return $languageDimensions;
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

    /**
     * Return the dimensions setting for the dimension language
     * @return array
     */
    protected function getLanguageDimensionsSettings(){
        return $this->getDimensionsFromSettings('language');
    }

    /**
     * Get the Dimension Settings from the Settings.yaml
     * @param string $dimensionName optional
     * @return array
     */
    protected function getDimensionsFromSettings(string $dimensionName) {
        $settings = $this->configurationManager->getConfiguration('Settings');

        $dimensionsSettings = $settings['TYPO3']['TYPO3CR']['contentDimensions'];

        if (!$dimensionName == null) {
            return $dimensionsSettings[$dimensionName];
        } else {
            return $dimensionsSettings;
        }

    }
}