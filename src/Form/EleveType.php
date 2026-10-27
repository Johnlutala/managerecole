<?php

namespace App\Form;

use App\Entity\Classe;
use App\Entity\Ecole;
use App\Entity\Eleve;
use App\Entity\Option;
use App\Entity\Section;
use App\Repository\ClasseRepository;
use App\Repository\OptionRepository;
use App\Repository\SectionRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Repository\EcoleRepository;


final class EleveType extends AbstractType
{
    public function __construct(
        private EcoleRepository $ecoles,
        private SectionRepository $sections,
        private ClasseRepository $classes,
        private OptionRepository $options,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('matricule', TextType::class, [
                'label' => 'Matricule',
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom',
            ])
            ->add('postnom', TextType::class, [
                'label' => 'Postnom',
                'required' => false,
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
            ])
            ->add('sexe', ChoiceType::class, [
                'label' => 'Sexe',
                'choices' => [
                    'Masculin' => 'M',
                    'Féminin' => 'F',
                ],
                'placeholder' => 'Sélectionner',
            ])
            ->add('dateNaissance', DateType::class, [
                'label' => 'Date de naissance',
                'widget' => 'single_text',
            ])
            ->add('lieuNaissance', TextType::class, [
                'label' => 'Lieu de naissance',
            ])
            ->add('adresse', TextType::class, [
                'label' => 'Adresse',
            ])
            ->add('phone', TextType::class, [
                'label' => 'Téléphone',
                'required' => false,
            ])
            ->add('email', EmailType::class, [
                'label' => 'E-mail',
                'required' => false,
            ])
            ->add('dateInscription', DateType::class, [
                'label' => "Date d'inscription",
                'widget' => 'single_text',
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Actif' => true,
                    'Inactif' => false,
                ],
            ])
            ->add('ecole', EntityType::class, [
                'class' => Ecole::class,
                'choice_label' => 'nom',
                'placeholder' => 'Sélectionner une école',
            ]);

        $formModifier = function (
            \Symfony\Component\Form\FormInterface $form,
            ?Ecole $ecole,
            ?Section $section,
            ?Classe $classe,
            ?Option $option,
            SectionRepository $sections,
            ClasseRepository $classes,
            OptionRepository $options
        ): void {
            $sectionsDisponibles = $ecole
                ? $sections->findBy(['ecole' => $ecole])
                : [];

            $sectionValide = $section
                && $ecole
                && $section->getEcole()?->getId() === $ecole->getId()
                ? $section
                : null;

            $classesDisponibles = $sectionValide
                ? $classes->findBy(['section' => $sectionValide])
                : [];

            $classeValide = $classe
                && $sectionValide
                && $classe->getSection()?->getId() === $sectionValide->getId()
                && $classe->getEcole()?->getId() === $ecole?->getId()
                ? $classe
                : null;

            $optionsDisponibles = $classeValide
                ? $options->findBy(['classe' => $classeValide])
                : [];

            $optionValide = $option
                && $classeValide
                && $option->getClasse()?->getId() === $classeValide->getId()
                ? $option
                : null;

            $form->add('section', EntityType::class, [
                'class' => Section::class,
                'choice_label' => 'nom',
                'placeholder' => 'Sélectionner une section',
                'mapped' => false,
                'required' => false,
                'choices' => $sectionsDisponibles,
                'data' => $sectionValide,
            ]);

            $form->add('classe', EntityType::class, [
                'class' => Classe::class,
                'choice_label' => 'nom',
                'placeholder' => 'Sélectionner une classe',
                'required' => false,
                'choices' => $classesDisponibles,
                'data' => $classeValide,
            ]);

            $form->add('option', EntityType::class, [
                'class' => Option::class,
                'choice_label' => 'nom',
                'placeholder' => 'Sélectionner une option',
                'required' => false,
                'choices' => $optionsDisponibles,
                'data' => $optionValide,
            ]);
        };

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use (
                $formModifier
            ): void {
                $eleve = $event->getData();

                if (!$eleve instanceof Eleve) {
                    return;
                }

                $classe = $eleve->getClasse();
                $option = $eleve->getOption();
                $section = $classe?->getSection();
                $ecole = $eleve->getEcole();

                // Les repositories sont injectés dans le constructeur
                // du formulaire pour construire les choix.
                $formModifier(
                    $event->getForm(),
                    $ecole,
                    $section,
                    $classe,
                    $option,
                    $this->sections,
                    $this->classes,
                    $this->options
                );
            }
        );

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use (
                $formModifier
            ): void {
                $data = $event->getData();

                if (!is_array($data)) {
                    return;
                }

                $ecole = !empty($data['ecole'])
                    ? $this->ecoles->find($data['ecole'])
                    : null;

                $section = !empty($data['section'])
                    ? $this->sections->find($data['section'])
                    : null;

                $classe = !empty($data['classe'])
                    ? $this->classes->find($data['classe'])
                    : null;

                $option = !empty($data['option'])
                    ? $this->options->find($data['option'])
                    : null;

                $formModifier(
                    $event->getForm(),
                    $ecole,
                    $section,
                    $classe,
                    $option,
                    $this->sections,
                    $this->classes,
                    $this->options
                );
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Eleve::class,
        ]);
    }
}
