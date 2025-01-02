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

class SocialNetworkAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
		$list = [
			"Bluesky" => (new \App\Service\Bluesky())->getLanguagesCanonical(),
			"Facebook" => (new \App\Service\Facebook())->getLanguagesCanonical(),
			"Mastodon" => (new \App\Service\Mastodon())->getLanguagesCanonical(),
			"Twitter" => (new \App\Service\TwitterAPI())->getLanguagesCanonical()
		];

        $builder
            ->add('text', TextareaType::class, ['label' => 'Texte', 'required' =>true, 'constraints' => [new NotBlank()]])
            ->add('url', TextType::class, ['required' => true, 'constraints' => [new NotBlank()]])
			->add('socialNetwork', ChoiceType::class, [
				'choices'  => $list,
				'label' => 'biographies.admin.Occupation', 'translation_domain' => 'validators', "attr" => ["class" => "form-control list-occupation"]
			]);
    }

    public function getBlockPrefix()
    {
        return 'ap_index_socialnetworkadmintype';
    }

	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults([]);
	}
}