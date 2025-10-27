<?php

namespace App\Service;

use App\Entity\Workshop;
use App\Repository\WorkshopDayRepository;
use App\Repository\WorkshopRepository;

class WorkshopListGeneration
{
    private WorkshopRepository $workshopRepository;
    private WorkshopDayRepository $dayRepository;


    public function __construct(
        WorkshopRepository $workshopRepository, 
        WorkshopDayRepository $dayRepository
    )
    {
        $this->workshopRepository = $workshopRepository;
        $this->dayRepository = $dayRepository;
    }


    public function generateList(
        Workshop $workshop
    ): array
    {
        // récupérer les jours du workshop
        $days = $this->dayRepository->findActiveByWorkshop($workshop);
        
        $participants = [];
        $finalList = [];

        // parcourir les participations de chacun de ces jours
        foreach ($days as $day) {
            $participations = $day->getParticipations();
            

            // pour chaque participation, récuperer le participant
            foreach ($participations as $participation) {

                if ($participation->isPresent()) {
                    $participant = $participation->getParticipant();
                    
                    if ($participant) {
                        $participants[] = $participant;
                    }
                }
                else {
                    continue;
                }
            }

        }
        
        // parcourir la liste des participants et générer la liste
        foreach ($participants as $participant) {
            
            $participantObject = [
                'id' => $participant->getId(),
                'phone' => $participant->getPhone(),
                'fullname' => $participant->getDemographic()->getFullname(),
                'numberOfDays' => 1,
            ];

            if (!in_array($participantObject, $finalList)) {
                $finalList[] = $participantObject;

            } else {
                // Si le participant est déjà dans la liste, on peut mettre à jour d'autres informations si nécessaire
                foreach ($finalList as &$existingParticipant) {
                    
                    if ($existingParticipant['id'] === $participant->getId()) {
                        // Incrémenter le nombre de jours
                        $existingParticipant['numberOfDays'] += 1;
                    }
                }
            }
        }


        // Retirer l'id du participant pour la version finale
        foreach ($finalList as &$entry) {
            unset($entry['id']);
        }

        return [
            'workshopName' => $workshop->getName(),
            'shortcode'=> $workshop->getConfiguration()->getShortcode() ?? "zando",
            'encadreur' => $workshop->getCreatedBy()->getFirstname() . ' ' . $workshop->getCreatedBy()->getLastname(),
            'totalDays' => strval(count($days)),
            'participants' => $finalList,
        ];
    }

}
