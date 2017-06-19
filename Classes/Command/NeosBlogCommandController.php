<?php

namespace ObisConcept\NeosBlog\Command;

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
use ObisConcept\NeosBlog\Domain\Model\Tag;
use ObisConcept\NeosBlog\Domain\Repository\CategoryRepository;
use ObisConcept\NeosBlog\Domain\Repository\TagRepository;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\ContentRepository\Domain\Repository\WorkspaceRepository;


/**
 * Class NeosBlogCommandController
 * “
 */

class NeosBlogCommandController extends CommandController {
    /**
     * @Flow\Inject
     * @var NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * @Flow\Inject
     * @var WorkspaceRepository
     */

    protected $workspaceRepository;

    /**
     * @Flow\Inject
     * @var categoryRepository
     */

    protected $categoryRepository;
    
    /**
     * @return integer
     */

    public function countPostsCommand(){

        $posts = $this->nodeDataRepository->findByNodeType('ObisConcept.NeosBlog:Post');

        $this->outputLine(
            'I counted %s posts in the datatbase',
            array(count($posts))
            );
    }

    /**
     * @return integer
     */

    public function countBlogsCommand(){

        $blogs = $this->nodeDataRepository->findByNodeType('ObisConcept.NeosBlog:Blog');

        $this->outputLine(
            'I counted %s blogs in the datatbase',
            array(count($blogs))
        );
    }

    /**
     * @return string
     */

    public function listAllBlogsCommand(){
        $blogResult = $this->nodeDataRepository->findByNodeType('ObisConcept.NeosBlog:Blog');

        $blogs = $blogResult->toArray();

        if(count($blogs) > 0) {
            foreach ($blogs as $blog) {
                $this->outputLine(
                    'Title: %s | Path: %s | Identifier: %s',
                    array($blog->getProperties()['title'], $blog->getPath(), $blog->getIdentifier())
                );
            }
        } else {
            $this->outputLine("There are currently no Blogs in the database. Blogcount: %s", array(count($blogs)));
        }
    }


    /**
     * @return string
     */

    public function listAllPostsCommand(){
        $postQueryResult = $this->nodeDataRepository->findByNodeType('ObisConcept.NeosBlog:Post');

        $posts = $postQueryResult->toArray();

        if(count($posts) > 0) {
            foreach ($posts as $post) {
                $this->outputLine(
                    'Title: %s | Path: %s | Identifier: %s',
                    array($post->getProperties()['title'], $post->getPath(), $post->getIdentifier())
                );
            }
        } else {
            $this->outputLine("There are currently no Posts in the database");
        }

    }
    /**
     * Set Post Property
     *
     * This command changes the property of an node.
     *
     * @param string $id The unique identifier of the post
     * @param string $workspace The workspace
     * @param  string $property The Propertyname
     * @param string $value The new vlue of the nodeproperty
     * @return void
     */

    public function setPostPropertyCommand($property, $value, $id, $workspace){

        $workspaceObject = $this->workspaceRepository->findByName($workspace)->toArray()[0];

        $postQueryResult = $this->nodeDataRepository->findOneByIdentifier($id, $workspaceObject);

        $postQueryResult->setProperty($property, $value);
        $newPostQueryResult= $postQueryResult->getProperties()[$property];

        $this->outputLine(
            'The Property [%s] was changed to: %s.',
            array($property, $newPostQueryResult)
        );
    }

    /**
     * Add Category to the database
     *
     * This command changes the property of an node.
     *
     * @param string $name The readable Name of the Category
     * @param string $name The readable Name of the Category
     * @throws \Neos\Flow\Persistence\Exception\IllegalObjectTypeException
     */

    public function addCategoryCommand(string $name, string $description) {
        $category = new Category();
        $category->setName($name);
        $category->setDescription($description);
        $category->setCreated(new \DateTime('now'));

        $this->categoryRepository->add($category);

        $this->outputLine(
            'The category %s was added to the database on %s!',
            array($category->getName(), $category->getCreated()->format('d-m-y'))
        );
    }


    public function listCategoriesCommand() {

        if($this->categoryRepository->countAll() > 0) {
            foreach ($this->categoryRepository->findAll() as $category) {
                $this->outputLine(
                    '%s',
                    array($category->getName())
                );
            }
        } else {
            $this->outputLine("There are currently no categories in the database");
        }
    }

    public function deleteAllCategories(){

        if($this->categoryRepository->countAll() > 0) {
            $this->categoryRepository->removeAll();
        } else {
            $this->outputLine("There are currently no categories in the database");
        }
    }
}
