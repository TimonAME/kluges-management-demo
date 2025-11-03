<?php
namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Appointment;
use App\Entity\Todo;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use function Webmozart\Assert\Tests\StaticAnalysis\null;
use App\Entity\Room;

class AppointmentProvider implements ProviderInterface
{
    private EntityManagerInterface $entityManager;
    private Security $security;

    public function __construct(EntityManagerInterface $entityManager, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
    }

    /**
     * @throws \Exception
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array|null|object
    {
        $route = $context['request']?->attributes->get('_route');
        $currentUser = $this->security->getUser();
        $appointmentRepository = $this->entityManager->getRepository(Appointment::class);

        switch ($route) {
            case 'readAll':
                return $appointmentRepository->getAllAppointmentsForUser($currentUser);
            case 'readAllTwoMonths':
                if ($context['request']->query->get('date') !== null) {
                    $thisMonth = new \DateTime($context['request']->query->get('date'));
                    return $appointmentRepository->getAllAppointmentsTimespanFromDate($currentUser, $thisMonth);
                }
                return $appointmentRepository->getAllAppointmentsTimespanFromDate($currentUser);
            case 'readAllToday':
                return $appointmentRepository->getAllAppointmentsToday($currentUser);
            case 'searchByTitle':
                if ($context['request']->query->get('title') !== null) {
                    $title = $context['request']->query->get('title');
                    return $appointmentRepository->getAllAppointmentsByTitle($title, $currentUser);
                }
                return $appointmentRepository->getAllAppointmentsForUser($currentUser);
            case 'searchByTimespan':
                if ($context['request']->query->get('startDate') !== null && $context['request']->query->get('endDate') !== null) {
                    $startDate = new \DateTimeImmutable($context['request']->query->get('startDate'));
                    $endDate = new \DateTimeImmutable($context['request']->query->get('endDate'));
                    if ($startDate > $endDate) {
                        return $appointmentRepository->getAllAppointmentsByStartTimeAndEndTimeForUser($endDate, $startDate, $currentUser);
                    }
                    return $appointmentRepository->getAllAppointmentsByStartTimeAndEndTimeForUser($startDate, $endDate, $currentUser);
                }
                return $appointmentRepository->getAllAppointmentsForUser($currentUser);
            case 'searchByCategory':
                if ($context['request']->query->get('categoryId') !== null) {
                    $catId = $context['request']->query->get('categoryId');
                    return $appointmentRepository->getAllAppointmentsByCategory($catId, $currentUser);
                }
                return $appointmentRepository->getAllAppointmentsByCategory(2);
            case 'getAvailableRooms':
                $startTime = new \DateTime($context['request']->query->get('startTime'));
                $endTime = new \DateTime($context['request']->query->get('endTime'));

                // Debug-Logging hinzufügen
                error_log("Checking room availability from " . $startTime->format('Y-m-d H:i:s') . " to " . $endTime->format('Y-m-d H:i:s'));

                // Änderung: Alle Räume holen, nicht nach Location filtern
                $roomRepository = $this->entityManager->getRepository(Room::class);
                $rooms = $roomRepository->findAll();

                // Prüfe, welche Räume verfügbar sind
                $availableRooms = [];
                foreach ($rooms as $room) {
                    // Prüfe, ob der Raum für den angegebenen Zeitraum verfügbar ist
                    $existingAppointments = $appointmentRepository->findOverlappingAppointmentsForRoom(
                        $room->getId(),
                        $startTime,
                        $endTime
                    );

                    if (count($existingAppointments) === 0) {
                        // Stelle sicher, dass die Location-Informationen geladen werden
                        $location = $room->getInLocation();
                        if ($location) {
                            // Füge die Location-Informationen direkt als Eigenschaften hinzu
                            $room->in_location_id = $location->getId();
                            $room->in_location_city = $location->getCity();
                        }
                        $availableRooms[] = $room;
                    }
                }

                return $availableRooms;
            case 'getTutoringAppointment':
                $id = $uriVariables['id'];
                $appointment = $appointmentRepository->find($id);

                // Überprüfen, ob es sich um einen Nachhilfetermin handelt
                if (!$appointment || $appointment->getAppointmentCategory()->getName() !== 'Nachhilfetermin') {
                    return null;
                }

                return $appointment;
            case 'searchTutoringByTimespan':
                if ($context['request']->query->get('startDate') !== null && $context['request']->query->get('endDate') !== null) {
                    $startDate = new \DateTimeImmutable($context['request']->query->get('startDate'));
                    $endDate = new \DateTimeImmutable($context['request']->query->get('endDate'));
                    $userId = $uriVariables['id'];

                    if ($startDate > $endDate) {
                        return $appointmentRepository->getAllAppointmentsByStartTimeAndEndTimeForUserButOnlyIfTutoringAppointment($endDate, $startDate, $userId);
                    }
                    return $appointmentRepository->getAllAppointmentsByStartTimeAndEndTimeForUserButOnlyIfTutoringAppointment($startDate, $endDate, $userId);
                }
                return [];
            case 'searchTutoringByUser':
                if ($context['request']->query->get('startDate') !== null && $context['request']->query->get('endDate') !== null) {
                    $startDate = new \DateTimeImmutable($context['request']->query->get('startDate'));
                    $endDate = new \DateTimeImmutable($context['request']->query->get('endDate'));
                    $userId = $uriVariables['id'];

                    if ($startDate > $endDate) {
                        return $appointmentRepository->getAllAppointmentsByStartTimeAndEndTimeForUserButOnlyIfTutoringAppointment($endDate, $startDate, $userId);
                    }
                    return $appointmentRepository->getAllAppointmentsByStartTimeAndEndTimeForUserButOnlyIfTutoringAppointment($startDate, $endDate, $userId);
                }
                return [];
        }
        return new ArrayCollection();
        // Custom query to get the required data
    }
}
