<?php
	namespace App\Twig;

	use Twig\Extension\AbstractExtension;
	use Twig\TwigFilter;
	use Twig\TwigFunction;
	use Doctrine\ORM\EntityManagerInterface;
	
	use App\Service\Currency;
	use App\Entity\Stores\Store;
	use App\Entity\Stores\MovieStore;

	class APStoreExtension extends AbstractExtension
	{
		private $em;
		
		public function __construct(EntityManagerInterface $em)
		{
			$this->em = $em;
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
				new TwigFunction('get_store_by_entity', array($this, 'getStoreByEntity')),
				new TwigFunction('image_embedded_code', array($this, 'getImageEmbeddedCodeByEntity'))
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

		private function getSymbolByCurrency($currency) {
			return Currency::getSymbolByCurrency($currency);
		}

		public function getName()
			{
				return 'ap_storeextension';
			}
		}