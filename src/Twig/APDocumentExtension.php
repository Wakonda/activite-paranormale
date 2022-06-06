<?php
	namespace App\Twig;

	use Twig\Extension\AbstractExtension;
	use Twig\TwigFunction;
	
	use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
	use Doctrine\ORM\EntityManagerInterface;
	use Symfony\Component\DependencyInjection\ContainerInterface;
	
	use App\Entity\Tags;

	class APDocumentExtension extends AbstractExtension
	{
		private $container;
		private $em;
		private $router;
		
		public function __construct(ContainerInterface $container, EntityManagerInterface $em, UrlGeneratorInterface $router)
		{
			$this->container = $container;
			$this->em = $em;
			$this->router = $router;
		}
		
		public function getFilters()
		{
			return array();
		}

		public function getFunctions()
		{
			return array(
				new TwigFunction('getTagsByEntityForDisplayDocument', array($this, 'getTagsByEntityForDisplayDocument'), array('is_safe' => array('html'))),
				new TwigFunction('mime_content_type', array($this, 'getMimeContentType'), array('is_safe' => array('html'))),
				new TwigFunction('filesize', array($this, 'getFileSize'), array('is_safe' => array('html')))
			);
		}

		public function getTagsByEntityForDisplayDocument($entity)
		{
			$className = array_reverse(explode("\\", get_class($entity)));
			$tags = $this->em->getRepository(Tags::class)->findBy(array('idClass' => $entity->getId(), 'nameClass' => $className));

			if(!empty($tags))
			{
				$tagsArray = array();
				
				foreach($tags as $tag)
				{
					if(!empty(trim($tag->getTagWord()->getTitle())))
						$tagsArray[] = '<a class="btn btn-light btn-sm ml-2" href="'.$this->router->generate('ap_tags_search', array('id' => $tag->getTagWord()->getId(), 'title' => $tag->getTagWord()->getTitle())).'" class="tags_display">'.$tag->getTagWord()->getTitle().'</a>';
				}
				
				if(empty($tagsArray))
					return null;

				return implode('', $tagsArray);
			}
			return null;
		}
		
		public function getMimeContentType($filename, $folder = "public")
		{
			$file = realpath($this->container->get('kernel')->getProjectDir()."/".$folder.$filename);
			
			if(!file_exists($file))
				return null;
			
			return strtoupper(mime_content_type($file));
		}
		
		public function getFileSize($filename, $decimals = 2, $folder = "public")
		{
			$bytes = filesize(realpath($this->container->get('kernel')->getProjectDir()."/".$folder.$filename));
			
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