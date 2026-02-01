<?php
namespace App\Cache;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\ORM\EntityManagerInterface;

class MenuService
{
    public function __construct(
        private CacheInterface $cache,
        private EntityManagerInterface $em,
        private RequestStack $requestStack
    ) {}
    
    public function getCounters(): array
    {
        $request = $this->requestStack->getCurrentRequest();
        $locale = $request?->getLocale() ?? 'fr'; 

        return $this->cache->get('menu_counters_'.$locale, function (ItemInterface $item) use ($locale) {
            $item->expiresAfter(24*60); // 24 hours
			$quotationCounter = $this->em->getRepository(\App\Entity\Quotation::class)->countByFamily($locale);

            return [
                'archive_'.$locale => $this->archiveCounter($locale),
                'world_news_'.$locale => $this->em->getRepository(\App\Entity\News::class)->countWorldNews(),
                'biography_'.$locale => $this->em->getRepository(\App\Entity\Biography::class)->countBiography($locale),
                'cartography_'.$locale => $this->em->getRepository(\App\Entity\Cartography::class)->nbrGMapByLang($locale),
                'document_'.$locale => $this->em->getRepository(\App\Entity\Document::class)->countDocument(),
                'creepy_story_'.$locale => $this->em->getRepository(\App\Entity\CreepyStory::class)->countCreepyStory($locale),
                'user_'.$locale => $this->em->getRepository(\App\Entity\User::class)->countAdmin(),
                'classified_ads_'.$locale => $this->em->getRepository(\App\Entity\ClassifiedAds::class)->countByLanguage($locale),
                'photo_'.$locale => $this->em->getRepository(\App\Entity\Photo::class)->nbrPicture($locale),
                'testimony_'.$locale => $this->em->getRepository(\App\Entity\Testimony::class)->countAllTestimoniesForLeftMenu($locale),
                'theme_'.$locale => $this->em->getRepository(\App\Entity\Theme::class)->nbrTheme($locale),
                'video_'.$locale => $this->em->getRepository(\App\Entity\Video::class)->nbrVideo($locale),
                'quotation_'.$locale => $quotationCounter[\App\Entity\Quotation::QUOTATION_FAMILY],
                'movie_'.$locale => $this->em->getRepository(\App\Entity\Movies\Movie::class)->countMovieByLanguage($locale),
                'book_'.$locale => $this->em->getRepository(\App\Entity\Book::class)->countByLanguage($locale),
                'music_'.$locale => $this->em->getRepository(\App\Entity\Artist::class)->countArtist($locale),
                'poem_'.$locale => $quotationCounter[\App\Entity\Quotation::POEM_FAMILY],
                'proverb_'.$locale => $quotationCounter[\App\Entity\Quotation::PROVERB_FAMILY],
                'humor_'.$locale => $quotationCounter[\App\Entity\Quotation::HUMOR_FAMILY],
                'saying_'.$locale => $quotationCounter[\App\Entity\Quotation::SAYING_FAMILY],
                'television_serie_'.$locale => $this->em->getRepository(\App\Entity\Movies\TelevisionSerie::class)->countByLanguage($locale)
            ];
        });
    }
	
	private function archiveCounter($locale) {
		$entities = $this->em->getConfiguration()->getMetadataDriverImpl()->getAllClassNames();

		foreach($entities as $entity) {
			if(method_exists($entity, "getArchive")) {
				$repository = $this->em->getRepository($entity);
				if(method_exists($repository, "countArchived"))
					$res[] = $this->em->getRepository($entity)->countArchived($locale);
			}
		}

		return array_sum($res);
	}
}