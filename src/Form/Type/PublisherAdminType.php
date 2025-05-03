<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;
use App\Form\Field\SourceEditType;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Form\Field\SocialNetworkEditType;
use App\Form\EventListener\InternationalNameFieldListener;

class PublisherAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', TextType::class, ['required' => true, 'constraints' => [new NotBlank()]])
            ->add('website', TextType::class, ['data_class' => null, 'required' => false, 'constraints' => [new Url()]])
			->add('illustration', IllustrationType::class, ['required' => false, 'base_path' => 'Publisher_Admin_ShowImageSelectorColorbox', 'file_path' => $builder->getData()->getAssetImagePath()])
			->add('wikidata', TextType::class, ['required' => false])
            ->add('text', TextareaType::class, ['required' => false])
            ->add('language', EntityType::class, ['class'=>'App\Entity\Language',
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
            ->add('socialNetwork', SocialNetworkEditType::class, ['required' => false])
			->add('source', SourceEditType::class, ['required' => false])
		;
		
		$builder->add('internationalName', HiddenType::class, ['required' => true, 'constraints' => [new NotBlank()]])->addEventSubscriber(new InternationalNameFieldListener(false));
    }

    public function getBlockPrefix()
    {
        return 'ap_book_publisheradmintype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults([
			'data_class' => 'App\Entity\Publisher'
		]);
	}
}