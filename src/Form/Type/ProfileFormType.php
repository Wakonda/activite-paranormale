<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityRepository;
use FOS\UserBundle\Form\Type\ProfileFormType as BaseType;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProfileFormType extends BaseType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', TextType::class, ['required' => true])
            ->add('email', EmailType::class)
			->add('birthDate')
			->add('civility', EntityType::class, ['class'=>'App\Entity\Civility', 'choice_label'=>'title', 'required' => true, 'expanded' => true, 'multiple' => false])	
			->add('country', EntityType::class, ['class'=>'App\Entity\Region', 'choice_label'=>'title', 'query_builder' => function(EntityRepository $er)
				{
					return $er->createQueryBuilder('u')
							->orderBy('u.title', 'ASC');
				},
			])
            ->add('avatar')
            ->add('city')
            ->add('siteWeb')
            ->add('blog')
            ->add('presentation')
        ;
    }

    public function getBlockPrefix()
    {
        return 'ap_user_profile';
    }

    protected function buildUserForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', TextType::class, ['required' => true])
        ;
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults([
			'data_class' => 'App\Entity\User'
		]);
	}
}