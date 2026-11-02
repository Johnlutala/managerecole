<?php

namespace App\Controller;

use App\Entity\Ecole;
use App\Entity\ConfigurationFicheCotation;
use App\Repository\ConfigurationFicheCotationRepository;
use App\Repository\EcoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Service\CotationStructure;

#[Route('/fiche-cotation', name: 'app_fiche_cotation_')]
#[IsGranted('ROLE_ADMIN')]
final class FicheCotationController extends AbstractController
{
    private const STRUCTURES = ['semestre', 'trimestre'];

    #[Route('', name: 'index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EcoleRepository $ecoleRepository,
        ConfigurationFicheCotationRepository $configurationRepository,
        CotationStructure $cotationStructure,
        EntityManagerInterface $entityManager
    ): Response
    {
        if ($request->isMethod('POST')) {
            $configurationId = $request->request->getInt('configuration_id');
            $csrfId = $configurationId > 0 ? (string) $configurationId : 'nouvelle';
            if (!$this->isCsrfTokenValid(
                'configurer_fiche_cotation_' . $csrfId,
                (string) $request->request->get('_token')
            )) {
                throw $this->createAccessDeniedException('Le jeton de sécurité est invalide.');
            }

            $ecoleId = $request->request->getInt('ecole');
            $structure = $request->request->get('structure');
            $ecole = $ecoleId > 0 ? $ecoleRepository->find($ecoleId) : null;
            if (!$ecole instanceof Ecole || !is_string($structure) || !in_array($structure, self::STRUCTURES, true)) {
                $this->addFlash('danger', 'Sélectionnez une école et une organisation valide.');

                return $this->redirectToRoute('app_fiche_cotation_index');
            }

            $configuration = $configurationId > 0 ? $configurationRepository->find($configurationId) : null;
            if ($configurationId > 0 && !$configuration instanceof ConfigurationFicheCotation) {
                throw $this->createNotFoundException('La configuration de cotation est introuvable.');
            }
            $configuration ??= new ConfigurationFicheCotation();
            $existingForSchool = $configurationRepository->findOneBy(['ecole' => $ecole]);
            if ($existingForSchool !== null && $existingForSchool->getId() !== $configuration->getId()) {
                $this->addFlash('danger', 'Une fiche est déjà configurée pour cette école. Utilisez Modifier sur sa ligne.');

                return $this->redirectToRoute('app_fiche_cotation_index');
            }

            $configuration->setEcole($ecole);
            $configuration->setStructure($structure);
            $entityManager->persist($configuration);
            $entityManager->flush();

            $this->addFlash('success', 'La structure de cotation a été enregistrée pour cette école.');

            return $this->redirectToRoute('app_fiche_cotation_index', ['ecole' => $ecole->getId()]);
        }

        [$ecoles, $ecole, $configuration] = $this->selection($request, $ecoleRepository, $configurationRepository);
        $structure = $configuration?->getStructure();
        $configurations = $configurationRepository->findBy([], ['id' => 'DESC']);
        $configuredSchoolIds = array_map(
            static fn (ConfigurationFicheCotation $item): ?int => $item->getEcole()?->getId(),
            $configurations
        );
        $ecolesDisponibles = array_values(array_filter(
            $ecoles,
            static fn (Ecole $item): bool => !in_array($item->getId(), $configuredSchoolIds, true)
        ));

        return $this->render('fiche_cotation/index.html.twig', [
            'ecoles' => $ecoles,
            'ecoles_disponibles' => $ecolesDisponibles,
            'configurations' => $configurations,
            'ecole_selectionnee' => $ecole,
            'configuration_enregistree' => $configuration !== null,
            'structure_selectionnee' => $structure,
            'groupes_colonnes' => $structure === null ? [] : $cotationStructure->groupes($structure),
            'annee_scolaire' => $this->anneeScolaireCourante(),
        ]);
    }

    #[Route('/pdf', name: 'pdf', methods: ['GET'])]
    public function pdf(
        Request $request,
        EcoleRepository $ecoleRepository,
        ConfigurationFicheCotationRepository $configurationRepository,
        CotationStructure $cotationStructure,
        SluggerInterface $slugger
    ): Response {
        [, $ecole, $configuration] = $this->selection($request, $ecoleRepository, $configurationRepository);
        $structure = $configuration?->getStructure();
        if ($ecole === null || $structure === null) {
            throw $this->createNotFoundException('Aucune structure de cotation n’est enregistrée pour cette école.');
        }

        $options = new Options();
        $options->setDefaultFont('DejaVu Sans');
        $options->setIsRemoteEnabled(false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($this->renderView('fiche_cotation/pdf.html.twig', [
            'ecole' => $ecole,
            'structure' => $structure,
            'groupes_colonnes' => $cotationStructure->groupes($structure),
            'annee_scolaire' => $this->anneeScolaireCourante(),
        ]), 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = sprintf(
            'fiche-cotation-%s-%s.pdf',
            $slugger->slug($ecole->getNom())->lower(),
            $structure
        );
        $response = new Response($dompdf->output());
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set(
            'Content-Disposition',
            HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $filename)
        );

        return $response;
    }

    /**
     * @return array{0: Ecole[], 1: ?Ecole, 2: ?ConfigurationFicheCotation}
     */
    private function selection(
        Request $request,
        EcoleRepository $ecoleRepository,
        ConfigurationFicheCotationRepository $configurationRepository
    ): array
    {
        $ecoles = $ecoleRepository->findBy([], ['nom' => 'ASC']);
        $ecole = null;
        if ($request->query->has('ecole') && $request->query->get('ecole') !== '') {
            $ecoleId = $request->query->getInt('ecole');
            $ecole = $ecoleId > 0 ? $ecoleRepository->find($ecoleId) : null;
            if (!$ecole instanceof Ecole) {
                throw $this->createNotFoundException('L’école sélectionnée est introuvable.');
            }
        }

        $configuration = $ecole === null ? null : $configurationRepository->findOneBy(['ecole' => $ecole]);

        return [$ecoles, $ecole, $configuration];
    }

    private function anneeScolaireCourante(): string
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('Africa/Kinshasa'));
        $year = (int) $now->format('Y');
        $startYear = (int) $now->format('n') >= 9 ? $year : $year - 1;

        return sprintf('%d-%d', $startYear, $startYear + 1);
    }
}
