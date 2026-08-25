-- Fields introduced or explicitly displayed by CS Form No. 212 (Revised 2026).
ALTER TABLE pds_personal_info
    ADD COLUMN IF NOT EXISTS dual_citizenship_type ENUM('By Birth','By Naturalization') NULL AFTER dual_citizenship_country,
    ADD COLUMN IF NOT EXISTS philsys_card_no VARCHAR(50) NULL AFTER sss_no,
    ADD COLUMN IF NOT EXISTS government_issued_id VARCHAR(100) NULL AFTER email,
    ADD COLUMN IF NOT EXISTS government_id_number VARCHAR(100) NULL AFTER government_issued_id,
    ADD COLUMN IF NOT EXISTS government_id_issuance VARCHAR(150) NULL AFTER government_id_number;

ALTER TABLE pds_family_background
    ADD COLUMN IF NOT EXISTS spouse_name_extension VARCHAR(20) NULL AFTER spouse_middle_name,
    ADD COLUMN IF NOT EXISTS father_name_extension VARCHAR(20) NULL AFTER father_middle_name;

ALTER TABLE pds_questionnaire
    ADD COLUMN IF NOT EXISTS q35b_date_filed DATE NULL AFTER q35b_details,
    ADD COLUMN IF NOT EXISTS q35b_status_cases VARCHAR(150) NULL AFTER q35b_date_filed;
