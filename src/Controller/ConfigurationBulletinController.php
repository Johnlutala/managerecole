<?php

namespace App\Controller;

use App\Entity\ConfigurationBulletin;
use App\Repository\ConfigurationFicheCotationRepository;
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
    public function new(
        Request $request,
        ConfigurationBulletinRepository $configurationBulletinRepository,
        ConfigurationFicheCotationRepository $ficheConfigurationRepository,
        EntityManagerInterface $entityManager
    ): Response
    {
        $configurationBulletin = new ConfigurationBulletin();
        $form = $this->createForm(ConfigurationBulletinType::class, $configurationBulletin);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (
                !$this->configurationEstCoherente($configurationBulletin, $ficheConfigurationRepository)
                || !$this->periodeEstUnique($configurationBulletin, $configurationBulletinRepository)
            ) {
                $this->addFlash('danger', 'Configurez d’abord la structure de cotation de l’école, puis choisissez un semestre ou trimestre correspondant et évitez les doublons.');

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
    public function edit(
        Request $request,
        ConfigurationBulletin $configurationBulletin,
        ConfigurationBulletinRepository $configurationBulletinRepository,
        ConfigurationFicheCotationRepository $ficheConfigurationRepository,
        EntityManagerInterface $entityManager
    ): Response
    {
        $form = $this->createForm(ConfigurationBulletinType::class, $configurationBulletin);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (
                !$this->configurationEstCoherente($configurationBulletin, $ficheConfigurationRepository)
                || !$this->periodeEstUnique($configurationBulletin, $configurationBulletinRepository)
            ) {
                $this->addFlash('danger', 'Configurez d’abord la structure de cotation de l’école, puis choisissez un semestre ou trimestre correspondant et évitez les doublons.');

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

    private function configurationEstCoherente(
        ConfigurationBulletin $configuration,
        ConfigurationFicheCotationRepository $ficheConfigurationRepository
    ): bool
    {
        $ecole = $configuration->getEcole();
        $classe = $configuration->getClasse();
        $annee = $configuration->getAnneeScolaire();
        $option = $configuration->getOptions();

        if (
            $ecole === null
            || $classe === null
            || $annee === null
            || $classe->getEcole()?->getId() !== $ecole->getId()
            || $annee->getEcole()?->getId() !== $ecole->getId()
            || ($option !== null && $option->getClasse()?->getId() !== $classe->getId())
        ) {
            return false;
        }

        $ficheConfiguration = $ficheConfigurationRepository->findOneBy(['ecole' => $ecole]);
        $type = $this->typeConfiguration($configuration->getNom());

        return $ficheConfiguration !== null
            && $type !== null
            && $ficheConfiguration->getStructure() === $type;
    }

    private function typeConfiguration(?string $nom): ?string
    {
        if ($nom === null || preg_match('/^(Semestre|Trimestre)\s+(I{1,3}|[1-3])$/iu', trim($nom), $matches) !== 1) {
            return null;
        }

        $kind = strtolower($matches[1]);
        $number = match (strtoupper($matches[2])) {
            'I', '1' => 1,
            'II', '2' => 2,
            'III', '3' => 3,
        };

        return $kind === 'semestre' && $number > 2 ? null : $kind;
    }

    private function periodeEstUnique(
        ConfigurationBulletin $configuration,
        ConfigurationBulletinRepository $repository
    ): bool {
        $periodKey = $this->clePeriode($configuration->getNom());
        if ($periodKey === null) {
            return false;
        }

        foreach ($repository->findBy([
            'ecole' => $configuration->getEcole(),
            'anneeScolaire' => $configuration->getAnneeScolaire(),
            'classe' => $configuration->getClasse(),
            'options' => $configuration->getOptions(),
        ]) as $existing) {
            if (
                $existing->getId() !== $configuration->getId()
                && $this->clePeriode($existing->getNom()) === $periodKey
            ) {
                return false;
            }
        }

        return true;
    }

    private function clePeriode(?string $nom): ?string
    {
        if (
            $nom === null
            || preg_match('/^(Semestre|Trimestre)\s+(I{1,3}|[1-3])$/iu', trim($nom), $matches) !== 1
        ) {
            return null;
        }

        $kind = strtolower($matches[1]);
        $number = match (strtoupper($matches[2])) {
            'I', '1' => 1,
            'II', '2' => 2,
            'III', '3' => 3,
        };
        if ($kind === 'semestre' && $number > 2) {
            return null;
        }

        return $kind . '-' . $number;
    }
}
