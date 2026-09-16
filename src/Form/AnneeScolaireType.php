<?php

namespace App\Form;

use App\Entity\AnneeScolaire;
use App\Entity\Ecole;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AnneeScolaireType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('libelle', null, [
                'label' => 'Libellé de l’année scolaire',
            ])
            ->add('dateDebut', null, [
                'label' => 'Date de début',
                'widget' => 'single_text',
            ])
            ->add('dateFin', null, [
                'label' => 'Date de fin',
                'widget' => 'single_text',
            ])
            ->add('ecole', EntityType::class, [
                'class' => Ecole::class,
                'choice_label' => 'nom',
                'placeholder' => 'Sélectionnez une école',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AnneeScolaire::class,
        ]);
    }
}
