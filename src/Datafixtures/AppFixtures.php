<?php

namespace App\Datafixtures;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\NotificationTag;
use App\Entity\Room;
use App\Entity\Tip;
use App\Entity\TipCategory;
use App\Entity\TipUser;
use App\Entity\User;
use App\Entity\Subject;
use App\Entity\Exam;
use App\Entity\Location;
use App\Entity\Notification;
use App\Entity\Todo;
use App\Entity\Appointment;
use App\Entity\AppointmentCategory;
use App\Entity\UserTodo;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $this->createSubjects($manager);
        $this->createUserLocations($manager);
        $this->createUsers($manager);
        $this->createExams($manager);
        $this->createTodos($manager);
        $this->createNotifications($manager);
        $this->createAppointmentCategories($manager);
        $this->createLocations($manager);
        $this->createRooms($manager);
        $this->createAppointments($manager);
        $this->createConversations($manager);
        $this->createMessages($manager);
        $this->createTipCategories($manager);
        $this->createTipps($manager);
    }

    public function createSubjects(ObjectManager $manager): void
    {
        $subjectData = [
            ['name' => 'Mathematik', 'color' => '#19A6F7'],
            ['name' => 'Biologie', 'color' => '#b2eb5e'],
            ['name' => 'Chemie', 'color' => '#eda34e'],
            ['name' => 'Physik', 'color' => '#6e4eed'],
            ['name' => 'Geschichte', 'color' => '#c4d34a'],
            ['name' => 'Geographie', 'color' => '#25b3ae'],
            ['name' => 'Kunst', 'color' => '#6397ff'],
            ['name' => 'Musik', 'color' => '#ff639f'],
            ['name' => 'Deutsch', 'color' => '#ff3c59'],
            ['name' => 'Englisch', 'color' => '#72e326'],
            ['name' => 'Informatik', 'color' => '#c252ff'],
        ];

        foreach ($subjectData as $data) {
            $subject = new Subject();
            $subject->setName($data['name']);
            $subject->setColorHexCode($data['color']);

            $manager->persist($subject);
        }

        $manager->flush();
    }

    private function createUserLocations(ObjectManager $manager): array
    {
        $locationData = [
            ['street' => 'Hauptstraße', 'number' => '1', 'postal' => '1010', 'city' => 'Wien'],
            ['street' => 'Mariahilfer Straße', 'number' => '10', 'postal' => '1060', 'city' => 'Wien'],
            ['street' => 'Landstraße', 'number' => '15', 'postal' => '1030', 'city' => 'Wien'],
            ['street' => 'Praterstraße', 'number' => '20', 'postal' => '1020', 'city' => 'Wien'],
            ['street' => 'Favoritenstraße', 'number' => '25', 'postal' => '1040', 'city' => 'Wien'],
        ];

        $locations = [];
        foreach ($locationData as $data) {
            $location = new Location();
            $location->setStreet($data['street']);
            $location->setHouseNumber($data['number']);
            $location->setPostalCode($data['postal']);
            $location->setCity($data['city']);
            $location->setType('private');

            $manager->persist($location);
            $locations[] = $location;
        }

        $manager->flush();

        return $locations;
    }

    public function createUsers(ObjectManager $manager)
    {
        // Create locations first
        $locations = $this->createUserLocations($manager);

        $subjects = $manager->getRepository(Subject::class)->findAll();

        // Add users for the missing roles
        $roles = [
            'ROLE_MANAGEMENT' => ['Anna', 'Sophia'],
            'ROLE_LOCATION_MANAGEMENT' => ['Tom', 'Lara'],
            'ROLE_OFFICE' => ['John', 'Emily'],
            'ROLE_GUARDIAN' => ['Michael', 'Sarah', 'David', 'Emma', 'Oliver', 'Sophie', 'Alexander', 'Mia'],
            'ROLE_MARKETING' => ['Chris', 'Nina']
        ];

        foreach ($roles as $role => $names) {
            foreach ($names as $name) {
                $user = new User();

                $birthdate = (new DateTime())->modify("-" . rand(25, 50) . " years")->modify('+' . rand(0, 364) . ' days');

                $user->setFirstName($name);
                $user->setLastName('Gruber');
                $user->setEmail(strtolower($name . '.gruber@example.com'));
                $user->setBirthday($birthdate);
                $user->setDateCreated(new \DateTime());
                $user->setGender(rand(0, 1));
                $user->setPassword($this->passwordHasher->hashPassword(
                    $user,
                    strtolower($name . 'gruber')
                ));

                // Set random location
                $user->setPrivateLocation($locations[array_rand($locations)]);

                // Set learning level based on role
                $learningLevel = match($role) {
                    'ROLE_MANAGEMENT' => 'Management mit Schwerpunkt Bildung',
                    'ROLE_LOCATION_MANAGEMENT' => 'Standortleitung mit pädagogischer Ausbildung',
                    'ROLE_OFFICE' => 'Verwaltung mit Fokus auf Bildungseinrichtungen',
                    'ROLE_GUARDIAN' => 'Erziehungsberechtigter',
                    'ROLE_MARKETING' => 'Marketing im Bildungsbereich',
                    default => ''
                };
                $user->setLearningLevel($learningLevel);

                $user->setRoles([$role]);
                $user->setFirstLogin(false);

                $manager->persist($user);
            }
        }
        $manager->flush();


        // Retrieve all guardian users after they have been created
        $guardianUsers = $manager->getRepository(User::class)->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%"ROLE_GUARDIAN"%')
            ->getQuery()
            ->getResult();








        $firstNames = ['Elias', 'Hannah', 'Julian', 'Emma', 'Leon'];
        $lastNames = ['Auer', 'Egger', 'Fuchs', 'Hartl', 'Koch'];

        foreach (range(0, sizeof($firstNames) - 1) as $i) {
            $user = new User();

            $birthdate = (new DateTime())->modify("-" . rand(18, 30) . " years")->modify('+' . rand(0, 364) . ' days');

            $user->setFirstName($firstNames[$i]);
            $user->setLastName($lastNames[$i]);
            $user->setEmail(strtolower($firstNames[$i] . '.' . $lastNames[$i] . '@example.com'));
            $user->setBirthday($birthdate);
            $user->setDateCreated(new \DateTime());
            $user->setGender(rand(0, 1));
            $user->setPassword($this->passwordHasher->hashPassword(
                $user,
                strtolower($firstNames[$i] . $lastNames[$i])
            ));
            // Set random location
            $user->setPrivateLocation($locations[array_rand($locations)]);

            $user->setLearningLevel("");
            $user->setFirstLogin(false);
            $user->setRoles(["ROLE_TEACHER"]);

            // Assign random subjects to the user
            $randomSubjects = array_rand($subjects, rand(1, 5));
            foreach ((array) $randomSubjects as $subjectIndex) {
                $user->addSubjectsRelatedToUser($subjects[$subjectIndex]);
            }

            $manager->persist($user);
        }
        $manager->flush();











        $firstNames = ['Lukas', 'Tobias', 'Julia', 'Fabian', 'Katharina', 'Maximilian', 'Leonie', 'Paul', 'Sarah', 'Felix', 'Sophia', 'Jonas', 'Marie', 'Simon', 'Laura', 'Moritz', 'Theresa', 'David', 'Lena'];
        $lastNames = ['Müller', 'Schmidt', 'Bauer', 'Weber', 'Huber', 'Schwarz', 'Wagner', 'Hofer', 'Moser', 'Mayr', 'Schmid', 'Berger', 'Leitner', 'Fischer', 'Binder', 'Pichler', 'Eder', 'Maier', 'Lang'];
        foreach (range(0, sizeof($firstNames) - 1) as $i) {
            $user = new User();

            $birthdate = (new DateTime())->modify("-" . rand(12, 18) . " years")->modify('+' . rand(0, 364) . ' days');


            $user->setFirstName($firstNames[$i]);
            $user->setLastName($lastNames[$i]);
            $user->setEmail(strtolower($firstNames[$i] . '.' . $lastNames[$i] . '@example.com'));
            $user->setBirthday($birthdate);
            $user->setDateCreated(new \DateTime());
            $user->setGender(rand(0, 1));
            $user->setPassword($this->passwordHasher->hashPassword(
                $user,
                strtolower($firstNames[$i] . $lastNames[$i])
            ));
            // Set random location
            $user->setPrivateLocation($locations[array_rand($locations)]);

            $user->setLearningLevel("Besucht das Gymnasium Kundmanngasse in der 3. Klasse (mein Beileid). Hat Schwierigkeiten in Mathematik und Informatik. Möchte anschließend die HTL-Rennweg besuchen.");
            $user->setRoles(["ROLE_STUDENT"]);
            $user->setFirstLogin(false);

            // Assign random subjects to the user
            $randomSubjects = array_rand($subjects, rand(1, 5));
            foreach ((array) $randomSubjects as $subjectIndex) {
                $user->addSubjectsRelatedToUser($subjects[$subjectIndex]);
            }

            // Assign random guardian to the user
            if (!empty($guardianUsers)) {
                $randomGuardian = $guardianUsers[array_rand($guardianUsers)];
                $user->setGuardian($randomGuardian);
            }

            $manager->persist($user);
        }
        $manager->flush();
    }

    public function createExams(ObjectManager $manager): void
    {
        $examNames = ['Abschlussprüfung', 'Zwischenprüfung', 'Leistungstest', 'Klausur', 'Wiederholungsprüfung', 'Mündliche Prüfung', 'Schriftliche Prüfung', 'Hausübung', 'Test', 'Quiz'];
        $subjects = $manager->getRepository(Subject::class)->findAll();
        $users = $manager->getRepository(User::class)->findAll();

        foreach ($examNames as $name) {
            $exam = new Exam();

            $exam->setExamName($name);
            $exam->setSubject($subjects[array_rand($subjects)]);  // Assign a random subject
            $exam->setUserTakingExam($users[array_rand($users)]); // Assign a random user

            $examDate = (new \DateTime())->modify('+' . rand(1, 90) . ' days');  // Random future date within 3 months
            $exam->setDate($examDate);

            $manager->persist($exam);
        }

        $manager->flush();
    }

    public function createTodos(ObjectManager $manager): void
    {
        $titles = [
            'Mathe-Test korrigieren',
            'Englisch-Aufsatz bewerten',
            'Elternabend vorbereiten',
            'Schulausflug organisieren',
            'Projektarbeit betreuen',
            'Klassenzimmer dekorieren',
            'Schulfest planen',
            'Lehrerkonferenz vorbereiten',
            'Schülergespräche führen',
            'Noten eintragen'
        ];

        $descriptions = [
            'Die Mathe-Tests der 5. Klasse korrigieren und bewerten.',
            'Den Englisch-Aufsatz der 7. Klasse bewerten und Feedback geben.',
            'Den Elternabend für die 6. Klasse vorbereiten und Einladungen verschicken.',
            'Den Schulausflug der 8. Klasse organisieren und Busse reservieren.',
            'Die Projektarbeit der 9. Klasse betreuen und Hilfestellung geben.',
            'Das Klassenzimmer der 4. Klasse dekorieren und aufräumen.',
            'Das Schulfest planen und Aufgaben verteilen.',
            'Die Lehrerkonferenz vorbereiten und Unterlagen zusammenstellen.',
            'Gespräche mit den Schülern der 10. Klasse führen und beraten.',
            'Die Noten der 3. Klasse eintragen und überprüfen.'
        ];

        $users = $manager->getRepository(User::class)->findAll();

        foreach ($users as $user) {
            $numTodos = rand(10, 20);
            for ($i = 0; $i < $numTodos; $i++) {
                $todo = new Todo();
                $todo->setTitle($titles[array_rand($titles)]);
                $todo->setDescription($descriptions[array_rand($descriptions)]);
                $todo->setDueDate((new \DateTime())->modify('+' . rand(1, 30) . ' days'));
                $todo->setCreator($user);

                $userTodo = new UserTodo();
                $userTodo->setUser($user);
                $userTodo->setChecked(false);
                $todo->addUserTodo($userTodo);

                $manager->persist($userTodo);
                $manager->persist($todo);
            }
        }

        $manager->flush();
    }

    public function createNotifications(ObjectManager $manager): void
    {
        $messages = [
            'Neue Hausaufgabe wurde hinzugefügt.',
            'Elternabend am Freitag um 18:00 Uhr.',
            'Noten für die letzte Prüfung sind verfügbar.',
            'Schulausflug am kommenden Mittwoch.',
            'Wichtige Information zur nächsten Lehrerkonferenz.',
            'Fortbildung zur digitalen Bildung am Montag.',
            'Neuer Schüler ist der Klasse hinzugefügt worden.',
            'Klassensprecherwahl findet nächste Woche statt.',
            'Dokumente für die Zeugnisvergabe sind online verfügbar.',
            'Feedback zur letzten Unterrichtsstunde wurde eingereicht.',
            'Plan für die nächste Sportveranstaltung ist bereit.',
            'Neue Schulregeln wurden in das System eingefügt.',
            'Schließfachzuteilung für das neue Schuljahr ist abgeschlossen.',
            'Erinnerung: Frühjahrsputz im Klassenzimmer nächste Woche.',
            'Neue Materialien für den Biologie-Unterricht verfügbar.',
            'Änderung im Stundenplan für die kommende Woche.',
            'Schulbibliothek erhält neue Bücher im nächsten Monat.',
            'Besprechung der Fachschaften am Donnerstag.',
            'Neue Abgabefrist für Projektdokumentationen gesetzt.',
            'Wichtige Sicherheitsinformation für alle Lehrer.'
        ];

        $tagData = [
            ['name' => 'Wichtig', 'hexColor' => '#e6405f'],
            ['name' => 'Erinnerung', 'hexColor' => '#2ecde6'],
            ['name' => 'Dringlich', 'hexColor' => '#e8568e'],
            ['name' => 'Info', 'hexColor' => '#3fe085'],
            ['name' => 'Update', 'hexColor' => '#dedc5f'],
            ['name' => 'Termin', 'hexColor' => '#f77e7e']
        ];
        $tags = [];

        // Create and persist notification tags with manual hex colors
        foreach ($tagData as $data) {
            $tag = new NotificationTag();
            $tag->setName($data['name']);
            $tag->setHexColor($data['hexColor']);
            $manager->persist($tag);
            $tags[] = $tag;
        }

        $users = $manager->getRepository(User::class)->findAll();

        foreach ($users as $user) {
            for ($i = 0; $i < 5; $i++) {
                $notification = new Notification();

                $notification->setMessage($messages[array_rand($messages)]);
                $notification->setIsRead(false); // Notifications are unread by default

                // Set dateCreated with an incremental timestamp for each notification
                $dateCreated = (new \DateTime())->modify('-' . (3 + ($i * 2)) . ' hours');
                $notification->setDateCreated($dateCreated);

                // Add a random number of users (between 1 and 15) to the notification
                $numUsers = rand(1, 15);
                $randomUsers = (array)array_rand($users, min($numUsers, count($users)));
                foreach ($randomUsers as $userIndex) {
                    $notification->addUser($users[$userIndex]);
                }

                // Assign random tags to the notification
                $numTags = rand(0, 2);
                if ($numTags > 0) {
                    $randomTags = array_rand($tags, $numTags);
                    foreach ((array) $randomTags as $tagIndex) {
                        $notification->addNotificationTag($tags[$tagIndex]);
                    }
                }
                $manager->persist($notification);
            }
        }

        $manager->flush();
    }

    public function createTipps(ObjectManager $manager): void
    {
        $tipsData = [
            'Lernmethoden' => [
                'Remember to review your notes daily.',
                'Organize your workspace to improve focus.',
                'Take regular breaks to avoid burnout.'
            ],
            'Buchempfehlungen' => [
                'Stay hydrated and eat healthy snacks.'
            ],
            'Einführungen' => [
                'Set clear goals for each study session.'
            ],
            'Sonstiges' => [
                'Find a study group to stay motivated.'
            ]
        ];

        $users = $manager->getRepository(User::class)->findAll();
        $categories = $manager->getRepository(TipCategory::class)->findAll();

        if (empty($users) || empty($categories)) {
            throw new \RuntimeException('No users or tip categories found. Please load user and category fixtures first.');
        }

        // Map categories by name for easy lookup
        $categoryMap = [];
        foreach ($categories as $category) {
            $categoryMap[$category->getName()] = $category;
        }

        foreach ($tipsData as $categoryName => $messages) {
            if (!isset($categoryMap[$categoryName])) {
                continue; // Skip if no matching category exists
            }

            $category = $categoryMap[$categoryName];

            foreach ($messages as $message) {
                $creator = $users[array_rand($users)]; // Select a random user as the creator

                $tip = new Tip();
                $tip->setMessage($message);
                $tip->setTitle('Tip: ' . substr($message, 0, 20) . '...');
                $tip->setCreationDate(new \DateTime());
                $tip->setTipCategory($category);
                $tip->setCreator($creator);

                $manager->persist($tip);

                // Assign the tip to all users except ROLE_STUDENT and ROLE_GUARDIAN
                foreach ($users as $user) {
                    $role = $user->getRoles()[0] ?? '';

                    if ($role === 'ROLE_STUDENT' || $role === 'ROLE_GUARDIAN') {
                        continue; // Skip this user
                    }

                    $tipUser = new TipUser();
                    $tipUser->setUser($user);
                    $tipUser->setTip($tip);
                    $tipUser->setReadAt(null);

                    $manager->persist($tipUser);
                }
            }
        }

        $manager->flush();
    }


    public function createAppointmentCategories(ObjectManager $manager): void
    {
        $categories = [
            ['name' => 'Termin', 'color' => '#4a90e2'],         // Blau
            ['name' => 'Nachhilfetermin', 'color' => '#2ecc71'], // Grün
            ['name' => 'Meeting', 'color' => '#9b59b6'],         // Lila
            ['name' => 'Prüfung', 'color' => '#e74c3c'],         // Rot
            ['name' => 'Workshop', 'color' => '#f1c40f']         // Gelb
        ];

        foreach ($categories as $categoryData) {
            $category = new AppointmentCategory();
            $category->setName($categoryData['name']);
            $manager->persist($category);
        }

        $manager->flush();
    }

    public function createLocations(ObjectManager $manager): void
    {
        $location = new Location();
        $location->setStreet('Hauptplatz');
        $location->setHouseNumber('17');
        $location->setPostalCode('2500');
        $location->setCity('Baden');
        $location->setType('On site');

        $manager->persist($location);
        $manager->flush();
    }

    public function createRooms(ObjectManager $manager): void
    {
        $location = $manager->getRepository(Location::class)->findOneBy(['city' => 'Baden']);

        $roomNumbers = ['101', '102', '103', '104', '105'];

        foreach ($roomNumbers as $roomNumber) {
            $room = new Room();
            $room->setRoomNumber($roomNumber);
            $room->setInLocation($location);

            $manager->persist($room);
        }

        $manager->flush();
    }
    
    public function createAppointments(ObjectManager $manager): void
    {
        $categories = $manager->getRepository(AppointmentCategory::class)->findAll();
        $location = $manager->getRepository(Location::class)->findOneBy(['city' => 'Baden']);
        $rooms = $manager->getRepository(Room::class)->findAll();
        $users = $manager->getRepository(User::class)->findAll();
        $annaGruber = $manager->getRepository(User::class)->findOneBy([
            'first_name' => 'Anna',
            'last_name' => 'Gruber'
        ]);

        
        $tutoringCategory = null;
        foreach ($categories as $category) {
            if ($category->getName() === 'Nachhilfetermin') {
                $tutoringCategory = $category;
                break;
            }
        }

        $startDate = new DateTime('2025-03-01');
        $endDate = new DateTime('2025-04-01');
        $interval = new \DateInterval('P1D');
        $period = new \DatePeriod($startDate, $interval, $endDate);

        foreach ($period as $date) {
            // Create 10 tutoring appointments per day with possible overlapping times
            for ($i = 0; $i < 10; $i++) {
                $appointment = new Appointment();

                $startTime = clone $date;
                // Generate random hour between 8 and 20
                $hour = rand(8, 20);
                // Generate random minutes that are divisible by 15 (0, 15, 30, 45)
                $minutes = rand(0, 3) * 15;
                $startTime->setTime($hour, $minutes);

                $endTime = clone $startTime;
                // Duration should also result in times divisible by 15
                $durationMinutes = rand(4, 8) * 15; // Random duration between 60 and 120 minutes, in 15-minute steps
                $endTime->modify("+{$durationMinutes} minutes");

                $appointment->setTitle('Nachhilfe ' . ($i + 1));
                $appointment->setDescription('Nachhilfetermin am ' . $date->format('Y-m-d'));
                $appointment->setStartTime($startTime);
                $appointment->setEndTime($endTime);
                $appointment->setAllDay(false);
                // Array von gut lesbaren Farben für Nachhilfetermine
                $tutorColors = [
                    '#2ecc71',  // Grün
                    '#27ae60',  // Dunkelgrün
                    '#3498db',  // Hellblau
                    '#2980b9',  // Dunkelblau
                    '#e67e22',  // Orange
                    '#d35400',  // Dunkelorange
                    '#f1c40f',  // Gelb
                    '#f39c12',  // Goldgelb
                    '#16a085',  // Türkis
                    '#1abc9c'   // Helltürkis
                ];

                $appointment->setColor($tutorColors[$i % count($tutorColors)]);
                $appointment->setLocation($location);
                $appointment->setAppointmentCategory($tutoringCategory);

                // Assign a random room to the appointment
                $appointment->setRoom($rooms[array_rand($rooms)]);

                $appointment->setCreator($annaGruber);
                $appointment->addUser($annaGruber);

                // Add 1-3 random participants
                for ($j = 0; $j < rand(1, 3); $j++) {
                    $randomUser = $users[array_rand($users)];
                    if ($randomUser !== $annaGruber) {
                        $appointment->addUser($randomUser);
                    }
                }

                $manager->persist($appointment);
            }

            // Create 2-4 random other appointments
            for ($i = 0; $i < rand(2, 4); $i++) {
                $appointment = new Appointment();

                // Select random category that isn't tutoring
                do {
                    $randomCategory = $categories[array_rand($categories)];
                } while ($randomCategory === $tutoringCategory);

                $startTime = clone $date;
                $isAllDay = rand(0, 1) === 1;

                if (!$isAllDay) {
                    $hour = rand(8, 16);
                    $minutes = rand(0, 3) * 15;
                    $startTime->setTime($hour, $minutes);

                    $endTime = clone $startTime;
                    $durationMinutes = rand(4, 16) * 15; // Random duration between 1-4 hours, in 15-minute steps
                    $endTime->modify("+{$durationMinutes} minutes");
                } else {
                    $startTime->setTime(0, 0);
                    $endTime = clone $startTime;
                    $endTime->setTime(0, 0);
                }

                $appointment->setTitle($randomCategory->getName() . ' ' . ($i + 1));
                $appointment->setDescription('Termin am ' . $date->format('Y-m-d'));
                $appointment->setStartTime($startTime);
                $appointment->setEndTime($endTime);
                $appointment->setAllDay($isAllDay);
                $appointment->setColor(match($randomCategory->getName()) {
                    'Termin' => '#4a90e2',    // Blau
                    'Meeting' => '#9b59b6',    // Lila
                    'Prüfung' => '#e74c3c',    // Rot
                    'Workshop' => '#f1c40f',   // Gelb
                    default => '#95a5a6'       // Grau
                });
                $appointment->setLocation($location);
                $appointment->setAppointmentCategory($randomCategory);

                // Assign a random room to the appointment
                $appointment->setRoom($rooms[array_rand($rooms)]);

                $appointment->setCreator($annaGruber);
                $appointment->addUser($annaGruber);

                // Add 1-5 random participants
                for ($j = 0; $j < rand(1, 5); $j++) {
                    $randomUser = $users[array_rand($users)];
                    if ($randomUser !== $annaGruber) {
                        $appointment->addUser($randomUser);
                    }
                }

                $manager->persist($appointment);
            }
        }

        $manager->flush();
    }

       //TODO: Implement the createConversations method, where the first user starts a conversation with the last user
    //TODO: Modify this method to make user with Id 81 have conversations with 5 random users, while making sure none of the conversations are duplicates or with number 81 itself
    public function createConversations(ObjectManager $manager): void
    {
        $users = $manager->getRepository(User::class)->findAll();
        $userAnnaGruber = $manager->getRepository(User::class)->findOneBy(['first_name' => 'Anna', 'last_name' => 'Gruber']);

        $randomUsers = array_rand($users, 5);
        foreach ($randomUsers as $randomUserIndex) {
            $randomUser = $users[$randomUserIndex];

            $conversation = new Conversation();
            $conversation->setInitiator($userAnnaGruber);
            $conversation->setRecipient($randomUser);

            $manager->persist($conversation);
        }

        $manager->flush();
    }

    //TODO: Implement the createMessages method, which takes the existing five conversations, and makes both users send a message to each other
    public function createMessages(ObjectManager $manager): void
    {
        $conversations = $manager->getRepository(Conversation::class)->findAll();

        foreach ($conversations as $conversation) {
            $message1 = new Message();
            $message1->setConversation($conversation);
            $message1->setSender($conversation->getInitiator());
            $message1->setContent('Hello, how are you?');
            $message1->setCreatedAt(new \DateTimeImmutable());

            $message2 = new Message();
            $message2->setConversation($conversation);
            $message2->setSender($conversation->getRecipient());
            $message2->setContent('I am fine, thank you!');
            $message2->setCreatedAt(new \DateTimeImmutable());

            $conversation->setLastMessage($message2);

            $manager->persist($message1);
            $manager->persist($message2);
            $manager->persist($conversation);
        }

        $manager->flush();
    }

    public function createTipCategories(ObjectManager $manager): void
    {
        $categories = [
            ['name' => 'Lernmethoden'],
            ['name' => 'Buchempfehlungen'],
            ['name' => 'Einführungen'],
            ['name' => 'Sonstiges']
        ];

        foreach ($categories as $categoryData) {
            $category = new TipCategory();
            $category->setName($categoryData['name']);
            $manager->persist($category);
        }

        $manager->flush();
    }
}
