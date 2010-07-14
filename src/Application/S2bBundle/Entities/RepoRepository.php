<?php

namespace Application\S2bBundle\Entities;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;

class RepoRepository extends EntityRepository
{
    public function search($query)
    {
        $pattern = '%'.str_replace(' ', '%', $query).'%';

        $qb = $this->createQueryBuilder('e')->orderBy('e.score', 'DESC');
        $qb->where($qb->expr()->orx(
            $qb->expr()->like('e.username', ':username'),
            $qb->expr()->like('e.name', ':name'),
            $qb->expr()->like('e.description', ':description')
        ));
        $qb->setParameters(array('username' => $pattern, 'name' => $pattern, 'description' => $pattern));
        return $qb->getQuery()->execute();
    }

    public function findAllSortedBy($field)
    {
        $query = $this->getSortedByQuery($field);

        return $query->execute();
    }

    public function getSortedByQuery($field)
    {
        $qb = $this->createQueryBuilder('e');
        $qb->orderBy('e.'.$field, 'name' === $field ? 'asc' : 'desc');
        return $qb->getQuery();
    }

    public function count()
    {
        return $this->_em->createQuery('SELECT COUNT(e.id) FROM '.$this->getEntityName().' e')->getSingleScalarResult();
    }

    public function getLastCommits($nb)
    {
        $repos = $this->findByLastCommitAt($nb);
        $commits = array();
        foreach($repos as $repo) {
            $commits = array_merge($commits, $repo->getLastCommits());
        }
        usort($commits, function($a, $b)
        {
            return strtotime($a['committed_date']) < strtotime($b['committed_date']);
        });
        $commits = array_slice($commits, 0, 5);

        return $commits;
    }

    public function findByLastCommitAt($nb)
    {
        return $this->createQueryBuilder('b')->orderBy('b.lastCommitAt', 'DESC')->getQuery()->setMaxResults($nb)->execute();
    }

    public function findOneByUsernameAndName($username, $name)
    {
        try {
            return $this->createQueryBuilder('e')
                ->where('e.username = :username')
                ->andWhere('e.name = :name')
                ->setParameter('username', $username)
                ->setParameter('name', $name)
                ->getQuery()
                ->getSingleResult();
        }
        catch(NoResultException $e) {
            return null;
        }
    }
}
