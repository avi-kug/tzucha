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

    public function searchPeople($search, $limit = 20) {
        $search = '%' . $search . '%';
        $stmt = $this->db->prepare("
            SELECT donor_number, first_name, last_name, id_number
            FROM people
            WHERE donor_number LIKE ? 
               OR first_name LIKE ? 
               OR last_name LIKE ?
               OR id_number LIKE ?
            LIMIT ?
        ");
        $stmt->bindValue(1, $search, PDO::PARAM_STR);
        $stmt->bindValue(2, $search, PDO::PARAM_STR);
        $stmt->bindValue(3, $search, PDO::PARAM_STR);
        $stmt->bindValue(4, $search, PDO::PARAM_STR);
        $stmt->bindValue(5, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPersonByDonorNumber($donorNumber) {
        $stmt = $this->db->prepare("
            SELECT * FROM people WHERE donor_number = ? LIMIT 1
        ");
        $stmt->execute([$donorNumber]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
