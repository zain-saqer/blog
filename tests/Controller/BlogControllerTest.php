<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

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
}
