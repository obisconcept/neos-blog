<?php
namespace ObisConcept\NeosBlog\Domain\Service;

/*
 * This file is part of the ObisConcept.NeosBlog package.
 *
 * (c) Dennis Schröder
 *
 * Based on Robert Lemkes https://github.com/robertlemke/RobertLemke.Plugin.Blog
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ResourceManagement\ResourceManager;

/**
 * A service which can render specific views of blog related content
 *
 * @Flow\Scope("singleton")
 */
class ContentService
{
    const NODEPATH = 'content';
    const NODETYPE = 'Neos.Neos:Content';

    /**
     * @Flow\Inject
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
     * Renders a teaser text with up to $maximumLength characters, with an outermost <p> and some more tags removed,
     * from the given Node (fetches the first Neos.NodeTypes:Text childNode as a base).
     *
     * If '<!-- read more -->' is found, the teaser will be the preceding content and $maximumLength is ignored.
     *
     * @param NodeInterface $node
     * @param integer $maximumLength
     * @return mixed
     */
    public function renderTeaser(NodeInterface $node, $maximumLength = 500)
    {
        $stringToTruncate = '';

        /** @var NodeInterface $contentNode */
        foreach ($node->getNode('content')->getChildNodes('Neos.NodeTypes:Text') as $contentNode) {
            foreach ($contentNode->getProperties() as $propertyValue) {
                if (!is_object($propertyValue) || method_exists($propertyValue, '__toString')) {
                    $stringToTruncate .= $propertyValue;
                }
            }
        }

        $jumpPosition = strpos($stringToTruncate, '<!-- read more -->');

        if ($jumpPosition !== false) {
            return $this->stripUnwantedTags(substr($stringToTruncate, 0, ($jumpPosition - 1)));
        }

        $jumpPosition = strpos($stringToTruncate, '</p>');
        if ($jumpPosition !== false && $jumpPosition < ($maximumLength + 100)) {
            return $this->stripUnwantedTags(substr($stringToTruncate, 0, $jumpPosition + 4));
        }

        if (strlen($stringToTruncate) > $maximumLength) {
            return substr($this->stripUnwantedTags($stringToTruncate), 0, $maximumLength + 1) . ' ...';
        } else {
            return $this->stripUnwantedTags($stringToTruncate);
        }
    }

    /**
     * Gets the first Image in the Post ContentCollection as the Post Image
     * @param NodeInterface $node
     * @return mixed
     */
    public function getPostImageResourceObject(NodeInterface $node)
    {
        $childNodes = $this->getChildNodeContent($node, self::NODEPATH, self::NODETYPE);

        $imageResource = array();

        /** @var NodeInterface $childNode */
        foreach ($childNodes as $childNode) {
            if ($childNode->getNodeType()->isOfType('Neos.NodeTypes:TextWithImage')) {
                $imageResource[] = $childNode->getProperty('image');
            } elseif ($childNode->getNodeType()->isOfType('Neos.NodeTypes:Image')) {
                $imageResource[] = $childNode->getProperty('image');
            } else {
                if (!$childNode->getProperty('image')) {
                    $imageResource[] = $childNode->getProperty('image');
                }
            }
        }

        return $imageResource;
    }

    /**
     * Gets the first Text in the Post ContentCollection as the Post Text Teaser
     * @param NodeInterface $node
     * @param int $maximumLength
     * @return mixed
     */
    public function getPostTextTeaser(NodeInterface $node, $maximumLength = 500)
    {
        $childNodes = $this->getChildNodeContent($node, self::NODEPATH, self::NODETYPE);

        $teaserText = array();

        /** @var NodeInterface $childNode */
        foreach ($childNodes as $childNode) {
            if ($childNode->getNodeType()->isOfType('Neos.NodeTypes:Text')) {
                if ($childNode->getProperty('text') !== null) {
                    $teaserText[] = $childNode->getProperty('text');
                }
            } elseif ($childNode->getNodeType()->isOfType('Neos.NodeTypes:TextWithImage')) {
                if ($childNode->getProperty('text') !== null) {
                    $teaserText[] = $childNode->getProperty('text');
                }
            } else {
                if (!$childNode->getProperty('text')) {
                    if ($childNode->getProperty('text') !== null) {
                        $teaserText[] = $childNode->getProperty('text');
                    }
                }
            }
        }

        if (!$teaserText == null) {
            $textToTrim = $teaserText[0];

            $jumpPosition = strpos($textToTrim, '<!-- read more -->');

            if ($jumpPosition !== false) {
                return $this->stripUnwantedTags(substr($textToTrim, 0, ($jumpPosition - 1)));
            }

            $jumpPosition = strpos($teaserText[0], '</p>');
            if ($jumpPosition !== false && $jumpPosition < ($maximumLength + 100)) {
                return $this->stripUnwantedTags(substr($textToTrim, 0, $jumpPosition + 4));
            }

            if (strlen($teaserText[0]) > $maximumLength) {
                return substr($this->stripUnwantedTags($textToTrim), 0, $maximumLength + 1) . ' ...';
            } else {
                return $this->stripUnwantedTags($textToTrim);
            }
        } else {
            return;
        }
    }

    /**
     * @param NodeInterface $node
     * @return string
     */
    public function renderContent(NodeInterface $node)
    {
        $content = '';

        /** @var NodeInterface $contentNode */
        foreach ($node->getNode('content')->getChildNodes('Neos.Neos:Content') as $contentNode) {
            if ($contentNode->getNodeType()->isOfType('Neos.NodeTypes:TextWithImage')) {
                $propertyValue = $contentNode->getProperty('image');
                $attributes = array(
                    'width="' . $propertyValue->getWidth() . '"',
                    'height="' . $propertyValue->getHeight() . '"',
                    'src="' . $this->resourceManager->getPublicPersistentResourceUri($propertyValue->getResource()) . '"',
                );
                $content .= $contentNode->getProperty('text');
                $content .= '<img ' . implode(' ', $attributes) . '/>';
            } elseif ($contentNode->getNodeType()->isOfType('Neos.NodeTypes:Image')) {
                $propertyValue = $contentNode->getProperty('image');
                $attributes = array(
                    'width="' . $propertyValue->getWidth() . '"',
                    'height="' . $propertyValue->getHeight() . '"',
                    'src="' . $this->resourceManager->getPublicPersistentResourceUri($propertyValue->getResource()) . '"',
                );
                $content .= '<img ' . implode(' ', $attributes) . '/>';
            } else {
                foreach ($contentNode->getProperties() as $propertyValue) {
                    if (!is_object($propertyValue) || method_exists($propertyValue, '__toString')) {
                        $content .= $propertyValue;
                    }
                }
            }
        }

        return $this->stripUnwantedTags($content);
    }

    /**
     * Removes a, span, strong, b, blockquote tags from $content.
     *
     * If the content starts with <p> and ends with </p> these tags are stripped as well.
     *
     * Non-breaking space entities are replaced by a single space character.
     *
     * @param string $content The original content
     * @return string The stripped content
     */
    protected function stripUnwantedTags($content)
    {
        $content = trim($content);
        $content = preg_replace(
            [
                '/\\<a [^\\>]+\\>/',
                '/\<\\/a\\>/',
                '/\\<span[^\\>]+\\>/',
                '/\\<\\/span>]+\\>/',
                '/\\<\\\\?(strong|b|blockquote)\\>/',
            ],
            '',
            $content
        );
        $content = str_replace('&nbsp;', ' ', $content);

        if (substr($content, 0, 3) === '<p>' && substr($content, -4, 4) === '</p>') {
            $content = substr($content, 3, -4);
        }

        return trim($content);
    }

    /**
     * @param NodeInterface $node
     * @param string $nodePath
     * @param string $postType
     * @return array
     */
    protected function getChildNodeContent(NodeInterface $node, string $nodePath, string $postType)
    {
        return $node->getNode($nodePath)->getChildNodes($postType);
    }
}
