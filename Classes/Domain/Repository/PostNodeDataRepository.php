<?php

namespace ObisConcept\NeosBlog\Domain\Repository;

use Doctrine\ORM\QueryBuilder;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Doctrine\Repository;

/**
 *
 * @Flow\Scope("singleton")
 */

class PostNodeDataRepository extends Repository
{
    const ENTITY_CLASSNAME = 'Neos\ContentRepository\Domain\Model\NodeData';

    /**
     * Main repository method to get data from the database.
     * This is used to get post and blog Nodes.
     *
     * @param array $dimension
     * @param Workspace $workspace
     * @param string $nodeType
     * @param string $searchTerm
     * @param bool $showArchived
     * @return array
     */

    public function getPostNodeData(array $dimension, Workspace $workspace, string $nodeType, string $searchTerm = '', bool $showArchived)
    {

        // get workspaceobjects
        $workspaces = $this->collectWorkspaceAndAllBaseWorkspaces($workspace);
        // get base queryBuilder
        $postQuery = $this->postQueryBuilder($nodeType);

        // add constraints
        $this->addDimensionJoinConstraintsToQueryBuilder($postQuery, $dimension);
        $this->addWorkspaceJoinContraintsToQueryBuilder($postQuery, $workspaces);

        // some constraints are only for Posts
        if ($nodeType == 'ObisConcept.NeosBlog:Post') {
            $this->addArchivedJoinConstraintsToQueryBuilder($postQuery, $showArchived);

            // add the searchTerm constraint only when not empty searchTerm
            if ($searchTerm != '') {
                $this->addSearchTermJoinConstraintsToQueryBuilder($postQuery, $searchTerm);
            }
            $this->sortPosts($postQuery);
        }

        // finally get the query result and return data
        $data = $postQuery->getQuery()->getResult();

        return $data;
    }

    /**
     * Sort Posts by creationDateTime as default
     *
     * @param QueryBuilder $queryBuilder
     */
    protected function sortPosts(QueryBuilder $queryBuilder)
    {
        $queryBuilder->orderBy('n.creationDateTime', 'DESC');
    }

    protected function addSearchTermJoinConstraintsToQueryBuilder(QueryBuilder $queryBuilder, string $searchTerm)
    {
        $queryBuilder
            ->andWhere('n.properties LIKE :searchTerm')
            ->setParameter('searchTerm', '%' . $searchTerm . '%');
    }

    /**
     * Limit the QueryResult to the given Workspaces
     *
     * @param QueryBuilder $queryBuilder
     * @param array $workspaces
     */

    protected function addWorkspaceJoinContraintsToQueryBuilder(QueryBuilder $queryBuilder, array $workspaces)
    {
        $queryBuilder
            ->andWhere('n.workspace IN (:workspaces)')
            ->setParameter('workspaces', $workspaces);
    }

    /**
     * If $dimensions is not empty, adds join constraints to the given $queryBuilder
     * limiting the query result to matching hits.
     *
     * @param QueryBuilder $queryBuilder
     * @param array $dimensions
     * @return void
     */

    protected function addDimensionJoinConstraintsToQueryBuilder(QueryBuilder $queryBuilder, array $dimensions)
    {
        $count = 0;
        foreach ($dimensions as $dimensionName => $dimensionValues) {
            $dimensionAlias = 'd' . $count;
            $queryBuilder->andWhere(
                'EXISTS (SELECT ' . $dimensionAlias . ' FROM Neos\ContentRepository\Domain\Model\NodeDimension ' . $dimensionAlias . ' WHERE ' . $dimensionAlias . '.nodeData = n AND ' . $dimensionAlias . '.name = \'' . $dimensionName . '\' AND ' . $dimensionAlias . '.value IN (:' . $dimensionAlias . ')) ' .
                'OR NOT EXISTS (SELECT ' . $dimensionAlias . '_c FROM Neos\ContentRepository\Domain\Model\NodeDimension ' . $dimensionAlias . '_c WHERE ' . $dimensionAlias . '_c.nodeData = n AND ' . $dimensionAlias . '_c.name = \'' . $dimensionName . '\')'
            );
            $queryBuilder->setParameter($dimensionAlias, $dimensionValues);
            $count++;
        }
    }

    /**
     * Filters postNodes by Dimensions
     *
     * @param QueryBuilder $queryBuilder
     * @param bool $archiveFilter
     */

    protected function addArchivedJoinConstraintsToQueryBuilder(QueryBuilder $queryBuilder, bool $archiveFilter)
    {
        $queryBuilder
            ->andWhere('n.properties LIKE :archived')
            // We had to use var_export() to convert the boolean to string cuz we are looking in the properties
            // of an node which is a json_string.
            ->setParameter('archived', '%"archived": ' . var_export($archiveFilter, true) . '%');
    }

    /**
     * @param string $nodeType
     * @return QueryBuilder
     */

    protected function postQueryBuilder(string $nodeType)
    {
        $queryBuilder = $this->createQueryBuilder('n');

        $queryBuilder->select('n')
            ->where('n.nodeType IN (:nodeType)')
            ->setParameter('nodeType', $nodeType);

        return $queryBuilder;
    }

    /**
     * Returns an array that contains the given workspace and all base (parent) workspaces of it.
     *
     * @param Workspace $workspace
     * @return array
     */

    protected function collectWorkspaceAndAllBaseWorkspaces(Workspace $workspace)
    {
        $workspaces = [];
        while ($workspace !== null) {
            $workspaces[] = $workspace;
            $workspace = $workspace->getBaseWorkspace();
        }

        return $workspaces;
    }
}
