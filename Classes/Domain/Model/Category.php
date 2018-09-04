<?php

namespace ObisConcept\NeosBlog\Domain\Model;

/*
 * This file is part of the ObisConcept.NeosBlog package.
 *
 * (c) Dennis Schröder
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Service;

/**
 * Class Category
 * @package ObisConcept\NeosBlog\Domain\Model
 * @Flow\Entity
 */

class Category
{

    /**
     * @var string
     * @Flow\Validate(type="NotEmpty")
     * @Flow\Validate(type="StringLength", options={ "minimum"=3, "maximum"=80 })
     */

    protected $name;

    /**
     * @var string
     * @Flow\Validate(type="NotEmpty")
     */

    protected $description;

    /**
     * @var \DateTime
     */

    protected $created;

    /**
     * @var string
     * @Flow\Validate(type="NotEmpty")
     */

    protected $author;

    /**
     * Where all the Magic begins
     */

    public function __construct()
    {
        $this->setCreated(new DateTime('now'));
    }

    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param \DateTime $created
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @param string $author
     */
    public function setAuthor($author)
    {
        $this->author = $author;
    }
}
