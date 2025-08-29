<?php

namespace App\Repository;

use App\Entity\TestLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TestLog>
 *
 * @method TestLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method TestLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method TestLog[]    findAll()
 * @method TestLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TestLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TestLog::class);
    }

    public function save(TestLog $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(TestLog $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findRecentLogs(int $limit = 10): array
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
