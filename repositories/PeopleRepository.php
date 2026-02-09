<?php
// repositories/PeopleRepository.php

class PeopleRepository {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getAll() {
        $stmt = $this->db->query("SELECT * FROM people"); // החלף בשם הטבלה שלך
        return $stmt->fetchAll();
    }
}
