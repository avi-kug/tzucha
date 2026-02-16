<?php
// repositories/SupportsRepository.php

class SupportsRepository {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * קבלת כל רשומות התמיכה
     */
    public function getAll() {
        $stmt = $this->db->query("
            SELECT s.*, p.donor_number 
            FROM supports s 
            LEFT JOIN people p ON s.person_id = p.id 
            ORDER BY s.created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * קבלת רשומת תמיכה לפי ID
     */
    public function getById($id) {
        $stmt = $this->db->prepare("
            SELECT s.*, p.donor_number 
            FROM supports s 
            LEFT JOIN people p ON s.person_id = p.id 
            WHERE s.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * קבלת רשומת תמיכה לפי מספר זהות
     */
    public function getByIdNumber($idNumber) {
        $stmt = $this->db->prepare("SELECT * FROM supports WHERE id_number = ?");
        $stmt->execute([$idNumber]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * קבלת רשומת תמיכה לפי person_id
     */
    public function getByPersonId($personId) {
        $stmt = $this->db->prepare("SELECT * FROM supports WHERE person_id = ?");
        $stmt->execute([$personId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * יצירת רשומת תמיכה חדשה
     */
    public function create($data) {
        $sql = "INSERT INTO supports (
            person_id, position_name, first_name, last_name, id_number, city, street, phone,
            household_members, married_children,
            study_work_place_1, income_scholarship_1, study_work_place_2, income_scholarship_2,
            child_allowance, survivor_allowance, disability_allowance, income_guarantee,
            income_supplement, rent_assistance, other_allowance_source, other_allowance_amount,
            housing_expenses, tuition_expenses, recurring_exceptional_expense, exceptional_expense_details,
            difficulty_reason, notes,
            account_holder_name, bank_name, branch_number, account_number,
            support_requester_name, transaction_number, include_exceptional_in_calc,
            support_amount, support_month
        ) VALUES (
            :person_id, :position_name, :first_name, :last_name, :id_number, :city, :street, :phone,
            :household_members, :married_children,
            :study_work_place_1, :income_scholarship_1, :study_work_place_2, :income_scholarship_2,
            :child_allowance, :survivor_allowance, :disability_allowance, :income_guarantee,
            :income_supplement, :rent_assistance, :other_allowance_source, :other_allowance_amount,
            :housing_expenses, :tuition_expenses, :recurring_exceptional_expense, :exceptional_expense_details,
            :difficulty_reason, :notes,
            :account_holder_name, :bank_name, :branch_number, :account_number,
            :support_requester_name, :transaction_number, :include_exceptional_in_calc,
            :support_amount, :support_month
        )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        return $this->db->lastInsertId();
    }

    /**
     * עדכון רשומת תמיכה קיימת
     */
    public function update($id, $data) {
        $sql = "UPDATE supports SET
            person_id = :person_id,
            position_name = :position_name,
            first_name = :first_name,
            last_name = :last_name,
            id_number = :id_number,
            city = :city,
            street = :street,
            phone = :phone,
            household_members = :household_members,
            married_children = :married_children,
            study_work_place_1 = :study_work_place_1,
            income_scholarship_1 = :income_scholarship_1,
            study_work_place_2 = :study_work_place_2,
            income_scholarship_2 = :income_scholarship_2,
            child_allowance = :child_allowance,
            survivor_allowance = :survivor_allowance,
            disability_allowance = :disability_allowance,
            income_guarantee = :income_guarantee,
            income_supplement = :income_supplement,
            rent_assistance = :rent_assistance,
            other_allowance_source = :other_allowance_source,
            other_allowance_amount = :other_allowance_amount,
            housing_expenses = :housing_expenses,
            tuition_expenses = :tuition_expenses,
            recurring_exceptional_expense = :recurring_exceptional_expense,
            exceptional_expense_details = :exceptional_expense_details,
            difficulty_reason = :difficulty_reason,
            notes = :notes,
            account_holder_name = :account_holder_name,
            bank_name = :bank_name,
            branch_number = :branch_number,
            account_number = :account_number,
            support_requester_name = :support_requester_name,
            transaction_number = :transaction_number,
            include_exceptional_in_calc = :include_exceptional_in_calc,
            support_amount = :support_amount,
            support_month = :support_month
        WHERE id = :id";

        $data['id'] = $id;
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    /**
     * מחיקת רשומת תמיכה
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM supports WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * קבלת כל הרשומות עם חישובים
     */
    public function getAllWithCalculations($includeExceptionalExpense = true) {
        $supports = $this->getAll();
        $results = [];

        foreach ($supports as $support) {
            $calculations = $this->calculateSupport($support, $includeExceptionalExpense);
            $results[] = array_merge($support, $calculations);
        }

        return $results;
    }

    /**
     * חישוב תמיכה לרשומה בודדת
     */
    public function calculateSupport($support, $includeExceptionalExpense = true) {
        // חישוב סה"כ הכנסות
        $totalIncome = 
            floatval($support['income_scholarship_1'] ?? 0) +
            floatval($support['income_scholarship_2'] ?? 0) +
            floatval($support['child_allowance'] ?? 0) +
            floatval($support['survivor_allowance'] ?? 0) +
            floatval($support['disability_allowance'] ?? 0) +
            floatval($support['income_guarantee'] ?? 0) +
            floatval($support['income_supplement'] ?? 0) +
            floatval($support['rent_assistance'] ?? 0) +
            floatval($support['other_allowance_amount'] ?? 0);

        // חישוב סה"כ הוצאות
        $totalExpenses = 
            floatval($support['housing_expenses'] ?? 0) +
            floatval($support['tuition_expenses'] ?? 0);

        // בדיקה אם לכלול הוצאה חריגה - לפי הגדרה אישית של הרשומה
        $shouldIncludeExceptional = isset($support['include_exceptional_in_calc']) 
            ? (bool)$support['include_exceptional_in_calc'] 
            : true;
        
        if ($shouldIncludeExceptional) {
            $totalExpenses += floatval($support['recurring_exceptional_expense'] ?? 0);
        }

        // חישוב הכנסה לנפש
        $householdMembers = intval($support['household_members'] ?? 1);
        if ($householdMembers < 1) $householdMembers = 1; // מניעת חלוקה באפס

        $incomePerPerson = ($totalIncome - $totalExpenses) / $householdMembers;

        // חישוב סכום תמיכה לפי התנאים
        $supportAmount = 0;
        if ($incomePerPerson < 700) {
            $supportAmount = 200 * $householdMembers;
        } elseif ($incomePerPerson < 800) {
            $supportAmount = (900 * $householdMembers) - $totalIncome;
        } else {
            $supportAmount = 100 * $householdMembers;
        }

        // ודא שסכום התמיכה לא שלילי
        $supportAmount = max(0, $supportAmount);

        return [
            'total_income' => round($totalIncome, 2),
            'total_expenses' => round($totalExpenses, 2),
            'income_per_person' => round($incomePerPerson, 2),
            'support_amount' => round($supportAmount, 2)
        ];
    }

    /**
     * קבלת אנשים שאינם ברשימת התמיכה
     */
    public function getPeopleNotInSupports() {
        $sql = "SELECT p.* FROM people p 
                LEFT JOIN supports s ON p.id = s.person_id 
                WHERE s.id IS NULL 
                ORDER BY p.first_name, p.last_name";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * בדיקה האם קיימת רשומה עם מספר זהות מסוים
     */
    public function existsByIdNumber($idNumber) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM supports WHERE id_number = ?");
        $stmt->execute([$idNumber]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * שיוך person_id לרשומת תמיכה לפי מספר זהות
     */
    public function linkPersonByIdNumber($supportId, $personId) {
        $stmt = $this->db->prepare("UPDATE supports SET person_id = ? WHERE id = ?");
        return $stmt->execute([$personId, $supportId]);
    }
}
