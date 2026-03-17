<?php

namespace App\Repository;

use App\Entity\Post;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Post>
 */
class PostRepository extends ServiceEntityRepository
{
    public const POSTS_PER_PAGE = 5;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    /**
     * @return Post[]
     */
    public function findAllOrderedByPublishedAt(): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.publishedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array{posts: Post[], total: int, pages: int}
     */
    public function findPaginatedAndSearch(int $page = 1, ?string $search = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->orderBy('p.publishedAt', 'DESC');

        $qb->andWhere('p.status = :status')
            ->setParameter('status', Post::STATUS_PUBLISHED);

        if (null !== $search && '' !== trim($search)) {
            $qb->andWhere('(p.title LIKE :search OR p.content LIKE :search)')
                ->setParameter('search', '%' . trim($search) . '%');
        }

        $total = (int) (clone $qb)->select('COUNT(p.id)')->getQuery()->getSingleScalarResult();
        $pages = (int) ceil($total / self::POSTS_PER_PAGE);
        $page = max(1, min($page, $pages ?: 1));

        $posts = $qb
            ->select('p')
            ->setFirstResult(($page - 1) * self::POSTS_PER_PAGE)
            ->setMaxResults(self::POSTS_PER_PAGE)
            ->getQuery()
            ->getResult();

        return [
            'posts' => $posts,
            'total' => $total,
            'pages' => $pages,
            'page' => $page,
        ];
    }
}
