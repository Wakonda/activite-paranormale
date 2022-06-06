<?php

/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
            ->add('username', TextType::class, array('required' => true))
            ->add('email', EmailType::class)
			->add('birthDate')
			->add('civility', EntityType::class, array('class'=>'App\Entity\Civility', 'choice_label'=>'title', 'required' => true, 'expanded' => true, 'multiple' => false))	
			->add('country', EntityType::class, array('class'=>'App\Entity\Country', 'choice_label'=>'title', 'query_builder' => function(EntityRepository $er) 
				{
					return $er->createQueryBuilder('u')
							->orderBy('u.title', 'ASC');
				},
			))
			
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

    /**
     * Builds the embedded form representing the user.
     *
     * @param FormBuilder $builder
     * @param array       $options
     */
    protected function buildUserForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', TextType::class, array('required' => true))
        ;
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => 'App\Entity\User'
		));
	}
}