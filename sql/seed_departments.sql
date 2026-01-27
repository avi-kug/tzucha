SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
INSERT INTO departments (name) VALUES
('אחים לחסד'),
('איסוף קופות'),
('בגופו'),
('בית נאמן'),
('חו"ל'),
('יעזורו תעסוקה'),
('מצהלות'),
('משרד ראשי'),
('שיקום משפחות'),
('שמחם'),
('תמיכות'),
('יעזורו זכויות'),
('כח הרבים')
ON DUPLICATE KEY UPDATE name=VALUES(name);
