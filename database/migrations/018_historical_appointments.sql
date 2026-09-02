-- Allows HR to encode appointments that predate the HRMS movement workflow.
ALTER TABLE personnel_movements
    MODIFY movement_type ENUM('School Transfer','Promotion','Historical Appointment') NOT NULL;

