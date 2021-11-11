<?php

namespace App\Tests;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class MKernelTestCase extends KernelTestCase
{
    /**
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws \Doctrine\ORM\ORMException
     */
    protected function truncateEntities(array $entities)
    {
        $container = self::getContainer();
        $em = $container->get(EntityManagerInterface::class);
        foreach ($entities as $entity) {
            $em->createQueryBuilder()
                ->delete($entity, 'f')
                ->getQuery()
                ->execute();
        }
        $em->flush();
    }
}