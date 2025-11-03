<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Appointment;
use App\Entity\AppointmentCategory;
use App\Entity\Location;
use App\Entity\Notification;
use App\Entity\NotificationTag;
use App\Entity\Room;
use App\Entity\Todo;
use App\Entity\User;
use App\Entity\UserTodo;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\User\UserInterface;
use function Webmozart\Assert\Tests\StaticAnalysis\inArray;

class AppointmentProcessor implements ProcessorInterface
{
    private Security $security;
    private EntityManagerInterface $entityManager;

    public function __construct(Security $security, EntityManagerInterface $entityManager)
    {
        $this->security = $security;
        $this->entityManager = $entityManager;
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        $currentUser = $this->security->getUser(); //get the current user
        if (!$currentUser instanceof UserInterface) { //check if the user is authenticated
            throw new \LogicException('No authenticated user found.');
        }
        $route = $context['request']?->attributes->get('_route'); //get the type of request for the switch case
        $decodedContent = json_decode($context['request']?->getContent(), true); //get the request body data
        $userRepository = $this->entityManager->getRepository(User::class);
        $locationRepository = $this->entityManager->getRepository(Location::class);
        $appointmentRepository = $this->entityManager->getRepository(Appointment::class);
        $categoryRepository = $this->entityManager->getRepository(AppointmentCategory::class);
        $roomRepository = $this->entityManager->getRepository(Room::class);
        $notificationTagRepository = $this->entityManager->getRepository(NotificationTag::class);

        switch ($route) {
            case 'createAppointment':
                #var_dump($currentUser->getRoles());
                #echo "Location " . $locationRepository->find($decodedContent['location']['id'])->getType();
                $appointment = new Appointment();
                $appointment->setCreator($currentUser);
                $appointment->setAllDay($decodedContent['allDay'] ?? false);
                $appointment->setAppointmentCategory($categoryRepository->find($decodedContent['appointmentCategory']['id']));
                $appointment->setDescription($decodedContent['description']);
                $appointment->setTitle($decodedContent['title']);
                $appointment->setColor($decodedContent['color']);
                $appointment->setStartTime(new \DateTime($decodedContent['startTime']));
                $appointment->setEndTime(new \DateTime($decodedContent['endTime']));
                foreach ($decodedContent['users'] as $user) {
                    $user = $userRepository->find($user['id']);
                    $appointment->addUser($user);

                    // add a notification for the added appointment
                    $notification = new Notification();
                    $notification->setDateCreated(new \DateTime('now'));
                    $notification->setMessage("Neuer Termin: " . $appointment->getTitle());
                    $notification->addNotificationTag($notificationTagRepository->findOneBy(
                        ['name' => 'Termin']
                    ));
                    $notification->setIsRead(false);
                    $notification->addUser($user);
                    $this->entityManager->persist($notification);
                }
                $appointment->setLocation($locationRepository->find($decodedContent['location']['id']));
                if (isset($decodedContent['room']) && is_array($decodedContent['room']) && !empty($decodedContent['room']['id'])) {
                    $appointment->setRoom($roomRepository->find($decodedContent['room']['id']));
                } else {
                    $appointment->setRoom(null);
                }
                if (isset($decodedContent['teacher']) && is_array($decodedContent['teacher']) && !empty($decodedContent['teacher']['id'])) {
                    $appointment->setTeacher($userRepository->find($decodedContent['teacher']['id']));
                }

                $this->entityManager->persist($appointment);
                break;

            case 'updateAppointment':
                $userToNotAdd = array();
                $appointment = $appointmentRepository->find($uriVariables['id']);
                $appointment->setAllDay($decodedContent['allDay'] ?? $appointment->isAllDay());
                if (isset($decodedContent['appointmentCategory'])) {
                    $appointment->setAppointmentCategory($categoryRepository->find($decodedContent['appointmentCategory']['id']));
                }
                $appointment->setDescription($decodedContent['description'] ?? $appointment->getDescription());
                $appointment->setTitle($decodedContent['title'] ?? $appointment->getTitle());
                $appointment->setColor($decodedContent['color'] ?? $appointment->getColor());
                if (isset($decodedContent['startTime'])) {
                    $appointment->setStartTime(new \DateTime($decodedContent['startTime']));
                }
                if (isset($decodedContent['endTime'])) {
                    $appointment->setEndTime(new \DateTime($decodedContent['endTime']) ?? $appointment->getEndTime());
                }
                if (isset($decodedContent['users'])) {
                    foreach ($decodedContent['users'] as $user) {
                        $userToAdd = $userRepository->find($user['id']);
                        $appointment->addUser($userToAdd);
                        $userToNotAdd[] = $user;
                    }
                }
                if (isset($decodedContent['location'])) {
                    $appointment->setLocation($locationRepository->find($decodedContent['location']['id']));
                }
                if (isset($decodedContent['room'])) {
                    if (is_array($decodedContent['room']) && !empty($decodedContent['room']['id'])) {
                        $appointment->setRoom($roomRepository->find($decodedContent['room']['id']));
                    } else {
                        $appointment->setRoom(null);
                    }
                }
                if (isset($decodedContent['teacher'])) {
                    if (is_array($decodedContent['teacher']) && !empty($decodedContent['teacher']['id'])) {
                        $appointment->setTeacher($userRepository->find($decodedContent['teacher']['id']));
                    } else {
                        $appointment->setTeacher(null);
                    }
                }
                if (isset($decodedContent['attendance'])) {
                    $appointment->setAttendance($decodedContent['attendance']);
                }
                if (isset($decodedContent['homework'])) {
                    $appointment->setHomework($decodedContent['homework']);
                }
                if (isset($decodedContent['notes'])) {
                    $appointment->setNotes($decodedContent['notes']);
                }
                $allUsers = $appointment->getUsers();
                foreach ($allUsers as $userNow) {
                    if (!inArray($userNow, $userToNotAdd)) {
                        $notification = new Notification();
                        $notification->setDateCreated(new \DateTime('now'));
                        $notification->setMessage("Neuer Termin: " . $appointment->getTitle());
                        $notification->addNotificationTag($notificationTagRepository->findOneBy(
                            ['name' => 'Termin']
                        ));
                        $notification->addNotificationTag($notificationTagRepository->findOneBy(
                            ['name' => 'Info']
                        ));
                        $notification->setIsRead(false);
                        $notification->addUser($user);
                        $this->entityManager->persist($notification);
                    }
                }
                break;

            case 'removeAssignedUser':
                $appointment = $appointmentRepository->find($uriVariables['id']);
                foreach ($decodedContent['users'] as $user) {
                    $userToRemove = $userRepository->find($user['id']);
                    $appointment->removeUser($userToRemove);

                    // add a notification for the added appointment
                    $notification = new Notification();
                    $notification->setDateCreated(new \DateTime('now'));
                    $notification->setMessage("Sie wurden verraten und vom Termin " . $appointment->getTitle() . " entfernt.");
                    $notification->addNotificationTag($notificationTagRepository->findOneBy(
                        ['name' => 'Termin']
                    ));
                    $notification->addNotificationTag($notificationTagRepository->findOneBy(
                        ['name' => 'Update']
                    ));
                    $notification->setIsRead(false);
                    $notification->addUser($user);
                    $this->entityManager->persist($notification);
                }
                break;
            case 'deleteAppointment':
                $appointment = $appointmentRepository->find($uriVariables['id']);
                $allUsers = $appointment->getUsers();
                foreach ($allUsers as $user) {
                    $notification = new Notification();
                    $notification->setDateCreated(new \DateTime('now'));
                    $notification->setMessage("Der Termin " . $appointment->getTitle() . " wurde entfernt.");
                    $notification->addNotificationTag($notificationTagRepository->findOneBy(
                        ['name' => 'Termin']
                    ));
                    $notification->addNotificationTag($notificationTagRepository->findOneBy(
                        ['name' => 'Wichtig']
                    ));
                    $notification->setIsRead(false);
                    $notification->addUser($user);
                    $this->entityManager->persist($notification);
                }
                $this->entityManager->remove($appointment);
        }
        $this->entityManager->flush();
        new JsonResponse(
            [
                'success' => true,
            ],
            200
        );
    }
}