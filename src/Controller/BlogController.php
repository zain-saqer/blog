<?php

namespace App\Controller;

use App\Entity\Blog;
use App\Form\NewPostType;
use App\Repository\BlogRepository;
use DateTimeImmutable;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BlogController extends AbstractController
{

    /**
     * @Route("/", name="blog_list")
     */
    public function list(BlogRepository $blogRepository): Response
    {
        $blogs = $blogRepository->findAll();

        return $this->render('blog/list.html.twig', [
            'blogs' => $blogs,
        ]);
    }

    /**
     * @Route("/b/{id}", name="blog_show")
     */
    public function show(BlogRepository $blogRepository, string $id): Response
    {
        $blog = $blogRepository->find($id);
        if ($blog === null)
            throw $this->createNotFoundException();

        return $this->render('blog/show.html.twig', [
            'blog' => $blog,
        ]);
    }

    /**
     * @Route("/new-blog", name="blog_new")
     */
    public function new(Request $request): Response
    {
        $blog = new Blog();

        $form = $this->createForm(NewPostType::class, $blog);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Blog $blog */
            $post = $form->getData();

            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $form->get('image')->getData();
            try {
                if ($uploadedFile !== null) {
                    // create unique file name
                    while (true) {
                        $newFilename = uniqid(rand(), true) . '.' . $uploadedFile->guessExtension();
                        if (!file_exists($this->getParameter("media_upload_path") . '/' . $newFilename)) break;
                    }

                    $uploadedFile->move(
                        $this->getParameter('media_upload_path'),
                        $newFilename
                    );

                    $post->setMediaFilename($newFilename);
                }

                $post->setCreatedAt(new DateTimeImmutable());

                $this->getDoctrine()->getManager()->persist($post);
                $this->getDoctrine()->getManager()->flush();
            } catch (Exception $e) {
                $this->addFlash('new_post.error', 'Something went wrong');
                return $this->renderForm('blog/new.html.twig', [
                    'form' => $form,
                ]);
            }

            return $this->redirectToRoute('blog_list');
        }

        return $this->renderForm('blog/new.html.twig', [
            'form' => $form,
        ]);
    }
}