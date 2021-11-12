<?php

namespace App\Tests\Controller;

use App\Entity\Blog;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class BlogControllerTest extends WebTestCase
{
    public function testNew(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/new-blog');

        $this->assertResponseIsSuccessful();

        $button = $crawler->selectButton('New');
        $form = $button->form();

        $client->submit($form, [
            'form[title]' => '',
            'form[body]' => '',
        ]);
        self::assertEquals(422, $client->getResponse()->getStatusCode());

        $client->submit($form, [
            'form[title]' => 'Title',
            'form[body]' => 'Body',
        ]);
        self::assertEquals(302, $client->getResponse()->getStatusCode());

        $crawler = $client->request('GET', '/new-blog');
        $this->assertResponseIsSuccessful();
        $button = $crawler->selectButton('New');
        $form = $button->form();

        $client->submit($form, [
            'form[title]' => 'Title',
            'form[body]' => 'Body',
        ]);
        self::assertEquals(422, $client->getResponse()->getStatusCode());

        $client->submit($form, [
            'form[title]' => 'Title 1',
            'form[body]' => 'Body',
        ]);
        self::assertEquals(302, $client->getResponse()->getStatusCode());
    }

    public function testList(): void
    {
        $client = static::createClient();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $crawler = $client->request('GET', '/');
        $this->assertResponseIsSuccessful();
        $text = $crawler->filter('p > span')->first()->text();
        self::assertEquals("No blogs", $text);

        $blog = new Blog();
        $blog->setBody("Body");
        $blog->setTitle("Title");
        $blog->setCreatedAt(new DateTimeImmutable());
        $entityManager->persist($blog);

        $blog = new Blog();
        $blog->setBody("Body");
        $blog->setTitle("Title 2");
        $blog->setCreatedAt(new DateTimeImmutable());
        $entityManager->persist($blog);

        $entityManager->flush();

        $crawler = $client->request('GET', '/');
        $this->assertResponseIsSuccessful();
        $names = $crawler->filter('ul > li > a')->each(function (Crawler $node, $i) {
            return $node->text();
        });

        self::assertTrue(in_array('Title', $names));
        self::assertTrue(in_array('Title 2', $names));
    }

    public function testShow(): void
    {
        $client = static::createClient();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $client->request('GET', '/b/123');
        self::assertEquals(404, $client->getResponse()->getStatusCode());

        $blog = new Blog();
        $blog->setBody("Body");
        $blog->setTitle("Title");
        $blog->setCreatedAt(new DateTimeImmutable());
        $entityManager->persist($blog);

        $entityManager->flush();

        $crawler = $client->request('GET', "/b/{$blog->getId()}");
        $this->assertResponseIsSuccessful();

        self::assertEquals("Title", $crawler->filter('.blog-title')->first()->text());
        self::assertEquals("Body", $crawler->filter('.blog-body')->first()->text());
        self::assertEquals($blog->getCreatedAt()->format("d.m.Y H:i"), $crawler->filter('.blog-date')->first()->text());
    }
}
