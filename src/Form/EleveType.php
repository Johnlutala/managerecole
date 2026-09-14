<?php

namespace App\Form;

use App\Entity\Classe;
use App\Entity\Ecole;
use App\Entity\Eleve;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class EleveType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('matricule', TextType::class, ['label' => 'Matricule'])
            ->add('nom', TextType::class, ['label' => 'Nom'])
            ->add('postnom', TextType::class, ['label' => 'Postnom', 'required' => false])
            ->add('prenom', TextType::class, ['label' => 'Prénom'])
            ->add('sexe', ChoiceType::class, ['label' => 'Sexe', 'choices' => ['Masculin' => 'M', 'Féminin' => 'F'], 'placeholder' => 'Sélectionner'])
            ->add('dateNaissance', DateType::class, ['label' => 'Date de naissance', 'widget' => 'single_text'])
            ->add('lieuNaissance', TextType::class, ['label' => 'Lieu de naissance'])
            ->add('adresse', TextType::class, ['label' => 'Adresse'])
            ->add('phone', TextType::class, ['label' => 'Téléphone', 'required' => false])
            ->add('email', EmailType::class, ['label' => 'E-mail', 'required' => false])
            ->add('dateInscription', DateType::class, ['label' => "Date d'inscription", 'widget' => 'single_text'])
            ->add('statut', ChoiceType::class, ['label' => 'Statut', 'choices' => ['Actif' => true, 'Inactif' => false]])
            ->add('ecole', EntityType::class, ['class' => Ecole::class, 'choice_label' => 'nom', 'placeholder' => 'Sélectionner une école'])
            ->add('classe', EntityType::class, ['class' => Classe::class, 'choice_label' => 'nom', 'placeholder' => 'Sélectionner une classe']);
    }
    public function configureOptions(OptionsResolver $resolver): void { $resolver->setDefaults(['data_class' => Eleve::class]); }
}
