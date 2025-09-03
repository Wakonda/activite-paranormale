<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;

use App\Service\Captcha;

class CaptchaController extends AbstractController
{
	#[Route('/reload_captcha', name: 'Captcha_Reload')]
	public function reloadCaptchaAction(Captcha $captcha)
	{
		$wordOrNumberRand = rand(1, 2);
		$length = rand(3, 7);

		if($wordOrNumberRand == 1)
			$word = $captcha->wordRandom($length);
		else
			$word = $captcha->numberRandom($length);

		return new JsonResponse(["new_captcha" => $captcha->generate($word)]);
	}
}