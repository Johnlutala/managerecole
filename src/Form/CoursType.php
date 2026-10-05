<?php

namespace App\Form;

use App\Entity\Classe;
use App\Entity\Cours;
use App\Entity\Ecole;
use App\Entity\Option;
use App\Entity\Section;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CoursType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('ecole', EntityType::class, [
                'class' => Ecole::class,
                'mapped' => false,
                'label' => 'École',
                'placeholder' => 'Sélectionner une école',
                'choice_label' => 'nom',
            ])

            ->add('section', EntityType::class, [
                'class' => Section::class,
                'mapped' => false,
                'label' => 'Section',
                'placeholder' => 'Sélectionner une section',
                'choice_label' => 'nom',
            ])

            ->add('classe', EntityType::class, [
                'class' => Classe::class,
                'label' => 'Classe',
                'choice_label' => 'nom',
                'placeholder' => 'Sélectionner une classe',
            ])

            ->add('option', EntityType::class, [
                'class' => Option::class,
                'required' => false,
                'label' => 'Option',
                'placeholder' => 'Sélectionner une option',
                'choice_label' => 'nom',
            ])

            ->add('nom', TextType::class, [
                'label' => 'Nom du cours',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Cours::class,
        ]);
    }
}
