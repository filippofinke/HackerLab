# 
# HackerLab
# Filippo finke
# 
# Creazione database
# 
DROP DATABASE IF EXISTS hackerlab;
CREATE DATABASE hackerlab;
USE hackerlab;

#
# Creazione tabelle
# 

# Tabella permessi
CREATE TABLE permissions(
    name VARCHAR(30) PRIMARY KEY
);

# Tabella utenti
CREATE TABLE users(
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    permission VARCHAR(30),
    full_name VARCHAR(30) NOT NULL,
    reset_token VARCHAR(255) DEFAULT NULL,
    enabled TINYINT(1) DEFAULT 1,
    UNIQUE(email),
    FOREIGN KEY(permission) REFERENCES permissions(name) ON UPDATE CASCADE
);

# Tabella articoli
CREATE TABLE articles(
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    title VARCHAR(255) NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    content TEXT(1000) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
);

# Tabella commenti
CREATE TABLE comments(
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT,
    user_id INT,
    comment VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY(article_id) REFERENCES articles(id) ON DELETE CASCADE

);

#
# Inserimento dati
#

# Permessi predefiniti
INSERT INTO permissions VALUES('user');
INSERT INTO permissions VALUES('administrator');

# Utenti predefiniti
INSERT INTO users VALUES(null,'admin@hackerlab.ch', '$2y$10$GBiarCslujuL/bqweq9HXOuunR4c/GAIebIfmEQ1F41JCeJyLLAYO', 'administrator', 'Administrator', NULL, 1); # Password: PasswordSegreta
INSERT INTO users VALUES(null,'filippo@finke.ch', '$2y$10$vh5dNYlbNzhOuuq4GjtZ2.Vl6xTYq94yxeJWfbGYGalNoNiOMEdUS', 'user', 'Filippo Finke', NULL, 1); # Password: 1234

# Articoli predefiniti
INSERT INTO articles VALUES(null, 1, 'Come installare Windows10', NULL, 'Per installare Windows10, ...', CURRENT_TIMESTAMP);
INSERT INTO articles VALUES(null, 2, 'Come installare Ubuntu', NULL, 'Per installare Ubuntu, ...', CURRENT_TIMESTAMP);

# Commenti predefiniti
INSERT INTO comments VALUES(null, 1, 1, 'Questa è la sezione commenti!', CURRENT_TIMESTAMP);
INSERT INTO comments VALUES(null, 1, 2, 'Se dovete installare Ubuntu guardate il mio profilo!', CURRENT_TIMESTAMP);
INSERT INTO comments VALUES(null, 2, 1, 'Articolo molto utile!', CURRENT_TIMESTAMP);
