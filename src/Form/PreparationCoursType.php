<?php

namespace App\Form;

use App\Entity\PreparationCours;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class PreparationCoursType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dateCours', DateType::class, [
                'label' => 'Date de la leçon',
                'widget' => 'single_text',
            ])
            ->add('jour', ChoiceType::class, [
                'label' => 'Jour',
                'choices' => [
                    'Lundi' => 'Lundi',
                    'Mardi' => 'Mardi',
                    'Mercredi' => 'Mercredi',
                    'Jeudi' => 'Jeudi',
                    'Vendredi' => 'Vendredi',
                    'Samedi' => 'Samedi',
                    'Dimanche' => 'Dimanche',
                ],
            ])
            ->add('heureDebut', TimeType::class, [
                'label' => 'Heure de début',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('heureFin', TimeType::class, [
                'label' => 'Heure de fin',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('sujet', TextType::class, ['label' => 'Sujet de la leçon'])
            ->add('reference', TextareaType::class, ['label' => 'Référence / sous-branche', 'required' => false])
            ->add('objectifGeneral', TextareaType::class, ['label' => 'Objectif général', 'required' => false])
            ->add('objectifsSpecifiques', TextareaType::class, ['label' => 'Objectifs spécifiques', 'required' => false])
            ->add('matiere', TextareaType::class, ['label' => 'Matière à enseigner', 'required' => false])
            ->add('methode', TextareaType::class, ['label' => 'Méthode / procédé', 'required' => false])
            ->add('materielDidactique', TextareaType::class, ['label' => 'Matériel didactique', 'required' => false])
            ->add('activitesInitialesEnseignant', TextareaType::class, ['label' => 'Activités initiales — enseignant', 'required' => false])
            ->add('activitesInitialesEleves', TextareaType::class, ['label' => 'Activités initiales — élèves', 'required' => false])
            ->add('activitesPrincipalesEnseignant', TextareaType::class, ['label' => 'Activités principales — enseignant', 'required' => false])
            ->add('activitesPrincipalesEleves', TextareaType::class, ['label' => 'Activités principales — élèves', 'required' => false])
            ->add('activitesFinalesEnseignant', TextareaType::class, ['label' => 'Activités finales — enseignant', 'required' => false])
            ->add('activitesFinalesEleves', TextareaType::class, ['label' => 'Activités finales — élèves', 'required' => false])
            ->add('evaluation', TextareaType::class, ['label' => 'Évaluation', 'required' => false])
            ->add('devoir', TextareaType::class, ['label' => 'Devoir / prolongement', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => PreparationCours::class]);
    }
}
