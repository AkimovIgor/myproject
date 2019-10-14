CREATE TABLE IF NOT EXISTS comments
(
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    text TEXT NOT NULL,
    date DATE NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    image VARCHAR(100) NOT NULL DEFAULT 'no-user.jpg',
    user_id INT(11) NOT NULL,
    status TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET utf8;