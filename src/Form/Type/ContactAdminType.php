<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class ContactAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('pseudoContact', TextType::class, ['required' => true])
            ->add('emailContact', TextType::class, ['required' => true])
            ->add('subjectContact', TextType::class, ['required' => true])
            ->add('messageContact', TextareaType::class, ['required' => true])
			->add('state', EntityType::class, ['class'=>'App\Entity\State', 
								'choice_label'=>'title', 
								'required' => true,
								'query_builder' => function(\App\Repository\StateRepository $repository) use ($language) { return $repository->getStateByLanguage($language);}])
        ;
    }

    public function getBlockPrefix(): string
    {
        return 'ap_contact_contactadmintype';
    }

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults([
			'data_class' => 'App\Entity\Contact',
			'validation_groups' => 'contact_validation',
		]);
	}
}