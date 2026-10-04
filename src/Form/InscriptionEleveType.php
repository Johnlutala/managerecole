<?php

namespace App\Form;

use App\Entity\AnneeScolaire;
use App\Entity\Classe;
use App\Entity\Ecole;
use App\Entity\InscriptionEleve;
use App\Entity\Option;
use App\Entity\Section;
use Doctrine\ORM\EntityManagerInterface;
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

final class InscriptionEleveType extends AbstractType
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /*
         * ============================================================
         * INFORMATIONS DE L'ÉLÈVE
         * ============================================================
         */

        $builder
            ->add('reference', TextType::class, [
                'label' => 'Matricule / référence',
                'required' => false,
            ])

            ->add('typeInscription', ChoiceType::class, [
                'label' => "Type d'élève",
                'choices' => [
                    'Nouvel élève' => 'nouveau',
                    'Ancien élève' => 'ancien',
                ],
                'placeholder' => 'Sélectionner',
            ])

            ->add('nom', TextType::class, [
                'label' => 'Nom',
            ])

            ->add('postnom', TextType::class, [
                'label' => 'Postnom',
            ])

            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'required' => false,
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

            ->add('email', EmailType::class, [
                'label' => 'Gmail',
                'required' => false,
            ])

            ->add('adresse', TextType::class, [
                'label' => 'Adresse',
            ])

            ->add('telephone', TextType::class, [
                'label' => 'Téléphone',
                'required' => false,
            ]);


        /*
         * ============================================================
         * INFORMATIONS DU PARENT / TUTEUR
         * ============================================================
         */

        $builder
            ->add('nomParent', TextType::class, [
                'label' => 'Nom du parent / tuteur',
            ])

            ->add('postnomParent', TextType::class, [
                'label' => 'Postnom',
                'required' => false,
            ])

            ->add('prenomParent', TextType::class, [
                'label' => 'Prénom',
                'required' => false,
            ])

            ->add('telephoneParent', TextType::class, [
                'label' => 'Téléphone',
                'required' => false,
            ])

            ->add('emailParent', EmailType::class, [
                'label' => 'E-mail',
                'required' => false,
            ])

            ->add('adresseParent', TextType::class, [
                'label' => 'Adresse',
                'required' => false,
            ])

            ->add('professionParent', TextType::class, [
                'label' => 'Profession',
                'required' => false,
            ])

            ->add('lienParental', ChoiceType::class, [
                'label' => 'Lien avec l’élève',
                'choices' => [
                    'Père' => 'Père',
                    'Mère' => 'Mère',
                    'Tuteur / Tutrice' => 'Tuteur',
                ],
                'placeholder' => 'Sélectionner',
            ]);


        /*
         * ============================================================
         * ÉTABLISSEMENT
         * ============================================================
         */

        $builder->add('etablissement', EntityType::class, [
            'class' => Ecole::class,
            'choice_label' => 'nom',
            'placeholder' => 'Sélectionner une école',
        ]);


        /*
         * ============================================================
         * ANNÉE SCOLAIRE
         * ============================================================
         */

        $builder->add('anneeScolaire', EntityType::class, [
            'class' => AnneeScolaire::class,
            'choice_label' => 'libelle',
            'placeholder' => 'Sélectionner une année',
            'required' => false,
            'choices' => [],
        ]);


        /*
         * ============================================================
         * SECTION
         * ============================================================
         */

        $builder->add('section', EntityType::class, [
            'class' => Section::class,
            'choice_label' => 'nom',
            'placeholder' => 'Sélectionner une section',
            'required' => false,
            'choices' => [],
        ]);


        /*
         * ============================================================
         * CLASSE
         * ============================================================
         */

        $builder->add('classe', EntityType::class, [
            'class' => Classe::class,
            'choice_label' => 'nom',
            'placeholder' => 'Sélectionner une classe',
            'required' => false,
            'choices' => [],
        ]);


        /*
         * ============================================================
         * OPTION
         * ============================================================
         */

        $builder->add('options', EntityType::class, [
            'class' => Option::class,
            'choice_label' => 'nom',
            'placeholder' => 'Aucune option',
            'required' => false,
            'choices' => [],
        ]);


        /*
         * ============================================================
         * PRE_SET_DATA
         *
         * Chargement des choix lorsque le formulaire est affiché.
         * ============================================================
         */

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {

                $inscription = $event->getData();
                $form = $event->getForm();

                if (!$inscription) {
                    return;
                }

                $ecole = $inscription->getEtablissement();
                $section = $inscription->getSection();
                $classe = $inscription->getClasse();


                /*
                 * --------------------------------------------------------
                 * ANNÉE SCOLAIRE + SECTION SELON L'ÉCOLE
                 * --------------------------------------------------------
                 */

                if ($ecole) {

                    $form->add('anneeScolaire', EntityType::class, [
                        'class' => AnneeScolaire::class,
                        'choice_label' => 'libelle',
                        'placeholder' => 'Sélectionner une année',
                        'required' => false,
                        'query_builder' => function ($repo) use ($ecole) {
                            return $repo->createQueryBuilder('a')
                                ->andWhere('a.ecole = :ecole')
                                ->setParameter('ecole', $ecole)
                                ->orderBy('a.dateDebut', 'DESC');
                        },
                    ]);

                    $form->add('section', EntityType::class, [
                        'class' => Section::class,
                        'choice_label' => 'nom',
                        'placeholder' => 'Sélectionner une section',
                        'required' => false,
                        'query_builder' => function ($repo) use ($ecole) {
                            return $repo->createQueryBuilder('s')
                                ->andWhere('s.ecole = :ecole')
                                ->setParameter('ecole', $ecole)
                                ->orderBy('s.nom', 'ASC');
                        },
                    ]);
                }


                /*
                 * --------------------------------------------------------
                 * CLASSES SELON LA SECTION
                 * --------------------------------------------------------
                 */

                if ($section) {

                    $form->add('classe', EntityType::class, [
                        'class' => Classe::class,
                        'choice_label' => 'nom',
                        'placeholder' => 'Sélectionner une classe',
                        'required' => false,
                        'query_builder' => function ($repo) use ($section) {
                            return $repo->createQueryBuilder('c')
                                ->andWhere('c.section = :section')
                                ->setParameter('section', $section)
                                ->orderBy('c.nom', 'ASC');
                        },
                    ]);
                }


                /*
                 * --------------------------------------------------------
                 * OPTIONS SELON LA CLASSE
                 *
                 * Uniquement pour HUMANITÉS.
                 * --------------------------------------------------------
                 */

                if ($classe) {

                    $options = $this->entityManager
                        ->getRepository(Option::class)
                        ->findBy(
                            ['classe' => $classe],
                            ['nom' => 'ASC']
                        );

                    $form->add('options', EntityType::class, [
                        'class' => Option::class,
                        'choice_label' => 'nom',
                        'placeholder' => 'Sélectionner une option',
                        'required' => false,
                        'choices' => $options,
                    ]);
                } else {

                    $form->add('options', EntityType::class, [
                        'class' => Option::class,
                        'choice_label' => 'nom',
                        'placeholder' => 'Aucune option pour cette section',
                        'required' => false,
                        'choices' => [],
                    ]);
                }
            }
        );


        /*
         * ============================================================
         * PRE_SUBMIT
         *
         * Reconstruction des choix avant validation du formulaire.
         * ============================================================
         */

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) {

                $data = $event->getData();
                $form = $event->getForm();


                /*
                 * --------------------------------------------------------
                 * ÉCOLE SÉLECTIONNÉE
                 * --------------------------------------------------------
                 */

                $ecole = null;

                if (!empty($data['etablissement'])) {

                    $ecole = $this->entityManager
                        ->getRepository(Ecole::class)
                        ->find($data['etablissement']);
                }


                /*
                 * --------------------------------------------------------
                 * ANNÉE SCOLAIRE + SECTION SELON L'ÉCOLE
                 * --------------------------------------------------------
                 */

                if ($ecole) {

                    $form->add('anneeScolaire', EntityType::class, [
                        'class' => AnneeScolaire::class,
                        'choice_label' => 'libelle',
                        'placeholder' => 'Sélectionner une année',
                        'required' => false,
                        'query_builder' => function ($repo) use ($ecole) {
                            return $repo->createQueryBuilder('a')
                                ->andWhere('a.ecole = :ecole')
                                ->setParameter('ecole', $ecole)
                                ->orderBy('a.dateDebut', 'DESC');
                        },
                    ]);

                    $form->add('section', EntityType::class, [
                        'class' => Section::class,
                        'choice_label' => 'nom',
                        'placeholder' => 'Sélectionner une section',
                        'required' => false,
                        'query_builder' => function ($repo) use ($ecole) {
                            return $repo->createQueryBuilder('s')
                                ->andWhere('s.ecole = :ecole')
                                ->setParameter('ecole', $ecole)
                                ->orderBy('s.nom', 'ASC');
                        },
                    ]);
                }


                /*
                 * --------------------------------------------------------
                 * SECTION SÉLECTIONNÉE
                 * --------------------------------------------------------
                 */

                $section = null;

                if (!empty($data['section'])) {

                    $section = $this->entityManager
                        ->getRepository(Section::class)
                        ->find($data['section']);
                }


                /*
                 * --------------------------------------------------------
                 * CLASSES SELON LA SECTION
                 * --------------------------------------------------------
                 */

                if ($section) {

                    $form->add('classe', EntityType::class, [
                        'class' => Classe::class,
                        'choice_label' => 'nom',
                        'placeholder' => 'Sélectionner une classe',
                        'required' => false,
                        'query_builder' => function ($repo) use ($section) {
                            return $repo->createQueryBuilder('c')
                                ->andWhere('c.section = :section')
                                ->setParameter('section', $section)
                                ->orderBy('c.nom', 'ASC');
                        },
                    ]);
                }


                /*
                 * --------------------------------------------------------
                 * CLASSE SÉLECTIONNÉE
                 * --------------------------------------------------------
                 */

                $classe = null;

                if (!empty($data['classe'])) {

                    $classe = $this->entityManager
                        ->getRepository(Classe::class)
                        ->find($data['classe']);
                }

                /*
                 * --------------------------------------------------------
                 * OPTIONS SELON LA CLASSE
                 *
                 * Uniquement pour HUMANITÉS.
                 * --------------------------------------------------------
                 */

                if ($classe) {

                    $form->add('options', EntityType::class, [
                        'class' => Option::class,
                        'choice_label' => 'nom',
                        'placeholder' => 'Sélectionner une option',
                        'required' => false,
                        'query_builder' => function ($repo) use ($classe) {
                            return $repo->createQueryBuilder('o')
                                ->andWhere('o.classe = :classe')
                                ->setParameter('classe', $classe)
                                ->orderBy('o.nom', 'ASC');
                        },
                    ]);
                } else {

                    $form->add('options', EntityType::class, [
                        'class' => Option::class,
                        'choice_label' => 'nom',
                        'placeholder' => 'Aucune option pour cette section',
                        'required' => false,
                        'choices' => [],
                    ]);
                }
            }
        );
    }


    /*
     * ================================================================
     * VÉRIFICATION DE LA SECTION HUMANITÉS
     * ================================================================
     */

    private function isHumanites(Section $section): bool
    {
        return mb_strtolower(trim($section->getNom())) === 'humanités';
    }


    /*
     * ================================================================
     * CONFIGURATION
     * ================================================================
     */

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => InscriptionEleve::class,
        ]);
    }
}
