<?php

namespace App\Form;

use App\Entity\Classe;
use App\Entity\Ecole;
use App\Entity\Section;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ClasseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add(
                'nom',
                TextType::class,
                ['label' => 'Nom de la classe']
            )
            ->add(
                'capacite',
                IntegerType::class,
                ['label' => 'Capacité', 'required' => false]
            )
            ->add(
                'ecole',
                EntityType::class,
                [
                    'class' => Ecole::class,
                    'choice_label' => 'nom',
                    'placeholder' =>
                    'Sélectionner une école'
                ]
            )

            ->add('section', EntityType::class, [
                'class' => Section::class,
                'choice_attr' => function (Section $section) {
                    return [
                        'data-ecole' => $section->getEcole()?->getId(),
                    ];
                },
            ]);
    }
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Classe::class]);
    }
}
