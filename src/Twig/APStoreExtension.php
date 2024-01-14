<?php
	namespace App\Twig;

	use Twig\Extension\AbstractExtension;
	use Twig\TwigFilter;
	use Twig\TwigFunction;
	use Doctrine\ORM\EntityManagerInterface;
	use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
	use Symfony\Component\HttpFoundation\RequestStack;
	
	use App\Service\Currency;
	use App\Entity\Stores\Store;
	use App\Entity\Stores\MovieStore;

	class APStoreExtension extends AbstractExtension
	{
		public function __construct(private EntityManagerInterface $em, private UrlGeneratorInterface $router, private RequestStack $requestStack) {
		}

		public function getFilters()
		{
			return [
				new TwigFilter('format_price', [$this, 'formatPriceFilter'])
			];
		}

		public function getFunctions()
		{
			return [
				new TwigFunction('get_store_by_entity', [$this, 'getStoreByEntity']),
				new TwigFunction('image_embedded_code', [$this, 'getImageEmbeddedCodeByEntity']),
				new TwigFunction('image_store', [$this, 'getImageEntity'])
				
			];
		}

		// Filters
		public function formatPriceFilter($price, $currency, $locale = "en")
		{
			return Currency::formatPrice($price, $currency, $locale);
		}
		
		// Functions
		public function getStoreByEntity($entity, string $category, string $className): ?array
		{
			return $this->em->getRepository("\App\Entity\Stores\\$className")->findBy([$category => $entity]);
		}
		
		public function getImageEmbeddedCodeByEntity($entity, string $category, string $className, string $store = "asc"): ?string
		{
			foreach($this->em->getRepository("\App\Entity\Stores\\$className")->findBy([$category => $entity], ["id" => $store]) as $store)
				if(!empty($iec = $store->getImageEmbeddedCode()))
					return $iec;
				
			return null;
		}

		public function getImageEntity($entity) {
			if(!empty($photo = $entity->getPhoto())) {
				$baseurl = $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost().$this->requestStack->getCurrentRequest()->getBasePath();
				return $baseurl."/".$entity->getAssetImagePath().$photo;
			} elseif($html = $entity->getImageEmbeddedCode()) {
				$dom = new \DOMDocument;
				@$dom->loadHTML($html);
				$imgTag = $dom->getElementsByTagName('img')->item(0);

				if ($imgTag) {
					$src = $imgTag->getAttribute('src');
					return $src;
				} else
					return null;
			}

			return null;
		}

		private function getSymbolByCurrency($currency) {
			return Currency::getSymbolByCurrency($currency);
		}

		public function getName() {
			return 'ap_storeextension';
		}
	}