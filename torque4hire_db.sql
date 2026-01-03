CREATE TABLE users (
    email VARCHAR(120) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    address VARCHAR(255),
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE admins (
    email VARCHAR(120) PRIMARY KEY,
    admin_level INT DEFAULT 1,
    password_hash VARCHAR(255) NOT NULL
);

CREATE TABLE owners (
    owner_email VARCHAR(120) PRIMARY KEY,
    company_name VARCHAR(120),
    CONSTRAINT fk_owner_user FOREIGN KEY (owner_email) REFERENCES users(email) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE renters (
    renter_email VARCHAR(120) PRIMARY KEY,
    license_no VARCHAR(50),      
    CONSTRAINT fk_renter_user FOREIGN KEY (renter_email) REFERENCES users(email) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE trainers (
    trainer_email VARCHAR(120) PRIMARY KEY,
    expertise VARCHAR(255),
    availability VARCHAR(50),    
    CONSTRAINT fk_trainer_user FOREIGN KEY (trainer_email) REFERENCES users(email) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE machine_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) UNIQUE NOT NULL,
    details TEXT
);

CREATE TABLE machinery (
    machine_id INT AUTO_INCREMENT PRIMARY KEY,
    owner_email VARCHAR(120) NOT NULL,
    category_id INT NOT NULL,
    model_name VARCHAR(100) NOT NULL,
    daily_rate DECIMAL(10, 2) NOT NULL,
    status ENUM('AVAILABLE', 'RENTED', 'MAINTENANCE') DEFAULT 'AVAILABLE',
    CONSTRAINT fk_machine_owner FOREIGN KEY (owner_email) REFERENCES owners(owner_email) ON UPDATE CASCADE,
    CONSTRAINT fk_machine_cat FOREIGN KEY (category_id) REFERENCES machine_categories(category_id)
);

CREATE TABLE rentals (
    rental_id INT AUTO_INCREMENT PRIMARY KEY,
    renter_email VARCHAR(120) NOT NULL,
    machine_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    total_cost DECIMAL(10, 2),
    rental_status VARCHAR(50) DEFAULT 'REQUESTED',
    CONSTRAINT fk_rental_renter FOREIGN KEY (renter_email) REFERENCES renters(renter_email) ON UPDATE CASCADE,
    CONSTRAINT fk_rental_machine FOREIGN KEY (machine_id) REFERENCES machinery(machine_id)
);

CREATE TABLE payments (
    rental_id INT,                       
    payment_id INT,                      
    admin_email VARCHAR(120),
    amount DECIMAL(10, 2) NOT NULL,
    method VARCHAR(50),
    status VARCHAR(50) DEFAULT 'PENDING',
    PRIMARY KEY (rental_id, payment_id), 
    CONSTRAINT fk_payment_rental FOREIGN KEY (rental_id) REFERENCES rentals(rental_id) ON DELETE CASCADE,
    CONSTRAINT fk_payment_admin FOREIGN KEY (admin_email) REFERENCES admins(email) ON UPDATE CASCADE
);

CREATE TABLE maintenance (
    machine_id INT,
    maintenance_id INT,
    start_date DATE NOT NULL,
    end_date DATE,
    PRIMARY KEY (machine_id, maintenance_id),
    CONSTRAINT fk_maint_machine FOREIGN KEY (machine_id) REFERENCES machinery(machine_id) ON DELETE CASCADE
);

CREATE TABLE penalties (
    penalty_id INT AUTO_INCREMENT PRIMARY KEY,
    rental_id INT NOT NULL,
    penalty_amount DECIMAL(10, 2),
    reason VARCHAR(255),
    penalty_status VARCHAR(50),
    CONSTRAINT fk_penalty_rental FOREIGN KEY (rental_id) REFERENCES rentals(rental_id)
);

CREATE TABLE training_sessions (
    session_id INT AUTO_INCREMENT PRIMARY KEY,
    trainer_email VARCHAR(120) NOT NULL,
    renter_email VARCHAR(120) NOT NULL,
    session_start DATETIME NOT NULL,     
    session_end DATETIME NOT NULL,       
    CONSTRAINT fk_session_trainer FOREIGN KEY (trainer_email) REFERENCES trainers(trainer_email) ON UPDATE CASCADE,
    CONSTRAINT fk_session_renter FOREIGN KEY (renter_email) REFERENCES renters(renter_email) ON UPDATE CASCADE
);