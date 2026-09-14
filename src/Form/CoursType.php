<?php

namespace App\Form;

use App\Entity\Classe;
use App\Entity\Cours;
use App\Entity\Professeur;
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
        $builder->add('nom', TextType::class, ['label' => 'Nom du cours'])->add('code', TextType::class, ['label' => 'Code'])->add('description', TextareaType::class, ['label' => 'Description', 'required' => false])->add('classe', EntityType::class, ['class' => Classe::class, 'choice_label' => 'nom', 'placeholder' => 'Sélectionner une classe'])->add('professeur', EntityType::class, ['class' => Professeur::class, 'choice_label' => fn(Professeur $p) => trim($p->getNom() . ' ' . $p->getPrenom()), 'placeholder' => 'Sélectionner un professeur']);
    }
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Cours::class]);
    }
}
