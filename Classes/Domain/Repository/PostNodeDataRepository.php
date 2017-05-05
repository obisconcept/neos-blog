<?php

namespace ObisConcept\NeosBlog\Domain\Repository;

use Doctrine\ORM\QueryBuilder;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Doctrine\Repository;
use Neos\ContentRepository\Domain\Model\Workspace;

/**
 *
 * @Flow\Scope("singleton")
 */

class PostNodeDataRepository extends Repository{

  const ENTITY_CLASSNAME = 'Neos\ContentRepository\Domain\Model\NodeData';

  /**
   * Main repository method to get data from the database.
   * This is used to get post and blog Nodes.
   *
   * @param array $dimension
   * @param Workspace $workspace
   * @param string $nodeType
   * @param string $searchTerm
   * @return array
   */

  public function getPostNodeData(array $dimension, Workspace $workspace, string $nodeType, string $searchTerm = ''){

    $workspaces = $this->collectWorkspaceAndAllBaseWorkspaces($workspace);
    $postQuery = $this->postQueryBuilder($nodeType);

    $this->addDimensionJoinConstraintsToQueryBuilder($postQuery, $dimension);
    $this->addWorkspaceJoinContraintsToQueryBuilder($postQuery, $workspaces);
    // archive and searchTerm constraints are only for Posts
    if ($nodeType == 'ObisConcept.NeosBlog:Post') {
      $this->addArchivedJoinConstraintsToQueryBuilder($postQuery, 'false');
      $this->addSearchTermJoinConstraintsToQueryBuilder($postQuery, $searchTerm);
    }

    $data = $postQuery->getQuery()->getResult();

    return $data;
  }

  protected function addSearchTermJoinConstraintsToQueryBuilder(QueryBuilder $queryBuilder, string $searchTerm) {
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

  protected function addWorkspaceJoinContraintsToQueryBuilder(QueryBuilder $queryBuilder, array $workspaces) {
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

  protected function addDimensionJoinConstraintsToQueryBuilder(QueryBuilder $queryBuilder, array $dimensions) {

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
   * @param string $archiveFilter
   */

  protected function addArchivedJoinConstraintsToQueryBuilder(QueryBuilder $queryBuilder, string $archiveFilter ) {
    $queryBuilder
      ->andWhere('n.properties LIKE :archived')
      ->setParameter('archived', '%"archived": ' .  $archiveFilter .'%');
  }

  /**
   * @param string $nodeType
   * @return QueryBuilder
   */

  protected function postQueryBuilder(string $nodeType){

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

  protected function collectWorkspaceAndAllBaseWorkspaces(Workspace $workspace) {
    $workspaces = [];
    while ($workspace !== null) {
      $workspaces[] = $workspace;
      $workspace = $workspace->getBaseWorkspace();
    }

    return $workspaces;
  }

}