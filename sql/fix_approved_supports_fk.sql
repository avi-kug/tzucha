-- Fix foreign key constraint to allow NULL for approved_by
ALTER TABLE approved_supports 
DROP FOREIGN KEY fk_approved_user_id;

ALTER TABLE approved_supports 
MODIFY COLUMN approved_by INT NULL;

ALTER TABLE approved_supports 
ADD CONSTRAINT fk_approved_user_id 
FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL;
