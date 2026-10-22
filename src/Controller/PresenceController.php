<?php

namespace App\Controller;

use App\Entity\Classe;
use App\Entity\Ecole;
use App\Entity\Presence;
use App\Entity\PresencePersonnel;
use App\Entity\Professeur;
use App\Entity\User;
use App\Repository\ClasseRepository;
use App\Repository\EcoleRepository;
use App\Repository\EleveRepository;
use App\Repository\OptionRepository;
use App\Repository\PresencePersonnelRepository;
use App\Repository\PresenceRepository;
use App\Repository\ProfesseurRepository;
use App\Repository\SectionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/presence', name: 'app_presence_')]
final class PresenceController extends AbstractController
{
    private const STATUTS = ['present', 'absent', 'retard', 'autorise', 'depart_anticipe'];

    #[Route('', name: 'index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        ClasseRepository $classes,
        EleveRepository $eleves,
        PresenceRepository $presences,
        EcoleRepository $ecoles,
        SectionRepository $sections,
        OptionRepository $options,
        EntityManagerInterface $em
    ): Response {
        $date = new \DateTime($request->query->get('date', 'today'));
        $ecole = $request->query->getInt('ecole') ? $ecoles->find($request->query->getInt('ecole')) : null;
        $section = $request->query->getInt('section') ? $sections->find($request->query->getInt('section')) : null;
        $classe = $request->query->getInt('classe') ? $classes->find($request->query->getInt('classe')) : null;
        $option = $request->query->getInt('option') ? $options->find($request->query->getInt('option')) : null;

        if ($section !== null && $ecole !== null && $section->getEcole()?->getId() !== $ecole->getId()) {
            $section = null;
        }
        if ($classe !== null && (
            ($ecole !== null && $classe->getEcole()?->getId() !== $ecole->getId())
            || ($section !== null && $classe->getSection()?->getId() !== $section->getId())
        )) {
            $classe = null;
        }
        if ($classe !== null) {
            $ecole ??= $classe->getEcole();
            $section ??= $classe->getSection();
        }
        if (
            $option !== null
            && (
                $option->isDeleted() === true
                || $option->getClasse()?->getId() !== $classe?->getId()
            )
        ) {
            $option = null;
        }

        $rows = $classe !== null
            ? $presences->findBy(['classe' => $classe, 'date' => $date, 'deleted' => false])
            : [];

        if ($request->isMethod('POST')) {
            if (!$classe instanceof Classe || !$this->isCsrfTokenValid('attendance_students', (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException();
            }
            if ($rows !== []) {
                $this->addFlash('warning', 'Les présences de cette classe sont déjà enregistrées. Modifiez-les depuis le contrôle des présences.');

                return $this->redirectToRoute('app_presence_index', [
                    'ecole' => $ecole?->getId(),
                    'section' => $section?->getId(),
                    'classe' => $classe->getId(),
                    'option' => $option?->getId(),
                    'date' => $date->format('Y-m-d'),
                ]);
            }

            $submittedAttendance = (array) $request->request->all('attendance');
            foreach ($submittedAttendance as $eleveId => $row) {
                $eleve = $eleves->find((int) $eleveId);
                $row = (array) $row;
                $status = (string) ($row['statut'] ?? '');
                if (
                    !$eleve
                    || $eleve->getClasse()?->getId() !== $classe->getId()
                    || ($option !== null && $eleve->getOption()?->getId() !== $option->getId())
                    || !in_array($status, ['present', 'absent', 'retard', 'autre'], true)
                    || ($status === 'autre' && trim((string) ($row['motif'] ?? '')) === '')
                ) {
                    $this->addFlash('danger', 'Vérifiez les élèves, les états et les motifs saisis.');

                    return $this->redirectToRoute('app_presence_index', [
                        'ecole' => $ecole?->getId(),
                        'section' => $section?->getId(),
                        'classe' => $classe->getId(),
                        'option' => $option?->getId(),
                        'date' => $date->format('Y-m-d'),
                    ]);
                }
                foreach (['arrivee', 'depart'] as $timeField) {
                    $timeValue = trim((string) ($row[$timeField] ?? ''));
                    if ($timeValue !== '' && $this->time($timeValue) === null) {
                        $this->addFlash('danger', 'Une heure d’arrivée ou de départ est invalide.');

                        return $this->redirectToRoute('app_presence_index', [
                            'ecole' => $ecole?->getId(),
                            'section' => $section?->getId(),
                            'classe' => $classe->getId(),
                            'option' => $option?->getId(),
                            'date' => $date->format('Y-m-d'),
                        ]);
                    }
                }
            }

            foreach ($submittedAttendance as $eleveId => $row) {
                $eleve = $eleves->find((int) $eleveId);
                if (!$eleve) {
                    continue;
                }
                $row = (array) $row;
                $statut = (string) $row['statut'];
                $presence = (new Presence())
                    ->setEleve($eleve)
                    ->setClasse($classe)
                    ->setDate(clone $date)
                    ->setStatut($statut)
                    ->setHeureArrivee($this->timeOrDefault($row['arrivee'] ?? null, '07:30'))
                    ->setHeureDepart($this->timeOrDefault($row['depart'] ?? null, '12:30'))
                    ->setObservation($statut === 'autre' ? (trim((string) ($row['motif'] ?? '')) ?: null) : null);
                if ($this->getUser() instanceof User) {
                    $presence->setCreatedBy($this->getUser());
                }
                $em->persist($presence);
            }

            $em->flush();
            $this->addFlash('success', 'Présences élèves enregistrées.');

            return $this->redirectToRoute('app_presence_index', [
                'ecole' => $ecole?->getId(),
                'section' => $section?->getId(),
                'classe' => $classe->getId(),
                'option' => $option?->getId(),
                'date' => $date->format('Y-m-d'),
            ]);
        }

        $studentCriteria = ['statut' => true, 'deleted' => false];
        if ($classe !== null) {
            $studentCriteria['classe'] = $classe;
        }
        if ($option !== null) {
            $studentCriteria['option'] = $option;
        }
        $students = $classe !== null ? $eleves->findBy($studentCriteria, ['nom' => 'ASC']) : [];
        $byStudent = [];
        foreach ($rows as $item) {
            $byStudent[$item->getEleve()?->getId()] = $item;
        }
        $author = ($rows[0] ?? null)?->getCreatedBy();
        $authorName = $author
            ? (trim(($author->getFirstname() ?? '') . ' ' . ($author->getLastname() ?? '')) ?: $author->getUsername())
            : null;

        $classCriteria = ['deleted' => false];
        if ($ecole !== null) {
            $classCriteria['ecole'] = $ecole;
        }
        if ($section !== null) {
            $classCriteria['section'] = $section;
        }
        $classList = $classes->findBy($classCriteria, ['nom' => 'ASC']);
        $optionList = $classe !== null
            ? $options->findBy(['classe' => $classe, 'deleted' => false], ['nom' => 'ASC'])
            : [];

        return $this->render('presence/index.html.twig', [
            'date' => $date,
            'ecole' => $ecole,
            'ecoles' => $ecoles->findBy(['deleted' => false], ['nom' => 'ASC']),
            'section' => $section,
            'sections' => $ecole !== null
                ? $sections->findBy(['ecole' => $ecole, 'deleted' => false], ['nom' => 'ASC'])
                : $sections->findBy(['deleted' => false], ['nom' => 'ASC']),
            'classe' => $classe,
            'classes' => $classList,
            'option' => $option,
            'options' => $optionList,
            'students' => $students,
            'byStudent' => $byStudent,
            'presenceAlreadyRecorded' => $rows !== [],
            'presenceAuthor' => $authorName,
        ]);
    }

    #[Route('/personnel', name: 'personnel', methods: ['GET', 'POST'])]
    public function personnel(
        Request $request,
        ProfesseurRepository $professeurs,
        UserRepository $users,
        PresencePersonnelRepository $presences,
        EntityManagerInterface $em
    ): Response {
        $date = new \DateTime($request->query->get('date', 'today'));
        $records = $presences->findBy(['date' => $date, 'deleted' => false]);
        $staff = $this->personnelList($professeurs, $users);
        $existing = [];
        foreach ($records as $record) {
            $key = $record->getProfesseur()
                ? 'prof-' . $record->getProfesseur()->getId()
                : 'user-' . $record->getPersonnel()?->getId();
            $existing[$key] = $record;
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('attendance_staff', (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException();
            }
            if ($records !== []) {
                $this->addFlash('warning', 'Les présences du personnel sont déjà enregistrées pour cette date. Modifiez-les depuis le contrôle.');

                return $this->redirectToRoute('app_presence_personnel', ['date' => $date->format('Y-m-d')]);
            }

            $submittedAttendance = (array) $request->request->all('attendance');
            foreach ($submittedAttendance as $key => $row) {
                $row = (array) $row;
                if (
                    !isset($staff[$key])
                    || !in_array($row['statut'] ?? '', ['present', 'absent', 'retard', 'autre'], true)
                    || (($row['statut'] ?? '') === 'autre' && trim((string) ($row['motif'] ?? '')) === '')
                ) {
                    $this->addFlash('danger', 'Vérifiez le personnel, les états et les motifs saisis.');

                    return $this->redirectToRoute('app_presence_personnel', ['date' => $date->format('Y-m-d')]);
                }
                foreach (['arrivee', 'depart'] as $timeField) {
                    $timeValue = trim((string) ($row[$timeField] ?? ''));
                    if ($timeValue !== '' && $this->time($timeValue) === null) {
                        $this->addFlash('danger', 'Une heure d’arrivée ou de départ est invalide.');

                        return $this->redirectToRoute('app_presence_personnel', ['date' => $date->format('Y-m-d')]);
                    }
                }
            }

            foreach ($submittedAttendance as $key => $row) {
                $row = (array) $row;
                [$kind, $id] = array_pad(explode('-', (string) $key, 2), 2, null);
                $person = $kind === 'prof'
                    ? $professeurs->find((int) $id)
                    : ($kind === 'user' ? $users->find((int) $id) : null);
                if (!$person) {
                    continue;
                }

                $presence = (new PresencePersonnel())->setDate(clone $date);
                $kind === 'prof'
                    ? $presence->setProfesseur($person)
                    : $presence->setPersonnel($person);
                $presence->setStatut((string) $row['statut'])
                    ->setHeureArrivee($this->timeOrDefault($row['arrivee'] ?? null, '07:30'))
                    ->setHeureDepart($this->timeOrDefault($row['depart'] ?? null, '12:30'))
                    ->setObservation($row['statut'] === 'autre'
                        ? (trim((string) ($row['motif'] ?? '')) ?: null)
                        : null);
                if ($this->getUser() instanceof User) {
                    $presence->setCreatedBy($this->getUser());
                }
                $em->persist($presence);
            }

            $em->flush();
            $this->addFlash('success', 'Présences du personnel enregistrées.');

            return $this->redirectToRoute('app_presence_personnel', ['date' => $date->format('Y-m-d')]);
        }

        $author = ($records[0] ?? null)?->getCreatedBy();

        return $this->render('presence/personnel.html.twig', [
            'date' => $date,
            'staff' => $staff,
            'existing' => $existing,
            'presenceAlreadyRecorded' => $records !== [],
            'presenceAuthor' => $author
                ? (trim(($author->getFirstname() ?? '') . ' ' . ($author->getLastname() ?? '')) ?: $author->getUsername())
                : null,
        ]);
    }

    #[Route('/personnel/controle', name: 'personnel_control', methods: ['GET', 'POST'])]
    public function personnelControl(
        Request $request,
        ProfesseurRepository $professeurs,
        UserRepository $users,
        PresencePersonnelRepository $presences,
        EntityManagerInterface $em
    ): Response {
        $date = new \DateTime($request->query->get('date', 'today'));
        $staff = $this->personnelList($professeurs, $users);
        $records = $presences->findBy(['date' => $date, 'deleted' => false]);
        $existing = [];
        foreach ($records as $record) {
            $key = $record->getProfesseur()
                ? 'prof-' . $record->getProfesseur()->getId()
                : 'user-' . $record->getPersonnel()?->getId();
            $existing[$key] = $record;
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('edit_staff_attendance', (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException();
            }

            $submittedAttendance = (array) $request->request->all('attendance');
            foreach ($submittedAttendance as $key => $row) {
                $row = (array) $row;
                if (
                    !isset($staff[$key])
                    || !in_array($row['statut'] ?? '', ['present', 'absent', 'retard', 'autre'], true)
                    || (($row['statut'] ?? '') === 'autre' && trim((string) ($row['motif'] ?? '')) === '')
                ) {
                    $this->addFlash('danger', 'Vérifiez le personnel, les états et les motifs saisis.');

                    return $this->redirectToRoute('app_presence_personnel_control', ['date' => $date->format('Y-m-d')]);
                }
                foreach (['arrivee', 'depart'] as $timeField) {
                    $timeValue = trim((string) ($row[$timeField] ?? ''));
                    if ($timeValue !== '' && $this->time($timeValue) === null) {
                        $this->addFlash('danger', 'Une heure d’arrivée ou de départ est invalide.');

                        return $this->redirectToRoute('app_presence_personnel_control', ['date' => $date->format('Y-m-d')]);
                    }
                }
            }

            foreach ($submittedAttendance as $key => $row) {
                $row = (array) $row;
                [$kind, $id] = array_pad(explode('-', (string) $key, 2), 2, null);
                $person = $kind === 'prof'
                    ? $professeurs->find((int) $id)
                    : ($kind === 'user' ? $users->find((int) $id) : null);
                if (!$person) {
                    continue;
                }

                $criteria = $kind === 'prof'
                    ? ['professeur' => $person, 'date' => $date, 'deleted' => false]
                    : ['personnel' => $person, 'date' => $date, 'deleted' => false];
                $presence = $presences->findOneBy($criteria)
                    ?? (new PresencePersonnel())->setDate(clone $date);
                $kind === 'prof'
                    ? $presence->setProfesseur($person)
                    : $presence->setPersonnel($person);
                $presence->setStatut((string) $row['statut'])
                    ->setHeureArrivee($this->timeOrDefault($row['arrivee'] ?? null, '07:30'))
                    ->setHeureDepart($this->timeOrDefault($row['depart'] ?? null, '12:30'))
                    ->setObservation($row['statut'] === 'autre'
                        ? (trim((string) ($row['motif'] ?? '')) ?: null)
                        : null);
                if ($this->getUser() instanceof User && $presence->getCreatedBy() === null) {
                    $presence->setCreatedBy($this->getUser());
                }
                $em->persist($presence);
            }

            $em->flush();
            $this->addFlash('success', 'Les présences du personnel ont été mises à jour.');

            return $this->redirectToRoute('app_presence_personnel_control', ['date' => $date->format('Y-m-d')]);
        }

        return $this->render('presence/personnel_control.html.twig', [
            'date' => $date,
            'staff' => $staff,
            'existing' => $existing,
            'presenceAlreadyRecorded' => $records !== [],
        ]);
    }

    #[Route('/controle', name: 'control', methods: ['GET', 'POST'])]
    public function control(Request $request, EcoleRepository $ecoles, SectionRepository $sections, ClasseRepository $classes, OptionRepository $options, EleveRepository $eleves, PresenceRepository $presences, EntityManagerInterface $em): Response
    {
        $date=new \DateTime($request->query->get('date','today')); $school=$request->query->getInt('ecole')?$ecoles->find($request->query->getInt('ecole')):$ecoles->findOneBy(['deleted'=>false]); $section=$request->query->getInt('section')?$sections->find($request->query->getInt('section')):null; $option=$request->query->getInt('option')?$options->find($request->query->getInt('option')):null; $selectedClasse=$request->query->getInt('classe')?$classes->find($request->query->getInt('classe')):null;
        $criteria=['deleted'=>false];if($school instanceof Ecole)$criteria['ecole']=$school;if($section)$criteria['section']=$section;if($selectedClasse)$criteria['id']=$selectedClasse->getId();if($option)$criteria['id']=$option->getClasse()?->getId()??0;$filteredClasses=$classes->findBy($criteria,['nom'=>'ASC']);
        if (
            $request->isMethod('POST')
            && $request->request->get('action') === 'edit_attendance'
        ) {
            if (
                !$selectedClasse instanceof Classe
                || !in_array($selectedClasse, $filteredClasses, true)
                || !$this->isCsrfTokenValid('edit_attendance', (string) $request->request->get('_token'))
            ) {
                throw $this->createAccessDeniedException();
            }

            $submittedAttendance = (array) $request->request->all('attendance');
            foreach ($submittedAttendance as $studentId => $row) {
                $student = $eleves->find((int) $studentId);
                $row = (array) $row;
                $status = (string) ($row['statut'] ?? '');
                $reason = trim((string) ($row['motif'] ?? ''));
                if (
                    !$student
                    || $student->getClasse()?->getId() !== $selectedClasse->getId()
                    || ($option !== null && $student->getOption()?->getId() !== $option->getId())
                    || !in_array($status, ['present', 'absent', 'retard', 'autre'], true)
                    || ($status === 'autre' && $reason === '')
                ) {
                    $this->addFlash('danger', 'Vérifiez les élèves, les états et les motifs saisis.');

                    return $this->redirectToRoute('app_presence_control', $this->filters($school, $section, $selectedClasse, $option, $date));
                }
                foreach (['arrivee', 'depart'] as $timeField) {
                    $timeValue = trim((string) ($row[$timeField] ?? ''));
                    if ($timeValue !== '' && $this->time($timeValue) === null) {
                        $this->addFlash('danger', 'Une heure d’arrivée ou de départ est invalide.');

                        return $this->redirectToRoute('app_presence_control', $this->filters($school, $section, $selectedClasse, $option, $date));
                    }
                }
            }

            foreach ($submittedAttendance as $studentId => $row) {
                $student = $eleves->find((int) $studentId);
                if (!$student) {
                    continue;
                }

                $row = (array) $row;
                $status = (string) $row['statut'];
                $presence = $presences->findOneBy([
                    'eleve' => $student,
                    'classe' => $selectedClasse,
                    'date' => $date,
                    'deleted' => false,
                ]);
                if ($presence === null) {
                    $presence = (new Presence())
                        ->setEleve($student)
                        ->setClasse($selectedClasse)
                        ->setDate(clone $date);
                    if ($this->getUser() instanceof User) {
                        $presence->setCreatedBy($this->getUser());
                    }
                }

                $presence->setStatut($status)
                    ->setObservation($status === 'autre' ? trim((string) $row['motif']) : null)
                    ->setHeureArrivee($this->timeOrDefault($row['arrivee'] ?? null, '07:30'))
                    ->setHeureDepart($this->timeOrDefault($row['depart'] ?? null, '12:30'));
                $em->persist($presence);
            }

            $em->flush();
            $this->addFlash('success', 'Les présences de la classe ont été mises à jour.');

            return $this->redirectToRoute('app_presence_control', $this->filters($school, $section, $selectedClasse, $option, $date));
        }
        if($request->isMethod('POST')&&$selectedClasse&&$this->isCsrfTokenValid('validate_day',(string)$request->request->get('_token'))){$students=$eleves->findBy(['classe'=>$selectedClasse,'statut'=>true,'deleted'=>false]);$records=$presences->findBy(['classe'=>$selectedClasse,'date'=>$date,'deleted'=>false]);if(count($records)<count($students)){$this->addFlash('error','Impossible de valider : toutes les présences doivent être renseignées.');}else{foreach($records as$record){$record->setValidatedAt(new \DateTime());if($this->getUser() instanceof User)$record->setValidatedBy($this->getUser());}$em->flush();$this->addFlash('success','Journée validée. Les rapports mobiles sont disponibles.');}return $this->redirectToRoute('app_presence_control',$this->filters($school,$section,$selectedClasse,$option,$date));}
        $dashboard=[];$lateLimit=(clone$date)->modify('-29 days');foreach($filteredClasses as$class){$students=$eleves->findBy(['classe'=>$class,'statut'=>true,'deleted'=>false]);$records=$presences->findBy(['classe'=>$class,'date'=>$date,'deleted'=>false]);$count=['present'=>0,'absent'=>0,'retard'=>0,'autorise'=>0,'depart_anticipe'=>0];foreach($records as$record)$count[$record->getStatut()]=($count[$record->getStatut()]??0)+1;$recurring=$presences->createQueryBuilder('p')->select('COUNT(DISTINCT IDENTITY(p.eleve))')->andWhere('p.classe=:classe')->andWhere('p.statut=:absent')->andWhere('p.date BETWEEN :start AND :end')->setParameter('classe', $class)->setParameter('absent', 'absent')->setParameter('start', $lateLimit)->setParameter('end', $date)->groupBy('p.eleve')->having('COUNT(p.id)>3')->getQuery()->getResult();$dashboard[]=['classe'=>$class,'total'=>count($students),'renseignes'=>count($records),'manquants'=>max(0,count($students)-count($records)),'present'=>$count['present'],'absent'=>$count['absent'],'retard'=>$count['retard'],'valides'=>count(array_filter($records,fn($p)=>$p->getValidatedAt()!==null)),'recurringAbsences'=>count($recurring)];}
        $detail=null;if($selectedClasse){$studentCriteria=['classe'=>$selectedClasse,'statut'=>true,'deleted'=>false];if($option)$studentCriteria['option']=$option;$students=$eleves->findBy($studentCriteria,['nom'=>'ASC']);$records=$presences->findBy(['classe'=>$selectedClasse,'date'=>$date,'deleted'=>false]);$map=[];foreach($records as$r)$map[$r->getEleve()?->getId()]=$r;if($records!==[])$detail=['students'=>$students,'records'=>$map];}
        $allSections=$school?$sections->findBy(['ecole'=>$school,'deleted'=>false],['nom'=>'ASC']):[];$allOptions=[];foreach($filteredClasses as$class)foreach($class->getOptions()as$item)$allOptions[]=$item;return $this->render('presence/control.html.twig',['date'=>$date,'school'=>$school,'section'=>$section,'option'=>$option,'selectedClasse'=>$selectedClasse,'schools'=>$ecoles->findBy(['deleted'=>false],['nom'=>'ASC']),'sections'=>$allSections,'options'=>$allOptions,'classes'=>$filteredClasses,'dashboard'=>$dashboard,'detail'=>$detail]);
    }
    private function filters(?Ecole $school,mixed $section,mixed $classe,mixed $option,\DateTime $date):array{return['ecole'=>$school?->getId(),'section'=>$section?->getId(),'classe'=>$classe?->getId(),'option'=>$option?->getId(),'date'=>$date->format('Y-m-d')];}
    private function personnelList(ProfesseurRepository $professeurs, UserRepository $users): array
    {
        $staff = [];
        foreach ($professeurs->findBy(['deleted' => false], ['nom' => 'ASC']) as $professeur) {
            $staff['prof-' . $professeur->getId()] = [
                'label' => trim($professeur->getNom() . ' ' . $professeur->getPostnom() . ' ' . $professeur->getPrenom()),
                'role' => 'Professeur',
            ];
        }
        foreach ($users->findBy(['deleted' => false], ['lastname' => 'ASC']) as $user) {
            if (!$user->getProfesseur()) {
                $staff['user-' . $user->getId()] = [
                    'label' => trim($user->getLastname() . ' ' . $user->getFirstname()) ?: $user->getUsername(),
                    'role' => 'Administratif',
                ];
            }
        }

        return $staff;
    }
    private function time(?string $value): ?\DateTime
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $time = \DateTime::createFromFormat('!H:i', $value);
        $errors = \DateTime::getLastErrors();

        return $time !== false
            && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
            ? $time
            : null;
    }

    private function timeOrDefault(?string $value, string $default): \DateTime
    {
        return $this->time($value) ?? new \DateTime($default);
    }
}
