<?php

namespace App\Repository;

use App\Entity\Author;
use App\Entity\PostAlert;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PostAlert>
 */
class PostAlertRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PostAlert::class);
    }

    /**
     * @return PostAlert[]
     */
    public function findByAuthor(Author $author): array
    {
        return $this->createQueryBuilder('pa')
            ->andWhere('pa.author = :author')
            ->setParameter('author', $author)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return PostAlert[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('pa')
            ->andWhere('pa.user = :user')
            ->setParameter('user', $user)
            ->orderBy('pa.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByUserAndAuthor(User $user, Author $author): ?PostAlert
    {
        return $this->createQueryBuilder('pa')
            ->andWhere('pa.user = :user')
            ->andWhere('pa.author = :author')
            ->setParameter('user', $user)
            ->setParameter('author', $author)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
