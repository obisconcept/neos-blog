<?php

namespace ObisConcept\NeosBlog\Domain\Repository;

use Doctrine\ORM\QueryBuilder;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\Doctrine\Repository;
use TYPO3\TYPO3CR\Domain\Model\NodeData;
use TYPO3\TYPO3CR\Domain\Model\Workspace;

/**
 *
 * @Flow\Scope("singleton")
 */

class PostNodeDataRepository extends Repository{

  const ENTITY_CLASSNAME = 'TYPO3\TYPO3CR\Domain\Model\NodeData';

  const POST_NODETYPE = 'ObisConcept.NeosBlog:Post';


  public function getPostNodeData(array $dimension, Workspace $workspace){

    $workspaces = $this->collectWorkspaceAndAllBaseWorkspaces($workspace);

    $postQuery = $this->postQueryBuilder();

    $this->addDimensionJoinConstraintsToQueryBuilder($postQuery, $dimension);
    $this->addArchivedJoinConstraintsToQueryBuilder($postQuery, 'false');
    $this->addWorkspaceJoinContraintsToQueryBuilder($postQuery, $workspaces);

    $data = $postQuery->getQuery()->getResult();
    
    return $data;
  }

  /**
   * Limit the QueryResult to the given Workspaces
   * 
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
        'EXISTS (SELECT ' . $dimensionAlias . ' FROM TYPO3\TYPO3CR\Domain\Model\NodeDimension ' . $dimensionAlias . ' WHERE ' . $dimensionAlias . '.nodeData = n AND ' . $dimensionAlias . '.name = \'' . $dimensionName . '\' AND ' . $dimensionAlias . '.value IN (:' . $dimensionAlias . ')) ' .
        'OR NOT EXISTS (SELECT ' . $dimensionAlias . '_c FROM TYPO3\TYPO3CR\Domain\Model\NodeDimension ' . $dimensionAlias . '_c WHERE ' . $dimensionAlias . '_c.nodeData = n AND ' . $dimensionAlias . '_c.name = \'' . $dimensionName . '\')'
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

  protected function postQueryBuilder(){

    $queryBuilder = $this->createQueryBuilder('post');

    $queryBuilder->select('n')
      ->from(NodeData::class, 'n')
      ->where('n.nodeType IN (:nodeType)')
      ->setParameter('nodeType', self::POST_NODETYPE);
    
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