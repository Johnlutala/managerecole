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
        // parcourir les participations
        foreach ($days as $day) {
            foreach ($day->getParticipations() as $participation) {

                if (!$participation->isPresent()) {
                    continue;
                }

                $participant = $participation->getParticipant();

                if (!$participant) {
                    continue;
                }

                $id = $participant->getId();

                if (!isset($participants[$id])) {

                    $participants[$id] = [
                        'phone' => $participant->getPhoneMobileMoney(),
                        'name' => $participant->getDemographic()->getFullname(),
                        'numberOfDays' => 1,
                        'amount' => $workshop->getDailyAmount(),
                        'currency' => $workshop->getCurrency(),
                    ];

                } else {
                    $participants[$id]['numberOfDays']++;
                }
            }
        }

        // calcul du montant final
        foreach ($participants as &$participant) {
            $participant['amount'] = $participant['amount'] * $participant['numberOfDays'];
        }

        return [
            'workshopName' => $workshop->getName(),
            'shortcode' => $workshop->getConfiguration()->getShortcode() ?? 'zando',
            'encadreur' => $workshop->getCreatedBy()->getFirstname().' '.$workshop->getCreatedBy()->getLastname(),
            'totalDays' => (string) count($days),
            'participants' => array_values($participants),
        ];
    }

}
