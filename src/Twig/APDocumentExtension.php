<?php
	namespace App\Twig;

	use Twig\Extension\AbstractExtension;
	use Twig\TwigFunction;
	
	use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
	use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
	use Doctrine\ORM\EntityManagerInterface;

	use App\Entity\Tags;

	class APDocumentExtension extends AbstractExtension
	{
		private $em;
		private $router;
		private $parameterBag;
		
		public function __construct(EntityManagerInterface $em, UrlGeneratorInterface $router, ParameterBagInterface $parameterBag)
		{
			$this->em = $em;
			$this->router = $router;
			$this->parameterBag = $parameterBag;
		}
		
		public function getFilters()
		{
			return [];
		}

		public function getFunctions()
		{
			return [
				new TwigFunction('getTagsByEntityForDisplayDocument', [$this, 'getTagsByEntityForDisplayDocument'], ['is_safe' => ['html']]),
				new TwigFunction('mime_content_type', [$this, 'getMimeContentType'], ['is_safe' => ['html']]),
				new TwigFunction('filesize', [$this, 'getFileSize'], ['is_safe' => ['html']])
			];
		}

		public function getTagsByEntityForDisplayDocument($entity)
		{
			$className = array_reverse(explode("\\", get_class($entity)));
			$tags = $this->em->getRepository(Tags::class)->findBy(['idClass' => $entity->getId(), 'nameClass' => $className]);

			if(!empty($tags))
			{
				$tagsArray = [];
				
				foreach($tags as $tag)
				{
					if(!empty(trim($tag->getTagWord()->getTitle())))
						$tagsArray[] = '<a class="btn btn-light btn-sm ms-2" href="'.$this->router->generate('ap_tags_search', ['id' => $tag->getTagWord()->getId(), 'title' => $tag->getTagWord()->getTitle()]).'" class="tags_display">'.$tag->getTagWord()->getTitle().'</a>';
				}
				
				if(empty($tagsArray))
					return null;

				return implode('', $tagsArray);
			}
			return null;
		}
		
		public function getMimeContentType($filename, $folder = "public")
		{
			$file = realpath($this->parameterBag->get('kernel.project_dir')."/".$folder.$filename);
			
			if(!file_exists($file))
				return null;
			
			return strtoupper(mime_content_type($file));
		}
		
		public function getFileSize($filename, $decimals = 2, $folder = "public")
		{
			$bytes = filesize(realpath($this->parameterBag->get('kernel.project_dir')."/".$folder.$filename));
			
			$factor = floor((strlen($bytes) - 1) / 3);
			
			if ($factor > 0)
				$sz = 'KMGT';
			
			return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor - 1] . 'B';
		}

		public function getName()
		{
			return 'ap_document_extension';
		}
	}