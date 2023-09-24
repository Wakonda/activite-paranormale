<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class BiographySearchType extends AbstractType
{
    private $entityManager;
    private $translator;

    public function __construct(EntityManagerInterface $entityManager, TranslatorInterface $translator)
    {
        $this->entityManager = $entityManager;
		$this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$entities = $this->entityManager->getConfiguration()->getMetadataDriverImpl()->getAllClassNames();
		$occupationChoice = [];
		
		foreach($entities as $entity) {
			if(is_subclass_of($entity, "App\Entity\EntityLinkBiography") or $entity == "App\Entity\EntityLinkBiography") {
				foreach($entity::getOccupations() as $occupation)
					$occupationChoice[$this->translator->trans("biography.search.".(new \ReflectionClass($entity))->getShortName(), [], 'validators')][$occupation] = $this->translator->trans("biography.occupation.".ucfirst($occupation), [], 'validators');
			}
		}
		
		$occupationByCanonicalName = array_flip(array_reduce($occupationChoice, 'array_merge', []));
		$occupationChoiceDatas = [];
		
		foreach(array_merge(...array_values($occupationChoice)) as $search)
		{
			$found = array_filter($occupationChoice,function($v, $k) use ($search) {
				return in_array($search, $v);
			}, ARRAY_FILTER_USE_BOTH); 

			$occupationChoiceDatas[implode(" / ", array_keys($found))][$search] = $occupationByCanonicalName[$search];
		}

		$language = $options['locale'];
		$builder->setMethod('GET');
        $builder
            ->add('title', TextType::class, ['required' => false])
            ->add('country', EntityType::class, ['label' => 'Thème', 
					'class'=>'App\Entity\Region',
					'choice_label'=>'title',
					'required' => false,
					'query_builder' => function(\App\Repository\RegionRepository $repository) use ($language) {
						return $repository->getCountryByLanguage($language);
					}])
			->add('occupation', ChoiceType::class, ['required' => false, 'choices' => $occupationChoiceDatas, 'translation_domain' => 'validators'])
		;
// dd($occupationChoiceDatas);
    }
    public function getBlockPrefix()
    {
        return 'form';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			'locale' => 'fr',
			'validation_groups' => ['form_validation_only']
		));
	}
}