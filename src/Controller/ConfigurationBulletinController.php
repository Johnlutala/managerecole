<?php

namespace App\Controller;

use App\Entity\ConfigurationBulletin;
use App\Form\ConfigurationBulletinType;
use App\Repository\ConfigurationBulletinRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/configuration/bulletin')]
#[IsGranted('ROLE_ADMIN')]
final class ConfigurationBulletinController extends AbstractController
{
    #[Route(name: 'app_configuration_bulletin_index', methods: ['GET'])]
    public function index(ConfigurationBulletinRepository $configurationBulletinRepository): Response
    {
        return $this->render('configuration_bulletin/index.html.twig', [
            'configuration_bulletins' => $configurationBulletinRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_configuration_bulletin_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $configurationBulletin = new ConfigurationBulletin();
        $form = $this->createForm(ConfigurationBulletinType::class, $configurationBulletin);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->configurationEstCoherente($configurationBulletin)) {
                $this->addFlash('danger', 'La classe, l’option, l’école et l’année scolaire doivent appartenir au même contexte scolaire.');

                return $this->render('configuration_bulletin/new.html.twig', [
                    'configuration_bulletin' => $configurationBulletin,
                    'form' => $form,
                ]);
            }
            $entityManager->persist($configurationBulletin);
            $entityManager->flush();

            return $this->redirectToRoute('app_configuration_bulletin_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('configuration_bulletin/new.html.twig', [
            'configuration_bulletin' => $configurationBulletin,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_configuration_bulletin_show', methods: ['GET'])]
    public function show(ConfigurationBulletin $configurationBulletin): Response
    {
        return $this->render('configuration_bulletin/show.html.twig', [
            'configuration_bulletin' => $configurationBulletin,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_configuration_bulletin_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ConfigurationBulletin $configurationBulletin, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ConfigurationBulletinType::class, $configurationBulletin);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->configurationEstCoherente($configurationBulletin)) {
                $this->addFlash('danger', 'La classe, l’option, l’école et l’année scolaire doivent appartenir au même contexte scolaire.');

                return $this->render('configuration_bulletin/edit.html.twig', [
                    'configuration_bulletin' => $configurationBulletin,
                    'form' => $form,
                ]);
            }
            $entityManager->flush();

            return $this->redirectToRoute('app_configuration_bulletin_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('configuration_bulletin/edit.html.twig', [
            'configuration_bulletin' => $configurationBulletin,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_configuration_bulletin_delete', methods: ['POST'])]
    public function delete(Request $request, ConfigurationBulletin $configurationBulletin, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$configurationBulletin->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($configurationBulletin);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_configuration_bulletin_index', [], Response::HTTP_SEE_OTHER);
    }

    private function configurationEstCoherente(ConfigurationBulletin $configuration): bool
    {
        $ecole = $configuration->getEcole();
        $classe = $configuration->getClasse();
        $annee = $configuration->getAnneeScolaire();
        $option = $configuration->getOptions();

        return $ecole !== null
            && $classe !== null
            && $annee !== null
            && $classe->getEcole()?->getId() === $ecole->getId()
            && $annee->getEcole()?->getId() === $ecole->getId()
            && ($option === null || $option->getClasse()?->getId() === $classe->getId());
    }
}
