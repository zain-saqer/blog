<?php

namespace App\Tests\Entity;

use App\Entity\Blog;
use App\Tests\MKernelTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BlogTest extends MKernelTestCase
{
    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function testValidationWorks()
    {
        self::bootKernel();
        $this->truncateEntities([
            Blog::class,
        ]);


        $container = self::getContainer();
        $validator = $container->get(ValidatorInterface::class);
        $em = $container->get(EntityManagerInterface::class);

        $blog = new Blog();

        self::assertCount(3, $validator->validate($blog));

        $blog->setBody("Body");
        $blog->setCreatedAt(new \DateTimeImmutable());
        $blog->setTitle("Title");

        self::assertCount(0, $validator->validate($blog));

        $em->persist($blog);
        $em->flush();


        $blog2 = new Blog();
        $blog2->setTitle("Title");
        $blog2->setBody("Body");
        $blog2->setCreatedAt(new \DateTimeImmutable());

        // UniqueEntity on title error
        self::assertCount(1, $validator->validate($blog2));

        $blog2->setTitle("Title 1");

        self::assertCount(0, $validator->validate($blog2));
    }
}