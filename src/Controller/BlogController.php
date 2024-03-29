<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Blog;

class BlogController extends AbstractController
{
    public function index(Request $request, EntityManagerInterface $em, $page)
    {
		$blogs = $em->getRepository(Blog::class)->getBlogByLang($request->getLocale());

		return $this->render('blog/Blog/index.html.twig', [
			'blogs' => $blogs,
		]);
    }
}