<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Form\EventListener\InternationalNameFieldListener;

use App\Form\Field\LinksEditType;
use App\Entity\UsefulLink;

class UsefulLinkAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$language = $options['locale'];

		$api = new \App\Service\WakondaGuru();
		$tags = $api->getTags($api->getOauth2Token());

        $builder
            ->add('title', TextType::class, ['required' => true, 'constraints' => [new NotBlank()]])
            ->add('text', TextareaType::class, ['required' => false])
            ->add('links', LinksEditType::class, ['required' => false])
            ->add('tags', TextType::class, ['required' => false, "attr" => ["data-whitelist" => implode(",", $tags)]])
            ->add('language', EntityType::class, array('class'=>'App\Entity\Language', 
				'choice_label' => function ($choice, $key, $value) {
					return $choice->getTitle()." [".$choice->getAbbreviation()."]";
				},
				'required' => true,
				'constraints' => [new NotBlank()],
				'query_builder' => function(EntityRepository $er) {
					return $er->createQueryBuilder('u')
							->orderBy('u.title', 'ASC');
				}
			))
			->add('licence', EntityType::class, ['class'=>'App\Entity\Licence', 
					'choice_label'=>'title', 
					'required' => true,
					'constraints' => [new NotBlank()],
					'query_builder' => function(\App\Repository\LicenceRepository $repository) use ($language) { return $repository->getLicenceByLanguage($language);}
			])
			->add('website', EntityType::class, ['class'=>'App\Entity\Blog', 
					'choice_label'=>'title', 
					'required' => false,
					'query_builder' => function(\App\Repository\BlogRepository $er) {
						return $er->createQueryBuilder('b')
						          ->where("b.active = true")
							      ->orderBy('b.title', 'ASC');
					}
			])
            ->add('category', ChoiceType::class, ['choices' => 
			[
				'usefullink.admin.'.ucfirst(UsefulLink::DEVELOPMENT_FAMILY) => UsefulLink::DEVELOPMENT_FAMILY, 
				'usefullink.admin.'.ucfirst(UsefulLink::RESOURCE_FAMILY) => UsefulLink::RESOURCE_FAMILY,
				'usefullink.admin.'.ucfirst(UsefulLink::TOOL_FAMILY) => UsefulLink::TOOL_FAMILY,
				'usefullink.admin.'.ucfirst(UsefulLink::USEFULLINK_FAMILY) => UsefulLink::USEFULLINK_FAMILY,
				'usefullink.admin.'.ucfirst(UsefulLink::TECHNICAL_FAMILY) => UsefulLink::TECHNICAL_FAMILY
			 ], 
			 'expanded' => false, 'multiple' => false, 'required' => true, 'constraints' => [new NotBlank()], 'translation_domain' => 'validators'])
			->add('illustration', IllustrationType::class, array('required' => false, 'base_path' => 'UsefulLink_Admin_ShowImageSelectorColorbox'));

		$builder->add('internationalName', HiddenType::class, ['required' => true, 'constraints' => [new NotBlank()]])->addEventSubscriber(new InternationalNameFieldListener());
		
		$builder->addEventListener(FormEvents::POST_SUBMIT, function(FormEvent $event) use ($builder) {
			$data = $event->getData();
			$form = $event->getForm();
			$notBlank = new NotBlank();

			$links = $form->get('links')->getNormData();

			if(($data->isDevelopment() or $data->isTool() or $data->isUsefulLink()) and empty($links))
				$form->get('links')->addError(new FormError($notBlank->message));

			$website = $form->get('website')->getNormData();

			if($data->isTechnical() and empty($website))
				$form->get('website')->addError(new FormError($notBlank->message));
		});
    }

    public function getBlockPrefix()
    {
        return 'ap_usefullink_usefullinkadmintype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\UsefulLink',
			'locale' => 'fr'
		));
	}
}