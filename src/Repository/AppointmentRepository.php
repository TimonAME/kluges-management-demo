<?php

namespace App\Repository;

use App\Entity\Appointment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Appointment>
 */
class AppointmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Appointment::class);
    }

    /**
     * @return Appointment[] Returns an array of Appointment objects
     */

    public function getAllAppointmentsForUser($user): array
    {
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQuery(
            'SELECT a, b, c, d, r
                FROM App\Entity\Appointment a
                LEFT JOIN a.location b
                LEFT JOIN a.creator c
                LEFT JOIN a.appointmentCategory d
                LEFT JOIN a.room r
                WHERE (a.creator = :id OR :id MEMBER OF a.users)'
        )
            ->setParameter('id', $user->getId());
        return $query->getResult();
    }

    /**
     * @return Appointment[] Returns an array of Appointment objects
     */

    public function getAllAppointmentsTimespanFromDate($user, ?\DateTimeInterface $referenceDate = null, ): array
    {
        $referenceDate = $referenceDate ?? new \DateTimeImmutable('now');
        $twoMonthsAgo = (clone $referenceDate)->modify('-2 months');
        $inTwoMonths = (clone $referenceDate)->modify('+2 months');

        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQuery(
            'SELECT a, b, c, d, r
                FROM App\Entity\Appointment a
                LEFT JOIN a.location b
                LEFT JOIN a.creator c
                LEFT JOIN a.appointmentCategory d
                LEFT JOIN a.room r
                WHERE (a.creator = :id OR :id MEMBER OF a.users)
                AND (((a.startTime BETWEEN :twoMonthsAgo and :referenceDate) OR (a.endTime BETWEEN :twoMonthsAgo and :referenceDate))
                OR ((a.startTime BETWEEN :referenceDate and :inTwoMonths) OR (a.endTime BETWEEN :referenceDate and :inTwoMonths)))'
        )
            ->setParameter('id', $user->getId())
            ->setParameter('twoMonthsAgo', $twoMonthsAgo->format('Y-m-d H:i:s'))
            ->setParameter('referenceDate', $referenceDate->format('Y-m-d H:i:s'))
            ->setParameter('inTwoMonths', $inTwoMonths->format('Y-m-d H:i:s'));

        return $query->getResult();
    }

    public function getAllAppointmentsToday($user): array
    {
        $todayStart = (new \DateTime('today'))->setTime(0, 0, 0);
        $todayEnd = (new \DateTime('today'))->setTime(23, 59, 59);


        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQuery(
            'SELECT a, b, c, d, r
                FROM App\Entity\Appointment a
                LEFT JOIN a.location b
                LEFT JOIN a.creator c
                LEFT JOIN a.appointmentCategory d
                LEFT JOIN a.room r
                WHERE (a.creator = :id OR :id MEMBER OF a.users)
                AND (
                    (a.startTime BETWEEN :todayStart AND :todayEnd)
                    OR (a.endTime BETWEEN :todayStart AND :todayEnd)
                    OR (:todayStart BETWEEN a.startTime AND a.endTime)
                )'
        )

            ->setParameter('id', $user->getId())
            ->setParameter('todayStart', $todayStart)
            ->setParameter('todayEnd', $todayEnd);
        return $query->getResult();
    }

    public function getAllAppointmentsByTitle($title, $user)
    {
        return $this->getEntityManager()->createQuery(
            'SELECT a, b, c, d, r
                FROM App\Entity\Appointment a
                LEFT JOIN a.location b
                LEFT JOIN a.creator c
                LEFT JOIN a.appointmentCategory d
                LEFT JOIN a.room r
                WHERE (a.creator = :user OR :user MEMBER OF a.users)
                AND (a.title LIKE :title)'
        )
            ->setParameter('user', $user->getId())
            ->setParameter('title', '%' . $title . '%')
            ->getResult();
    }

    public function getAllAppointmentsByStartTimeAndEndTimeForUser($startDate, $endDate, $user)
    {
        return $this->getEntityManager()->createQuery(
            'SELECT a, b, c, d, r
                FROM App\Entity\Appointment a
                LEFT JOIN a.location b
                LEFT JOIN a.creator c
                LEFT JOIN a.appointmentCategory d
                LEFT JOIN a.room r
                WHERE (a.creator = :id OR :id MEMBER OF a.users)
                AND (a.startTime BETWEEN :startDate and :endDate)'
        )
            ->setParameter('id', $user->getId())
            ->setParameter('startDate', $startDate->format('Y-m-d H:i:s'))
            ->setParameter('endDate', $endDate->format('Y-m-d H:i:s'))
            ->getResult();
    }

    public function getAllAppointmentsByStartTimeAndEndTimeForUserButOnlyIfTutoringAppointment($startDate, $endDate, $userId)
    {
        return $this->getEntityManager()->createQuery(
            'SELECT a, b, c, d, r
                FROM App\Entity\Appointment a
                LEFT JOIN a.location b
                LEFT JOIN a.creator c
                LEFT JOIN a.appointmentCategory d
                LEFT JOIN a.room r
                WHERE (a.creator = :id OR :id MEMBER OF a.users)
                AND (a.startTime BETWEEN :startDate and :endDate)
                AND (d.name = \'Nachhilfetermin\')'
        )
            ->setParameter('id', $userId)
            ->setParameter('startDate', $startDate->format('Y-m-d H:i:s'))
            ->setParameter('endDate', $endDate->format('Y-m-d H:i:s'))
            ->getResult();
    }

    public function getAllAppointmentsByCategory($categoryId, $user)
    {
        return $this->getEntityManager()->createQuery(
            'SELECT a, b, c, d, r
                FROM App\Entity\Appointment a
                LEFT JOIN a.location b
                LEFT JOIN a.creator c
                LEFT JOIN a.appointmentCategory d
                LEFT JOIN a.room r
                WHERE (a.creator = :user OR :user MEMBER OF a.users)
                AND (d.id = :category)'
        )
            ->setParameter('user', $user)
            ->setParameter('category', $categoryId)
            ->getResult();
    }

    /**
     * Findet Termine, die sich mit dem angegebenen Zeitraum für einen bestimmten Raum überschneiden
     */
    public function findOverlappingAppointmentsForRoom(int $roomId, \DateTime $startTime, \DateTime $endTime): array
    {
        // Debug-Logging hinzufügen
        error_log("Checking room $roomId from " . $startTime->format('Y-m-d H:i:s') . " to " . $endTime->format('Y-m-d H:i:s'));

        $result = $this->createQueryBuilder('a')
            ->where('a.room = :roomId')
            ->andWhere('
                (a.startTime < :endTime AND a.endTime > :startTime)
            ')
            ->setParameter('roomId', $roomId)
            ->setParameter('startTime', $startTime)
            ->setParameter('endTime', $endTime)
            ->getQuery()
            ->getResult();

        // Debug-Logging für gefundene Termine
        error_log("Found " . count($result) . " overlapping appointments for room $roomId");

        return $result;
    }
}
