-- MySQL Workbench Forward Engineering

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- -----------------------------------------------------
-- Schema mydb
-- -----------------------------------------------------
-- -----------------------------------------------------
-- Schema cafeterias
-- -----------------------------------------------------

-- -----------------------------------------------------
-- Schema cafeterias
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `cafeterias` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci ;
USE `cafeterias` ;

-- -----------------------------------------------------
-- Table `cafeterias`.`customer`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `cafeterias`.`customer` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(50) NULL DEFAULT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `cafeterias`.`employee`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `cafeterias`.`employee` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(50) NULL DEFAULT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `cafeterias`.`event`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `cafeterias`.`event` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `event_name` VARCHAR(255) NOT NULL,
  `event_date` DATE NOT NULL,
  `description` TEXT,
  PRIMARY KEY (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `cafeterias`.`event_customer`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `cafeterias`.`event_customer` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `event_id` INT NOT NULL,
  `customer_id` INT NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `event_id` (`event_id` ASC) VISIBLE,
  INDEX `customer_id` (`customer_id` ASC) VISIBLE,
  CONSTRAINT `event_customer_ibfk_1`
    FOREIGN KEY (`event_id`)
    REFERENCES `cafeterias`.`event` (`id`),
  CONSTRAINT `event_customer_ibfk_2`
    FOREIGN KEY (`customer_id`)
    REFERENCES `cafeterias`.`customer` (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `cafeterias`.`expense_category`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `cafeterias`.`expense_category` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `category_name` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `cafeterias`.`expenses`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `cafeterias`.`expenses` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `expense_category_id` INT NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `description` TEXT,
  `expense_date` DATE DEFAULT CURRENT_DATE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `expense_category_id` (`expense_category_id` ASC) VISIBLE,
  CONSTRAINT `expenses_ibfk_1`
    FOREIGN KEY (`expense_category_id`)
    REFERENCES `cafeterias`.`expense_category` (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `cafeterias`.`feedback`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `cafeterias`.`feedback` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `customer_id` INT NOT NULL,
  `rating` INT NOT NULL COMMENT 'Allowed values: 1–5',
  PRIMARY KEY (`id`),
  INDEX `customer_id` (`customer_id` ASC) VISIBLE,
  CONSTRAINT `feedback_ibfk_1`
    FOREIGN KEY (`customer_id`)
    REFERENCES `cafeterias`.`customer` (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `cafeterias`.`menu_category` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `category_name` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`))


-- -----------------------------------------------------
-- Table `cafeterias`.`service`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `cafeterias`.`service` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `service_name` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `price` DECIMAL(10,2),
  `duration_minutes` INT,
  `max_capacity` INT,
  `is_active` BOOLEAN DEFAULT TRUE,
  PRIMARY KEY (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `cafeterias`.`order`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `cafeterias`.`order` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `customer_id` INT NOT NULL,
  `employee_id` INT NOT NULL,
  `service_id` INT NULL DEFAULT NULL,
  `order_date` DATETIME NULL DEFAULT now(),
  `status` VARCHAR(50) DEFAULT 'pending',
  PRIMARY KEY (`id`),
  INDEX `customer_id` (`customer_id` ASC) VISIBLE,
  INDEX `employee_id` (`employee_id` ASC) VISIBLE,
  INDEX `service_id` (`service_id` ASC) VISIBLE,
  CONSTRAINT `order_ibfk_1`
    FOREIGN KEY (`customer_id`)
    REFERENCES `cafeterias`.`customer` (`id`),
  CONSTRAINT `order_ibfk_2`
    FOREIGN KEY (`employee_id`)
    REFERENCES `cafeterias`.`employee` (`id`),
  CONSTRAINT `order_ibfk_3`
    FOREIGN KEY (`service_id`)
    REFERENCES `cafeterias`.`service` (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `cafeterias`.`product`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `cafeterias`.`product` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `product_name` VARCHAR(255) NOT NULL,
  `category_id` INT NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `quantity` INT NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `category_id` (`category_id` ASC) VISIBLE,
  CONSTRAINT `product_ibfk_1`
    FOREIGN KEY (`category_id`)
    REFERENCES `cafeterias`.`menu_category` (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `cafeterias`.`order_detail`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `cafeterias`.`order_detail` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `order_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `quantity` INT NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `order_id` (`order_id` ASC) VISIBLE,
  INDEX `product_id` (`product_id` ASC) VISIBLE,
  CONSTRAINT `order_detail_ibfk_1`
    FOREIGN KEY (`order_id`)
    REFERENCES `cafeterias`.`order` (`id`),
  CONSTRAINT `order_detail_ibfk_2`
    FOREIGN KEY (`product_id`)
    REFERENCES `cafeterias`.`product` (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `cafeterias`.`payment_method`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `cafeterias`.`payment_method` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `method_name` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `method_name` (`method_name` ASC) VISIBLE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `cafeterias`.`payment`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `cafeterias`.`payment` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `order_id` INT NOT NULL,
  `payment_method_id` INT NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `order_id` (`order_id` ASC) VISIBLE,
  INDEX `payment_method_id` (`payment_method_id` ASC) VISIBLE,
  CONSTRAINT `payment_ibfk_1`
    FOREIGN KEY (`order_id`)
    REFERENCES `cafeterias`.`order` (`id`),
  CONSTRAINT `payment_ibfk_2`
    FOREIGN KEY (`payment_method_id`)
    REFERENCES `cafeterias`.`payment_method` (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `cafeterias`.`supplier`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `cafeterias`.`supplier` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `supplier_name` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(50) NULL DEFAULT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `cafeterias`.`purchase`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `cafeterias`.`purchase` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `supplier_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `quantity` INT NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `supplier_id` (`supplier_id` ASC) VISIBLE,
  INDEX `product_id` (`product_id` ASC) VISIBLE,
  CONSTRAINT `purchase_ibfk_1`
    FOREIGN KEY (`supplier_id`)
    REFERENCES `cafeterias`.`supplier` (`id`),
  CONSTRAINT `purchase_ibfk_2`
    FOREIGN KEY (`product_id`)
    REFERENCES `cafeterias`.`product` (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `cafeterias`.`reservation`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `cafeterias`.`reservation` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `customer_id` INT NOT NULL,
  `service_id` INT NOT NULL,
  `reservation_date` DATE NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `customer_id` (`customer_id` ASC) VISIBLE,
  INDEX `service_id` (`service_id` ASC) VISIBLE,
  CONSTRAINT `reservation_ibfk_1`
    FOREIGN KEY (`customer_id`)
    REFERENCES `cafeterias`.`customer` (`id`),
  CONSTRAINT `reservation_ibfk_2`
    FOREIGN KEY (`service_id`)
    REFERENCES `cafeterias`.`service` (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `cafeterias`.`role`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `cafeterias`.`role` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `role_name` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `role_name` (`role_name` ASC) VISIBLE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `cafeterias`.`shift`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `cafeterias`.`shift` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `employee_id` INT NOT NULL,
  `shift_date` DATE NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `employee_id` (`employee_id` ASC) VISIBLE,
  CONSTRAINT `shift_ibfk_1`
    FOREIGN KEY (`employee_id`)
    REFERENCES `cafeterias`.`employee` (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `cafeterias`.`user`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `cafeterias`.`user` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(100) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role_id` INT NOT NULL,
  `employee_id` INT NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `username` (`username` ASC) VISIBLE,
  INDEX `role_id` (`role_id` ASC) VISIBLE,
  INDEX `employee_id` (`employee_id` ASC) VISIBLE,
  CONSTRAINT `user_ibfk_1`
    FOREIGN KEY (`role_id`)
    REFERENCES `cafeterias`.`role` (`id`),
  CONSTRAINT `user_ibfk_2`
    FOREIGN KEY (`employee_id`)
    REFERENCES `cafeterias`.`employee` (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;

-- Insert sample services
INSERT INTO `cafeterias`.`service` (service_name, description, price, duration_minutes, max_capacity) VALUES
('Table Reservation', 'Reserve a table for dining', 0.00, 120, 4),
('Private Room', 'Private dining room booking', 50.00, 180, 20),
('Catering Service', 'Food catering for events', 200.00, 480, 100),
('Coffee Tasting', 'Premium coffee tasting session', 25.00, 60, 10),
('Cooking Class', 'Learn to cook cafeteria specialties', 75.00, 120, 15),
('Birthday Party', 'Birthday party package', 150.00, 240, 30),
('Business Meeting', 'Meeting room with catering', 100.00, 180, 25),
('Takeaway Service', 'Order takeaway in advance', 0.00, 30, NULL);

USE `cafeterias` ;

-- -----------------------------------------------------
-- Placeholder table for view `cafeterias`.`vw_daily_sales`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `cafeterias`.`vw_daily_sales` (`sale_date` INT, `total_sales` INT);

-- -----------------------------------------------------
-- Placeholder table for view `cafeterias`.`vw_employee_shifts`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `cafeterias`.`vw_employee_shifts` (`shift_id` INT, `full_name` INT, `phone` INT, `shift_date` INT);

-- -----------------------------------------------------
-- Placeholder table for view `cafeterias`.`vw_event_attendance`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `cafeterias`.`vw_event_attendance` (`event_name` INT, `event_date` INT, `customer_name` INT, `phone` INT);

-- -----------------------------------------------------
-- Placeholder table for view `cafeterias`.`vw_expenses`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `cafeterias`.`vw_expenses` (`expense_id` INT, `category_name` INT, `amount` INT);

-- -----------------------------------------------------
-- Placeholder table for view `cafeterias`.`vw_feedback`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `cafeterias`.`vw_feedback` (`feedback_id` INT, `customer_name` INT, `rating` INT);

-- -----------------------------------------------------
-- Placeholder table for view `cafeterias`.`vw_order_details`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `cafeterias`.`vw_order_details` (`order_detail_id` INT, `order_id` INT, `product_name` INT, `quantity` INT, `price` INT, `line_total` INT);

-- -----------------------------------------------------
-- Placeholder table for view `cafeterias`.`vw_order_totals`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `cafeterias`.`vw_order_totals` (`order_id` INT, `order_total` INT);

-- -----------------------------------------------------
-- Placeholder table for view `cafeterias`.`vw_orders`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `cafeterias`.`vw_orders` (`order_id` INT, `customer_name` INT, `employee_name` INT, `service_name` INT, `order_date` INT);

-- -----------------------------------------------------
-- Placeholder table for view `cafeterias`.`vw_payments`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `cafeterias`.`vw_payments` (`payment_id` INT, `order_id` INT, `method_name` INT, `amount` INT);

-- -----------------------------------------------------
-- Placeholder table for view `cafeterias`.`vw_products`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `cafeterias`.`vw_products` (`product_id` INT, `product_name` INT, `category_name` INT, `price` INT, `quantity` INT);

-- -----------------------------------------------------
-- Placeholder table for view `cafeterias`.`vw_purchases`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `cafeterias`.`vw_purchases` (`purchase_id` INT, `supplier_name` INT, `supplier_phone` INT, `product_name` INT, `quantity` INT);

-- -----------------------------------------------------
-- Placeholder table for view `cafeterias`.`vw_reservations`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `cafeterias`.`vw_reservations` (`reservation_id` INT, `customer_name` INT, `service_name` INT, `reservation_date` INT);

-- -----------------------------------------------------
-- Placeholder table for view `cafeterias`.`vw_users_roles`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `cafeterias`.`vw_users_roles` (`user_id` INT, `username` INT, `role_name` INT, `employee_name` INT, `employee_phone` INT);

-- -----------------------------------------------------
-- procedure sp_add_feedback_simple
-- -----------------------------------------------------

DELIMITER $$
USE `cafeterias`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_add_feedback_simple`(
    IN p_customer_id INT,
    IN p_rating INT
)
BEGIN
    DECLARE err_msg VARCHAR(255);

    -- Validate rating
    IF p_rating < 1 OR p_rating > 5 THEN
        SET err_msg = 'Rating must be between 1 and 5';
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = err_msg;
    END IF;

    -- Insert feedback
    INSERT INTO feedback (customer_id, rating)
    VALUES (p_customer_id, p_rating);

END$$

DELIMITER ;

-- -----------------------------------------------------
-- procedure sp_add_payment_simple
-- -----------------------------------------------------

DELIMITER $$
USE `cafeterias`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_add_payment_simple`(
    IN p_order_id INT,
    IN p_payment_method_id INT,
    IN p_amount DECIMAL(10,2)
)
BEGIN
    DECLARE order_total DECIMAL(10,2);
    DECLARE err_msg VARCHAR(255);

    -- Get total order amount
    SELECT SUM(od.quantity * p.price)
    INTO order_total
    FROM order_detail od
    JOIN product p ON od.product_id = p.id
    WHERE od.order_id = p_order_id;

    -- Validate payment
    IF p_amount > order_total THEN
        SET err_msg = 'Payment exceeds order total';
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = err_msg;
    END IF;

    IF p_amount <= 0 THEN
        SET err_msg = 'Payment must be greater than zero';
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = err_msg;
    END IF;

    -- Insert payment
    INSERT INTO payment (order_id, payment_method_id, amount)
    VALUES (p_order_id, p_payment_method_id, p_amount);

END$$

DELIMITER ;

-- -----------------------------------------------------
-- procedure sp_add_purchase_simple
-- -----------------------------------------------------

DELIMITER $$
USE `cafeterias`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_add_purchase_simple`(
    IN p_supplier_id INT,
    IN p_product_id INT,
    IN p_quantity INT
)
BEGIN
    -- Insert purchase
    INSERT INTO purchase (supplier_id, product_id, quantity)
    VALUES (p_supplier_id, p_product_id, p_quantity);

    -- Update stock
    UPDATE product
    SET quantity = quantity + p_quantity
    WHERE id = p_product_id;

END$$

DELIMITER ;

-- -----------------------------------------------------
-- procedure sp_daily_sales_simple
-- -----------------------------------------------------

DELIMITER $$
USE `cafeterias`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_daily_sales_simple`(
    IN p_date DATE
)
BEGIN
    SELECT SUM(od.quantity * p.price) AS total_sales
    FROM `order` o
    JOIN order_detail od ON o.id = od.order_id
    JOIN product p ON od.product_id = p.id
    WHERE DATE(o.order_date) = p_date;
END$$

DELIMITER ;

-- -----------------------------------------------------
-- procedure sp_place_order_simple
-- -----------------------------------------------------

DELIMITER $$
USE `cafeterias`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_place_order_simple`(
    IN p_customer_id INT,
    IN p_employee_id INT,
    IN p_service_id INT,
    IN p_product_id INT,
    IN p_quantity INT
)
BEGIN
    DECLARE stock_qty INT;
    DECLARE last_order_id INT;
    DECLARE err_msg VARCHAR(255);

    -- Check stock
    SELECT quantity INTO stock_qty
    FROM product
    WHERE id = p_product_id;

    IF stock_qty < p_quantity THEN
        SET err_msg = CONCAT('Not enough stock for product ', p_product_id);
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = err_msg;
    END IF;

    -- Insert order
    INSERT INTO `order` (customer_id, employee_id, service_id)
    VALUES (p_customer_id, p_employee_id, p_service_id);

    SET last_order_id = LAST_INSERT_ID();

    -- Insert order detail
    INSERT INTO order_detail (order_id, product_id, quantity)
    VALUES (last_order_id, p_product_id, p_quantity);

    -- Reduce stock
    UPDATE product
    SET quantity = quantity - p_quantity
    WHERE id = p_product_id;

END$$

DELIMITER ;

-- -----------------------------------------------------
-- procedure sp_reserve_service_simple
-- -----------------------------------------------------

DELIMITER $$
USE `cafeterias`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_reserve_service_simple`(
    IN p_customer_id INT,
    IN p_service_id INT,
    IN p_reservation_date DATE
)
BEGIN
    DECLARE err_msg VARCHAR(255);

    -- Check duplicate reservation
    IF EXISTS (
        SELECT 1 FROM reservation
        WHERE customer_id = p_customer_id
          AND service_id = p_service_id
          AND reservation_date = p_reservation_date
    ) THEN
        SET err_msg = 'Duplicate reservation for this service on the same date';
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = err_msg;
    END IF;

    -- Insert reservation
    INSERT INTO reservation (customer_id, service_id, reservation_date)
    VALUES (p_customer_id, p_service_id, p_reservation_date);

END$$

DELIMITER ;

-- -----------------------------------------------------
-- View `cafeterias`.`vw_daily_sales`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `cafeterias`.`vw_daily_sales`;
USE `cafeterias`;
CREATE  OR REPLACE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `cafeterias`.`vw_daily_sales` AS select cast(`o`.`order_date` as date) AS `sale_date`,sum((`od`.`quantity` * `p`.`price`)) AS `total_sales` from ((`cafeterias`.`order` `o` join `cafeterias`.`order_detail` `od` on((`o`.`id` = `od`.`order_id`))) join `cafeterias`.`product` `p` on((`od`.`product_id` = `p`.`id`))) group by cast(`o`.`order_date` as date);

-- -----------------------------------------------------
-- View `cafeterias`.`vw_employee_shifts`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `cafeterias`.`vw_employee_shifts`;
USE `cafeterias`;
CREATE  OR REPLACE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `cafeterias`.`vw_employee_shifts` AS select `sh`.`id` AS `shift_id`,`e`.`full_name` AS `full_name`,`e`.`phone` AS `phone`,`sh`.`shift_date` AS `shift_date` from (`cafeterias`.`shift` `sh` join `cafeterias`.`employee` `e` on((`sh`.`employee_id` = `e`.`id`)));

-- -----------------------------------------------------
-- View `cafeterias`.`vw_event_attendance`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `cafeterias`.`vw_event_attendance`;
USE `cafeterias`;
CREATE  OR REPLACE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `cafeterias`.`vw_event_attendance` AS select `ev`.`event_name` AS `event_name`,`ev`.`event_date` AS `event_date`,`c`.`full_name` AS `customer_name`,`c`.`phone` AS `phone` from ((`cafeterias`.`event_customer` `ec` join `cafeterias`.`event` `ev` on((`ec`.`event_id` = `ev`.`id`))) join `cafeterias`.`customer` `c` on((`ec`.`customer_id` = `c`.`id`)));

-- -----------------------------------------------------
-- View `cafeterias`.`vw_expenses`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `cafeterias`.`vw_expenses`;
USE `cafeterias`;
CREATE  OR REPLACE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `cafeterias`.`vw_expenses` AS select `ex`.`id` AS `expense_id`,`ec`.`category_name` AS `category_name`,`ex`.`amount` AS `amount` from (`cafeterias`.`expenses` `ex` join `cafeterias`.`expense_category` `ec` on((`ex`.`expense_category_id` = `ec`.`id`)));

-- -----------------------------------------------------
-- View `cafeterias`.`vw_feedback`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `cafeterias`.`vw_feedback`;
USE `cafeterias`;
CREATE  OR REPLACE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `cafeterias`.`vw_feedback` AS select `f`.`id` AS `feedback_id`,`c`.`full_name` AS `customer_name`,`f`.`rating` AS `rating` from (`cafeterias`.`feedback` `f` join `cafeterias`.`customer` `c` on((`f`.`customer_id` = `c`.`id`)));

-- -----------------------------------------------------
-- View `cafeterias`.`vw_order_details`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `cafeterias`.`vw_order_details`;
USE `cafeterias`;
CREATE  OR REPLACE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `cafeterias`.`vw_order_details` AS select `od`.`id` AS `order_detail_id`,`od`.`order_id` AS `order_id`,`p`.`product_name` AS `product_name`,`od`.`quantity` AS `quantity`,`p`.`price` AS `price`,(`od`.`quantity` * `p`.`price`) AS `line_total` from (`cafeterias`.`order_detail` `od` join `cafeterias`.`product` `p` on((`od`.`product_id` = `p`.`id`)));

-- -----------------------------------------------------
-- View `cafeterias`.`vw_order_totals`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `cafeterias`.`vw_order_totals`;
USE `cafeterias`;
CREATE  OR REPLACE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `cafeterias`.`vw_order_totals` AS select `od`.`order_id` AS `order_id`,sum((`od`.`quantity` * `p`.`price`)) AS `order_total` from (`cafeterias`.`order_detail` `od` join `cafeterias`.`product` `p` on((`od`.`product_id` = `p`.`id`))) group by `od`.`order_id`;

-- -----------------------------------------------------
-- View `cafeterias`.`vw_orders`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `cafeterias`.`vw_orders`;
USE `cafeterias`;
CREATE  OR REPLACE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `cafeterias`.`vw_orders` AS select `o`.`id` AS `order_id`,`c`.`full_name` AS `customer_name`,`e`.`full_name` AS `employee_name`,`s`.`service_name` AS `service_name`,`o`.`order_date` AS `order_date` from (((`cafeterias`.`order` `o` join `cafeterias`.`customer` `c` on((`o`.`customer_id` = `c`.`id`))) join `cafeterias`.`employee` `e` on((`o`.`employee_id` = `e`.`id`))) left join `cafeterias`.`service` `s` on((`o`.`service_id` = `s`.`id`)));

-- -----------------------------------------------------
-- View `cafeterias`.`vw_payments`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `cafeterias`.`vw_payments`;
USE `cafeterias`;
CREATE  OR REPLACE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `cafeterias`.`vw_payments` AS select `pay`.`id` AS `payment_id`,`pay`.`order_id` AS `order_id`,`pm`.`method_name` AS `method_name`,`pay`.`amount` AS `amount` from (`cafeterias`.`payment` `pay` join `cafeterias`.`payment_method` `pm` on((`pay`.`payment_method_id` = `pm`.`id`)));

-- -----------------------------------------------------
-- View `cafeterias`.`vw_products`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `cafeterias`.`vw_products`;
USE `cafeterias`;
CREATE  OR REPLACE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `cafeterias`.`vw_products` AS select `p`.`id` AS `product_id`,`p`.`product_name` AS `product_name`,`mc`.`category_name` AS `category_name`,`p`.`price` AS `price`,`p`.`quantity` AS `quantity` from (`cafeterias`.`product` `p` join `cafeterias`.`menu_category` `mc` on((`p`.`category_id` = `mc`.`id`)));

-- -----------------------------------------------------
-- View `cafeterias`.`vw_purchases`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `cafeterias`.`vw_purchases`;
USE `cafeterias`;
CREATE  OR REPLACE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `cafeterias`.`vw_purchases` AS select `pu`.`id` AS `purchase_id`,`s`.`supplier_name` AS `supplier_name`,`s`.`phone` AS `supplier_phone`,`pr`.`product_name` AS `product_name`,`pu`.`quantity` AS `quantity` from ((`cafeterias`.`purchase` `pu` join `cafeterias`.`supplier` `s` on((`pu`.`supplier_id` = `s`.`id`))) join `cafeterias`.`product` `pr` on((`pu`.`product_id` = `pr`.`id`)));

-- -----------------------------------------------------
-- View `cafeterias`.`vw_reservations`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `cafeterias`.`vw_reservations`;
USE `cafeterias`;
CREATE  OR REPLACE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `cafeterias`.`vw_reservations` AS select `r`.`id` AS `reservation_id`,`c`.`full_name` AS `customer_name`,`s`.`service_name` AS `service_name`,`r`.`reservation_date` AS `reservation_date` from ((`cafeterias`.`reservation` `r` join `cafeterias`.`customer` `c` on((`r`.`customer_id` = `c`.`id`))) join `cafeterias`.`service` `s` on((`r`.`service_id` = `s`.`id`)));

-- -----------------------------------------------------
-- View `cafeterias`.`vw_users_roles`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `cafeterias`.`vw_users_roles`;
USE `cafeterias`;
CREATE  OR REPLACE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `cafeterias`.`vw_users_roles` AS select `u`.`id` AS `user_id`,`u`.`username` AS `username`,`r`.`role_name` AS `role_name`,`e`.`full_name` AS `employee_name`,`e`.`phone` AS `employee_phone` from ((`cafeterias`.`user` `u` join `cafeterias`.`role` `r` on((`u`.`role_id` = `r`.`id`))) left join `cafeterias`.`employee` `e` on((`u`.`employee_id` = `e`.`id`)));
USE `cafeterias`;

DELIMITER $$
USE `cafeterias`$$
CREATE
DEFINER=`root`@`localhost`
TRIGGER `cafeterias`.`trg_prevent_duplicate_event_customer`
BEFORE INSERT ON `cafeterias`.`event_customer`
FOR EACH ROW
BEGIN
    IF EXISTS (
        SELECT 1 FROM event_customer
        WHERE event_id = NEW.event_id
          AND customer_id = NEW.customer_id
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Customer already registered for this event';
    END IF;
END$$

USE `cafeterias`$$
CREATE
DEFINER=`root`@`localhost`
TRIGGER `cafeterias`.`trg_validate_feedback_rating`
BEFORE INSERT ON `cafeterias`.`feedback`
FOR EACH ROW
BEGIN
    IF NEW.rating < 1 OR NEW.rating > 5 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Rating must be between 1 and 5';
    END IF;
END$$

USE `cafeterias`$$
CREATE
DEFINER=`root`@`localhost`
TRIGGER `cafeterias`.`trg_set_order_date`
BEFORE INSERT ON `cafeterias`.`order`
FOR EACH ROW
BEGIN
    IF NEW.order_date IS NULL THEN
        SET NEW.order_date = CURRENT_TIMESTAMP;
    END IF;
END$$

USE `cafeterias`$$
CREATE
DEFINER=`root`@`localhost`
TRIGGER `cafeterias`.`trg_prevent_negative_stock`
BEFORE INSERT ON `cafeterias`.`order_detail`
FOR EACH ROW
BEGIN
    DECLARE available_qty INT;

    SELECT quantity INTO available_qty
    FROM product
    WHERE id = NEW.product_id;

    IF NEW.quantity > available_qty THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Not enough stock available';
    END IF;
END$$

USE `cafeterias`$$
CREATE
DEFINER=`root`@`localhost`
TRIGGER `cafeterias`.`trg_reduce_stock_after_order`
AFTER INSERT ON `cafeterias`.`order_detail`
FOR EACH ROW
BEGIN
    UPDATE product
    SET quantity = quantity - NEW.quantity
    WHERE id = NEW.product_id;
END$$

USE `cafeterias`$$
CREATE
DEFINER=`root`@`localhost`
TRIGGER `cafeterias`.`trg_validate_payment_amount`
BEFORE INSERT ON `cafeterias`.`payment`
FOR EACH ROW
BEGIN
    IF NEW.amount <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Payment amount must be greater than zero';
    END IF;
END$$

USE `cafeterias`$$
CREATE
DEFINER=`root`@`localhost`
TRIGGER `cafeterias`.`trg_validate_payment_vs_order`
BEFORE INSERT ON `cafeterias`.`payment`
FOR EACH ROW
BEGIN
    DECLARE order_total DECIMAL(10,2);

    SELECT SUM(od.quantity * p.price)
    INTO order_total
    FROM order_detail od
    JOIN product p ON od.product_id = p.id
    WHERE od.order_id = NEW.order_id;

    IF NEW.amount > order_total THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Payment exceeds order total';
    END IF;
END$$

USE `cafeterias`$$
CREATE
DEFINER=`root`@`localhost`
TRIGGER `cafeterias`.`trg_increase_stock_after_purchase`
AFTER INSERT ON `cafeterias`.`purchase`
FOR EACH ROW
BEGIN
    UPDATE product
    SET quantity = quantity + NEW.quantity
    WHERE id = NEW.product_id;
END$$

USE `cafeterias`$$
CREATE
DEFINER=`root`@`localhost`
TRIGGER `cafeterias`.`trg_prevent_duplicate_reservation`
BEFORE INSERT ON `cafeterias`.`reservation`
FOR EACH ROW
BEGIN
    IF EXISTS (
        SELECT 1 FROM reservation
        WHERE customer_id = NEW.customer_id
          AND service_id = NEW.service_id
          AND reservation_date = NEW.reservation_date
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Duplicate reservation detected';
    END IF;
END$$


DELIMITER ;

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
