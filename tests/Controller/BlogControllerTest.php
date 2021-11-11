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

        // test sending invalid form
        $client->submit($form, [
            'form[title]' => '',
            'form[body]' => '',
        ]);
        self::assertEquals(422, $client->getResponse()->getStatusCode());

        // test sending valid form
        $client->submit($form, [
            'form[title]' => 'Title',
            'form[body]' => 'Body',
        ]);
        self::assertEquals(302, $client->getResponse()->getStatusCode());

        $crawler = $client->request('GET', '/new-blog');
        $this->assertResponseIsSuccessful();
        $button = $crawler->selectButton('New');
        $form = $button->form();

        // test send duplicate freelancer name
        $client->submit($form, [
            'form[title]' => 'Title',
            'form[body]' => 'Body',
        ]);
        self::assertEquals(422, $client->getResponse()->getStatusCode());

        // test submit valid form
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

        //test empty list
        $crawler = $client->request('GET', '/');
        $this->assertResponseIsSuccessful();
        $text = $crawler->filter('p > span')->first()->text();
        self::assertEquals("No blogs", $text);

        //test list populated list
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
        $names = $crawler->filter('ul > li > span')->each(function (Crawler $node, $i) {
            return $node->text();
        });

        self::assertTrue(in_array('Title', $names));
        self::assertTrue(in_array('Title 2', $names));
    }
}
