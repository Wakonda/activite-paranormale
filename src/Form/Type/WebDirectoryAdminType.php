<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use App\Form\Field\DatePartialType;
use App\Form\Field\SourceEditType;
use App\Form\Type\FileSelectorType;
use App\Form\EventListener\InternationalNameFieldListener;
use App\Form\Field\SocialNetworkEditType;

use App\Entity\Language;

class WebDirectoryAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$language = $options['locale'];

        $builder
            ->add('title', TextType::class, ['required' => true, 'constraints' => [new NotBlank()]])
            ->add('link', TextType::class, ['required' => true, 'constraints' => [new NotBlank()]])
			->add('illustration', IllustrationType::class, ['required' => false, 'base_path' => 'WebDirectory_Admin_ShowImageSelectorColorbox', 'file_path' => $builder->getData()->getAssetImagePath()])
            ->add('language', EntityType::class, [
				'class'=> Language::class,
				'choice_label' => function ($choice, $key, $value) {
					return $choice->getTitle()." [".$choice->getAbbreviation()."]";
				},
				'required' => true,
				'constraints' => [new NotBlank()],
				'query_builder' => function(EntityRepository $er)
				{
					return $er->createQueryBuilder('u')
							->orderBy('u.title', 'ASC');
				},
			])
			->add('websiteLanguage', EntityType::class, [
				'class'=> Language::class,
				'choice_label'=>'title',
				'required' => true,
				'constraints' => [new NotBlank()],
				'query_builder' => function(EntityRepository $er)
				{
					return $er->createQueryBuilder('u')
							->orderBy('u.title', 'ASC');
				},
			])
			->add('licence', EntityType::class, ['class'=>'App\Entity\Licence',
				'choice_label'=>'title',
				'required' => false,
				'query_builder' => function(\App\Repository\LicenceRepository $repository) use ($language) { return $repository->getLicenceByLanguage($language);}
			])
			->add('state', EntityType::class, array('class'=>'App\Entity\State', 
					'choice_label'=>'title',
					'constraints' => array(new NotBlank()),
					'choice_attr' => function($val, $key, $index) {
						return ['data-intl' => $val->getInternationalName()];
					},
					'required' => true,
					'query_builder' => function(\App\Repository\StateRepository $repository) use ($language) { return $repository->getStateByLanguage($language);}
			))
            ->add('socialNetwork', SocialNetworkEditType::class, ['required' => false])
			->add('text', TextareaType::class, ['required' => false])
			->add('foundedYear', DatePartialType::class, ['required' => false])
			->add('defunctYear', DatePartialType::class, ['required' => false])
			->add('wikidata', TextType::class, ['required' => false])
            ->add('source', SourceEditType::class, ['required' => false])
		;

		$builder->add('internationalName', HiddenType::class, ['required' => true, 'constraints' => [new NotBlank()]])->addEventSubscriber(new InternationalNameFieldListener());
    }

	public function onPreSubmitData(FormEvent $event)
	{
		$data = $event->getData();

		$data["socialNetwork"] = json_encode($socialNetworkJson);

		$event->setData($data);
	}

    public function getBlockPrefix()
    {
        return 'ap_webdirectory_webdirectoryadmintype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults([
			'data_class' => 'App\Entity\WebDirectory',
			'locale' => 'fr'
		]);
	}
}