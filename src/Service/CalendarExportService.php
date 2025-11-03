<?php

namespace App\Service;

use App\Entity\Appointment;
use App\Repository\AppointmentRepository;

class CalendarExportService
{
    private AppointmentRepository $appointmentRepository;

    public function __construct(AppointmentRepository $appointmentRepository)
    {
        $this->appointmentRepository = $appointmentRepository;
    }

    public function generateICS(array $appointments = null): string
    {
        if ($appointments === null) {
            $appointments = $this->appointmentRepository->findAll();
        }

        $ics = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//YourApp//Calendar//DE',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
        ];

        foreach ($appointments as $appointment) {
            $ics = array_merge($ics, $this->createEventEntry($appointment));
        }

        $ics[] = 'END:VCALENDAR';

        return implode("\r\n", $ics);
    }

    private function createEventEntry(Appointment $appointment): array
    {
        $start = $appointment->getStartTime()->format($appointment->isAllDay() ? 'Ymd' : "Ymd\THis");
        $end = $appointment->getEndTime()->format($appointment->isAllDay() ? 'Ymd' : "Ymd\THis");
        
        $event = [
            'BEGIN:VEVENT',
            'UID:' . $appointment->getId() . '@yourapp.com',
            'SUMMARY:' . $this->escapeString($appointment->getTitle()),
            'DTSTART' . ($appointment->isAllDay() ? ';VALUE=DATE:' : ':') . $start,
            'DTEND' . ($appointment->isAllDay() ? ';VALUE=DATE:' : ':') . $end,
        ];

        if ($appointment->getDescription()) {
            $event[] = 'DESCRIPTION:' . $this->escapeString($appointment->getDescription());
        }

        if ($appointment->getLocation()) {
            $location = $appointment->getLocation();
            $locationStr = trim(sprintf('%s %s, %s %s',
                $location->getStreet(),
                $location->getHouseNumber(),
                $location->getPostalCode(),
                $location->getCity()
            ));
            $event[] = 'LOCATION:' . $this->escapeString($locationStr);
        }

        $event[] = 'END:VEVENT';

        return $event;
    }

    private function escapeString(string $string): string
    {
        $string = str_replace(["\r\n", "\n"], "\\n", $string);
        $string = str_replace(",", "\,", $string);
        $string = str_replace(";", "\;", $string);
        return $string;
    }
}