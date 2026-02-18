<?php
/**
 * סקריפט לביצוע סקריפטי SQL - יצירת טבלאות children ו-beit_neeman
 */

require_once '../config/db.php';

try {
    echo "מתחיל יצירת טבלאות...\n\n";
    
    // יצירת טבלת children
    echo "יוצר טבלת children...\n";
    $childrenSQL = file_get_contents('../sql/create_children_table.sql');
    $pdo->exec($childrenSQL);
    echo "✓ טבלת children נוצרה בהצלחה\n\n";
    
    // יצירת טבלת beit_neeman
    echo "יוצר טבלת beit_neeman...\n";
    $beitNeemanSQL = file_get_contents('../sql/create_beit_neeman_table.sql');
    $pdo->exec($beitNeemanSQL);
    echo "✓ טבלת beit_neeman נוצרה בהצלחה\n\n";
    
    echo "הכל הושלם בהצלחה!\n";
    
} catch (PDOException $e) {
    echo "שגיאה: " . $e->getMessage() . "\n";
    exit(1);
}
