<?php
// controllers/MainController.php

require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../repositories/PeopleRepository.php';
require_once __DIR__ . '/../services/PeopleService.php';

class MainController {
    public function people() {
        $db = getDb(); // החיבור למסד
        $repo = new PeopleRepository($db);
        $service = new PeopleService($repo);

        $people = $service->getPeople();

        // הצגה של הנתונים (תוכל לשנות לדף view שלך)
        require_once '/../views/pages/people.php';
    }
}
