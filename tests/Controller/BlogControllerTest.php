<?php

namespace App\Tests\Controller;

use App\Entity\Blog;
use Container3D0n8aL\getContainer_EnvVarProcessorService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use FilesystemIterator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class BlogControllerTest extends WebTestCase
{

    private array $tmpFilePathsTobeRemoved = [];

    // this value should be the same as MEDIA_UPLOAD_PATH env var
    private string $tmpUploadDir = '/tmp/blog_tests/';

    protected function setUp(): void
    {
        if (file_exists($this->tmpUploadDir)) {
            $this->clearTmpUploadDir();
        } else {
            mkdir($this->tmpUploadDir);
        }
    }


    protected function tearDown(): void
    {
        parent::tearDown();

        foreach ($this->tmpFilePathsTobeRemoved as $path) {
            unlink($path);
        }
        if (file_exists($this->tmpUploadDir)) {
            $this->clearTmpUploadDir();
        }
    }

    /**
     * deletes uploaded medias
     */
    protected function clearTmpUploadDir(): void
    {
        $fi = new FilesystemIterator($this->tmpUploadDir
            , FilesystemIterator::SKIP_DOTS);
        while($fi->valid()) {
            unlink($fi->getPathname());
            $fi->next();
        }
        rmdir($this->tmpUploadDir);
    }


    /**
     * @throws Exception
     */
    private function createTmpImage(): string
    {
        $im = imagecreatetruecolor(800, 600);
        $text_color = imagecolorallocate($im, 233, 14, 91);
        imagestring($im, 24, 300, 300, 'A Simple Text String', $text_color);
        $path = sys_get_temp_dir() . '/' . uniqid("test", true) . '.jpg';
        imagejpeg($im, $path);
        imagedestroy($im);

        $path = realpath($path);
        if (!$path) {
            throw new Exception();
        }

        $this->tmpFilePathsTobeRemoved[] = $path;

        return realpath($path);
    }

    /**
     * @throws Exception
     */
    private function createInvalidTmpImage(): string
    {
        $path = sys_get_temp_dir() . '/' . uniqid("test", true) . '.jpg';
        touch($path);
        $path = realpath($path);
        if (!$path) {
            throw new Exception();
        }

        $this->tmpFilePathsTobeRemoved[] = $path;

        return realpath($path);
    }

    /**
     * @throws Exception
     */
    public function testNewPostFormLoads(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/new-blog');

        $this->assertResponseIsSuccessful();

        // form loads
        self::assertEquals(1, $crawler->filter('input[name="new_post[title]"]')->count());
        self::assertEquals(1, $crawler->filter('textarea[name="new_post[body]"]')->count());
        self::assertEquals(1, $crawler->filter('input[name="new_post[image]"]')->count());
        self::assertEquals(1, $crawler->filter('[name="new_post[new]"]')->count());
    }

    /**
     * @throws Exception
     */
    public function testNewPostWithoutMedia(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/new-blog');

        $this->assertResponseIsSuccessful();

        // test validation
        $button = $crawler->selectButton('New');
        $form = $button->form();

        $crawler = $client->submit($form, [
            'new_post[title]' => '',
            'new_post[body]' => '',
        ]);
        self::assertEquals(422, $client->getResponse()->getStatusCode());
        self::assertCount(2, $crawler->filter("div.invalid-feedback"));

        $client->submit($form, [
            'new_post[title]' => 'Title',
            'new_post[body]' => 'Body',
        ]);
        self::assertEquals(302, $client->getResponse()->getStatusCode());

        $crawler = $client->request('GET', '/new-blog');
        $this->assertResponseIsSuccessful();

        $button = $crawler->selectButton('New');
        $form = $button->form();
        $client->submit($form, [
            'new_post[title]' => 'Title',
            'new_post[body]' => 'Body',
        ]);
        self::assertEquals(422, $client->getResponse()->getStatusCode());

        $client->submit($form, [
            'new_post[title]' => 'Title 1',
            'new_post[body]' => 'Body',
        ]);
        self::assertEquals(302, $client->getResponse()->getStatusCode());
    }

    /**
     * @throws Exception
     */
    public function testNewPostMedia(): void
    {
        // make sure upload dir is empty
        self::assertCount(0, new FilesystemIterator($this->tmpUploadDir
            , FilesystemIterator::SKIP_DOTS));

        $client = static::createClient();

        $crawler = $client->request('GET', '/new-blog');

        $this->assertResponseIsSuccessful();

        $button = $crawler->selectButton('New');
        $form = $button->form();

        $imagePath = $this->createInvalidTmpImage();

        $client->submit($form, [
            'new_post[title]' => 'Title',
            'new_post[body]' => 'Body',
            'new_post[image]' => $imagePath,
        ]);
        self::assertEquals(422, $client->getResponse()->getStatusCode());
        // assert that no files have been uploaded
        self::assertCount(0, new FilesystemIterator(self::getContainer()->getParameter('media_upload_path')
            , FilesystemIterator::SKIP_DOTS));

        $crawler = $client->request('GET', '/new-blog');
        $button = $crawler->selectButton('New');
        $form = $button->form();
        $client->submit($form, [
            'new_post[title]' => 'Title',
            'new_post[body]' => 'Body',
            'new_post[image]' => $this->createTmpImage(),
        ]);
        self::assertEquals(302, $client->getResponse()->getStatusCode());

        // 1 file has been uploaded
        self::assertCount(1, new FilesystemIterator(self::getContainer()->getParameter('media_upload_path')
            , FilesystemIterator::SKIP_DOTS));
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
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
        $names = $crawler->filter('ul > li > a')->each(function (Crawler $node) {
            return $node->text();
        });

        self::assertTrue(in_array('Title', $names));
        self::assertTrue(in_array('Title 2', $names));
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
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
