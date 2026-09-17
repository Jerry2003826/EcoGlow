-- config/db/patch_enquiry_status.sql

ALTER TABLE Enquiry
    ADD COLUMN Status VARCHAR(50) NOT NULL DEFAULT 'New';

UPDATE Enquiry
SET Status = 'New'
WHERE Status IS NULL
   OR Status = '';
