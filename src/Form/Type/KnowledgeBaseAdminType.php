<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Form\EventListener\InternationalNameFieldListener;

use App\Entity\UsefulLink;

class KnowledgeBaseAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
		$language = $options['locale'];

        $builder
            ->add('title', TextType::class, ['required' => true, 'constraints' => [new NotBlank()]])
            ->add('text', TextareaType::class, ['required' => false])
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
			));

		$builder->add('internationalName', HiddenType::class, ['required' => true, 'constraints' => [new NotBlank()]])->addEventSubscriber(new InternationalNameFieldListener());
    }

    public function getBlockPrefix(): string
    {
        return 'ap_knowledgebase_knowledgebaseadmintype';
    }

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults(array(
			'data_class' => UsefulLink::class,
			'locale' => 'fr'
		));
	}
}