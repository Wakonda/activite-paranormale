<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

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
            ->add('links', LinksEditType::class, ['required' => true, 'constraints' => [new NotBlank()]])
            ->add('tags', TextType::class, ['required' => false, "attr" => ["data-whitelist" => implode(",", $tags)]])
            ->add('language', EntityType::class, array('class'=>'App\Entity\Language', 
					'choice_label'=>'title', 
					'required' => true,
					'constraints' => [new NotBlank()],
					'query_builder' => function(EntityRepository $er) 
						{
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
            ->add('category', ChoiceType::class, ['choices' => ['usefullink.admin.'.ucfirst(UsefulLink::DEVELOPMENT_FAMILY) => UsefulLink::DEVELOPMENT_FAMILY, 'usefullink.admin.'.ucfirst(UsefulLink::RESOURCE_FAMILY) => UsefulLink::RESOURCE_FAMILY], 'expanded' => false, 'multiple' => false, 'required' => true, 'constraints' => [new NotBlank()], 'translation_domain' => 'validators'])
        ;
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