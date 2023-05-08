<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

use App\Entity\Blog;

class BlogController extends AbstractController
{
    public function indexAction(Request $request, $page)
    {
		$em = $this->getDoctrine()->getManager();
		$blogs = $em->getRepository(Blog::class)->getBlogByLang($request->getLocale());

		return $this->render('blog/Blog/index.html.twig', [
			'blogs' => $blogs, 
		]);
    }
}