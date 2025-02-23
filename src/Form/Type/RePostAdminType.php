<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Validator\Constraints\NotBlank;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class RePostAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$list = [
			"Twitter" => (new \App\Service\TwitterAPI())->getLanguagesCanonical()
		];

        $builder
            ->add('postId', TextType::class, ['label' => 'Id post', 'required' => true, 'constraints' => [new NotBlank()]])
			->add('socialNetwork', ChoiceType::class, [
				'choices'  => $list,
				'multiple' => true,
				'constraints' => [new NotBlank()],
				'label' => 'biographies.admin.Occupation', 'translation_domain' => 'validators', "attr" => ["class" => "form-control list-occupation"]
			]);
    }

    public function getBlockPrefix()
    {
        return 'ap_index_reportadmintype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults([]);
	}
}