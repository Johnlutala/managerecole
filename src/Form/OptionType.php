<?php

namespace App\Form;

use App\Entity\Classe;
use App\Entity\Ecole;
use App\Entity\Option;
use App\Repository\ClasseRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class OptionType extends AbstractType
{
    public function __construct(
        private ClasseRepository $classeRepository
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('ecole', EntityType::class, [
                'class' => Ecole::class,
                'choice_label' => 'nom',
                'mapped' => false,
                'placeholder' => 'Sélectionner une école',
                'required' => true,
                'attr' => [
                    'class' => 'form-select',
                    'id' => 'option_ecole',
                ],
                'row_attr' => [
                    'class' => 'mb-0',
                ],
            ])

            ->add('classe', EntityType::class, [
                'class' => Classe::class,
                'choice_label' => 'nom',
                'placeholder' => 'Sélectionner d\'abord une école',
                'required' => true,
                'choices' => [],
                'attr' => [
                    'class' => 'form-select',
                    'id' => 'option_classe',
                ],
                'row_attr' => [
                    'class' => 'mb-0',
                ],
            ])

            ->add('nom', TextType::class, [
                'label' => 'Nom de l\'option',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex. Informatique',
                ],
                'row_attr' => [
                    'class' => 'mb-0',
                ],
            ]);

        /*
         * Lors de la soumission :
         * on récupère l'école sélectionnée et on recharge
         * les classes appartenant à cette école.
         */
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event): void {
                $data = $event->getData();

                if (!is_array($data)) {
                    return;
                }
                $ecoleId = $data['ecole'] ?? null;
                $classes = [];
                if ($ecoleId) {
                    $classes = $this->classeRepository->findBy(
                        ['ecole' => $ecoleId],
                        ['nom' => 'ASC']
                    );
                }

                $form = $event->getForm();

                $form->add('classe', EntityType::class, [
                    'class' => Classe::class,
                    'choice_label' => 'nom',
                    'placeholder' => 'Sélectionner une classe',
                    'required' => true,
                    'choices' => $classes,
                    'attr' => [
                        'class' => 'form-select',
                        'id' => 'option_classe',
                    ],
                    'row_attr' => [
                        'class' => 'mb-0',
                    ],
                ]);
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Option::class,
        ]);
    }
}