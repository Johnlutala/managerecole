<?php

namespace App\Form;

use App\Entity\Jour;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class JourType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
         ->add('nom', TextType::class, [
                'label' => 'Nom',
                'attr' => [
                    'placeholder' => 'Ex. : 1ère heure',
                    'class' => 'form-control',
                ],
            ])
            ->add('ordre', IntegerType::class, [
                'label' => 'Ordre',
                'attr' => [
                    'placeholder' => 'Ex. : 1',
                    'class' => 'form-control',
                    'min' => 1,
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Jour::class,
        ]);
    }
}
