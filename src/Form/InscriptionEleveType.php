<?php

namespace App\Form;

use App\Entity\AnneeScolaire;
use App\Entity\Classe;
use App\Entity\Ecole;
use App\Entity\InscriptionEleve;
use App\Entity\Option;
use App\Entity\Section;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class InscriptionEleveType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reference', TextType::class, ['label' => 'Matricule / référence', 'required' => false])
            ->add('typeInscription', ChoiceType::class, ['label' => "Type d'élève", 'choices' => ['Nouvel élève' => 'nouveau', 'Ancien élève' => 'ancien'], 'placeholder' => 'Sélectionner'])
            ->add('nom', TextType::class, ['label' => 'Nom'])
            ->add('postnom', TextType::class, ['label' => 'Postnom'])
            ->add('prenom', TextType::class, ['label' => 'Prénom', 'required' => false])
            ->add('sexe', ChoiceType::class, ['label' => 'Sexe', 'choices' => ['Masculin' => 'M', 'Féminin' => 'F'], 'placeholder' => 'Sélectionner'])
            ->add('dateNaissance', DateType::class, ['label' => 'Date de naissance', 'widget' => 'single_text'])
            ->add('lieuNaissance', TextType::class, ['label' => 'Lieu de naissance'])
            ->add('nationalite', TextType::class, ['label' => 'Nationalité', 'required' => false])
            ->add('adresse', TextType::class, ['label' => 'Adresse'])
            ->add('telephone', TextType::class, ['label' => 'Téléphone', 'required' => false])
            ->add('nomParent', TextType::class, ['label' => 'Nom du parent / tuteur'])
            ->add('postnomParent', TextType::class, ['label' => 'Postnom', 'required' => false])
            ->add('prenomParent', TextType::class, ['label' => 'Prénom', 'required' => false])
            ->add('telephoneParent', TextType::class, ['label' => 'Téléphone', 'required' => false])
            ->add('emailParent', EmailType::class, ['label' => 'E-mail', 'required' => false])
            ->add('adresseParent', TextType::class, ['label' => 'Adresse', 'required' => false])
            ->add('professionParent', TextType::class, ['label' => 'Profession', 'required' => false])
            ->add('lienParental', ChoiceType::class, ['label' => 'Lien avec l’élève', 'choices' => ['Père' => 'Père', 'Mère' => 'Mère', 'Tuteur / Tutrice' => 'Tuteur'], 'placeholder' => 'Sélectionner'])
            ->add('etablissement', EntityType::class, ['class' => Ecole::class, 'choice_label' => 'nom', 'placeholder' => 'Sélectionner une école'])
            ->add('anneeScolaire', EntityType::class, ['class' => AnneeScolaire::class, 'choice_label' => 'libelle', 'placeholder' => 'Sélectionner une année'])
            ->add('section', EntityType::class, ['class' => Section::class, 'choice_label' => 'nom', 'placeholder' => 'Sélectionner une section'])
            ->add('classe', EntityType::class, ['class' => Classe::class, 'choice_label' => 'nom', 'placeholder' => 'Sélectionner une classe'])
            ->add('options', EntityType::class, ['class' => Option::class, 'choice_label' => 'nom', 'placeholder' => 'Sélectionner une option', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => InscriptionEleve::class]);
    }
}
