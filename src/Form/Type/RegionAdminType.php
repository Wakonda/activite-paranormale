<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Validator\Constraints\NotBlank;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Form\Type\FileSelectorType;

use App\Entity\Region;

class RegionAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
		$language = $options['locale'];

        $builder
            ->add('title', TextType::class, ['required' => true, 'constraints' => [new NotBlank()]])
            ->add('flag', FileType::class, ['data_class' => null, 'required' => true])
			->add('photo_selector', FileSelectorType::class, ['required' => false, 'mapped' => false, 'base_path' => 'Region_Admin_ShowImageSelectorColorbox', 'data' => $builder->getData()->getFlag()])
			->add('internationalName', TextType::class, ['label'=>'Nom international', 'required' => true, 'constraints' => [new NotBlank()]])
            ->add('language', EntityType::class, ['class'=>'App\Entity\Language',
				'choice_label' => function ($choice, $key, $value) {
					return $choice->getTitle()." [".$choice->getAbbreviation()."]";
				},
				'required' => true,
				'query_builder' => function(EntityRepository $er) 
				{
					return $er->createQueryBuilder('u')
							->orderBy('u.title', 'ASC');
				},
				'constraints' => [new NotBlank()]
			])
			->add('family', ChoiceType::class, ['multiple' => false, 'expanded' => false,
					"choices" => [
						"region.form.".ucfirst(Region::COUNTRY_FAMILY) => Region::COUNTRY_FAMILY,
						"region.form.".ucfirst(Region::SUBDIVISION_FAMILY) => Region::SUBDIVISION_FAMILY,
						"region.form.".ucfirst(Region::AREA_FAMILY) => Region::AREA_FAMILY
					],
					'translation_domain' => 'validators'
			])
			->add('higherLevel', EntityType::class, ['class'=>'App\Entity\Region', 
				'choice_label'=>'title', 
				'required' => false,
				'choice_value' => function ($entity) {
					return $entity ? $entity->getInternationalName() : '';
				},
				'query_builder' => function(\App\Repository\RegionRepository $repository) use ($language) { return $repository->getCountryByLanguage($language, null);}]);
    }

    public function getBlockPrefix(): string
    {
        return 'ap_index_regiontype';
    }

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults([
			'data_class' => Region::class,
			'locale' => 'fr'
		]);
	}
}