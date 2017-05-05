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

use Neos\Flow\Annotations as Flow;
use Neos\Neos\Service\UserService;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Domain\Service\ContentContext;
use Neos\Neos\Domain\Service\ContentContextFactory;
use Neos\ContentRepository\Domain\Factory\NodeFactory;
use ObisConcept\NeosBlog\Domain\Repository\PostNodeDataRepository;

/**
 * Class PostService
 * @package ObisConcept\NeosBlog\Service
 * @Flow\Scope("singleton")
 */

class PostService {
  
    const POST_NODETYPE = 'ObisConcept.NeosBlog:Post';

    /**
     * @Flow\Inject
     * @var PostNodeDataRepository
     */
    protected $postNodeDataRepository;

    /**
     * @Flow\Inject
     * @var NodeFactory
     */
    protected $nodeFactory;

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
     * @var ConfigurationManager
     */

    protected $configurationManager;


  /**
   * @param $dimension
   * @param $searchTerm
   * @return array
   */
  public function getPersonalPosts($dimension, $searchTerm) {
        $userWorkspace = $this->userService->getPersonalWorkspace();
        $nodeData = $this->postNodeDataRepository->getPostNodeData($dimension, $userWorkspace, self::POST_NODETYPE, $searchTerm);
        
        return $this->postNodeCreator($nodeData, $dimension);
    }
    
    public function postNodeCreator(array $nodeDataRecords, $dimension) {

        $userWorkspace = $this->userService->getPersonalWorkspace();
        $context = $this->createContentContext($userWorkspace->getName(), $dimension);

        $posts = array();

        foreach ($nodeDataRecords as $nodeData) {
            $node = $this->nodeFactory->createFromNodeData($nodeData, $context);
            if ($node !== null && $node->getNodeType() == self::POST_NODETYPE) {
                $posts[$node->getPath()] = $node;
            }
        }

        return $posts;
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

        // Get the default language preset name from the settings.yaml
        $defaultLanguage = $languageSettings['defaultPreset'];

        // Put the default language in the array at first
        $languageDimensions[$presets[$defaultLanguage]['label']] = array(
            'language' => array(
                0 => $defaultLanguage
            )
        );

        unset($presets[$defaultLanguage]);

        // Put the other language dimensions into the array
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

        $dimensionsSettings = $settings['Neos']['ContentRepository']['contentDimensions'];

        if (!$dimensionName == null) {
            return $dimensionsSettings[$dimensionName];
        } else {
            return $dimensionsSettings;
        }

    }
}