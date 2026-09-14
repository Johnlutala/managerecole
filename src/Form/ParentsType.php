<?php

namespace App\Form;

use App\Entity\Eleve;
use App\Entity\Parents;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParentsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom')
            ->add('postnom')
            ->add('prenom')
            ->add('telephone')
            ->add('adresse')
            ->add('profession')
            ->add('type')
            ->add('eleve', EntityType::class, [
                'class' => Eleve::class,
                'choice_label' => 'id',
                'multiple' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Parents::class,
        ]);
    }
}
