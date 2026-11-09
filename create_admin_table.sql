-- Create admin table for storing admin credentials
CREATE TABLE IF NOT EXISTS `cafeterias`.`admin` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(255) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;

-- Insert default admin account
INSERT INTO `cafeterias`.`admin` (`username`, `password`, `full_name`) 
VALUES ('admin', 'mwasa', 'Administrator')
ON DUPLICATE KEY UPDATE `full_name` = 'Administrator';
