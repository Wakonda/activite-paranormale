<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class VoteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$builder->add('valueVote', TextType::class, ["data" => $options["averageVote"], "attr" => ["class" => "rating-loading cursor-pointer", "dir" => "ltr", "data-size" => "sm"]]);
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults([
			'data_class' => 'App\Entity\Vote',
			'averageVote' => 0
		]);
	}

    public function getBlockPrefix()
    {
        return 'ap_vote_votetype';
    }
}