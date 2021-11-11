<?php

namespace App\Tests\Entity;

use App\Entity\Blog;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BlogTest extends KernelTestCase
{
    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function testValidationWorks()
    {
        self::bootKernel();
        $container = self::getContainer();
        $validator = $container->get(ValidatorInterface::class);
        $em = $container->get(EntityManagerInterface::class);

        $blog = new Blog();

        self::assertCount(2, $validator->validate($blog));

        $blog->setBody("Body");
        $blog->setTitle("Title");

        self::assertCount(0, $validator->validate($blog));

        $blog->setCreatedAt(new DateTimeImmutable());
        $em->persist($blog);
        $em->flush();


        $blog2 = new Blog();
        $blog2->setTitle("Title");
        $blog2->setBody("Body");
        $blog2->setCreatedAt(new DateTimeImmutable());

        // UniqueEntity on title error
        self::assertCount(1, $validator->validate($blog2));

        $blog2->setTitle("Title 1");

        self::assertCount(0, $validator->validate($blog2));
    }
}