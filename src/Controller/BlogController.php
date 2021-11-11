<?php

namespace App\Controller;

use App\Entity\Blog;
use App\Repository\BlogRepository;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
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

        $form = $this->createFormBuilder($blog)
            ->add('title', TextType::class)
            ->add('body', TextareaType::class)
            ->add('new', SubmitType::class)
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Blog $blog */
            $blog = $form->getData();

            $blog->setCreatedAt(new DateTimeImmutable());

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($blog);
            $entityManager->flush();

            return $this->redirectToRoute('blog_list');
        }

        return $this->renderForm('blog/new.html.twig', [
            'form' => $form,
        ]);
    }
}