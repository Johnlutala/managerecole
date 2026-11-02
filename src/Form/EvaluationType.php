<?php

namespace App\Form;

use App\Entity\ConfigurationBulletin;
use App\Entity\Cours;
use App\Entity\Evaluation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EvaluationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Période ou examen',
                'help' => 'Nom du point préconfiguré, par exemple « Période 1 » ou « Examen 1 ». ',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex. Période 1',
                ],
            ])
            ->add('type', TextType::class, [
                'label' => 'Type de cotation',
                'help' => 'Ce libellé sera proposé au professeur, par exemple interrogation, devoir ou examen.',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex. Interrogation',
                ],
            ])
            ->add('bareme', NumberType::class, [
                'label' => 'Barème maximal',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex. 20',
                    'min' => 0,
                ],
            ])
            ->add('coefficient', NumberType::class, [
                'label' => 'Coefficient',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex. 1',
                    'min' => 0,
                ],
            ])
            ->add('dateEvaluation', DateType::class, [
                'label' => "Date d'évaluation",
                'widget' => 'single_text',
                'html5' => true,
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('cours', EntityType::class, [
                'class' => Cours::class,
                'choice_label' => 'nom',
                'label' => 'Cours',
                'placeholder' => 'Sélectionnez un cours',
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
            ->add('configurationBulletin', EntityType::class, [
                'class' => ConfigurationBulletin::class,
                'choice_label' => 'nom',
                'label' => 'Semestre ou trimestre',
                'placeholder' => 'Sélectionnez une période',
                'attr' => [
                    'class' => 'form-select',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Evaluation::class,
        ]);
    }
}
