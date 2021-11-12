<?php

namespace App\Tests\Repository;

use App\Entity\Blog;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class BlogRepositoryTest extends KernelTestCase
{
    public function testSomething(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $entityManager = self::getContainer()->get('doctrine.orm.default_entity_manager');
        $blogRepo = $entityManager->getRepository(Blog::class);

        self::assertCount(0, $blogRepo->recentBlogs());

        for ($i = 0; $i < 3; $i++) {
            $blog = new Blog();
            $blog->setTitle("Title $i");
            $blog->setBody("Body $i");
            $blog->setCreatedAt(new \DateTimeImmutable());

            $entityManager->persist($blog);
            $entityManager->flush();
        }

        self::assertCount(3, $blogRepo->recentBlogs());
        self::assertCount(2, $blogRepo->recentBlogs(2));


        for ($i = 3; $i < 10; $i++) {
            $blog = new Blog();
            $blog->setTitle("Title $i");
            $blog->setBody("Body $i");
            $blog->setCreatedAt(new \DateTimeImmutable());

            $entityManager->persist($blog);
            $entityManager->flush();
        }

        self::assertCount(5, $blogRepo->recentBlogs());
        self::assertCount(2, $blogRepo->recentBlogs(2));

    }
}
